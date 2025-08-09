#!/bin/bash
set -e

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
    if [ ! -f /var/www/html/wp-config.php ]; then
        echo "Setting up WordPress..."

        # Wait for database
        wait_for_db

        # Install WordPress
        wp core install \
            --url="${WORDPRESS_HOME:-http://localhost:8081}" \
            --title="WP QR Trackr Nonprod" \
            --admin_user="${WORDPRESS_ADMIN_USER:-trackr}" \
            --admin_password="${WORDPRESS_ADMIN_PASSWORD:-trackr}" \
            --admin_email="${WORDPRESS_ADMIN_EMAIL:-nonprod@example.com}" \
            --skip-email \
            --path=/var/www/html

        # Set permalink structure
        wp rewrite structure '/%postname%/' --path=/var/www/html
        wp rewrite flush --hard --path=/var/www/html

        # Activate Query Monitor
        wp plugin activate query-monitor --path=/var/www/html

        echo "WordPress setup complete!"
    else
        echo "WordPress already configured."
    fi
}

# Function to setup plugin
setup_plugin() {
    if [ -d "/var/www/html/wp-content/plugins/wp-qr-trackr" ]; then
        echo "Setting up WP QR Trackr plugin..."

        # Fix permissions
        chown -R www-data:www-data /var/www/html/wp-content/plugins/wp-qr-trackr

        # Activate plugin if not already active
        if ! wp plugin is-active wp-qr-trackr --path=/var/www/html 2>/dev/null; then
            wp plugin activate wp-qr-trackr --path=/var/www/html
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

# Function to fix permissions
fix_permissions() {
    echo "Fixing permissions..."
    chown -R www-data:www-data /var/www/html/wp-content
    chmod -R 755 /var/www/html/wp-content
    chmod -R 755 /var/www/html/wp-content/upgrade
    chmod -R 755 /var/www/html/wp-content/uploads
}

# Main execution
echo "Starting WP QR Trackr Nonprod Environment..."

# Fix permissions first
fix_permissions

# Setup WordPress if needed
setup_wordpress

# Setup plugin if mounted
setup_plugin

# Start Apache
echo "Starting Apache..."
exec apache2-foreground
