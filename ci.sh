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
if [ ! -d "vendor" ] || [ ! -f "vendor/bin/phpcs" ]; then
    echo "Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist
fi

if [ ! -d "node_modules" ]; then
    echo "Installing Node.js dependencies..."
    yarn install --frozen-lockfile
fi

# Install WordPress test suite
echo "Installing WordPress test suite..."
bash scripts/install-wp-tests.sh wpdb wpuser wppass db latest

# Run full validation using our validation script
echo "Running full validation..."
bash scripts/validate.sh

echo "CI validation completed successfully!"
