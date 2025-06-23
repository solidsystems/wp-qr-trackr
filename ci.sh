#!/bin/bash
set -e

echo "--- Setting up WordPress Test Environment ---"
# Move to the WordPress root
cd /var/www/html

# Copy the plugin source to the correct location
cp -a /usr/src/app/wp-content/. ./wp-content/

# Set the path to the project root
PROJECT_ROOT=$(pwd)

# Wait for the database to be ready
/usr/wait-for-it.sh db-nonprod:3306 -t 60 -- echo "Database is up"

# Install the WordPress test suite
/usr/src/app/wp-content/plugins/wp-qr-trackr/bin/install-wp-tests.sh \
  wordpress_test \
  root \
  password \
  db-nonprod \
  latest

# Return to the plugin directory to run tests
cd /var/www/html/wp-content/plugins/wp-qr-trackr

echo "--- Running PHP Code Sniffer ---"
./vendor/bin/phpcs --standard="$PROJECT_ROOT/.phpcs.xml"

echo "--- Running PHPUnit Tests ---"
./vendor/bin/phpunit --configuration phpunit.xml

echo "--- Running Stylelint ---"
yarn stylelint "**/*.css"

echo "--- Running ESLint ---"
yarn eslint .

echo "--- CI Checks Passed ---" 