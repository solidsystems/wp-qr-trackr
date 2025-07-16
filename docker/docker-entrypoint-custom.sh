#!/bin/bash
set -euo pipefail

# Execute the original WordPress entrypoint
docker-entrypoint.sh apache2-foreground &

# Wait for MySQL to be ready
until wp core is-installed --path=/var/www/html --allow-root 2>/dev/null
do
    echo "Waiting for WordPress to be ready..."
    sleep 2
done

# If WordPress is not installed, install it
if ! wp core is-installed --path=/var/www/html --allow-root; then
    wp core install \
        --path=/var/www/html \
        --url=http://localhost:8080 \
        --title="QR Trackr Dev" \
        --admin_user=trackr \
        --admin_password=trackr \
        --admin_email=dev@example.com \
        --skip-email \
        --allow-root
fi

# Activate the plugin if it's not already activated
if ! wp plugin is-active wp-qr-trackr --path=/var/www/html --allow-root; then
    wp plugin activate wp-qr-trackr --path=/var/www/html --allow-root
fi

# Keep the container running
wait 