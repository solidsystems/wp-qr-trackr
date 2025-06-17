#!/bin/bash

set -e

echo "Stopping and removing all Docker containers..."
docker-compose down

echo "Removing database volume (db_data)..."
docker volume rm $(docker volume ls -q | grep 'db_data$') || true

# Uncomment the following lines if you want to clear uploads as well
# echo "Removing wp-content/uploads directory..."
# rm -rf ./wp-content/uploads

echo "Rebuilding Docker images from scratch..."
docker-compose build --no-cache

echo "Re-initializing Docker environment..."
./scripts/init-docker.sh

# Ensure dev debug config is present in WordPress root
if [ -f ./wp-config-dev.php ]; then
    cp -f ./wp-config-dev.php ./wp-config-dev.php
    echo "Development debug config (wp-config-dev.php) ensured in WordPress root."
fi

# Ensure debug.log exists and is writable
touch ./wp-content/debug.log
chmod 666 ./wp-content/debug.log

echo "WordPress environment has been fully reset (including fresh image build)."

# Note: Removing the Docker image (docker rmi) and rebuilding from the Dockerfile will only reset the code and environment, not the database data. The MySQL data is stored in the db_data volume, so removing the volume is the correct way to fully reset the database. If you want to force a rebuild of the image, you can add:
# docker-compose build --no-cache 