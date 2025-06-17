#!/bin/bash

# Deactivate the QR Trackr plugin from the CLI using WP-CLI in Docker

PLUGIN_SLUG="wp-qr-trackr-v1.0.2"
PLUGIN_DIR="/var/www/html/wp-content/plugins/$PLUGIN_SLUG"
CONTAINER_NAME="wp-qr-trackr-wordpress-1"

set -e

if ! docker compose ps | grep -q "$CONTAINER_NAME"; then
  echo "[ERROR] WordPress container ($CONTAINER_NAME) is not running. Start Docker first."
  exit 1
fi

echo "[INFO] Attempting to deactivate plugin: $PLUGIN_SLUG in container: $CONTAINER_NAME using WP-CLI..."

if docker compose exec wordpress wp plugin deactivate "$PLUGIN_SLUG"; then
  echo "[SUCCESS] Plugin '$PLUGIN_SLUG' deactivated via WP-CLI."
  exit 0
else
  echo "[WARNING] WP-CLI deactivation failed. Attempting to rename plugin directory as fallback..."
  # Try to rename the plugin directory inside the container
  if docker compose exec wordpress bash -c "if [ -d '$PLUGIN_DIR' ]; then mv '$PLUGIN_DIR' '${PLUGIN_DIR}-disabled'; fi"; then
    echo "[SUCCESS] Plugin directory renamed to '${PLUGIN_DIR}-disabled'. Plugin will be deactivated on next admin load."
    exit 0
  else
    echo "[ERROR] Failed to rename plugin directory. Manual intervention required."
    exit 1
  fi
fi 