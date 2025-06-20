#!/bin/bash

set -euo pipefail

# Reset the non-production Docker environment (port 8081)

echo "\n=== Resetting Non-Production WordPress Docker Environment (8081) ===\n"

echo "Stopping and removing nonprod containers..."
docker-compose down

echo "Removing nonprod database volume (db_data)..."
docker volume rm $(docker volume ls -q | grep 'db_data$') 2>/dev/null || true

echo "Rebuilding Docker images for a clean start..."
docker-compose build --no-cache

echo "Non-production Docker environment has been fully reset."
echo "You can now launch it with: ./scripts/launch-nonprod-docker.sh" 