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

# Start dev environment (port 8080)
echo "[INFO] Starting DEV environment (port 8080)..."
if [ -f docker-compose.dev.yml ]; then
  docker-compose -f docker-compose.dev.yml up -d
else
  # Use docker-compose.yml with port override for dev
  docker-compose -f docker-compose.yml up -d
fi

# Start nonprod environment (port 8081)
echo "[INFO] Starting NONPROD environment (port 8081)..."
docker-compose -f docker-compose.yml up -d

# Start local GitHub MCP server
echo "[INFO] Starting local GitHub MCP server (port 7000)..."
# Use npx to run the official MCP GitHub server (see https://github.com/docker/mcp-servers)
# You may need to set GITHUB_PERSONAL_ACCESS_TOKEN for full functionality
npx -y @modelcontextprotocol/server-github --port 7000 &
MCP_PID=$!

# Start local Context7 MCP server (advanced documentation as a service)
echo "[INFO] Starting local Context7 MCP server (port 7001)..."
npx -y @modelcontextprotocol/server-context7 --port 7001 &
CONTEXT7_PID=$!

# Start local DigitalOcean MCP server (cloud/devops automation)
echo "[INFO] Starting local DigitalOcean MCP server (port 7002)..."
npx -y @modelcontextprotocol/server-digitalocean --port 7002 &
DO_MCP_PID=$!

# Print status and access URLs
echo "\n[INFO] All environments started:"
echo "  DEV:       http://localhost:8080 (WordPress dev)"
echo "  NONPROD:   http://localhost:8081 (WordPress nonprod)"
echo "  MCP:       http://localhost:7000 (GitHub MCP API)"
echo "  CONTEXT7:  http://localhost:7001 (Context7 MCP: documentation as a service)"
echo "  DIGITALOCEAN: http://localhost:7002 (DigitalOcean MCP: cloud/devops automation)"
echo "\n[INFO] MCP server PID: $MCP_PID"
echo "[INFO] Context7 MCP server PID: $CONTEXT7_PID"
echo "[INFO] DigitalOcean MCP server PID: $DO_MCP_PID"

echo "[INFO] Tailing logs for all Docker containers... (Ctrl+C to stop)"
docker-compose logs -f 