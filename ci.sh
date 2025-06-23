#!/bin/bash
set -ex

# Wait for the database to be ready
wait-for-it.sh db-nonprod:3306 -t 60 -- echo "Database is up"

# Grant execute permissions to our local script.
chmod +x scripts/install-wp-tests.sh

# Use our local, non-interactive script to set up the test environment.
# This will checkout the WordPress test suite into /tmp/ and our plugin is in /usr/src/app
./scripts/install-wp-tests.sh wordpress_test root password db-nonprod latest

echo "--- Running PHP Code Sniffer ---"
# Run from the root, phpcs will find the .phpcs.xml config file automatically
# and scan ONLY our plugin's files.
./vendor/bin/phpcs \
	wp-qr-trackr.php \
	includes/module-activation.php \
	includes/module-admin.php \
	includes/module-ajax.php \
	includes/module-qr.php \
	includes/module-requirements.php \
	includes/module-rewrite.php \
	includes/module-utils.php

echo "--- Running PHPUnit Tests ---"
# Run from the root, phpunit will find the phpunit.xml config file automatically.
./vendor/bin/phpunit

echo "--- Running Stylelint ---"
yarn stylelint "**/*.css"

echo "--- Running ESLint ---"
yarn eslint .

echo "--- CI Checks Passed ---" 