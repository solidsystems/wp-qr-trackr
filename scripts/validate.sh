#!/bin/bash
set -e

echo "=== QR Trackr: Full Code Validation ==="

# Enforce container-only
if [ ! -f /.dockerenv ] && [ -z "$DOCKER_CONTAINER" ]; then
  echo "ERROR: This script must be run inside a Docker container (ci-runner)."
  exit 1
fi

# Lint PHP
echo "Running PHPCS..."
vendor/bin/phpcs

# Auto-fix (optional, comment out if not desired)
# echo "Running PHPCBF..."
# vendor/bin/phpcbf

# Run tests
if [ -f vendor/bin/phpunit ]; then
  echo "Running PHPUnit..."
  vendor/bin/phpunit
fi

echo "All validation steps completed." 