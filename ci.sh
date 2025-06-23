#!/bin/bash
set -ex

# Wait for the database to be ready
wait-for-it.sh db-nonprod:3306 -t 60 -- echo "Database is up"

# Use our local, non-interactive script to set up the test environment.
./scripts/install-wp-tests.sh wordpress_test root password db-nonprod latest

echo "--- Running PHP Code Sniffer ---"
# Run from the root, phpcs will find the .phpcs.xml config file automatically
# and scan the entire project directory.
./vendor/bin/phpcs .

echo "--- Running PHPUnit Tests ---"
# Run from the root, phpunit will find the phpunit.xml config file automatically.
./vendor/bin/phpunit

echo "--- Running Stylelint ---"
yarn stylelint "**/*.css"

echo "--- Running ESLint ---"
yarn eslint .

echo "--- CI Checks Passed ---" 