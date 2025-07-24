# Control Scripts Guide

## Overview

This guide provides comprehensive instructions for using the new control scripts in the WP QR Trackr project. These scripts ensure dependency minimization by providing standardized access to all operations through Docker containers, eliminating the need for local PHP, Composer, Node.js, or other development tools.

## Quick Start

### Prerequisites
- Docker Desktop (latest)
- Git
- Terminal (macOS default terminal or iTerm2)

### First Time Setup
```bash
# Clone the repository
git clone https://github.com/solidsystems/wp-qr-trackr.git
cd wp-qr-trackr

# Check environment setup
bash scripts/check-onboarding.sh

# Start development environment
./scripts/setup-wordpress.sh dev
```

## Control Scripts Overview

### 1. WordPress Operations (`scripts/wp-operations.sh`)

**Purpose**: Standardized WordPress CLI operations through Docker containers.

**Usage**: `./scripts/wp-operations.sh [dev|nonprod] [command] [args...]`

#### Common WordPress Commands

##### Plugin Management
```bash
# List all plugins
./scripts/wp-operations.sh dev plugin list

# Check plugin status
./scripts/wp-operations.sh dev plugin status wp-qr-trackr

# Activate plugin
./scripts/wp-operations.sh dev plugin activate wp-qr-trackr

# Deactivate plugin
./scripts/wp-operations.sh dev plugin deactivate wp-qr-trackr

# Update plugin
./scripts/wp-operations.sh dev plugin update wp-qr-trackr
```

##### WordPress Core Operations
```bash
# Check if WordPress is installed
./scripts/wp-operations.sh dev core is-installed

# Get WordPress version
./scripts/wp-operations.sh dev core version

# Check for updates
./scripts/wp-operations.sh dev core check-update

# Update WordPress
./scripts/wp-operations.sh dev core update
```

##### Options and Settings
```bash
# Get site URL
./scripts/wp-operations.sh dev option get siteurl

# Get permalink structure
./scripts/wp-operations.sh dev option get permalink_structure

# Update site title
./scripts/wp-operations.sh dev option update blogname "New Site Name"

# Get all options
./scripts/wp-operations.sh dev option list
```

##### User Management
```bash
# List users
./scripts/wp-operations.sh dev user list

# Create user
./scripts/wp-operations.sh dev user create username email@example.com --role=administrator

# Update user
./scripts/wp-operations.sh dev user update 1 --user_pass=newpassword
```

##### Database Operations
```bash
# Check database connection
./scripts/wp-operations.sh dev db check

# Optimize database
./scripts/wp-operations.sh dev db optimize

# Export database
./scripts/wp-operations.sh dev db export backup.sql

# Import database
./scripts/wp-operations.sh dev db import backup.sql
```

### 2. Debug Operations (`scripts/debug.sh`)

**Purpose**: Standardized debugging operations through Docker containers.

**Usage**: `./scripts/debug.sh [dev|nonprod|ci] [command]`

#### Health Checks
```bash
# Check overall health
./scripts/debug.sh dev health

# Check nonprod environment health
./scripts/debug.sh nonprod health

# Check CI environment health
./scripts/debug.sh ci health
```

#### WordPress Diagnostics
```bash
# Check WordPress status
./scripts/debug.sh dev wordpress

# Run comprehensive diagnostics
./scripts/debug.sh dev diagnose

# Check plugin status
./scripts/debug.sh dev plugin
```

#### Log Analysis
```bash
# View recent logs
./scripts/debug.sh dev logs

# Follow logs in real-time
./scripts/debug.sh dev logs --follow

# View error logs only
./scripts/debug.sh dev logs --errors
```

#### Container Status
```bash
# Check container status
./scripts/debug.sh dev container-status

# Check resource usage
./scripts/debug.sh dev resources

# Check network connectivity
./scripts/debug.sh dev network
```

### 3. Enhanced Container Management (`scripts/manage-containers.sh`)

**Purpose**: Comprehensive container management with WordPress-specific operations.

**Usage**: `./scripts/manage-containers.sh [command] [dev|nonprod]`

#### WordPress-Specific Commands
```bash
# Install WordPress
./scripts/manage-containers.sh wp-install dev

# Check WordPress status
./scripts/manage-containers.sh wp-status dev

# Reset WordPress installation
./scripts/manage-containers.sh wp-reset dev

# Check plugin status
./scripts/manage-containers.sh wp-plugin-status dev
```

