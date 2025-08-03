#!/bin/bash

# Enhanced Container Management Script for WP QR Trackr
# Detects issues with Docker containers and automatically redeploys when needed

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
MAX_RESTART_ATTEMPTS=3
HEALTH_CHECK_TIMEOUT=30
LOG_FILE="/tmp/wp-qr-trackr-container-manager.log"

# Logging function
log() {
    local level=$1
    shift
    local message="$*"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo -e "${timestamp} [${level}] ${message}" | tee -a "$LOG_FILE"
}

# Function to check if Docker is running
check_docker() {
    if ! command -v docker &>/dev/null; then
        log "ERROR" "Docker is not installed. Please install Docker Desktop."
        exit 1
    fi

    if ! docker info &>/dev/null; then
        log "ERROR" "Docker is not running. Please start Docker Desktop."
        exit 1
    fi

    log "INFO" "Docker is running and accessible."
}

# Function to check container health
check_container_health() {
    local container_name=$1
    local max_attempts=${2:-5}
    local attempt=1

    log "INFO" "Checking health of container: $container_name"

    while [ $attempt -le $max_attempts ]; do
        if docker ps --format "table {{.Names}}\t{{.Status}}" | grep -q "$container_name.*Up"; then
            log "INFO" "Container $container_name is running and healthy."
            return 0
        fi

        if docker ps -a --format "table {{.Names}}\t{{.Status}}" | grep -q "$container_name.*Exited"; then
            log "WARN" "Container $container_name has exited. Attempt $attempt/$max_attempts"
            return 1
        fi

        if ! docker ps --format "{{.Names}}" | grep -q "$container_name"; then
            log "WARN" "Container $container_name not found. Attempt $attempt/$max_attempts"
            return 1
        fi

        log "INFO" "Waiting for container $container_name to stabilize... (attempt $attempt/$max_attempts)"
        sleep 2
        attempt=$((attempt + 1))
    done

    log "ERROR" "Container $container_name failed health check after $max_attempts attempts."
    return 1
}

# Function to check WordPress accessibility
check_wordpress_accessibility() {
    local port=$1
    local max_attempts=${2:-10}
    local attempt=1

    log "INFO" "Checking WordPress accessibility on port $port"

    while [ $attempt -le $max_attempts ]; do
        if curl -s -f "http://localhost:$port" > /dev/null 2>&1; then
            log "INFO" "WordPress is accessible on port $port"
            return 0
        fi

        log "WARN" "WordPress not accessible on port $port. Attempt $attempt/$max_attempts"
        sleep 3
        attempt=$((attempt + 1))
    done

    log "ERROR" "WordPress failed accessibility check on port $port after $max_attempts attempts."
    return 1
}

# Function to check database connectivity
check_database_connectivity() {
    local container_name=$1
    local db_host=${2:-"db-dev"}
    local db_name=${3:-"wpdb"}
    local db_user=${4:-"wpuser"}
    local db_pass=${5:-"wppass"}

    log "INFO" "Checking database connectivity from $container_name"

    if docker exec "$container_name" mysql -h"$db_host" -u"$db_user" -p"$db_pass" "$db_name" -e "SELECT 1;" > /dev/null 2>&1; then
        log "INFO" "Database connectivity check passed for $container_name"
        return 0
    else
        log "ERROR" "Database connectivity check failed for $container_name"
        return 1
    fi
}

# Function to check WordPress installation
check_wordpress_installation() {
    local container_name=$1
    local port=$2

    log "INFO" "Checking WordPress installation in $container_name"

    # Check if wp-config.php exists
    if ! docker exec "$container_name" test -f /var/www/html/wp-config.php; then
        log "ERROR" "wp-config.php not found in $container_name"
        return 1
    fi

    # Check if WordPress core files exist
    if ! docker exec "$container_name" test -f /var/www/html/wp-load.php; then
        log "ERROR" "WordPress core files not found in $container_name"
        return 1
    fi

    # Check if plugin directory exists
    if ! docker exec "$container_name" test -d /var/www/html/wp-content/plugins/wp-qr-trackr; then
        log "ERROR" "WP QR Trackr plugin directory not found in $container_name"
        return 1
    fi

    log "INFO" "WordPress installation check passed for $container_name"
    return 0
}

# Function to restart containers
restart_containers() {
    local env=$1
    local compose_file="docker/docker-compose.$env.yml"

    log "INFO" "Restarting containers for $env environment"

    # Stop containers
    log "INFO" "Stopping containers..."
    docker compose -f "$compose_file" down

    # Remove any orphaned containers
    log "INFO" "Cleaning up orphaned containers..."
    docker compose -f "$compose_file" down --remove-orphans

    # Start containers
    log "INFO" "Starting containers..."
    docker compose -f "$compose_file" up -d

    # Wait for containers to be ready
    log "INFO" "Waiting for containers to be ready..."
    sleep 10
}

