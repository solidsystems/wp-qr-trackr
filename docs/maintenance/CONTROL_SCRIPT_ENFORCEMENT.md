# Control Script Enforcement Strategy

## Overview

This document outlines the strategy for ensuring that control scripts are used wherever possible, minimizing local command usage and maintaining dependency minimization as outlined in the Claude prompt engineering guidelines.

## Current State Analysis

### ✅ Well-Implemented Control Scripts

The project already has excellent control script coverage in several areas:

1. **Environment Management**
   - `scripts/setup-wordpress.sh` - WordPress environment setup
   - `scripts/setup-wordpress-enhanced.sh` - Enhanced setup with auto-recovery
   - `scripts/manage-containers.sh` - Comprehensive container management

2. **Development Workflow**
   - `scripts/validate.sh` - Full code validation
   - `scripts/check-onboarding.sh` - Environment verification
   - `scripts/fix-phpcs.sh` - Code style fixes

3. **CI/CD Integration**
   - `Makefile` targets using Docker containers
   - GitHub Actions using containerized workflows
   - Lefthook pre-commit hooks using containers

### ⚠️ Areas Needing Improvement

#### 1. Documentation Examples
Several documentation files contain direct `docker exec` commands that should be replaced with control script calls:

**Current Problematic Examples:**
```bash
# ❌ Direct docker exec commands in docs
docker exec wordpress-dev wp core is-installed --path=/var/www/html
docker exec wordpress-dev wp plugin status wp-qr-trackr --path=/var/www/html
docker exec wordpress-dev wp plugin activate wp-qr-trackr --path=/var/www/html
```

**Should be replaced with:**
```bash
# ✅ Control script usage
./scripts/manage-containers.sh health dev
./scripts/manage-containers.sh diagnose dev
```

#### 2. Manual WordPress CLI Operations
Some scripts use direct `docker exec` for WordPress operations instead of using the dedicated WP-CLI containers:

**Current Pattern:**
```bash
docker exec "$wp_container" wp core install --url="http://localhost:$port" ...
```

**Better Pattern:**
```bash
docker compose -f $compose_file exec wpcli-$env wp core install --url="http://localhost:$port" ...
```

#### 3. Debugging Commands
Documentation contains manual debugging commands that should be scripted:

**Current:**
```bash
docker compose -f docker/docker-compose.ci.yml run --rm ci-runner bash -c "which composer && which yarn && which php"
```

**Should be:**
```bash
./scripts/validate.sh  # Includes dependency verification
```

## Implementation Plan

### Phase 1: Create Missing Control Scripts

#### 1. WordPress Operations Script
Create `scripts/wp-operations.sh` for common WordPress CLI operations:

```bash
#!/bin/bash
# WordPress Operations Control Script
# Usage: ./scripts/wp-operations.sh [dev|nonprod] [command] [args...]

set -e

ENV=$1
COMMAND=$2
shift 2

case $ENV in
    "dev")
        COMPOSE_FILE="docker/docker-compose.dev.yml"
        WPCLI_CONTAINER="wpcli-dev"
        ;;
    "nonprod")
        COMPOSE_FILE="docker/docker-compose.nonprod.yml"
        WPCLI_CONTAINER="wpcli-nonprod"
        ;;
    *)
        echo "Invalid environment: $ENV"
        exit 1
        ;;
esac

docker compose -f $COMPOSE_FILE exec $WPCLI_CONTAINER wp "$COMMAND" "$@"
```

#### 2. Debug Operations Script
Create `scripts/debug.sh` for common debugging operations:

```bash
#!/bin/bash
# Debug Operations Control Script
# Usage: ./scripts/debug.sh [dev|nonprod|ci] [command]

set -e

ENV=$1
COMMAND=$2

case $COMMAND in
    "dependencies")
        docker compose -f docker/docker-compose.$ENV.yml run --rm ci-runner bash -c "which composer && which yarn && which php"
        ;;
    "container-status")
        ./scripts/manage-containers.sh status $ENV
        ;;
    "logs")
        ./scripts/manage-containers.sh logs $ENV
        ;;
    *)
        echo "Unknown debug command: $COMMAND"
        exit 1
        ;;
esac
```

