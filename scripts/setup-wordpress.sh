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

# Function to install WordPress using wp-cli in Docker
install_wordpress() {
    local container=$1
    local port=$2
    
    echo "Installing WordPress in container $container..."
    
    # Install WordPress core
    docker compose -f docker/docker-compose.playwright.yml exec -T $container wp core install \
        --url="http://localhost:$port" \
        --title="WP QR Trackr" \
        --admin_user=trackr \
        --admin_password=trackr \
        --admin_email=test@example.com \
        --skip-email \
        --path=/var/www/html

    # If playwright env, set siteurl and home to service name for container access
    if [ "$container" = "wordpress-playwright" ]; then
        docker compose -f docker/docker-compose.playwright.yml exec -T $container wp option update siteurl http://wordpress-playwright --path=/var/www/html
        docker compose -f docker/docker-compose.playwright.yml exec -T $container wp option update home http://wordpress-playwright --path=/var/www/html
    fi

    # Set permalink structure
    docker compose -f docker/docker-compose.playwright.yml exec -T $container wp rewrite structure '/%postname%/' --path=/var/www/html
    docker compose -f docker/docker-compose.playwright.yml exec -T $container wp rewrite flush --hard --path=/var/www/html

    # Activate plugin
    docker compose -f docker/docker-compose.playwright.yml exec -T $container wp plugin activate wp-qr-trackr --path=/var/www/html
}

# Setup WordPress based on environment
setup_wordpress() {
    local env=$1
    local port
    local wp_container
    
    case $env in
        "dev")
            port=8080
            wp_container="wordpress"
            ;;
        "nonprod")
            port=8081
            wp_container="wordpress-nonprod"
            ;;
        "playwright")
            port=8087
            wp_container="wordpress-playwright"
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
    install_wordpress $wp_container $port

    echo "WordPress setup complete for $env environment!"
    echo "Admin URL: http://localhost:$port/wp-admin"
    echo "Username: trackr"
    echo "Password: trackr"
}

# Main script
if [ $# -ne 1 ]; then
    echo "Usage: $0 [dev|nonprod|playwright]"
    exit 1
fi

setup_wordpress $1 