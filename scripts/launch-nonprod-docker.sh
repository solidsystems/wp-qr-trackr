#!/bin/bash

set -euo pipefail

# Launch the non-production vanilla WordPress Docker environment for plugin testing

echo "\n=== Launching Non-Production WordPress Docker Environment ===\n"

echo "Tearing down any existing nonprod containers and volumes (port 8081)..."
docker-compose down
# Remove only the db_data volume for this environment, if it exists
docker volume rm $(docker volume ls -q | grep 'db_data$') 2>/dev/null || true

echo "Bringing up containers (this may take a moment if not already pulled)..."
docker-compose up -d

echo "\nWordPress should be accessible at: http://localhost:8081"
echo "\nDatabase credentials:"
echo "  DB Name:     wpdb"
echo "  DB User:     wpuser"
echo "  DB Password: wppass"
echo "  MySQL Root:  rootpass"

echo "\nTo test your plugin:"
echo "  1. Open the WordPress admin at http://localhost:8081"
echo "  2. Complete the install wizard (choose any admin credentials)"
echo "  3. Go to Plugins → Add New → Upload Plugin, and upload your plugin ZIP file."
echo "  4. Activate and test in a clean environment."

echo "\nTo stop the environment:"
echo "  docker-compose down"

echo "\n[Done] Non-production Docker environment is running."

echo -e "\n---\nTailing Docker logs for nonprod environment (press Ctrl+C to exit logs, containers will keep running)...\n"
docker-compose logs -f 