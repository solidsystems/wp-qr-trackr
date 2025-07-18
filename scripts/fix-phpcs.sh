#!/bin/bash

# Exit on error
set -e

echo "Running PHPCBF to fix automatically fixable issues..."

# Run PHPCBF to auto-fix PHPCS issues
./vendor/bin/phpcbf --standard=config/ci/.phpcs.xml .

echo "PHPCBF completed. Please review the changes and commit them." 