#!/bin/bash

# Debug script for WP QR Trackr plugin
# Provides debugging and troubleshooting commands for the development environment

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_header() {
    echo -e "${BLUE}[DEBUG]${NC} $1"
}

# Function to show usage
show_usage() {
    echo "Usage: $0 {dev|nonprod} {health|diagnose|wordpress|logs|enable-verbose|disable-verbose|logging-status}"
    echo ""
    echo "Commands:"
    echo "  health           - Check container health and status"
    echo "  diagnose         - Run comprehensive diagnostics"
    echo "  wordpress        - Check WordPress status and configuration"
    echo "  logs             - Show recent container logs"
    echo "  enable-verbose   - Enable verbose logging for nonprod environment"
    echo "  disable-verbose  - Disable verbose logging for nonprod environment"
    echo "  logging-status   - Show current logging configuration"
    echo ""
    echo "Environments:"
    echo "  dev              - Development environment (port 8080)"
    echo "  nonprod          - Non-production environment (port 8081)"
}

# Function to check if environment is valid
check_environment() {
    local env=$1
    if [[ "$env" != "dev" && "$env" != "nonprod" ]]; then
        print_error "Invalid environment: $env. Use 'dev' or 'nonprod'"
        exit 1
    fi
}

# Function to check if containers are running
check_containers() {
    local env=$1
    local compose_file="docker/docker-compose.${env}.yml"

    if [[ ! -f "$compose_file" ]]; then
        print_error "Docker Compose file not found: $compose_file"
        exit 1
    fi

    if ! docker compose -f "$compose_file" ps | grep -q "Up"; then
        print_error "Containers are not running for environment: $env"
        print_warning "Start containers first with: ./scripts/setup-wordpress.sh $env"
        exit 1
    fi
}

# Function to get container name
get_container_name() {
    local env=$1
    if [[ "$env" == "dev" ]]; then
        echo "wordpress-dev"
    elif [[ "$env" == "nonprod" ]]; then
        echo "wordpress-nonprod"
    fi
}

# Function to check health
check_health() {
    local env=$1
    local container=$(get_container_name $env)

    print_header "Checking container health for $env environment..."

    # Check if container is running
    if docker ps | grep -q "$container"; then
        print_status "Container $container is running"
    else
        print_error "Container $container is not running"
        return 1
    fi

    # Check container health
    local health_status=$(docker inspect --format='{{.State.Health.Status}}' "$container" 2>/dev/null || echo "no-health-check")
    if [[ "$health_status" == "healthy" ]]; then
        print_status "Container health: $health_status"
    elif [[ "$health_status" == "no-health-check" ]]; then
        print_warning "No health check configured for container"
    else
        print_error "Container health: $health_status"
    fi

    # Check WordPress accessibility
    local port=$([[ "$env" == "dev" ]] && echo "8080" || echo "8081")
    if curl -s -o /dev/null -w "%{http_code}" "http://localhost:$port" | grep -q "200\|302"; then
        print_status "WordPress is accessible on port $port"
    else
        print_error "WordPress is not accessible on port $port"
    fi
}

# Function to run diagnostics
run_diagnose() {
    local env=$1
    local container=$(get_container_name $env)

    print_header "Running comprehensive diagnostics for $env environment..."

    # Check container status
    print_header "Container Status:"
    docker compose -f "docker/docker-compose.${env}.yml" ps

    # Check container logs
    print_header "Recent Container Logs:"
    docker compose -f "docker/docker-compose.${env}.yml" logs --tail=20

    # Check WordPress status
    print_header "WordPress Status:"
    docker exec "$container" wp core is-installed --path=/var/www/html 2>/dev/null && print_status "WordPress is installed" || print_error "WordPress is not installed"

    # Check plugin status
    print_header "Plugin Status:"
    docker exec "$container" wp plugin list --name=wp-qr-trackr --path=/var/www/html 2>/dev/null || print_error "Plugin not found or not active"

    # Check database connectivity
    print_header "Database Status:"
    docker exec "$container" wp db check --path=/var/www/html 2>/dev/null && print_status "Database connection OK" || print_error "Database connection failed"

    # Check file permissions
    print_header "File Permissions:"
    docker exec "$container" ls -la /var/www/html/wp-content/plugins/wp-qr-trackr/ 2>/dev/null || print_error "Plugin directory not accessible"
}

# Function to check WordPress status
check_wordpress() {
    local env=$1
    local container=$(get_container_name $env)

    print_header "WordPress Status for $env environment..."

    # Check WordPress installation
    if docker exec "$container" wp core is-installed --path=/var/www/html 2>/dev/null; then
        print_status "WordPress is installed"

        # Get WordPress version
        local version=$(docker exec "$container" wp core version --path=/var/www/html 2>/dev/null)
        print_status "WordPress version: $version"

        # Check permalink structure
        local permalinks=$(docker exec "$container" wp option get permalink_structure --path=/var/www/html 2>/dev/null)
        print_status "Permalink structure: $permalinks"

        # Check active plugins
        print_header "Active Plugins:"
        docker exec "$container" wp plugin list --status=active --path=/var/www/html 2>/dev/null || print_error "Failed to get plugin list"

    else
        print_error "WordPress is not installed"
    fi
}

