#!/bin/bash
set -e

# Playwright + Docker user flow script for QR Trackr
# 1. Reset dev Docker environment
# 2. Wait for WordPress to be up
# 3. Run Playwright user flow script
#
# Usage: ./scripts/playwright-docker-userflow.sh

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$SCRIPT_DIR/.."

cd "$PROJECT_ROOT"

# 1. Reset dev Docker environment
./scripts/reset-docker.sh dev

# 2. Wait for WordPress to be up
WP_URL="http://localhost:8080/wp-login.php"
echo "Waiting for WordPress dev site to be up at $WP_URL..."
for i in {1..30}; do
  if curl -s --head "$WP_URL" | grep -q "200 OK"; then
    echo "WordPress is up!"
    break
  fi
  echo "...still waiting ($i/30)"
  sleep 2
done

# 3. Run Playwright user flow script
echo "Running Playwright user flow script..."
node scripts/playwright-qrtrackr-userflow.js
STATUS=$?

if [ $STATUS -eq 0 ]; then
  echo "Playwright user flow completed successfully."
else
  echo "Playwright user flow failed with status $STATUS."
fi

exit $STATUS 