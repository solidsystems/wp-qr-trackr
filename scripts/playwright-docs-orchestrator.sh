#!/bin/bash
set -e

# Playwright docs orchestrator: foolproof, up-to-date docs on demand (uses port 8087)
# Usage: PLUGIN_DIR=wp-qr-trackr ./scripts/playwright-docs-orchestrator.sh

# 1. Check for anything running on 8087 and kill/stop it
PORT=8087
COMPOSE_FILE="docker-compose.playwright.yml"
WPCLI_INIT="./scripts/wpcli-dev-init.sh"
PLUGIN_DIR="${PLUGIN_DIR:-wp-qr-trackr}"
PLAYWRIGHT_SCRIPT="scripts/playwright-${PLUGIN_DIR}-userflow.js"
SITE_URL="http://localhost:$PORT"

# Kill any process using 8087
if lsof -i :$PORT | grep LISTEN; then
  echo "Killing process on port $PORT..."
  lsof -ti :$PORT | xargs kill -9 || true
fi

# Stop and remove any running containers on 8087
if docker compose -f $COMPOSE_FILE ps | grep Up; then
  echo "Stopping existing Playwright containers..."
  docker compose -f $COMPOSE_FILE down -v || true
fi

# Ensure WordPress core files are present in the volume
./scripts/dev-force-wp-core.sh --playwright

# 2. Start fresh dev environment on 8087
echo "Starting fresh Playwright dev environment on port $PORT..."
docker compose -f $COMPOSE_FILE up -d

# 3. Run WP-CLI init to set up the site (pass PLUGIN_DIR)
export SITE_URL="$SITE_URL"
export PLUGIN_DIR
$WPCLI_INIT

# 4. Run Playwright user flow script (must use $PORT)
echo "Running Playwright user flow for documentation (port $PORT)..."
SITE_URL="$SITE_URL" node $PLAYWRIGHT_SCRIPT

# 5. Done
echo "Documentation screenshots are up to date for port $PORT." 