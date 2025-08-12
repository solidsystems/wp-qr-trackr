# Command Reference Guide

This guide provides the correct control script usage for all common operations in the WP QR Trackr project. **Always use these control scripts instead of direct Docker commands.**

## Quick Reference

### Environment Setup
```bash
# Start development environment
./scripts/setup-wordpress.sh dev

# Start nonprod environment
./scripts/setup-wordpress.sh nonprod

# Enhanced setup with auto-recovery
./scripts/setup-wordpress-enhanced.sh dev
```

### Container Management
```bash
# Start containers
./scripts/manage-containers.sh start dev
./scripts/manage-containers.sh start nonprod

# Check health
./scripts/manage-containers.sh health dev
./scripts/manage-containers.sh health nonprod

# Restart containers
./scripts/manage-containers.sh restart dev
./scripts/manage-containers.sh restart nonprod

# View logs
./scripts/manage-containers.sh logs dev
./scripts/manage-containers.sh logs nonprod
```

### WordPress Operations
```bash
# List plugins
./scripts/wp-operations.sh dev plugin list
./scripts/wp-operations.sh nonprod plugin list

# Check WordPress installation
./scripts/wp-operations.sh dev core is-installed
./scripts/wp-operations.sh nonprod core is-installed

# Check plugin status
./scripts/wp-operations.sh dev plugin status wp-qr-trackr
./scripts/wp-operations.sh nonprod plugin status wp-qr-trackr

# Get permalink structure
./scripts/wp-operations.sh dev option get permalink_structure
./scripts/wp-operations.sh nonprod option get permalink_structure
```

### Debug Operations
```bash
# Check dependencies
./scripts/debug.sh dev dependencies
./scripts/debug.sh nonprod dependencies

# Check container status
./scripts/debug.sh dev container-status
./scripts/debug.sh nonprod container-status

# Check WordPress status
./scripts/debug.sh dev wordpress
./scripts/debug.sh nonprod wordpress

# Test database connectivity
./scripts/debug.sh dev database
./scripts/debug.sh nonprod database
```

### Code Validation
```bash
# Full validation (linting only; E2E skipped by default)
make validate

# Full validation including Playwright E2E (local only)
make validate-e2e

# Linting only
make lint

# Auto-fix code style
make fix

# Run tests
make test
```

## Makefile Targets

### WordPress Operations
```bash
# WordPress operations for dev environment
make wp-dev COMMAND="plugin list"
make wp-dev COMMAND="core is-installed"
make wp-dev COMMAND="option get permalink_structure"

# WordPress operations for nonprod environment
make wp-nonprod COMMAND="plugin list"
make wp-nonprod COMMAND="core is-installed"
make wp-nonprod COMMAND="option get permalink_structure"
```

### Debug Operations
```bash
# Debug operations for dev environment
make debug-dev COMMAND="dependencies"
make debug-dev COMMAND="container-status"
make debug-dev COMMAND="wordpress"
make debug-dev COMMAND="database"

# Debug operations for nonprod environment
make debug-nonprod COMMAND="dependencies"
make debug-nonprod COMMAND="container-status"
make debug-nonprod COMMAND="wordpress"
make debug-nonprod COMMAND="database"
```

### Container Management
```bash
# Container management for dev environment
make containers-dev COMMAND="start"
make containers-dev COMMAND="health"
make containers-dev COMMAND="wp-status"
make containers-dev COMMAND="wp-plugin-status"

# Container management for nonprod environment
make containers-nonprod COMMAND="start"
make containers-nonprod COMMAND="health"
make containers-nonprod COMMAND="wp-status"
make containers-nonprod COMMAND="wp-plugin-status"
```

## Common Operations

### 1. Starting Development Environment
```bash
# ✅ Correct way
./scripts/setup-wordpress.sh dev

# ❌ Don't do this
docker compose -f docker/docker-compose.dev.yml up -d
docker exec wordpress-dev wp core install --url="http://localhost:8080" ...
```