# Function to redeploy containers
redeploy_containers() {
    local env=$1
    local compose_file="docker/docker-compose.$env.yml"

    log "INFO" "Redeploying containers for $env environment"

    # Stop and remove containers
    log "INFO" "Stopping and removing containers..."
    docker compose -f "$compose_file" down -v

    # Remove images to force rebuild
    log "INFO" "Removing images to force rebuild..."
    docker compose -f "$compose_file" down --rmi all

    # Rebuild and start containers
    log "INFO" "Rebuilding and starting containers..."
    docker compose -f "$compose_file" up -d --build

    # Wait for containers to be ready
    log "INFO" "Waiting for containers to be ready..."
    sleep 15
}

# Function to diagnose container issues
diagnose_issues() {
    local env=$1
    local compose_file="docker/docker-compose.$env.yml"

    log "INFO" "Diagnosing issues for $env environment"

    # Check container status
    log "INFO" "Container status:"
    docker compose -f "$compose_file" ps

    # Check container logs
    log "INFO" "Recent container logs:"
    docker compose -f "$compose_file" logs --tail=20

    # Check resource usage
    log "INFO" "Resource usage:"
    docker stats --no-stream --format "table {{.Container}}\t{{.CPUPerc}}\t{{.MemUsage}}\t{{.NetIO}}"

    # Check disk space
    log "INFO" "Disk space usage:"
    docker system df
}

# Function to setup WordPress after container restart
setup_wordpress_after_restart() {
    local env=$1
    local port
    local wp_container

            case $env in
            "dev")
                port=8080
                wp_container="docker-wordpress-dev-1"
                ;;
        "nonprod")
            port=8081
            wp_container="docker-wordpress-nonprod-1"
            ;;
        *)
            log "ERROR" "Invalid environment: $env"
            return 1
            ;;
    esac

    log "INFO" "Setting up WordPress for $env environment after restart"

    # Wait for WordPress to be accessible
    if ! check_wordpress_accessibility "$port"; then
        log "ERROR" "WordPress not accessible after restart"
        return 1
    fi

    # Check if WordPress needs installation
    if ! docker exec "$wp_container" wp core is-installed --path=/var/www/html 2>/dev/null; then
        log "INFO" "WordPress not installed, running installation..."

        # Install WordPress
        docker exec "$wp_container" wp core install \
            --url="http://localhost:$port" \
            --title="WP QR Trackr" \
            --admin_user=trackr \
            --admin_password=trackr \
            --admin_email=test@example.com \
            --skip-email \
            --path=/var/www/html

        # Set permalink structure
        docker exec "$wp_container" wp rewrite structure '/%postname%/' --path=/var/www/html
        docker exec "$wp_container" wp rewrite flush --hard --path=/var/www/html

        # Activate plugin
        docker exec "$wp_container" wp plugin activate wp-qr-trackr --path=/var/www/html

        log "INFO" "WordPress installation completed"
    else
        log "INFO" "WordPress already installed, checking plugin status..."

        # Ensure plugin is activated
        if ! docker exec "$wp_container" wp plugin is-active wp-qr-trackr --path=/var/www/html 2>/dev/null; then
            log "INFO" "Activating WP QR Trackr plugin..."
            docker exec "$wp_container" wp plugin activate wp-qr-trackr --path=/var/www/html
        fi
    fi

    log "INFO" "WordPress setup completed for $env environment"
    return 0
}

# Function to check WordPress status
check_wordpress_status() {
    local env=$1
    local compose_file="docker/docker-compose.$env.yml"
    local wp_container

    case $env in
        "dev")
            wp_container="docker-wordpress-dev-1"
            ;;
        "nonprod")
            wp_container="docker-wordpress-nonprod-1"
            ;;
        *)
            log "ERROR" "Invalid environment: $env"
            return 1
            ;;
    esac

    log "INFO" "Checking WordPress status for $env environment"

    # Check if WordPress is installed
    if docker exec "$wp_container" wp core is-installed --path=/var/www/html 2>/dev/null; then
        log "INFO" "WordPress is installed"

        # Get site URL
        local site_url=$(docker exec "$wp_container" wp option get siteurl --path=/var/www/html 2>/dev/null || echo "Not set")
        log "INFO" "Site URL: $site_url"

        # Get home URL
        local home_url=$(docker exec "$wp_container" wp option get home --path=/var/www/html 2>/dev/null || echo "Not set")
        log "INFO" "Home URL: $home_url"

        # Get permalink structure
        local permalink_structure=$(docker exec "$wp_container" wp option get permalink_structure --path=/var/www/html 2>/dev/null || echo "Not set")
        log "INFO" "Permalink structure: $permalink_structure"
    else
        log "WARN" "WordPress is not installed"
    fi
}

