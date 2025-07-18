#!/bin/bash

# Exit on error
set -e

echo "Starting CI validation..."

# Debug: List files in current directory
echo "Current directory: $(pwd)"
echo "Files in current directory:"
ls -la

# Debug: Check if we're in the right place
if [ -f "composer.json" ]; then
    echo "Found composer.json"
else
    echo "composer.json not found in $(pwd)"
    echo "Looking for it in parent directories..."
    find /usr/src/app -name "composer.json" 2>/dev/null || echo "No composer.json found anywhere"
fi

# Install dependencies if needed
if [ ! -d "vendor" ]; then
    echo "Installing Composer dependencies..."
    composer install --no-interaction
fi

if [ ! -d "node_modules" ]; then
    echo "Installing Node.js dependencies..."
    yarn install --frozen-lockfile
fi

# Install WordPress test suite
echo "Installing WordPress test suite..."
bash scripts/install-wp-tests.sh wpdb wpuser wppass db latest

# Run PHPCS (code style check)
# echo "Running PHPCS..."
# cd /usr/src/app
# ./vendor/bin/phpcs --standard=config/ci/.phpcs.xml .

# Run PHPUnit tests from project root
echo "Running PHPUnit tests..."
# Check if PHPUnit exists and run it
if [ -f "./vendor/bin/phpunit" ]; then
    echo "Found PHPUnit at ./vendor/bin/phpunit"
    ./vendor/bin/phpunit
elif [ -f "/usr/src/app/vendor/bin/phpunit" ]; then
    echo "Found PHPUnit at /usr/src/app/vendor/bin/phpunit"
    /usr/src/app/vendor/bin/phpunit
else
    echo "PHPUnit not found. Checking vendor directory..."
    ls -la vendor/bin/ || echo "vendor/bin directory not found"
    echo "Installing Composer dependencies..."
    composer install --no-interaction
    if [ -f "./vendor/bin/phpunit" ]; then
        ./vendor/bin/phpunit
    else
        echo "ERROR: PHPUnit still not found after composer install"
        exit 1
    fi
fi

# Run Playwright tests
# Playwright tests require full WordPress environment - skipping in CI for now
# echo "Running Playwright tests..."
# yarn test:e2e

echo "CI validation completed successfully!"