### Phase 2: Update Documentation

#### 1. Replace Direct Commands in Documentation
Update all documentation files to use control scripts instead of direct Docker commands:

**Files to update:**
- `docs/development/CONTAINER_MANAGEMENT.md`
- `docs/development/CI_CD_WORKFLOW.md`
- `docs/maintenance/TROUBLESHOOTING.md`
- `docs/dev-guide/QUICK_REFERENCE.md`

#### 2. Create Command Reference
Add a command reference section to documentation showing the proper control script usage.

### Phase 3: Enhance Existing Scripts

#### 1. Improve `manage-containers.sh`
Add more WordPress-specific operations to the existing container management script:

```bash
# Add to manage-containers.sh
"wp-install")
    setup_wordpress_after_restart "$environment"
    ;;
"wp-status")
    check_wordpress_status "$environment"
    ;;
"wp-reset")
    reset_wordpress_installation "$environment"
    ;;
```

#### 2. Enhance `validate.sh`
Add dependency verification and environment checks to the validation script.

## Control Script Usage Guidelines

### ✅ Correct Usage Patterns

1. **Environment Setup:**
   ```bash
   ./scripts/setup-wordpress.sh dev
   ./scripts/setup-wordpress.sh nonprod
   ```

2. **Container Management:**
   ```bash
   ./scripts/manage-containers.sh start dev
   ./scripts/manage-containers.sh health dev
   ./scripts/manage-containers.sh restart dev
   ```

3. **Code Validation:**
   ```bash
   make validate
   make lint
   make test
   ```

4. **WordPress Operations:**
   ```bash
   ./scripts/wp-operations.sh dev plugin list
   ./scripts/wp-operations.sh dev core is-installed
   ```

5. **Debugging:**
   ```bash
   ./scripts/debug.sh dev dependencies
   ./scripts/debug.sh dev container-status
   ```

### ❌ Prohibited Patterns

1. **Direct Docker Exec:**
   ```bash
   # ❌ Don't do this
   docker exec wordpress-dev wp plugin list
   ```

2. **Direct Docker Compose Run:**
   ```bash
   # ❌ Don't do this
   docker compose -f docker/docker-compose.dev.yml run --rm ci-runner vendor/bin/phpcs
   ```

3. **Local Tool Usage:**
   ```bash
   # ❌ Don't do this
   phpcs --standard=WordPress wp-content/plugins/wp-qr-trackr/
   composer install
   yarn install
   ```

## Benefits of Control Script Enforcement

### 1. Consistency
- All developers use the same commands
- Same environment across all machines
- Reduced "works on my machine" issues

### 2. Dependency Minimization
- No local PHP, Composer, Node.js required
- Only Docker Desktop and Git needed
- Consistent tool versions across environments

### 3. Maintainability
- Centralized command logic
- Easy to update and improve
- Clear documentation of operations

### 4. Error Prevention
- Built-in error handling
- Validation of environment state
- Automatic recovery mechanisms

## Migration Checklist

### For Developers
- [ ] Replace direct `docker exec` commands with control scripts
- [ ] Use `make` targets for common operations
- [ ] Use `./scripts/manage-containers.sh` for container operations
- [ ] Use `./scripts/wp-operations.sh` for WordPress CLI operations

### For Documentation
- [ ] Update all command examples to use control scripts
- [ ] Remove direct Docker command references
- [ ] Add control script usage examples
- [ ] Create command reference guide

### For CI/CD
- [ ] Ensure all CI steps use control scripts
- [ ] Verify no direct Docker commands in workflows
- [ ] Use `make validate` for all validation steps

## Monitoring and Enforcement

### Pre-commit Hooks
The existing `scripts/check-onboarding.sh` already enforces container-only execution for validation.

### Documentation Reviews
Regular reviews of documentation to ensure control script usage.

### Code Reviews
Review all PRs for proper control script usage instead of direct commands.

## Conclusion

By enforcing control script usage throughout the project, we maintain the dependency minimization principle while providing a consistent, reliable development experience. This aligns with the Claude prompt engineering guidelines for professional WordPress plugin development with excellent security standards and elegant, maintainable code.