# Function to reset WordPress installation
reset_wordpress_installation() {
    local env=$1
    local compose_file="docker/docker-compose.$env.yml"
    local wp_container

    case $env in
        "dev")
            wp_container="docker-wordpress-dev-1"
            ;;
        "nonprod")
            wp_container="docker-wordpress-nonprod-1"
            ;;
        *)
            log "ERROR" "Invalid environment: $env"
            return 1
            ;;
    esac

    log "INFO" "Resetting WordPress installation for $env environment"

    # Stop containers
    docker compose -f "$compose_file" down

    # Remove volumes to reset data
    docker compose -f "$compose_file" down -v

    # Start containers
    docker compose -f "$compose_file" up -d

    # Wait for containers to be ready
    sleep 10

    # Setup WordPress
    setup_wordpress_after_restart "$env"

    log "INFO" "WordPress installation reset completed"
}

# Function to check plugin status
check_plugin_status() {
    local env=$1
    local compose_file="docker/docker-compose.$env.yml"
    local wp_container

    case $env in
        "dev")
            wp_container="docker-wordpress-dev-1"
            ;;
        "nonprod")
            wp_container="docker-wordpress-nonprod-1"
            ;;
        *)
            log "ERROR" "Invalid environment: $env"
            return 1
            ;;
    esac

    log "INFO" "Checking plugin status for $env environment"

    # Check if plugin exists
    if docker exec "$wp_container" test -d /var/www/html/wp-content/plugins/wp-qr-trackr; then
        log "INFO" "WP QR Trackr plugin is installed"

        # Check plugin status
        if docker exec "$wp_container" wp plugin is-active wp-qr-trackr --path=/var/www/html 2>/dev/null; then
            log "INFO" "WP QR Trackr plugin is active"
        else
            log "WARN" "WP QR Trackr plugin is not active"
        fi

        # List all plugins
        log "INFO" "All installed plugins:"
        docker exec "$wp_container" wp plugin list --path=/var/www/html
    else
        log "WARN" "WP QR Trackr plugin is not installed"
    fi
}

# Function to monitor containers continuously
monitor_containers() {
    local env=$1
    local interval=${2:-60}  # Check every 60 seconds by default

    log "INFO" "Starting continuous monitoring for $env environment (checking every ${interval}s)"

    while true; do
        local issues_found=false

        # Check container health
        case $env in
            "dev")
                if ! check_container_health "docker-wordpress-dev-1"; then
                    log "WARN" "WordPress dev container health check failed"
                    issues_found=true
                fi
                if ! check_container_health "docker-db-dev-1"; then
                    log "WARN" "Database dev container health check failed"
                    issues_found=true
                fi
                ;;
            "nonprod")
                if ! check_container_health "docker-wordpress-nonprod-1"; then
                    log "WARN" "WordPress nonprod container health check failed"
                    issues_found=true
                fi
                if ! check_container_health "docker-db-nonprod-1"; then
                    log "WARN" "Database nonprod container health check failed"
                    issues_found=true
                fi
                ;;
        esac

        # Check WordPress accessibility
        case $env in
            "dev")
                if ! check_wordpress_accessibility 8080; then
                    log "WARN" "WordPress dev accessibility check failed"
                    issues_found=true
                fi
                ;;
            "nonprod")
                if ! check_wordpress_accessibility 8081; then
                    log "WARN" "WordPress nonprod accessibility check failed"
                    issues_found=true
                fi
                ;;
        esac

        if [ "$issues_found" = true ]; then
            log "WARN" "Issues detected, attempting restart..."
            restart_containers "$env"
            setup_wordpress_after_restart "$env"
        else
            log "INFO" "All health checks passed"
        fi

        sleep "$interval"
    done
}

# Function to perform comprehensive health check
comprehensive_health_check() {
    local env=$1
    local compose_file="docker/docker-compose.$env.yml"
    local all_healthy=true

    log "INFO" "Performing comprehensive health check for $env environment"

    # Check if compose file exists
    if [ ! -f "$compose_file" ]; then
        log "ERROR" "Docker Compose file not found: $compose_file"
        return 1
    fi

    # Check container status
    log "INFO" "Checking container status..."
    if ! docker compose -f "$compose_file" ps | grep -q "Up"; then
        log "ERROR" "No containers are running"
        all_healthy=false
    fi

    # Check specific containers based on environment
            case $env in
            "dev")
                if ! check_container_health "docker-wordpress-dev-1"; then
                    all_healthy=false
                fi
                if ! check_container_health "docker-db-dev-1"; then
                    all_healthy=false
                fi
                if ! check_wordpress_accessibility 8080; then
                    all_healthy=false
                fi
                if ! check_wordpress_installation "docker-wordpress-dev-1" 8080; then
                    all_healthy=false
                fi
                ;;
                    "nonprod")
                if ! check_container_health "docker-wordpress-nonprod-1"; then
                    all_healthy=false
                fi
                if ! check_container_health "docker-db-nonprod-1"; then
                    all_healthy=false
                fi
                if ! check_wordpress_accessibility 8081; then
                    all_healthy=false
                fi
                if ! check_wordpress_installation "docker-wordpress-nonprod-1" 8081; then
                    all_healthy=false
                fi
                ;;
    esac

    if [ "$all_healthy" = true ]; then
        log "INFO" "All health checks passed for $env environment"
        return 0
    else
        log "ERROR" "Health checks failed for $env environment"
        return 1
    fi
}

