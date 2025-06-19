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
else
  WPCLI_CONTAINER="wpcli-dev"
fi

echo "[wpcli-dev-init] Using container: $WPCLI_CONTAINER for $SITE_URL"

docker compose exec $WPCLI_CONTAINER wp core is-installed || \
  docker compose exec $WPCLI_CONTAINER wp core install --url="$SITE_URL" --title="QR Trackr Dev" --admin_user=trackr --admin_password=trackr --admin_email=trackr@example.com --skip-email

docker compose exec $WPCLI_CONTAINER wp user update trackr --user_pass=trackr --display_name="Trackr Admin" --role=administrator

docker compose exec $WPCLI_CONTAINER wp option update show_on_front posts

docker compose exec $WPCLI_CONTAINER wp option update wp_user_roles "$(docker compose exec $WPCLI_CONTAINER wp option get wp_user_roles)"

echo "[wpcli-dev-init] WordPress site initialized at $SITE_URL with user trackr:trackr." 