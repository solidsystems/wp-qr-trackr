#!/bin/bash
set -ex

# The working directory is /usr/src/app (WordPress root).

# Wait for the database to be ready
./scripts/wait-for-it.sh db-nonprod:3306 -t 60 -- echo "Database is up"

# Enforce 2G memory limit for Composer and PHPCS
export COMPOSER_MEMORY_LIMIT=2G

# Always reset PHPCS installed_paths to only supported sniffs
php -d memory_limit=2G ./vendor/bin/phpcs --config-set installed_paths vendor/wp-coding-standards/wpcs,vendor/phpcsstandards/phpcsutils

# Debug: Show PHPCS config
./vendor/bin/phpcs --config-show

# Print PHPCS version and working directory
./vendor/bin/phpcs --version
pwd

# Print the list of files PHPCS might scan (excluding common large/binary types)
find wp-content/plugins/wp-qr-trackr -type f | grep -vE 'vendor|node_modules|\.git|\.png|\.jpg|\.jpeg|\.gif|\.svg|\.zip|\.tar|\.gz|\.pdf|\.mp4|\.mov|\.webm|\.ico|\.DS_Store|\.log|\.coverage|\.js|\.css' || true

# Example Composer usage
composer install --prefer-source

# Example PHPCS usage (only PHP files, ignore vendor)
php -d memory_limit=2G ./vendor/bin/phpcs --standard=WordPress --extensions=php --ignore='vendor/*,build/**' wp-content/plugins/wp-qr-trackr

# --- JS Linting (run from WP root) ---
echo "--- Running Stylelint ---"
# We need to specify the path to the plugin's CSS files.
# The config file is at the root and will be found automatically.
yarn stylelint "wp-content/plugins/wp-qr-trackr/**/*.css"

echo "--- Running ESLint ---"
# We need to tell ESLint to check only the plugin directory.
# The eslint.config.js at the root will be used.
yarn eslint "wp-content/plugins/wp-qr-trackr/"

# --- PHP Tests (run from plugin root) ---
echo "--- Switching to plugin directory for PHP tests ---"
cd wp-content/plugins/wp-qr-trackr

# Grant execute permissions to our local script.
# The path is now relative to the plugin directory.
chmod +x ../../../scripts/install-wp-tests.sh

# Use our local, non-interactive script to set up the test environment.
# This will create a wp-tests-config.php file in a temp directory.
../../../scripts/install-wp-tests.sh wordpress_test root password db-nonprod latest

echo "--- Running PHP Code Sniffer ---"
# Run from the root of our plugin, phpcs will find the .phpcs.xml config file
# and scan all files in the current directory.
php -d memory_limit=2G ./vendor/bin/phpcs --ignore='vendor/*,build/**' .

echo "--- Running PHPUnit Tests ---"
# Run from the root, phpunit will find the phpunit.xml and wp-tests-config.php
# files automatically.
./vendor/bin/phpunit

echo "--- CI Checks Passed ---" 