#!/bin/bash

# WordPress Operations Control Script
# Provides standardized access to WordPress CLI operations through Docker containers
# Usage: ./scripts/wp-operations.sh [dev|nonprod] [command] [args...]

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging function
log() {
    local level=$1
    shift
    local message="$*"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo -e "${timestamp} [${level}] ${message}"
}

# Show usage
show_usage() {
    echo "WordPress Operations Control Script"
    echo ""
    echo "Usage: $0 [dev|nonprod] [command] [args...]"
    echo ""
    echo "Environments:"
    echo "  dev     - Development environment (port 8080)"
    echo "  nonprod - Non-production environment (port 8081)"
    echo ""
    echo "Common Commands:"
    echo "  core is-installed                    - Check if WordPress is installed"
    echo "  plugin list                          - List all plugins"
    echo "  plugin status [plugin-name]          - Check plugin status"
    echo "  plugin activate [plugin-name]        - Activate a plugin"
    echo "  plugin deactivate [plugin-name]      - Deactivate a plugin"
    echo "  option get [option-name]             - Get a WordPress option"
    echo "  option update [option-name] [value]  - Update a WordPress option"
    echo "  rewrite structure [structure]        - Set permalink structure"
    echo "  rewrite flush                        - Flush rewrite rules"
    echo "  user list                            - List users"
    echo "  db query [sql]                       - Run database query"
    echo ""
    echo "Examples:"
    echo "  $0 dev plugin list"
    echo "  $0 dev core is-installed"
    echo "  $0 nonprod plugin status wp-qr-trackr"
    echo "  $0 dev option get permalink_structure"
    echo ""
}

# Check if required arguments are provided
if [ $# -lt 2 ]; then
    log "ERROR" "Missing required arguments"
    show_usage
    exit 1
fi

ENV=$1
COMMAND=$2
shift 2
ARGS="$@"

# Validate environment
case $ENV in
    "dev")
        COMPOSE_FILE="docker/docker-compose.dev.yml"
        WPCLI_CONTAINER="wpcli-dev"
        PORT=8080
        ;;
    "nonprod")
        COMPOSE_FILE="docker/docker-compose.nonprod.yml"
        WPCLI_CONTAINER="wpcli-nonprod"
        PORT=8081
        ;;
    *)
        log "ERROR" "Invalid environment: $ENV"
        log "ERROR" "Valid environments: dev, nonprod"
        exit 1
        ;;
esac

# Check if compose file exists
if [ ! -f "$COMPOSE_FILE" ]; then
    log "ERROR" "Docker Compose file not found: $COMPOSE_FILE"
    exit 1
fi

# Check if containers are running
if ! docker compose -f "$COMPOSE_FILE" ps --format json | grep -q '"State":"running"'; then
    log "WARN" "Containers for $ENV environment are not running"
    log "INFO" "Starting containers..."
    docker compose -f "$COMPOSE_FILE" up -d
    sleep 5
fi

# Check if WP-CLI container is running
if ! docker compose -f "$COMPOSE_FILE" ps "$WPCLI_CONTAINER" | grep -q "Up"; then
    log "ERROR" "WP-CLI container $WPCLI_CONTAINER is not running"
    log "INFO" "Please ensure the $ENV environment is properly started"
    exit 1
fi

# Execute WordPress CLI command
log "INFO" "Executing WordPress CLI command in $ENV environment"
log "INFO" "Command: wp $COMMAND $ARGS"

# Run the command
if docker compose -f "$COMPOSE_FILE" exec -T "$WPCLI_CONTAINER" wp "$COMMAND" $ARGS --path=/var/www/html; then
    log "INFO" "WordPress CLI command executed successfully"
else
    log "ERROR" "WordPress CLI command failed"
    exit 1
fi
