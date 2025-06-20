#!/bin/bash
set -e

# Playwright Full Reset Script
# Removes all Playwright-related containers and volumes for a clean environment.
# Usage: ./scripts/playwright-full-reset.sh

COMPOSE_FILE="docker-compose.playwright.yml"

echo "[playwright-full-reset] Stopping and removing all Playwright containers and volumes..."
docker compose -f $COMPOSE_FILE down -v

echo "[playwright-full-reset] All Playwright containers and volumes removed."
echo "You can now run ./scripts/playwright-docs-orchestrator.sh for a fresh environment." 