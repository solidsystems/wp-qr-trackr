#!/bin/bash

set -e

PORT=8080

# Check if the port is in use
if lsof -i :$PORT | grep LISTEN; then
  echo "Port $PORT is in use. Attempting to stop the Docker container using it..."
  # Get the container ID using the port
  CONTAINER_ID=$(docker ps --format '{{.ID}} {{.Ports}}' | grep 0.0.0.0:$PORT | awk '{print $1}')
  if [ -n "$CONTAINER_ID" ]; then
    echo "Stopping container $CONTAINER_ID..."
    docker stop $CONTAINER_ID
  else
    echo "Port $PORT is in use, but not by a Docker container. Please free the port manually."
    exit 1
  fi
else
  echo "Port $PORT is free."
fi

echo "Starting Docker dev environment with docker-compose.dev.yml..."
docker-compose -f docker-compose.dev.yml up --build 