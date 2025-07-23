#!/bin/bash

# Enhanced WordPress Setup Script with Automatic Issue Detection and Recovery
# Integrates with the container management system for robust deployment

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
MAX_RETRY_ATTEMPTS=3
HEALTH_CHECK_TIMEOUT=30

# Logging function
log() {
    local level=$1
    shift
    local message="$*"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo -e "${timestamp} [${level}] ${message}"
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

# Function to diagnose and fix issues
diagnose_and_fix() {
    local env=$1
    local compose_file="docker/docker-compose.$env.yml"
    local attempt=1

    log "INFO" "Diagnosing issues for $env environment..."

    while [ $attempt -le $MAX_RETRY_ATTEMPTS ]; do
        log "INFO" "Diagnosis attempt $attempt/$MAX_RETRY_ATTEMPTS"

        # Check container status
        log "INFO" "Checking container status..."
        if ! docker compose -f "$compose_file" ps | grep -q "Up"; then
            log "WARN" "No containers are running, attempting restart..."
            docker compose -f "$compose_file" down
            docker compose -f "$compose_file" up -d
            sleep 15
            continue
        fi

        # Check specific containers based on environment
        local container_issues=false
        case $env in
            "dev")
                if ! check_container_health "wordpress-dev"; then
                    container_issues=true
                fi
                if ! check_container_health "db-dev"; then
                    container_issues=true
                fi
                if ! check_wordpress_accessibility 8080; then
                    container_issues=true
                fi
                ;;
            "nonprod")
                if ! check_container_health "wordpress-nonprod"; then
                    container_issues=true
                fi
                if ! check_container_health "db-nonprod"; then
                    container_issues=true
                fi
                if ! check_wordpress_accessibility 8081; then
                    container_issues=true
                fi
                ;;
        esac

        if [ "$container_issues" = false ]; then
            log "INFO" "All health checks passed!"
            return 0
        fi

        log "WARN" "Issues detected, attempting recovery..."
        
        # Show container logs for debugging
        log "INFO" "Recent container logs:"
        docker compose -f "$compose_file" logs --tail=10

        # Attempt restart
        log "INFO" "Restarting containers..."
        docker compose -f "$compose_file" restart
        sleep 10

        attempt=$((attempt + 1))
    done

    log "ERROR" "Failed to resolve issues after $MAX_RETRY_ATTEMPTS attempts."
    log "ERROR" "Please check the logs and try manual intervention:"
    log "ERROR" "  docker compose -f $compose_file logs"
    log "ERROR" "  docker compose -f $compose_file down"
    log "ERROR" "  docker compose -f $compose_file up -d"
    return 1
}

# Function to wait for WordPress container to be ready
wait_for_wordpress() {
    local port=$1
    echo "Waiting for WordPress to be ready on port $port..."
    local max_attempts=30
    local attempt=1
    while [ $attempt -le $max_attempts ]; do
        if curl -s "http://localhost:$port" > /dev/null; then
            echo "WordPress is ready!"
            return 0
        fi
        echo "Attempt $attempt/$max_attempts - Waiting for WordPress..."
        sleep 2
        attempt=$((attempt + 1))
    done
    echo "WordPress failed to become ready"
    return 1
}

# Function to install WordPress using wp-cli in Docker
install_wordpress() {
    local container=$1
    local port=$2
    
    echo "Installing WordPress in container $container..."
    
    # Install WordPress core
    docker compose -f docker/docker-compose.playwright.yml exec -T $container wp core install \
        --url="http://localhost:$port" \
        --title="WP QR Trackr" \
        --admin_user=trackr \
        --admin_password=trackr \
        --admin_email=test@example.com \
        --skip-email \
        --path=/var/www/html

    # If playwright env, set siteurl and home to service name for container access
    if [ "$container" = "wordpress-playwright" ]; then
        docker compose -f docker/docker-compose.playwright.yml exec -T $container wp option update siteurl http://wordpress-playwright --path=/var/www/html
        docker compose -f docker/docker-compose.playwright.yml exec -T $container wp option update home http://wordpress-playwright --path=/var/www/html
    fi

    # Set permalink structure
    docker compose -f docker/docker-compose.playwright.yml exec -T $container wp rewrite structure '/%postname%/' --path=/var/www/html
    docker compose -f docker/docker-compose.playwright.yml exec -T $container wp rewrite flush --hard --path=/var/www/html

    # Activate plugin
    docker compose -f docker/docker-compose.playwright.yml exec -T $container wp plugin activate wp-qr-trackr --path=/var/www/html
}

