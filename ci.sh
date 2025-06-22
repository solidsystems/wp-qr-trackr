#!/bin/bash
set -e

echo "--- Running PHP Code Sniffer ---"
./vendor/bin/phpcs --standard=.phpcs.xml

echo "--- Running PHPUnit Tests ---"
./vendor/bin/phpunit --configuration phpunit.xml

echo "--- Running Stylelint ---"
yarn stylelint "**/*.css"

echo "--- Running ESLint ---"
yarn eslint .

echo "--- CI Checks Passed ---" 