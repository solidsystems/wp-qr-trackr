#!/bin/bash
# launch-all-docker.sh
# Starts dev, nonprod, and local GitHub MCP environments for wp-qr-trackr
#
# - Dev: WordPress on port 8080 (live-mounts, rapid iteration)
# - Nonprod: WordPress on port 8081 (clean, no live-mounts)
# - MCP: Local GitHub MCP server for merge/conflict attention
#
# Usage: ./scripts/launch-all-docker.sh

set -euo pipefail

# Check for required tools
tool_check() {
  for tool in "$@"; do
    if ! command -v "$tool" >/dev/null 2>&1; then
      echo "[ERROR] Required tool '$tool' not found. Please install it." >&2
      exit 1
    fi
  done
}
tool_check docker docker-compose npx

# Remove stopped containers and dangling volumes for a specific compose file
cleanup_env() {
  local compose_file="$1"
  # Remove stopped containers for this compose file
  stopped=$(docker-compose -f "$compose_file" ps -q -a | xargs docker ps -aq --no-trunc | xargs)
  if [ -n "$stopped" ]; then
    echo "[INFO] Removing stopped containers for $compose_file..."
    docker rm -f $stopped || true
  fi
  # Remove dangling volumes
  if [ "$(docker volume ls -qf dangling=true)" ]; then
    echo "[INFO] Removing dangling Docker volumes..."
    docker volume rm $(docker volume ls -qf dangling=true) || true
  fi
}

# Function to wait for a container to be healthy
docker_wait_for_healthy() {
  local container_name="$1"
  local retries=20
  local count=0
  echo "[INFO] Waiting for $container_name to be healthy..."
  while [ $count -lt $retries ]; do
    health=$(docker inspect --format='{{.State.Health.Status}}' "$container_name" 2>/dev/null || echo "none")
    if [ "$health" = "healthy" ]; then
      echo "[INFO] $container_name is healthy."
      return 0
    fi
    sleep 2
    count=$((count+1))
  done
  echo "[ERROR] $container_name did not become healthy in time."
  return 1
}

# Stop and remove existing dev services if running
if docker-compose -p wpqrdev -f docker-compose.dev.yml ps | grep 'Up'; then
  echo "[INFO] Stopping and removing existing DEV containers..."
  docker-compose -p wpqrdev -f docker-compose.dev.yml down --remove-orphans
  cleanup_env docker-compose.dev.yml
fi

# Start dev DB first, then wait for healthy, then start WordPress
echo "[INFO] Starting DEV DB..."
docker-compose -p wpqrdev -f docker-compose.dev.yml up -d --remove-orphans db-dev
sleep 2
docker_wait_for_healthy wpqrdev_db-dev_1 || true

# Start dev WordPress (with retry)
echo "[INFO] Starting DEV WordPress..."
for i in {1..3}; do
  if docker-compose -p wpqrdev -f docker-compose.dev.yml up -d --remove-orphans wordpress-dev; then
    break
  else
    echo "[WARN] Retry $i for DEV WordPress startup..."
    sleep 3
  fi
  if [ $i -eq 3 ]; then
    echo "[ERROR] DEV WordPress failed to start after 3 attempts."
    exit 1
  fi

done

# Stop and remove existing nonprod services if running
if docker-compose -p wpqrnonprod -f docker-compose.yml ps | grep 'Up'; then
  echo "[INFO] Stopping and removing existing NONPROD containers..."
  docker-compose -p wpqrnonprod -f docker-compose.yml down --remove-orphans
  cleanup_env docker-compose.yml
fi

# Start nonprod DB first, then wait for healthy, then start WordPress
echo "[INFO] Starting NONPROD DB..."
docker-compose -p wpqrnonprod -f docker-compose.yml up -d --remove-orphans db-nonprod
sleep 2
docker_wait_for_healthy wpqrnonprod_db-nonprod_1 || true

# Start nonprod WordPress (with retry)
echo "[INFO] Starting NONPROD WordPress..."
for i in {1..3}; do
  if docker-compose -p wpqrnonprod -f docker-compose.yml up -d --remove-orphans wordpress-nonprod; then
    break
  else
    echo "[WARN] Retry $i for NONPROD WordPress startup..."
    sleep 3
  fi
  if [ $i -eq 3 ]; then
    echo "[ERROR] NONPROD WordPress failed to start after 3 attempts."
    exit 1
  fi

done

# Print status and access URLs
echo "\n[INFO] All environments started:"
echo "  DEV:       http://localhost:8080 (WordPress dev)"
echo "  NONPROD:   http://localhost:8081 (WordPress nonprod)"

# Tail logs for both environments in parallel
echo "[INFO] Tailing logs for DEV and NONPROD Docker containers... (Ctrl+C to stop)"
docker-compose -p wpqrdev -f docker-compose.dev.yml logs -f &
docker-compose -p wpqrnonprod -f docker-compose.yml logs -f &
wait 