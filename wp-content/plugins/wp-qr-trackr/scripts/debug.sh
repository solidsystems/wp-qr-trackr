#!/bin/bash

# Debug Operations Control Script
# Provides standardized debugging operations through Docker containers
# Usage: ./scripts/debug.sh [dev|nonprod|ci] [command]

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
    echo "Debug Operations Control Script"
    echo ""
    echo "Usage: $0 [dev|nonprod|ci] [command]"
    echo ""
    echo "Environments:"
    echo "  dev     - Development environment (port 8080)"
    echo "  nonprod - Non-production environment (port 8081)"
    echo "  ci      - CI environment"
    echo ""
    echo "Commands:"
    echo "  dependencies     - Check if required tools are available"
    echo "  container-status - Show container status"
    echo "  logs            - Show container logs"
    echo "  health          - Perform comprehensive health check"
    echo "  diagnose        - Diagnose environment issues"
    echo "  wordpress       - Check WordPress installation status"
    echo "  database        - Test database connectivity"
    echo "  plugin          - Check plugin status"
    echo "  permissions     - Check critical directory permissions"
    echo ""
    echo "Examples:"
    echo "  $0 dev dependencies"
    echo "  $0 dev container-status"
    echo "  $0 nonprod health"
    echo "  $0 ci logs"
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

# Validate environment
case $ENV in
    "dev")
        COMPOSE_FILE="docker/docker-compose.dev.yml"
        WPCLI_CONTAINER="wpcli-dev"
        WP_CONTAINER="wordpress-dev"
        DB_CONTAINER="db-dev"
        ;;
    "nonprod")
        COMPOSE_FILE="docker/docker-compose.nonprod.yml"
        WPCLI_CONTAINER="wpcli-nonprod"
        WP_CONTAINER="wordpress-nonprod"
        DB_CONTAINER="db-nonprod"
        ;;
    "ci")
        COMPOSE_FILE="docker/docker-compose.ci.yml"
        CI_CONTAINER="ci-runner"
        DB_CONTAINER="db"
        ;;
    *)
        log "ERROR" "Invalid environment: $ENV"
        log "ERROR" "Valid environments: dev, nonprod, ci"
        exit 1
        ;;
esac

# Check if compose file exists
if [ ! -f "$COMPOSE_FILE" ]; then
    log "ERROR" "Docker Compose file not found: $COMPOSE_FILE"
    exit 1
fi

# Function to check dependencies
check_dependencies() {
    log "INFO" "Checking dependencies in $ENV environment"

    case $ENV in
        "dev"|"nonprod")
            docker compose -f "$COMPOSE_FILE" exec "$WPCLI_CONTAINER" bash -c "
                echo '=== PHP ==='
                php --version
                echo '=== WP-CLI ==='
                wp --version
                echo '=== MySQL ==='
                mysql --version
            "
            ;;
        "ci")
            docker compose -f "$COMPOSE_FILE" run --rm "$CI_CONTAINER" bash -c "
                echo '=== PHP ==='
                php --version
                echo '=== Composer ==='
                composer --version
                echo '=== Yarn ==='
                yarn --version
                echo '=== Node ==='
                node --version
                echo '=== MySQL ==='
                mysql --version
            "
            ;;
    esac
}

# Function to show container status
show_container_status() {
    log "INFO" "Container status for $ENV environment"
    docker compose -f "$COMPOSE_FILE" ps
}

# Function to show logs
show_logs() {
    log "INFO" "Recent logs for $ENV environment"
    docker compose -f "$COMPOSE_FILE" logs --tail=50
}

# Function to perform health check
perform_health_check() {
    log "INFO" "Performing health check for $ENV environment"
    ./scripts/manage-containers.sh health "$ENV"
}

# Function to diagnose issues
diagnose_issues() {
    log "INFO" "Diagnosing issues for $ENV environment"
    ./scripts/manage-containers.sh diagnose "$ENV"
}

# Function to check WordPress status
check_wordpress_status() {
    log "INFO" "Checking WordPress status in $ENV environment"

    case $ENV in
        "dev"|"nonprod")
            ./scripts/wp-operations.sh "$ENV" core is-installed
            ./scripts/wp-operations.sh "$ENV" option get siteurl
            ./scripts/wp-operations.sh "$ENV" option get home
            ./scripts/wp-operations.sh "$ENV" option get permalink_structure
            ;;
        "ci")
            log "WARN" "WordPress status check not available for CI environment"
            ;;
    esac
}

# Function to test database connectivity
test_database_connectivity() {
    log "INFO" "Testing database connectivity in $ENV environment"

    case $ENV in
        "dev"|"nonprod")
            docker compose -f "$COMPOSE_FILE" exec "$DB_CONTAINER" mysqladmin ping -h localhost -u wpuser -pwppass
            ;;
        "ci")
            docker compose -f "$COMPOSE_FILE" run --rm "$CI_CONTAINER" mysqladmin ping -h "$DB_CONTAINER" -u wpuser -pwppass
            ;;
    esac
}

# Function to check plugin status
check_plugin_status() {
    log "INFO" "Checking plugin status in $ENV environment"

    case $ENV in
        "dev"|"nonprod")
            ./scripts/wp-operations.sh "$ENV" plugin list --name=wp-qr-trackr
            ./scripts/wp-operations.sh "$ENV" plugin status wp-qr-trackr
            ;;
        "ci")
            log "WARN" "Plugin status check not available for CI environment"
            ;;
    esac
}

# Function to check permissions
check_permissions() {
    log "INFO" "Checking critical directory permissions in $ENV environment"

    case $ENV in
        "dev"|"nonprod")
            docker compose -f "$COMPOSE_FILE" exec "$WP_CONTAINER" bash -c "
                echo '=== Upgrade Directory ==='
                ls -la /var/www/html/wp-content/upgrade
                echo '=== Uploads Directory ==='
                ls -la /var/www/html/wp-content/uploads
                echo '=== Plugins Directory ==='
                ls -la /var/www/html/wp-content/plugins
                echo '=== Plugin Directory ==='
                ls -la /var/www/html/wp-content/plugins/wp-qr-trackr
            "
            ;;
        "ci")
            log "WARN" "Permission check not available for CI environment"
            ;;
    esac
}

# Execute the requested command
case $COMMAND in
    "dependencies")
        check_dependencies
        ;;
    "container-status")
        show_container_status
        ;;
    "logs")
        show_logs
        ;;
    "health")
        perform_health_check
        ;;
    "diagnose")
        diagnose_issues
        ;;
    "wordpress")
        check_wordpress_status
        ;;
    "database")
        test_database_connectivity
        ;;
    "plugin")
        check_plugin_status
        ;;
    "permissions")
        check_permissions
        ;;
    *)
        log "ERROR" "Unknown command: $COMMAND"
        show_usage
        exit 1
        ;;
esac

log "INFO" "Debug operation completed successfully"
