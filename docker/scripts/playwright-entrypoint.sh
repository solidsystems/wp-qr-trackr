#!/bin/bash
set -e

# Ensure we are in the plugin directory
cd /usr/src/app/wp-content/plugins/wp-qr-trackr

# Install Composer dependencies in the plugin directory if missing
if [ ! -f ./vendor/bin/phpcs ]; then
  echo "[Entrypoint] Running composer install in plugin directory..."
  composer install
fi

# Also try installing in the project root if still missing
if [ ! -f ./vendor/bin/phpcs ] && [ -f /usr/src/app/composer.json ]; then
  echo "[Entrypoint] Running composer install in project root..."
  cd /usr/src/app
  composer install
  cd /usr/src/app/wp-content/plugins/wp-qr-trackr
fi

# Run validation using our validation script
echo "[Entrypoint] Running validation..."
cd /usr/src/app
bash scripts/validate.sh

# Conditionally run Playwright E2E
cd /usr/src/app/wp-content/plugins/wp-qr-trackr

# Skip E2E in GitHub Actions or when RUN_E2E!=1
if [ "${GITHUB_ACTIONS}" = "true" ] || [ "${RUN_E2E}" != "1" ]; then
  echo "[Entrypoint] Skipping Playwright E2E (GITHUB_ACTIONS=${GITHUB_ACTIONS:-false}, RUN_E2E=${RUN_E2E:-0})."
  exit 0
fi

echo "[Entrypoint] Preparing Playwright E2E dependencies..."
# Install dev deps locally if missing
if [ ! -d node_modules ]; then
  yarn install --frozen-lockfile || yarn install
fi

# Install Playwright browsers and system deps inside the container
npx playwright install --with-deps || yarn playwright install --with-deps || true

echo "[Entrypoint] Running Playwright tests..."
yarn test:e2e || yarn playwright test
