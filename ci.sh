#!/bin/bash
set -e

cd /var/www/html/wp-content/plugins/wp-qr-trackr

echo "Installing Composer dependencies..."
composer install --prefer-dist --no-progress

echo "Installing Yarn dependencies..."
yarn install

echo "Setting up husky pre-commit hooks..."
yarn add -D husky
yarn husky install
yarn husky add .husky/pre-commit "yarn lint && yarn stylelint && phpcs -d memory_limit=512M --standard=.phpcs.xml --ignore=vendor ."

echo "Running PHP_CodeSniffer..."
phpcs -d memory_limit=512M --standard=.phpcs.xml --ignore=vendor .

echo "Running ESLint..."
yarn lint

echo "Running Stylelint..."
yarn stylelint

echo "Running PHPUnit..."
./vendor/bin/phpunit --coverage-clover=coverage.xml

echo "Composer Audit..."
composer audit || true

echo "Yarn Audit..."
yarn audit --groups dependencies --level moderate || true 