#### Container Operations
```bash
# Start containers
./scripts/manage-containers.sh start dev

# Stop containers
./scripts/manage-containers.sh stop dev

# Restart containers
./scripts/manage-containers.sh restart dev

# Check container health
./scripts/manage-containers.sh health dev

# View container logs
./scripts/manage-containers.sh logs dev

# Check container status
./scripts/manage-containers.sh status dev
```

#### Troubleshooting
```bash
# Diagnose container issues
./scripts/manage-containers.sh diagnose dev

# Clean up containers
./scripts/manage-containers.sh cleanup dev

# Rebuild containers
./scripts/manage-containers.sh rebuild dev
```

## Makefile Integration

The Makefile provides convenient shortcuts for common operations:

### WordPress Operations
```bash
# WordPress operations for dev environment
make wp-dev COMMAND="plugin list"
make wp-dev COMMAND="core is-installed"
make wp-dev COMMAND="option get permalink_structure"

# WordPress operations for nonprod environment
make wp-nonprod COMMAND="plugin list"
make wp-nonprod COMMAND="core is-installed"
```

### Debug Operations
```bash
# Debug operations for dev environment
make debug-dev COMMAND="health"
make debug-dev COMMAND="diagnose"
make debug-dev COMMAND="wordpress"

# Debug operations for nonprod environment
make debug-nonprod COMMAND="health"
make debug-nonprod COMMAND="diagnose"
```

### Container Management
```bash
# Container management for dev environment
make containers-dev COMMAND="start"
make containers-dev COMMAND="health"
make containers-dev COMMAND="wp-status"

# Container management for nonprod environment
make containers-nonprod COMMAND="start"
make containers-nonprod COMMAND="health"
```

## Development Workflow

### Daily Development Workflow
```bash
# 1. Start development environment
./scripts/setup-wordpress.sh dev

# 2. Check environment health
./scripts/debug.sh dev health

# 3. Verify WordPress is running
./scripts/wp-operations.sh dev core is-installed

# 4. Check plugin status
./scripts/wp-operations.sh dev plugin status wp-qr-trackr

# 5. Make your changes...

# 6. Validate code
make validate

# 7. Test in browser
# Visit http://localhost:8080
```

### Troubleshooting Workflow
```bash
# 1. Check overall health
./scripts/debug.sh dev health

# 2. Check WordPress status
./scripts/debug.sh dev wordpress

# 3. Check container status
./scripts/manage-containers.sh status dev

# 4. View logs
./scripts/debug.sh dev logs

# 5. Run diagnostics
./scripts/debug.sh dev diagnose

# 6. If needed, restart containers
./scripts/manage-containers.sh restart dev
```

### Plugin Development Workflow
```bash
# 1. Activate plugin
./scripts/wp-operations.sh dev plugin activate wp-qr-trackr

# 2. Check plugin status
./scripts/wp-operations.sh dev plugin status wp-qr-trackr

# 3. Make changes to plugin files

# 4. Test changes in browser
# Visit http://localhost:8080

# 5. Run validation
make validate

# 6. Check for errors
./scripts/debug.sh dev logs
```

## Environment Management

### Development Environment (Port 8080)
```bash
# Start development environment
./scripts/setup-wordpress.sh dev

# Access URLs
# WordPress: http://localhost:8080
# Admin: http://localhost:8080/wp-admin
# Credentials: trackr/trackr
```

### Nonprod Environment (Port 8081)
```bash
# Start nonprod environment
./scripts/setup-wordpress.sh nonprod

# Access URLs
# WordPress: http://localhost:8081
# Admin: http://localhost:8081/wp-admin
# Credentials: trackr/trackr
```

### Enhanced Setup with Auto-Recovery
```bash
# Enhanced setup with automatic recovery
./scripts/setup-wordpress-enhanced.sh dev
```

## Error Handling and Troubleshooting

### Common Issues and Solutions

#### WordPress Not Installed
```bash
# Check if WordPress is installed
./scripts/wp-operations.sh dev core is-installed

# If not installed, install WordPress
./scripts/manage-containers.sh wp-install dev
```

#### Plugin Not Activated
```bash
# Check plugin status
./scripts/wp-operations.sh dev plugin status wp-qr-trackr

# Activate plugin if needed
./scripts/wp-operations.sh dev plugin activate wp-qr-trackr
```