### 2. Checking Plugin Status
```bash
# ✅ Correct way
./scripts/wp-operations.sh dev plugin status wp-qr-trackr

# ❌ Don't do this
docker exec wordpress-dev wp plugin status wp-qr-trackr --path=/var/www/html
```

### 3. Running Code Validation
```bash
# ✅ Correct way
make validate
make validate-e2e  # to include local Playwright E2E

# ❌ Don't do this
docker compose -f docker/docker-compose.playwright.yml run --rm playwright-runner ./vendor/bin/phpcs
```

### 4. Debugging Issues
```bash
# ✅ Correct way
./scripts/debug.sh dev health
./scripts/debug.sh dev diagnose
./scripts/debug.sh dev wordpress

# ❌ Don't do this
docker compose -f docker/docker-compose.dev.yml logs
docker exec wordpress-dev wp core is-installed --path=/var/www/html
```

### 5. Container Health Checks
```bash
# ✅ Correct way
./scripts/manage-containers.sh health dev
./scripts/manage-containers.sh diagnose dev

# ❌ Don't do this
docker compose -f docker/docker-compose.dev.yml ps
docker exec wordpress-dev curl -s http://localhost
```

## WordPress CLI Commands

### Plugin Management
```bash
# List all plugins
./scripts/wp-operations.sh dev plugin list
./scripts/wp-operations.sh nonprod plugin list

# Check specific plugin status
./scripts/wp-operations.sh dev plugin status wp-qr-trackr
./scripts/wp-operations.sh nonprod plugin status wp-qr-trackr

# Activate plugin
./scripts/wp-operations.sh dev plugin activate wp-qr-trackr
./scripts/wp-operations.sh nonprod plugin activate wp-qr-trackr

# Deactivate plugin
./scripts/wp-operations.sh dev plugin deactivate wp-qr-trackr
./scripts/wp-operations.sh nonprod plugin deactivate wp-qr-trackr
```

### WordPress Core Operations
```bash
# Check if WordPress is installed
./scripts/wp-operations.sh dev core is-installed
./scripts/wp-operations.sh nonprod core is-installed

# Get WordPress version
./scripts/wp-operations.sh dev core version
./scripts/wp-operations.sh nonprod core version

# Update WordPress
./scripts/wp-operations.sh dev core update
./scripts/wp-operations.sh nonprod core update
```

### Options and Settings
```bash
# Get site URL
./scripts/wp-operations.sh dev option get siteurl
./scripts/wp-operations.sh nonprod option get siteurl

# Get home URL
./scripts/wp-operations.sh dev option get home
./scripts/wp-operations.sh nonprod option get home

# Get permalink structure
./scripts/wp-operations.sh dev option get permalink_structure
./scripts/wp-operations.sh nonprod option get permalink_structure

# Set permalink structure
./scripts/wp-operations.sh dev rewrite structure '/%postname%/'
./scripts/wp-operations.sh nonprod rewrite structure '/%postname%/'

# Flush rewrite rules
./scripts/wp-operations.sh dev rewrite flush
./scripts/wp-operations.sh nonprod rewrite flush
```

### User Management
```bash
# List users
./scripts/wp-operations.sh dev user list
./scripts/wp-operations.sh nonprod user list

# Get user info
./scripts/wp-operations.sh dev user get 1
./scripts/wp-operations.sh nonprod user get 1
```

### Database Operations
```bash
# Run database query
./scripts/wp-operations.sh dev db query "SELECT COUNT(*) FROM wp_posts"
./scripts/wp-operations.sh nonprod db query "SELECT COUNT(*) FROM wp_posts"

# Check database tables
./scripts/wp-operations.sh dev db tables
./scripts/wp-operations.sh nonprod db tables
```

## Troubleshooting Commands

