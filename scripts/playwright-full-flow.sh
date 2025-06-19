#!/bin/bash
set -e

# Playwright full user flow: start containers, init site, run Playwright, capture screenshots
# Usage: ./scripts/playwright-full-flow.sh

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$SCRIPT_DIR/.."
cd "$PROJECT_ROOT"

# 1. Start dev containers
echo "[1/3] Starting dev containers..."
docker compose -f docker-compose.dev.yml up -d

# 2. Run WP-CLI init to ensure site is ready
echo "[2/3] Initializing WordPress site with WP-CLI..."
./scripts/wpcli-dev-init.sh

# 3. Run Playwright user flow and capture screenshots
echo "[3/3] Running Playwright user flow and capturing screenshots..."
node scripts/playwright-qrtrackr-userflow.js

# Uncomment to stop containers after run:
# docker compose -f docker-compose.dev.yml down

echo "All done! Screenshots should be in assets/screenshots/." 