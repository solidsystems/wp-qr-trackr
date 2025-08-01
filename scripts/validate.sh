#!/bin/bash
set -e

echo "=== QR Trackr: Full Code Validation ==="

# Enforce container-only
if [ ! -f /.dockerenv ] && [ -z "$DOCKER_CONTAINER" ]; then
  echo "ERROR: This script must be run inside a Docker container (ci-runner)."
  exit 1
fi

# Check if PHPCS is available
if [ ! -f "vendor/bin/phpcs" ]; then
    echo "ERROR: PHPCS not found at vendor/bin/phpcs"
    echo "Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist
fi

# Lint PHP using our custom configuration with explicit memory limit
echo "Running PHPCS..."
vendor/bin/phpcs -d memory_limit=2048M --standard=config/ci/.phpcs.xml --extensions=php wp-content/plugins/wp-qr-trackr/wp-qr-trackr.php wp-content/plugins/wp-qr-trackr/includes/ wp-content/plugins/wp-qr-trackr/templates/

# Auto-fix (optional, comment out if not desired)
echo "Running PHPCBF..."
vendor/bin/phpcbf -d memory_limit=2048M --standard=config/ci/.phpcs.xml --extensions=php wp-content/plugins/wp-qr-trackr/wp-qr-trackr.php wp-content/plugins/wp-qr-trackr/includes/ wp-content/plugins/wp-qr-trackr/templates/

# Run tests
if [ -f vendor/bin/phpunit ]; then
  echo "Running PHPUnit..."
  vendor/bin/phpunit
fi

echo "All validation steps completed."
