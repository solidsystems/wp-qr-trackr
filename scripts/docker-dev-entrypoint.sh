#!/bin/bash
set -e

# Entrypoint for dev Docker container (8080)
# - Waits for DB
# - Installs WordPress (if needed)
# - Ensures 'trackr' admin user exists
# - Skips setup wizard
# - Starts Apache

DB_HOST=${WORDPRESS_DB_HOST:-db-dev:3306}
DB_USER=${WORDPRESS_DB_USER:-wpuser}
DB_PASS=${WORDPRESS_DB_PASSWORD:-wppass}
DB_NAME=${WORDPRESS_DB_NAME:-wpdb}
SITE_URL=${WORDPRESS_SITE_URL:-http://localhost:8080}
ADMIN_USER=trackr
ADMIN_PASS=trackr
ADMIN_EMAIL=trackr@example.com
SITE_TITLE="QR Trackr Dev"

# Wait for DB
echo "Waiting for database at $DB_HOST..."
until mysql -h"${DB_HOST%%:*}" -P"${DB_HOST##*:}" -u"$DB_USER" -p"$DB_PASS" -e "USE $DB_NAME;" >/dev/null 2>&1; do
  sleep 2
done

echo "Database is ready."

cd /var/www/html

# Install WordPress if not already installed
if ! wp core is-installed --allow-root; then
  echo "Installing WordPress..."
  wp core install --url="$SITE_URL" --title="$SITE_TITLE" --admin_user="$ADMIN_USER" --admin_password="$ADMIN_PASS" --admin_email="$ADMIN_EMAIL" --skip-email --allow-root
else
  echo "WordPress already installed."
fi

# Always ensure 'trackr' admin user exists and has correct password
if wp user get "$ADMIN_USER" --field=ID --allow-root >/dev/null 2>&1; then
  echo "Updating 'trackr' user password and role..."
  wp user update "$ADMIN_USER" --user_pass="$ADMIN_PASS" --role=administrator --display_name="QR Trackr Admin" --allow-root
else
  echo "Creating 'trackr' admin user..."
  wp user create "$ADMIN_USER" "$ADMIN_EMAIL" --user_pass="$ADMIN_PASS" --role=administrator --display_name="QR Trackr Admin" --allow-root
fi

# Skip setup wizard and mark site as complete
wp option update wp_setup_wizard_completed 1 --allow-root || true
wp option update show_on_front 'posts' --allow-root
wp option update wp_user_roles "$(wp option get wp_user_roles --allow-root)" --allow-root

# Start Apache
echo "Starting Apache..."
exec apache2-foreground 