### Environment Issues
```bash
# Check if containers are running
./scripts/debug.sh dev container-status
./scripts/debug.sh nonprod container-status

# Check container logs
./scripts/debug.sh dev logs
./scripts/debug.sh nonprod logs

# Perform health check
./scripts/debug.sh dev health
./scripts/debug.sh nonprod health

# Diagnose issues
./scripts/debug.sh dev diagnose
./scripts/debug.sh nonprod diagnose
```

### WordPress Issues
```bash
# Check WordPress installation
./scripts/debug.sh dev wordpress
./scripts/debug.sh nonprod wordpress

# Check plugin status
./scripts/debug.sh dev plugin
./scripts/debug.sh nonprod plugin

# Check database connectivity
./scripts/debug.sh dev database
./scripts/debug.sh nonprod database
```

### Permission Issues
```bash
# Check critical directory permissions
./scripts/debug.sh dev permissions
./scripts/debug.sh nonprod permissions
```

## CI/CD Commands

### Local CI Testing
```bash
# Run full CI workflow locally
docker compose -f docker/docker-compose.ci.yml run --rm ci-runner

# Run validation script
docker compose -f docker/docker-compose.ci.yml run --rm ci-runner bash scripts/validate.sh

# Run validation (no E2E) using Playwright runner harness
make validate

# Run validation + E2E locally
make validate-e2e
```

### Pre-commit Checks
```bash
# Run onboarding check
./scripts/check-onboarding.sh

# Run validation
make validate
```

## Migration from Direct Commands

### Before (❌ Don't do this)
```bash
# Direct Docker commands
docker compose -f docker/docker-compose.dev.yml up -d
docker exec wordpress-dev wp plugin list --path=/var/www/html
docker exec wordpress-dev wp core is-installed --path=/var/www/html
docker compose -f docker/docker-compose.dev.yml logs
docker compose -f docker/docker-compose.playwright.yml run --rm playwright-runner ./vendor/bin/phpcs
```

### After (✅ Do this)
```bash
# Control script usage
./scripts/setup-wordpress.sh dev
./scripts/wp-operations.sh dev plugin list
./scripts/wp-operations.sh dev core is-installed
./scripts/manage-containers.sh logs dev
make validate
```

## Benefits of Using Control Scripts

1. **Consistency**: All developers use the same commands
2. **Error Prevention**: Built-in validation and error handling
3. **Dependency Minimization**: No local tools required
4. **Maintainability**: Centralized command logic
5. **Documentation**: Clear usage examples and help text

## Getting Help

### Script Help
```bash
# Get help for any script
./scripts/wp-operations.sh
./scripts/debug.sh
./scripts/manage-containers.sh --help
```

### Makefile Help
```bash
# Get help for make targets
make wp-dev
make debug-dev
make containers-dev
```

### Environment-Specific Help
```bash
# Get help for specific environment
./scripts/wp-operations.sh dev
./scripts/debug.sh dev
./scripts/manage-containers.sh health dev
```

## Best Practices

1. **Always use control scripts** instead of direct Docker commands
2. **Use Makefile targets** for common operations
3. **Check script help** when unsure about usage
4. **Use environment-specific commands** (dev vs nonprod)
5. **Run validation** before committing changes
6. **Use debug commands** for troubleshooting

## Common Patterns

### Development Workflow
```bash
# 1. Start environment
./scripts/setup-wordpress.sh dev

# 2. Make code changes
# (edit files)

# 3. Validate changes
make validate

# 4. Test in browser
# (visit http://localhost:8080)

# 5. Check plugin status
./scripts/wp-operations.sh dev plugin status wp-qr-trackr
```

### Troubleshooting Workflow
```bash
# 1. Check container status
./scripts/debug.sh dev container-status

# 2. Check WordPress status
./scripts/debug.sh dev wordpress

# 3. Check plugin status
./scripts/debug.sh dev plugin

# 4. Check logs
./scripts/debug.sh dev logs

# 5. Perform health check
./scripts/debug.sh dev health
```

This command reference ensures consistent, reliable development practices while maintaining the dependency minimization principle of the project.
