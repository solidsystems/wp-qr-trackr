#!/bin/bash
set -e

# Function to wait for database
wait_for_db() {
    echo "Waiting for database to be ready..."
    # Use application credentials instead of anonymous/root to avoid auth warnings.
    local db_host_port
    local db_user
    local db_pass

    # Prefer environment; if missing, derive from wp-config.php
    if [ -n "${WORDPRESS_DB_HOST:-}" ] && [ -n "${WORDPRESS_DB_USER:-}" ] && [ -n "${WORDPRESS_DB_PASSWORD:-}" ]; then
        db_host_port="${WORDPRESS_DB_HOST}"
        db_user="${WORDPRESS_DB_USER}"
        db_pass="${WORDPRESS_DB_PASSWORD}"
    elif [ -f /var/www/html/wp-config.php ]; then
        # Use PHP to safely read DB constants from wp-config.php.
        read -r db_host_port db_user db_pass <<EOF
$(php -r "include '/var/www/html/wp-config.php'; echo DB_HOST.' '.DB_USER.' '.DB_PASSWORD;" 2>/dev/null)
EOF
    else
        # Fallback defaults matching compose
        db_host_port="db-nonprod:3306"
        db_user="wpuser"
        db_pass="wppass"
    fi

    # Split host:port if provided.
    local db_host
    local db_port
    if [[ "$db_host_port" == *":"* ]]; then
        db_host="${db_host_port%%:*}"
        db_port="${db_host_port##*:}"
    else
        db_host="$db_host_port"
        db_port="3306"
    fi

    # Avoid infinite loop when credentials are empty
    if [ -z "$db_host" ] || [ -z "$db_user" ] || [ -z "$db_pass" ]; then
        echo "Database credentials not available; skipping wait."
        return 0
    fi

    while ! mysqladmin ping -h"$db_host" -P"$db_port" -u"$db_user" -p"$db_pass" --protocol=tcp --silent >/dev/null 2>&1; do
        sleep 1
    done
    echo "Database is ready!"
}

# Function to setup WordPress
setup_wordpress() {
    if [ ! -f /var/www/html/wp-config.php ]; then
        echo "Setting up WordPress..."

        # Wait for database
        wait_for_db

        # Create wp-config.php from environment database settings if missing.
        wp config create \
            --dbname="${WORDPRESS_DB_NAME:-wpdb}" \
            --dbuser="${WORDPRESS_DB_USER:-wpuser}" \
            --dbpass="${WORDPRESS_DB_PASSWORD:-wppass}" \
            --dbhost="${WORDPRESS_DB_HOST:-db-nonprod:3306}" \
            --path=/var/www/html \
            --skip-check --allow-root || true

        # Install WordPress core.
        wp core install \
            --url="${WORDPRESS_HOME:-http://localhost:8081}" \
            --title="WP QR Trackr Nonprod" \
            --admin_user="${WORDPRESS_ADMIN_USER:-trackr}" \
            --admin_password="${WORDPRESS_ADMIN_PASSWORD:-trackr}" \
            --admin_email="${WORDPRESS_ADMIN_EMAIL:-nonprod@example.com}" \
            --skip-email \
            --path=/var/www/html \
            --allow-root

        # Apply site options and permalinks.
        wp option update home "${WORDPRESS_HOME:-http://localhost:8081}" --path=/var/www/html --allow-root
        wp option update siteurl "${WORDPRESS_HOME:-http://localhost:8081}" --path=/var/www/html --allow-root
        wp rewrite structure '/%postname%/' --path=/var/www/html --allow-root
        wp rewrite flush --hard --path=/var/www/html --allow-root

        # Activate Query Monitor for diagnostics.
        wp plugin activate query-monitor --path=/var/www/html --allow-root || true

        echo "WordPress setup complete!"
    else
        echo "WordPress already configured."
    fi
}

# Function to setup plugin
setup_plugin() {
    if [ -d "/var/www/html/wp-content/plugins/wp-qr-trackr" ]; then
        echo "Setting up WP QR Trackr plugin..."

        # Note: The plugin is bind-mounted from host. Avoid chown on bind mounts to prevent permission errors.
        # If needed, permissive fix can be attempted, but ignore failures on bind-mounted files.
        chown -R www-data:www-data /var/www/html/wp-content/plugins/wp-qr-trackr 2>/dev/null || echo "Skipping chown for bind-mounted plugin directory."

        # Activate plugin if not already active
        if ! wp plugin is-active wp-qr-trackr --path=/var/www/html --allow-root 2>/dev/null; then
            wp plugin activate wp-qr-trackr --path=/var/www/html --allow-root
            echo "WP QR Trackr plugin activated!"
        else
            echo "WP QR Trackr plugin already active."
        fi

        # Flush rewrite rules after plugin activation
        wp rewrite flush --hard --path=/var/www/html
    else
        echo "Warning: WP QR Trackr plugin directory not found."
    fi
}

# Function to create editor user
create_editor_user() {
    echo "Setting up editor user..."

    # Use environment variables with defaults
    local editor_user="${WORDPRESS_EDITOR_USER:-editor}"
    local editor_password="${WORDPRESS_EDITOR_PASSWORD:-editor}"
    local editor_email="${WORDPRESS_EDITOR_EMAIL:-editor@example.com}"

    # Always try to create the editor user (will fail gracefully if already exists)
    echo "Creating editor user: $editor_user"
    wp user create "$editor_user" "$editor_email" --role=editor --user_pass="$editor_password" --path=/var/www/html --allow-root 2>/dev/null || echo "Editor user already exists or could not be created."
}

# Function to fix permissions
fix_permissions() {
    echo "Fixing permissions for writable directories..."
    # Only adjust writable dirs to avoid bind-mount chown failures
    mkdir -p /var/www/html/wp-content/upgrade /var/www/html/wp-content/uploads
    chown -R www-data:www-data /var/www/html/wp-content/upgrade /var/www/html/wp-content/uploads
    chmod -R 755 /var/www/html/wp-content/upgrade /var/www/html/wp-content/uploads
}

# Main execution
echo "Starting WP QR Trackr Nonprod Environment..."

# Start Apache first so port 80 is bound even if setup takes time.
echo "Starting Apache in background..."
docker-entrypoint.sh apache2-foreground &

# Fix permissions first
fix_permissions

# Setup WordPress if needed
setup_wordpress || true

# Setup plugin if mounted
setup_plugin || true

# Create editor user
create_editor_user || true

# Keep container running tied to Apache process.
wait
