#!/bin/bash
set -e
# Ensure core files are present by letting the official entrypoint provision WordPress first.
start_official_entrypoint() {
    echo "Starting official WordPress entrypoint in background..."
    docker-entrypoint.sh apache2-foreground &
}

# Wait until WordPress core files exist so WP-CLI commands can run reliably.
wait_for_wp_core_files() {
    echo "Waiting for WordPress core files to be available..."
    local attempts=0
    local max_attempts=30
    until [ -f /var/www/html/wp-load.php ] || [ $attempts -ge $max_attempts ]; do
        sleep 1
        attempts=$((attempts+1))
    done
    if [ ! -f /var/www/html/wp-load.php ]; then
        echo "Warning: WordPress core files not detected after waiting; continuing anyway."
    else
        echo "WordPress core files detected."
    fi
}

# Function to wait for database
wait_for_db() {
    echo "Waiting for database to be ready..."
    while ! mysqladmin ping -h"$WORDPRESS_DB_HOST" --silent; do
        sleep 1
    done
    echo "Database is ready!"
}

# Function to setup WordPress
setup_wordpress() {
    echo "Checking WordPress installation..."

    # Wait for database
    wait_for_db

    if wp core is-installed --path=/var/www/html --allow-root >/dev/null 2>&1; then
        echo "WordPress already installed."
    else
        echo "Installing WordPress..."
        wp core install \
            --url="${WORDPRESS_HOME:-http://localhost:8080}" \
            --title="WP QR Trackr Dev" \
            --admin_user="${WORDPRESS_ADMIN_USER:-trackr}" \
            --admin_password="${WORDPRESS_ADMIN_PASSWORD:-trackr}" \
            --admin_email="${WORDPRESS_ADMIN_EMAIL:-dev@example.com}" \
            --skip-email \
            --path=/var/www/html \
            --allow-root

        echo "WordPress installation complete."
    fi

    # Set permalink structure
    wp rewrite structure '/%postname%/' --path=/var/www/html --allow-root || true
    wp rewrite flush --hard --path=/var/www/html --allow-root || true

    # Activate Query Monitor
    wp plugin activate query-monitor --path=/var/www/html --allow-root || true
}

# Function to setup plugin
setup_plugin() {
    if [ -d "/var/www/html/wp-content/plugins/wp-qr-trackr" ]; then
        echo "Setting up WP QR Trackr plugin..."

        # Do not chown the bind-mounted plugin directory to avoid macOS Docker permission errors.

        # Activate plugin if not already active
        if ! wp plugin is-active wp-qr-trackr --path=/var/www/html --allow-root 2>/dev/null; then
            wp plugin activate wp-qr-trackr --path=/var/www/html --allow-root
            echo "WP QR Trackr plugin activated!"
        else
            echo "WP QR Trackr plugin already active."
        fi

        # Flush rewrite rules after plugin activation
        wp rewrite flush --hard --path=/var/www/html --allow-root
    else
        echo "Warning: WP QR Trackr plugin directory not found."
    fi
}

# Function to fix permissions
fix_permissions() {
    echo "Fixing permissions..."
    # Adjust only WordPress-writable directories. Avoid bind-mounted sources (e.g., wp-qr-trackr) to prevent macOS Docker chown errors.
    chown -R www-data:www-data /var/www/html/wp-content/upgrade 2>/dev/null || echo "Notice: Could not chown upgrade; continuing."
    chown -R www-data:www-data /var/www/html/wp-content/uploads 2>/dev/null || echo "Notice: Could not chown uploads; continuing."

    # Ensure other bundled plugins (not the bind-mounted wp-qr-trackr) are writable.
    if [ -d "/var/www/html/wp-content/plugins" ]; then
        find /var/www/html/wp-content/plugins -mindepth 1 -maxdepth 1 -type d ! -name 'wp-qr-trackr' -exec chown -R www-data:www-data {} \; 2>/dev/null || true
        find /var/www/html/wp-content/plugins -mindepth 1 -maxdepth 1 -type d ! -name 'wp-qr-trackr' -exec chmod -R 755 {} \; 2>/dev/null || true
    fi

    chmod -R 755 /var/www/html/wp-content/upgrade || true
    chmod -R 755 /var/www/html/wp-content/uploads || true
}

# Main execution
echo "Starting WP QR Trackr Dev Environment..."

# Start the official entrypoint so it can initialize the volume with core files and start Apache.
start_official_entrypoint

# Wait for WordPress files to appear in the mounted volume.
wait_for_wp_core_files

# Fix permissions on writable dirs (skips bind-mounted plugin).
fix_permissions

# Setup WordPress if needed.
setup_wordpress

# Setup plugin if mounted.
setup_plugin

# Keep the container running by waiting on background processes started by the official entrypoint.
wait
