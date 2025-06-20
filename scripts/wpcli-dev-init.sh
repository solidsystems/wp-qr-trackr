#!/bin/bash
#
# WP-CLI init script for dev and playwright environments.
# Usage: SITE_URL=http://localhost:8087 ./scripts/wpcli-dev-init.sh
# If SITE_URL is not set, defaults to http://localhost:8080 and dev container.
#

set -e

SITE_URL="${SITE_URL:-http://localhost:8080}"
PORT="${SITE_URL##*:}"
PORT="${PORT##*/}"

# Detect container name based on port
if [[ "$PORT" == "8087" ]]; then
  WPCLI_CONTAINER="wpcli-playwright"
  DBCONTAINER="db-playwright"
else
  WPCLI_CONTAINER="wpcli-dev"
  DBCONTAINER="db-dev"
fi

echo "[wpcli-dev-init] Using container: $WPCLI_CONTAINER for $SITE_URL"

# Wait for the database to be ready
MAX_TRIES=30
TRIES=0
until docker compose exec $DBCONTAINER mysqladmin ping -h"localhost" --silent > /dev/null 2>&1; do
  TRIES=$((TRIES+1))
  if [ $TRIES -ge $MAX_TRIES ]; then
    echo "[wpcli-dev-init] ERROR: Database did not become ready after $MAX_TRIES attempts."
    exit 1
  fi
  echo "[wpcli-dev-init] Waiting for database ($DBCONTAINER) to be ready... ($TRIES/$MAX_TRIES)"
  sleep 2
done

docker compose exec $WPCLI_CONTAINER wp core is-installed || \
  docker compose exec $WPCLI_CONTAINER wp core install --url="$SITE_URL" --title="QR Trackr Dev" --admin_user=trackr --admin_password=trackr --admin_email=trackr@example.com --skip-email

docker compose exec $WPCLI_CONTAINER wp user update trackr --user_pass=trackr --display_name="Trackr Admin" --role=administrator

docker compose exec $WPCLI_CONTAINER wp option update show_on_front posts

# Activate the plugin (parameterized for multi-project support)
PLUGIN_DIR="${PLUGIN_DIR:-wp-qr-trackr}"
docker compose exec $WPCLI_CONTAINER wp plugin activate "$PLUGIN_DIR"

echo "[wpcli-dev-init] WordPress site initialized at $SITE_URL with user trackr:trackr." 