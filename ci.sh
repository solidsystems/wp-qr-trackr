#!/bin/bash
set -ex

# Define paths for clarity
WP_CORE_DIR=/tmp/wordpress
PLUGIN_SLUG="wp-qr-trackr"
PLUGIN_SRC_DIR="/usr/src/app"
PLUGIN_TEST_DIR="${WP_CORE_DIR}/wp-content/plugins/${PLUGIN_SLUG}"

# Wait for the database to be ready
/usr/wait-for-it.sh db-nonprod:3306 -t 60 -- echo "Database is up"

# Set up WordPress test environment non-interactively.
# The `yes |` command automatically answers 'y' to the confirmation prompt
# for deleting an existing database, which is essential for CI.
# We run this from /tmp to keep the project directory clean.
cd /tmp
yes | /usr/install-wp-tests.sh wordpress_test root password db-nonprod latest

# Copy the plugin source from the build context to the correct location
# within the temporary WordPress installation. This is a critical step
# that allows the test runner to find the plugin files.
mkdir -p "${PLUGIN_TEST_DIR}"
cp -a "${PLUGIN_SRC_DIR}/." "${PLUGIN_TEST_DIR}/"

# Navigate to the plugin's directory to run tests and linting.
# This ensures that the correct vendor binaries and configuration
# files (phpunit.xml, .phpcs.xml) are used.
cd "${PLUGIN_TEST_DIR}"

echo "--- Running PHP Code Sniffer ---"
./vendor/bin/phpcs --standard=./.phpcs.xml .

echo "--- Running PHPUnit Tests ---"
./vendor/bin/phpunit

echo "--- Running Stylelint ---"
yarn stylelint "**/*.css"

echo "--- Running ESLint ---"
yarn eslint .

echo "--- CI Checks Passed ---" 