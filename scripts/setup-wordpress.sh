#!/bin/bash

# WordPress Auto-Setup Script
# Automatically installs WordPress with trackr:trackr credentials

set -e

ENVIRONMENT=${1:-dev}
SITE_URL=""
CONTAINER_PREFIX=""

case $ENVIRONMENT in
    "dev")
        SITE_URL="http://localhost:8080"
        CONTAINER_PREFIX="wpqrdev"
        WP_CONTAINER="wordpress-dev"
        ;;
    "nonprod")
        SITE_URL="http://localhost:8081"
        CONTAINER_PREFIX="wpqrnonprod"
        WP_CONTAINER="wordpress-nonprod"
        ;;
    *)
        echo "Usage: $0 [dev|nonprod]"
        echo "Environment must be 'dev' or 'nonprod'"
        exit 1
        ;;
esac

echo "🚀 Setting up WordPress for $ENVIRONMENT environment..."
echo "📍 Site URL: $SITE_URL"

# Function to run WP-CLI commands directly in WordPress container
run_wp_cli() {
    docker compose -p $CONTAINER_PREFIX exec $WP_CONTAINER bash -c "
        cd /var/www/html
        # Download wp-cli if not present
        if [ ! -f /tmp/wp-cli.phar ]; then
            curl -s -o /tmp/wp-cli.phar https://raw.githubusercontent.com/wp-cli/wp-cli/v2.8.1/phar/wp-cli.phar
            chmod +x /tmp/wp-cli.phar
        fi
        php -d memory_limit=1G /tmp/wp-cli.phar --allow-root $*
    "
}

# Function to check if WordPress is accessible
check_wordpress_ready() {
    local http_code
    http_code=$(curl -s -o /dev/null -w "%{http_code}" "$SITE_URL" 2>/dev/null || echo "000")
    case $http_code in
        "200"|"302"|"301") return 0 ;;
        *) return 1 ;;
    esac
}

# Wait for containers to be ready
echo "⏳ Waiting for containers to be ready..."
sleep 10

# Wait for WordPress container to be accessible
echo "🔍 Waiting for WordPress container to be accessible..."
for i in {1..12}; do
    if check_wordpress_ready; then
        echo "✅ WordPress container is accessible!"
        break
    fi
    echo "⏳ Waiting for WordPress... (attempt $i/12)"
    sleep 5
done

# Wait a bit more for the database to be fully ready
sleep 5

# Check if WordPress is already installed
echo "🔍 Checking WordPress installation status..."
if run_wp_cli core is-installed 2>/dev/null; then
    echo "✅ WordPress is already installed!"
    ALREADY_INSTALLED=true
else
    echo "📦 Installing WordPress with trackr:trackr credentials..."
    ALREADY_INSTALLED=false
    
    # Create wp-config.php if it doesn't exist
    if ! run_wp_cli config path 2>/dev/null; then
        echo "⚙️ Creating WordPress configuration..."
        run_wp_cli config create \
            --dbname=wpdb \
            --dbuser=wpuser \
            --dbpass=wppass \
            --dbhost=db-${ENVIRONMENT}:3306 \
            --force || {
            echo "⚠️ WordPress config may already exist"
        }
    fi
    
    # Install WordPress
    echo "📦 Installing WordPress..."
    run_wp_cli core install \
        --url="$SITE_URL" \
        --title="QR Trackr Development" \
        --admin_user="trackr" \
        --admin_password="trackr" \
        --admin_email="admin@example.com" \
        --skip-email || {
        echo "❌ Failed to install WordPress"
        exit 1
    }
    
    echo "✅ WordPress installed successfully!"
fi

# Set permalink structure for QR redirects
echo "🔗 Setting permalink structure..."
run_wp_cli rewrite structure '/%postname%/' || {
    echo "⚠️ Warning: Could not set permalink structure"
}

# Flush rewrite rules
echo "🔄 Flushing rewrite rules..."
run_wp_cli rewrite flush --hard || {
    echo "⚠️ Warning: Could not flush rewrite rules"
}

# Configure WordPress for development
if [ "$ALREADY_INSTALLED" = false ]; then
    echo "⚙️ Configuring WordPress for development..."
    
    # Enable debug mode for dev environment
    if [ "$ENVIRONMENT" = "dev" ]; then
        run_wp_cli config set WP_DEBUG true --raw --type=constant || true
        run_wp_cli config set WP_DEBUG_LOG true --raw --type=constant || true
        run_wp_cli config set WP_DEBUG_DISPLAY false --raw --type=constant || true
    fi
    
    # Set timezone
    run_wp_cli option update timezone_string 'America/New_York' || true
    
    # Update site description
    run_wp_cli option update blogdescription 'QR Code Generation and Tracking' || true
    
    # Set date format
    run_wp_cli option update date_format 'F j, Y' || true
    run_wp_cli option update time_format 'g:i a' || true
fi

# Activate the QR Trackr plugin for dev environment
if [ "$ENVIRONMENT" = "dev" ]; then
    echo "🔌 Activating QR Trackr plugin..."
    if run_wp_cli plugin list 2>/dev/null | grep -q wp-qr-trackr; then
        run_wp_cli plugin activate wp-qr-trackr || {
            echo "⚠️ Could not activate QR Trackr plugin"
        }
    else
        echo "⚠️ QR Trackr plugin not found - check plugin mount"
    fi
fi

echo ""
echo "🎉 WordPress setup complete!"
echo "=============================="
echo "📱 Site URL: $SITE_URL"
echo "👤 Username: trackr"
echo "🔑 Password: trackr"
echo "📧 Email: admin@example.com"
echo ""
echo "🔗 Quick Access URLs:"
echo "🏠 Site: $SITE_URL"
echo "🔧 Admin: $SITE_URL/wp-admin"
echo "⚙️ Plugins: $SITE_URL/wp-admin/plugins.php"

if [ "$ENVIRONMENT" = "dev" ]; then
    echo "🔌 QR Trackr Plugin: $SITE_URL/wp-admin/admin.php?page=qr-codes"
fi

echo ""
echo "✅ WordPress is ready to use! No browser setup required." 