# Function to show logs
show_logs() {
    local env=$1

    print_header "Recent logs for $env environment..."
    docker compose -f "docker/docker-compose.${env}.yml" logs --tail=50
}

# Function to enable verbose logging
enable_verbose_logging() {
    local env=$1
    local container=$(get_container_name $env)

    if [[ "$env" != "nonprod" ]]; then
        print_error "Verbose logging can only be enabled for nonprod environment"
        exit 1
    fi

    print_header "Enabling verbose logging for nonprod environment..."

    # Check if WordPress is accessible
    if ! docker exec "$container" wp core is-installed --path=/var/www/html 2>/dev/null; then
        print_error "WordPress is not installed or accessible"
        exit 1
    fi

    # Enable verbose logging via WordPress option
    docker exec "$container" wp option update qr_trackr_verbose_logging true --path=/var/www/html 2>/dev/null
    if [[ $? -eq 0 ]]; then
        print_status "Verbose logging enabled successfully"
        print_status "Logs will now show informational messages in addition to errors and warnings"
    else
        print_error "Failed to enable verbose logging"
        exit 1
    fi
}

# Function to disable verbose logging
disable_verbose_logging() {
    local env=$1
    local container=$(get_container_name $env)

    if [[ "$env" != "nonprod" ]]; then
        print_error "Verbose logging can only be disabled for nonprod environment"
        exit 1
    fi

    print_header "Disabling verbose logging for nonprod environment..."

    # Check if WordPress is accessible
    if ! docker exec "$container" wp core is-installed --path=/var/www/html 2>/dev/null; then
        print_error "WordPress is not installed or accessible"
        exit 1
    fi

    # Disable verbose logging via WordPress option
    docker exec "$container" wp option delete qr_trackr_verbose_logging --path=/var/www/html 2>/dev/null
    if [[ $? -eq 0 ]]; then
        print_status "Verbose logging disabled successfully"
        print_status "Only errors and warnings will be logged"
    else
        print_error "Failed to disable verbose logging"
        exit 1
    fi
}

# Function to show logging status
show_logging_status() {
    local env=$1
    local container=$(get_container_name $env)

    print_header "Logging Status for $env environment..."

    # Check if WordPress is accessible
    if ! docker exec "$container" wp core is-installed --path=/var/www/html 2>/dev/null; then
        print_error "WordPress is not installed or accessible"
        exit 1
    fi

    # Get logging configuration
    local verbose_logging=$(docker exec "$container" wp option get qr_trackr_verbose_logging --path=/var/www/html 2>/dev/null || echo "false")
    local wp_debug=$(docker exec "$container" wp config get WP_DEBUG --path=/var/www/html 2>/dev/null || echo "false")
    local environment_type=$(docker exec "$container" wp config get WP_ENVIRONMENT_TYPE --path=/var/www/html 2>/dev/null || echo "not_set")

    print_status "Environment: $env"
    print_status "WP_DEBUG: $wp_debug"
    print_status "WP_ENVIRONMENT_TYPE: $environment_type"
    print_status "Verbose Logging: $verbose_logging"

    if [[ "$env" == "dev" ]]; then
        print_status "Dev environment (8080): Extra verbose logging with Query Monitor integration"
    elif [[ "$env" == "nonprod" ]]; then
        print_status "Nonprod environment (8081): Informational logging with verbose capability"
        if [[ "$verbose_logging" == "true" ]]; then
            print_status "Verbose mode: ENABLED - All info messages will be logged"
        else
            print_status "Verbose mode: DISABLED - Only errors and warnings logged"
        fi
    fi
}

# Main script logic
main() {
    if [[ $# -lt 2 ]]; then
        show_usage
        exit 1
    fi

    local env=$1
    local command=$2

    # Check environment
    check_environment "$env"

    # Check if containers are running
    check_containers "$env"

    # Execute command
    case "$command" in
        "health")
            check_health "$env"
            ;;
        "diagnose")
            run_diagnose "$env"
            ;;
        "wordpress")
            check_wordpress "$env"
            ;;
        "logs")
            show_logs "$env"
            ;;
        "enable-verbose")
            enable_verbose_logging "$env"
            ;;
        "disable-verbose")
            disable_verbose_logging "$env"
            ;;
        "logging-status")
            show_logging_status "$env"
            ;;
        *)
            print_error "Unknown command: $command"
            show_usage
            exit 1
            ;;
    esac
}

# Run main function with all arguments
main "$@"
