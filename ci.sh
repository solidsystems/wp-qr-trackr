#!/bin/bash
set -ex

# The project root is the working directory (/usr/src/app)

# --- Start Debugging ---
echo "--- DEBUG: Initial State ---"
pwd
ls -la
echo "--- END DEBUG: Initial State ---"

# Wait for the database to be ready
/usr/wait-for-it.sh db-nonprod:3306 -t 60 -- echo "Database is up"

# --- Start Debugging ---
echo "--- DEBUG: Checking Scripts ---"
ls -la scripts/
echo "--- DEBUG: Content of install-wp-tests.sh ---"
cat scripts/install-wp-tests.sh
echo "--- END DEBUG: Content of install-wp-tests.sh ---"
# --- End Debugging ---

# Ensure our local script is executable
chmod +x scripts/install-wp-tests.sh

# Use our local, non-interactive script to set up the test environment.
# This avoids any issues with pipes or interactive prompts in CI.
./scripts/install-wp-tests.sh wordpress_test root password db-nonprod latest

echo "--- Running PHP Code Sniffer ---"
# Run from the root, phpcs will find the .phpcs.xml config file automatically.
./vendor/bin/phpcs

echo "--- Running PHPUnit Tests ---"
# Run from the root, phpunit will find the phpunit.xml config file automatically.
./vendor/bin/phpunit

echo "--- Running Stylelint ---"
yarn stylelint "**/*.css"

echo "--- Running ESLint ---"
yarn eslint .

echo "--- CI Checks Passed ---" 