#!/bin/bash

# Exit on error
set -e

echo "Running PHPCBF to fix automatically fixable issues..."

# Run PHPCBF
./vendor/bin/phpcbf --standard=.phpcs.xml .

echo "PHPCBF completed. Please review the changes and commit them." 