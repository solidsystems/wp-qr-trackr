#!/bin/bash
set -ex

# The working directory is /usr/src/app, which is our plugin root.

# Wait for the database to be ready
wait-for-it.sh db-nonprod:3306 -t 60 -- echo "Database is up"

# Grant execute permissions to our local script.
chmod +x scripts/install-wp-tests.sh

# Use our local, non-interactive script to set up the test environment.
# This will create a wp-tests-config.php file in the current directory.
./scripts/install-wp-tests.sh wordpress_test root password db-nonprod latest

echo "--- Running PHP Code Sniffer ---"
# Run from the root of our plugin, phpcs will find the .phpcs.xml config file
# and scan all files in the current directory.
./vendor/bin/phpcs .

echo "--- Running PHPUnit Tests ---"
# Run from the root, phpunit will find the phpunit.xml and wp-tests-config.php
# files automatically.
./vendor/bin/phpunit

echo "--- Running Stylelint ---"
yarn stylelint "**/*.css"

echo "--- Running ESLint ---"
yarn eslint .

echo "--- CI Checks Passed ---" 