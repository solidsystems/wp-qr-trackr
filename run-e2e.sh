#!/bin/bash
set -e

# Step 1: Check Docker login (DISABLED FOR LOCAL E2E)
# echo "[INFO] [host] Checking Docker login for GitHub Container Registry (ghcr.io)..."
# if ! docker info 2>/dev/null | grep -q 'ghcr.io'; then
#   if [ -n "$GH_TOKEN" ]; then
#     echo "[INFO] [host] GH_TOKEN detected in environment. Using it for Docker login."
#     GH_USER=$(gh api user --jq .login 2>/dev/null || echo "github-user")
#     echo $GH_TOKEN | docker login ghcr.io -u "$GH_USER" --password-stdin
#   else
#     echo "[INFO] [host] Not logged in to GitHub Container Registry (ghcr.io). Launching authentication..."
#     if ! command -v gh >/dev/null 2>&1; then
#       echo "[ERROR] [host] GitHub CLI (gh) is not installed. Please install it with 'brew install gh' and try again."
#       exit 1
#     fi
#     if ! gh auth login --web --scopes "read:packages"; then
#       echo "[WARN] [host] Web authentication failed. Falling back to device code authentication..."
#       gh auth login --scopes "read:packages"
#     fi
#     GH_TOKEN=$(gh auth token)
#     GH_USER=$(gh api user --jq .login)
#     echo $GH_TOKEN | docker login ghcr.io -u "$GH_USER" --password-stdin
#   fi
# fi

echo "[INFO] Parsing E2E config values..."
CONFIG_FILE="e2e.config.json"

# Parse config values using jq
COMPOSE_FILE=$(jq -r .docker_compose_file $CONFIG_FILE)
SERVICE=$(jq -r .service $CONFIG_FILE)
PLAYWRIGHT_CONFIG=$(jq -r .playwright_config $CONFIG_FILE)

# Set the correct base path for the plugin inside the container depending on the service
if [ "$SERVICE" = "playwright-runner" ]; then
  PLUGIN_BASE_PATH="/usr/src/app/wp-content/plugins/wp-qr-trackr"
else
  PLUGIN_BASE_PATH="/var/www/html/wp-content/plugins/wp-qr-trackr"
fi

PLAYWRIGHT_CONFIG_PATH="$PLUGIN_BASE_PATH/tests/e2e/playwright/$(basename $PLAYWRIGHT_CONFIG)"
TEST_DIR_PATH="$PLUGIN_BASE_PATH/tests/e2e/playwright/tests"

echo "[INFO] Exporting environment variables from config (if any)..."
# Optionally export environment variables from config
if [ -n "$(jq -r '.env | to_entries[]? | .key' $CONFIG_FILE)" ]; then
  while IFS= read -r key; do
    value=$(jq -r ".env.$key" $CONFIG_FILE)
    export $key="$value"
  done < <(jq -r '.env | to_entries[] | .key' $CONFIG_FILE)
fi

echo "[INFO] [container: $SERVICE] Listing contents of Playwright config directory in container..."
docker compose -f "$COMPOSE_FILE" run --rm --entrypoint "" -e SERVICE="$SERVICE" -e PLAYWRIGHT_CONFIG="$PLAYWRIGHT_CONFIG_PATH" "$SERVICE" bash -c "echo \"[INFO] [container: \$SERVICE] Listing $PLUGIN_BASE_PATH/tests/e2e/playwright/\"; ls -l $PLUGIN_BASE_PATH/tests/e2e/playwright/"

# Ensure WordPress is installed and ready for E2E tests
bash scripts/setup-wordpress.sh playwright

echo "[INFO] [container: $SERVICE] Running Playwright E2E tests using yarn (verbose output)..."
exec docker compose -f "$COMPOSE_FILE" run --rm --entrypoint "" -e SERVICE="$SERVICE" -e PLAYWRIGHT_CONFIG="$PLAYWRIGHT_CONFIG_PATH" "$SERVICE" bash -c "echo \"[INFO] [container: \$SERVICE] Running Playwright tests with config: \$PLAYWRIGHT_CONFIG_PATH (verbose)\"; yarn playwright test --config=\"\$PLAYWRIGHT_CONFIG_PATH\" --reporter=list" 