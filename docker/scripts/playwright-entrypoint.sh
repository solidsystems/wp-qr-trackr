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

# Run Playwright tests
cd /usr/src/app/wp-content/plugins/wp-qr-trackr
if [ -f ./node_modules/.bin/playwright ]; then
  echo "[Entrypoint] Running Playwright tests..."
  yarn playwright test
else
  echo "[Entrypoint] Playwright not found! Did you run 'yarn install'? Exiting."
  exit 1
fi
