#!/bin/bash
set -e

# Script to force-copy WordPress core files into the dev or playwright container's /var/www/html
# Usage: ./scripts/dev-force-wp-core.sh [--playwright]
# If --playwright is passed, uses docker-compose.playwright.yml and wordpress-playwright service.
# Otherwise, defaults to dev.

if [[ "$1" == "--playwright" ]]; then
  COMPOSE_FILE="docker-compose.playwright.yml"
  SERVICE="wordpress-playwright"
else
  COMPOSE_FILE="docker-compose.dev.yml"
  SERVICE="wordpress-dev"
fi

# 1. Start the container with no plugin mounts (temporary override)
echo "[1/3] Starting $SERVICE container with no plugin mounts to force core copy..."
docker compose -f $COMPOSE_FILE up -d $SERVICE

# 2. Wait for wp-login.php to exist in the container
WP_PATH="/var/www/html/wp-login.php"
echo "[2/3] Waiting for WordPress core files to be present in the container..."
for i in {1..30}; do
  if docker compose -f $COMPOSE_FILE exec $SERVICE test -f "$WP_PATH"; then
    echo "WordPress core files found."
    break
  fi
  echo "...still waiting for core files ($i/30)"
  sleep 2
done

# 3. Stop the container so normal compose up can mount plugins

echo "[3/3] Stopping $SERVICE container. You can now run your normal workflow."
docker compose -f $COMPOSE_FILE stop $SERVICE

echo "Done. WordPress core files are now present in the volume for $SERVICE." 