# Function to setup WordPress after container restart
setup_wordpress_after_restart() {
    local env=$1
    local port
    local wp_container

    case $env in
        "dev")
            port=8080
            wp_container="wordpress-dev"
            ;;
        "nonprod")
            port=8081
            wp_container="wordpress-nonprod"
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

# Setup WordPress based on environment
setup_wordpress() {
    local env=$1
    local port
    local wp_container
    local compose_file="docker/docker-compose.$env.yml"
    
    case $env in
        "dev")
            port=8080
            wp_container="wordpress-dev"
            ;;
        "nonprod")
            port=8081
            wp_container="wordpress-nonprod"
            ;;
        "playwright")
            port=8087
            wp_container="wordpress-playwright"
            ;;
        *)
            echo "Invalid environment: $env"
            echo "Usage: $0 [dev|nonprod|playwright]"
            exit 1
            ;;
    esac

    log "INFO" "Setting up WordPress for $env environment..."

    # Check Docker
    check_docker

    # Check if compose file exists
    if [ ! -f "$compose_file" ]; then
        log "ERROR" "Docker Compose file not found: $compose_file"
        exit 1
    fi

    # Start containers
    log "INFO" "Starting containers..."
    docker compose -f "$compose_file" up -d

    # Wait for containers to be ready
    log "INFO" "Waiting for containers to be ready..."
    sleep 10

    # For dev and nonprod environments, perform health checks and auto-recovery
    if [[ "$env" =~ ^(dev|nonprod)$ ]]; then
        log "INFO" "Performing health checks and auto-recovery..."
        
        # Diagnose and fix any issues
        if ! diagnose_and_fix "$env"; then
            log "ERROR" "Failed to resolve container issues automatically."
            log "ERROR" "Please check the logs and try manual intervention."
            exit 1
        fi

        # Setup WordPress after successful health checks
        if ! setup_wordpress_after_restart "$env"; then
            log "ERROR" "Failed to setup WordPress after container recovery."
            exit 1
        fi
    else
        # For playwright environment, use original logic
        # Wait for WordPress to be ready
        wait_for_wordpress $port || {
            echo "Failed to connect to WordPress"
            exit 1
        }

        # Install WordPress
        install_wordpress $wp_container $port
    fi

    log "INFO" "WordPress setup complete for $env environment!"
    log "INFO" "Admin URL: http://localhost:$port/wp-admin"
    log "INFO" "Username: trackr"
    log "INFO" "Password: trackr"
    
    # Show container status
    log "INFO" "Container status:"
    docker compose -f "$compose_file" ps
}

# Function to show usage
show_usage() {
    echo "Usage: $0 [ENVIRONMENT] [OPTIONS]"
    echo ""
    echo "Environments:"
    echo "  dev         - Development environment (port 8080)"
    echo "  nonprod     - Non-production environment (port 8081)"
    echo "  playwright  - Playwright testing environment (port 8087)"
    echo ""
    echo "Options:"
    echo "  --help      - Show this help message"
    echo ""
    echo "Features:"
    echo "  - Automatic issue detection and recovery"
    echo "  - Health checks for containers and WordPress"
    echo "  - Auto-restart on container failures"
    echo "  - Comprehensive logging and diagnostics"
    echo ""
    echo "Examples:"
    echo "  $0 dev       - Start development environment with auto-recovery"
    echo "  $0 nonprod   - Start nonprod environment with auto-recovery"
}

# Main script
if [ $# -eq 0 ] || [ "$1" = "--help" ]; then
    show_usage
    exit 0
fi

if [ $# -ne 1 ]; then
    echo "Usage: $0 [dev|nonprod|playwright]"
    exit 1
fi

setup_wordpress $1 