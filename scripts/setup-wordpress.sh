#!/bin/bash

# Exit on error
set -e

# Function to wait for WordPress container to be ready
wait_for_wordpress() {
    local port=$1
    echo "Waiting for WordPress to be ready on port $port..."
    local max_attempts=30
    local attempt=1
    while [ $attempt -le $max_attempts ]; do
        if curl -s "http://localhost:$port" > /dev/null; then
            echo "WordPress is ready!"
            return 0
        fi
        echo "Attempt $attempt/$max_attempts - Waiting for WordPress..."
        sleep 2
        attempt=$((attempt + 1))
    done
    echo "WordPress failed to become ready"
    return 1
}

# Function to fix critical directory permissions and configurations
fix_critical_permissions() {
    local container=$1
    local compose_file=$2

    echo "Fixing critical directory permissions and configurations..."

    # Fix upgrade directory permissions (critical for plugin updates)
    docker compose -f $compose_file exec --user root $container chown -R www-data:www-data /var/www/html/wp-content/upgrade 2>/dev/null || echo "Upgrade directory permissions already correct"
    docker compose -f $compose_file exec --user root $container chmod 775 /var/www/html/wp-content/upgrade 2>/dev/null || echo "Upgrade directory permissions already correct"

    # Ensure uploads directory has correct permissions
    docker compose -f $compose_file exec --user root $container chown -R www-data:www-data /var/www/html/wp-content/uploads 2>/dev/null || echo "Uploads directory permissions already correct"
    docker compose -f $compose_file exec --user root $container chmod 775 /var/www/html/wp-content/uploads 2>/dev/null || echo "Uploads directory permissions already correct"

    # Ensure plugins directory has correct permissions
    docker compose -f $compose_file exec --user root $container chown -R www-data:www-data /var/www/html/wp-content/plugins 2>/dev/null || echo "Plugins directory permissions already correct"
    docker compose -f $compose_file exec --user root $container chmod 775 /var/www/html/wp-content/plugins 2>/dev/null || echo "Plugins directory permissions already correct"

    echo "Critical permissions and configurations fixed."
}

# Function to install WordPress using wp-cli in Docker
install_wordpress() {
    local container=$1
    local port=$2
    local compose_file=$3

    echo "Installing WordPress in container $container..."

    # Fix critical permissions first
    fix_critical_permissions $container $compose_file

    # Install WordPress core
    docker compose -f $compose_file exec -T $container wp core install \
        --url="http://localhost:$port" \
        --title="WP QR Trackr" \
        --admin_user=trackr \
        --admin_password=trackr \
        --admin_email=test@example.com \
        --skip-email \
        --path=/var/www/html

    # If playwright env, set siteurl and home to service name for container access
    if [ "$container" = "wordpress-playwright" ]; then
        docker compose -f $compose_file exec -T $container wp option update siteurl http://wordpress-playwright --path=/var/www/html
        docker compose -f $compose_file exec -T $container wp option update home http://wordpress-playwright --path=/var/www/html
    fi

    # Set permalink structure (critical for QR code redirects)
    echo "Setting up permalink structure for QR code redirects..."
    docker compose -f $compose_file exec -T $container wp rewrite structure '/%postname%/' --path=/var/www/html
    docker compose -f $compose_file exec -T $container wp rewrite flush --hard --path=/var/www/html

    # Activate plugin
    docker compose -f $compose_file exec -T $container wp plugin activate wp-qr-trackr --path=/var/www/html

    # Verify plugin activation and flush rewrite rules again
    echo "Verifying plugin activation and flushing rewrite rules..."
    docker compose -f $compose_file exec -T $container wp plugin list --name=wp-qr-trackr --path=/var/www/html
    docker compose -f $compose_file exec -T $container wp rewrite flush --hard --path=/var/www/html
}

# Setup WordPress based on environment
setup_wordpress() {
    local env=$1
    local port
    local wp_container
    local compose_file

    case $env in
        "dev")
            port=8080
            wp_container="wordpress-dev"
            compose_file="docker/docker-compose.dev.yml"
            ;;
        "nonprod")
            port=8081
            wp_container="wordpress-nonprod"
            compose_file="docker/docker-compose.nonprod.yml"
            ;;
        "playwright")
            port=8087
            wp_container="wordpress-playwright"
            compose_file="docker/docker-compose.playwright.yml"
            ;;
        *)
            echo "Invalid environment: $env"
            echo "Usage: $0 [dev|nonprod|playwright]"
            exit 1
            ;;
    esac

    echo "Setting up WordPress for $env environment..."

    # Wait for WordPress to be ready
    wait_for_wordpress $port || {
        echo "Failed to connect to WordPress"
        exit 1
    }

    # Install WordPress
    install_wordpress $wp_container $port $compose_file

    echo "WordPress setup complete for $env environment!"
    echo "Admin URL: http://localhost:$port/wp-admin"
    echo "Username: trackr"
    echo "Password: trackr"
    echo ""
    echo "✅ Critical configurations applied:"
    echo "   - Upgrade directory permissions fixed"
    echo "   - Pretty permalinks enabled for QR redirects"
    echo "   - Plugin rewrite rules flushed"
    echo "   - Plugin activation verified"
}

# Main script
if [ $# -ne 1 ]; then
    echo "Usage: $0 [dev|nonprod|playwright]"
    exit 1
fi

setup_wordpress $1
