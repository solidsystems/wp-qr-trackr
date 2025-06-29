#!/bin/bash
set -ex

# The working directory is /usr/src/app (WordPress root)

# Wait for the database to be ready
if [ -x /usr/local/bin/wait-for-it.sh ]; then
  /usr/local/bin/wait-for-it.sh db-nonprod:3306 -t 60 -- echo "Database is up"
elif [ -x ./scripts/wait-for-it.sh ]; then
  ./scripts/wait-for-it.sh db-nonprod:3306 -t 60 -- echo "Database is up"
else
  echo "wait-for-it.sh not found!" >&2
  exit 1
fi

# Enforce memory limits
export COMPOSER_MEMORY_LIMIT=2G

# Ensure dependencies are installed
composer install --prefer-dist

# Install Node.js dependencies
echo "Installing Node.js dependencies..."
yarn install

# Set PHPCS paths to include all standards
php -d memory_limit=2G ./vendor/bin/phpcs --config-set installed_paths vendor/wp-coding-standards/wpcs,vendor/phpcsstandards/phpcsutils,vendor/phpcsstandards/phpcsextra

# Debug: Show PHPCS config and version
./vendor/bin/phpcs --config-show
./vendor/bin/phpcs --version

# List files to be scanned
echo "Files to be scanned:"
find wp-content/plugins/wp-qr-trackr -type f | grep -vE 'vendor|node_modules|\.git|\.png|\.jpg|\.jpeg|\.gif|\.svg|\.zip|\.tar|\.gz|\.pdf|\.mp4|\.mov|\.webm|\.ico|\.DS_Store|\.log|\.coverage|\.js|\.css' || true

# Run PHPCS on plugin files
echo "Running PHPCS..."
php -d memory_limit=2G ./vendor/bin/phpcs --standard=WordPress-Core --extensions=php --ignore='vendor/*,build/**,node_modules/**' wp-content/plugins/wp-qr-trackr

# Run JS/CSS linting
echo "Running Stylelint..."
yarn stylelint "wp-content/plugins/wp-qr-trackr/**/*.css"

echo "Running ESLint..."
yarn eslint "wp-content/plugins/wp-qr-trackr/"

# Run PHP tests
echo "Setting up test environment..."
chmod +x ./scripts/install-wp-tests.sh
./scripts/install-wp-tests.sh wordpress_test root password db-nonprod latest

echo "Running PHPUnit tests..."
./vendor/bin/phpunit --configuration=wp-content/plugins/wp-qr-trackr/phpunit.xml

echo "All CI checks completed successfully" 