# Function to show usage
show_usage() {
    echo "Usage: $0 [COMMAND] [ENVIRONMENT] [OPTIONS]"
    echo ""
    echo "Commands:"
    echo "  start [dev|nonprod]     - Start containers for specified environment"
    echo "  stop [dev|nonprod]      - Stop containers for specified environment"
    echo "  restart [dev|nonprod]   - Restart containers for specified environment"
    echo "  redeploy [dev|nonprod]  - Redeploy containers (rebuild and restart)"
    echo "  health [dev|nonprod]    - Perform comprehensive health check"
    echo "  monitor [dev|nonprod]   - Start continuous monitoring"
    echo "  diagnose [dev|nonprod]  - Diagnose container issues"
    echo "  logs [dev|nonprod]      - Show container logs"
    echo "  status [dev|nonprod]    - Show container status"
    echo "  wp-install [dev|nonprod] - Install WordPress"
    echo "  wp-status [dev|nonprod]  - Check WordPress status"
    echo "  wp-reset [dev|nonprod]   - Reset WordPress installation"
    echo "  wp-plugin-status [dev|nonprod] - Check plugin status"
    echo ""
    echo "Options:"
    echo "  --interval SECONDS      - Monitoring interval (default: 60)"
    echo "  --help                  - Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0 start dev            - Start development environment"
    echo "  $0 health nonprod       - Check nonprod environment health"
    echo "  $0 monitor dev --interval 30  - Monitor dev environment every 30 seconds"
}

# Main script logic
main() {
    local command=$1
    local environment=$2
    local interval=60

    # Parse options
    while [[ $# -gt 0 ]]; do
        case $1 in
            --interval)
                interval="$2"
                shift 2
                ;;
            --help)
                show_usage
                exit 0
                ;;
            *)
                shift
                ;;
        esac
    done

    # Check Docker
    check_docker

    # Validate command and environment
    if [ -z "$command" ] || [ -z "$environment" ]; then
        log "ERROR" "Command and environment are required"
        show_usage
        exit 1
    fi

    if [[ ! "$environment" =~ ^(dev|nonprod)$ ]]; then
        log "ERROR" "Invalid environment: $environment. Use 'dev' or 'nonprod'"
        exit 1
    fi

    local compose_file="docker/docker-compose.$environment.yml"

    # Execute command
    case $command in
        "start")
            log "INFO" "Starting $environment environment..."
            docker compose -f "$compose_file" up -d
            sleep 10
            setup_wordpress_after_restart "$environment"
            ;;
        "stop")
            log "INFO" "Stopping $environment environment..."
            docker compose -f "$compose_file" down
            ;;
        "restart")
            restart_containers "$environment"
            setup_wordpress_after_restart "$environment"
            ;;
        "redeploy")
            redeploy_containers "$environment"
            setup_wordpress_after_restart "$environment"
            ;;
        "health")
            comprehensive_health_check "$environment"
            ;;
        "monitor")
            monitor_containers "$environment" "$interval"
            ;;
        "diagnose")
            diagnose_issues "$environment"
            ;;
        "logs")
            log "INFO" "Showing logs for $environment environment..."
            docker compose -f "$compose_file" logs -f
            ;;
        "status")
            log "INFO" "Container status for $environment environment:"
            docker compose -f "$compose_file" ps
            ;;
        "wp-install")
            log "INFO" "Installing WordPress for $environment environment..."
            setup_wordpress_after_restart "$environment"
            ;;
        "wp-status")
            log "INFO" "Checking WordPress status for $environment environment..."
            check_wordpress_status "$environment"
            ;;
        "wp-reset")
            log "INFO" "Resetting WordPress installation for $environment environment..."
            reset_wordpress_installation "$environment"
            ;;
        "wp-plugin-status")
            log "INFO" "Checking plugin status for $environment environment..."
            check_plugin_status "$environment"
            ;;
        *)
            log "ERROR" "Unknown command: $command"
            show_usage
            exit 1
            ;;
    esac
}

# Run main function with all arguments
main "$@"
