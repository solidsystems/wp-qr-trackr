#!/bin/bash
set -ex

# The working directory is /usr/src/app (WordPress root).

# Wait for the database to be ready
wait-for-it.sh db-nonprod:3306 -t 60 -- echo "Database is up"

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
./vendor/bin/phpcs .

echo "--- Running PHPUnit Tests ---"
# Run from the root, phpunit will find the phpunit.xml and wp-tests-config.php
# files automatically.
./vendor/bin/phpunit

echo "--- CI Checks Passed ---" 