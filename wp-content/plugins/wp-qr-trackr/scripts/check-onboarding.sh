#!/bin/bash

set -e

echo "=== QR Trackr Onboarding Check ==="

# Check for Docker
if ! command -v docker &>/dev/null; then
  echo "ERROR: Docker is not installed. Please install Docker Desktop."
  exit 1
fi

# Check if Docker is running
if ! docker info &>/dev/null; then
  echo "ERROR: Docker is not running. Please start Docker Desktop."
  exit 1
fi

# Check for Git
if ! command -v git &>/dev/null; then
  echo "ERROR: Git is not installed. Please install Git."
  exit 1
fi

# Warn if local PHP, Composer, or Node is installed (optional)
for tool in php composer node; do
  if command -v $tool &>/dev/null; then
    echo "WARNING: $tool is installed locally. All development should be done in Docker containers."
  fi
done

# Check if running inside Docker
if [ ! -f /.dockerenv ] && [ -z "$DOCKER_CONTAINER" ]; then
  echo "ERROR: This script must be run inside a Docker container (ci-runner). Please use 'docker compose run --rm ci-runner ...' for all development, linting, and testing."
  exit 1
fi

echo "Onboarding check passed! Use Docker containers for all development, linting, and testing." 