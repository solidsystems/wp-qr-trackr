#!/bin/bash
set -e

echo "--- Setting up WordPress Test Environment ---"
# Move to the WordPress root
cd /var/www/html

# Copy the plugin source to the correct location
cp -a /usr/src/app/wp-content/. ./wp-content/

# Install the WordPress test suite
/usr/src/app/wp-content/plugins/wp-qr-trackr/bin/install-wp-tests.sh \
  wordpress_test \
  rootpass \
  db-nonprod \
  db-nonprod \
  latest

# Return to the plugin directory to run tests
cd /var/www/html/wp-content/plugins/wp-qr-trackr

echo "--- Running PHP Code Sniffer ---"
./vendor/bin/phpcs --standard=.phpcs.xml

echo "--- Running PHPUnit Tests ---"
./vendor/bin/phpunit --configuration phpunit.xml

echo "--- Running Stylelint ---"
yarn stylelint "**/*.css"

echo "--- Running ESLint ---"
yarn eslint .

echo "--- CI Checks Passed ---" 