#### Container Issues
```bash
# Check container health
./scripts/manage-containers.sh health dev

# Restart containers if needed
./scripts/manage-containers.sh restart dev

# Rebuild containers if necessary
./scripts/manage-containers.sh rebuild dev
```

#### Permission Issues
```bash
# Check file permissions
./scripts/debug.sh dev permissions

# Fix permissions if needed
./scripts/manage-containers.sh fix-permissions dev
```

### Getting Help

All control scripts include built-in help:

```bash
# WordPress operations help
./scripts/wp-operations.sh --help

# Debug operations help
./scripts/debug.sh --help

# Container management help
./scripts/manage-containers.sh --help
```

## Best Practices

### 1. Always Use Control Scripts
- ✅ **Use**: `./scripts/wp-operations.sh dev plugin list`
- ❌ **Don't use**: `docker exec wordpress-dev wp plugin list`

### 2. Check Health Regularly
```bash
# Before starting work
./scripts/debug.sh dev health

# After making changes
./scripts/debug.sh dev health
```

### 3. Use Appropriate Environment
- Use `dev` for development work
- Use `nonprod` for testing
- Use `ci` for continuous integration

### 4. Validate Code Changes
```bash
# Always run validation after changes
make validate

# Check for linting errors
make lint

# Run tests
make test
```

### 5. Monitor Logs
```bash
# Check logs regularly
./scripts/debug.sh dev logs

# Follow logs during development
./scripts/debug.sh dev logs --follow
```

## Advanced Usage

### Custom WordPress Commands
```bash
# Run any WordPress CLI command
./scripts/wp-operations.sh dev help

# Search and replace in database
./scripts/wp-operations.sh dev search-replace 'old-url.com' 'new-url.com'

# Generate configuration
./scripts/wp-operations.sh dev config create --dbname=test --dbuser=root --dbpass=password

# Run cron events
./scripts/wp-operations.sh dev cron event run --due-now
```

### Database Operations
```bash
# Backup database
./scripts/wp-operations.sh dev db export backup-$(date +%Y%m%d).sql

# Restore database
./scripts/wp-operations.sh dev db import backup-20231201.sql

# Check database size
./scripts/wp-operations.sh dev db size

# Optimize database
./scripts/wp-operations.sh dev db optimize
```

### Performance Monitoring
```bash
# Check resource usage
./scripts/debug.sh dev resources

# Monitor container performance
./scripts/debug.sh dev performance

# Check memory usage
./scripts/debug.sh dev memory
```

## Integration with IDEs

### VS Code Integration
Add these tasks to your `.vscode/tasks.json`:

```json
{
    "version": "2.0.0",
    "tasks": [
        {
            "label": "Start Dev Environment",
            "type": "shell",
            "command": "./scripts/setup-wordpress.sh",
            "args": ["dev"],
            "group": "build"
        },
        {
            "label": "Check Health",
            "type": "shell",
            "command": "./scripts/debug.sh",
            "args": ["dev", "health"],
            "group": "test"
        },
        {
            "label": "Validate Code",
            "type": "shell",
            "command": "make",
            "args": ["validate"],
            "group": "test"
        }
    ]
}
```

### PhpStorm Integration
Configure external tools in PhpStorm:

1. **Start Environment**: `./scripts/setup-wordpress.sh dev`
2. **Check Health**: `./scripts/debug.sh dev health`
3. **Validate Code**: `make validate`

## Conclusion

The control scripts provide a comprehensive, standardized way to interact with the WP QR Trackr development environment. By using these scripts, you ensure:

- **Dependency Minimization**: No local tools required
- **Consistency**: Same commands across all environments
- **Reliability**: Built-in error handling and validation
- **Maintainability**: Centralized command logic

For more information, refer to:
- `docs/dev-guide/COMMAND_REFERENCE.md` - Complete command reference
- `docs/maintenance/CONTROL_SCRIPT_ENFORCEMENT.md` - Implementation strategy
- `docs/maintenance/CONTROL_SCRIPT_IMPLEMENTATION_SUMMARY.md` - Implementation summary

## Support

If you encounter issues with the control scripts:

1. Check the built-in help: `./scripts/[script-name].sh --help`
2. Review the logs: `./scripts/debug.sh dev logs`
3. Run diagnostics: `./scripts/debug.sh dev diagnose`
4. Check the troubleshooting section in this guide
5. Refer to the project documentation

The control scripts are designed to be self-documenting and include comprehensive error messages to help you resolve issues quickly.
