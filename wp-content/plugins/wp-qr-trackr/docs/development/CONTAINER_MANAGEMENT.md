# Container Management Guide

## Overview

The WP QR Trackr project now includes enhanced container management capabilities that can automatically detect issues with Docker containers and redeploy them when needed. This ensures robust development and testing environments.

## Available Scripts

### 1. Enhanced WordPress Setup (`setup-wordpress-enhanced.sh`)

**Purpose**: Enhanced version of the original setup script with automatic issue detection and recovery.

**Features**:
- Automatic health checks for containers and WordPress
- Auto-restart on container failures
- Comprehensive logging and diagnostics
- Automatic WordPress installation and plugin activation

**Usage**:
```bash
# Start development environment with auto-recovery
./scripts/setup-wordpress-enhanced.sh dev

# Start nonprod environment with auto-recovery
./scripts/setup-wordpress-enhanced.sh nonprod

# Start playwright environment (uses original logic)
./scripts/setup-wordpress-enhanced.sh playwright
```

**Benefits**:
- **Automatic Recovery**: Detects and fixes common container issues
- **Health Monitoring**: Checks container status, WordPress accessibility, and database connectivity
- **Retry Logic**: Attempts recovery up to 3 times before failing
- **Detailed Logging**: Provides comprehensive logs for troubleshooting

### 2. Container Management (`manage-containers.sh`)

**Purpose**: Comprehensive container management system with monitoring and diagnostic capabilities.

**Commands**:

#### Basic Operations
```bash
# Start containers
./scripts/manage-containers.sh start dev
./scripts/manage-containers.sh start nonprod

# Stop containers
./scripts/manage-containers.sh stop dev
./scripts/manage-containers.sh stop nonprod

# Restart containers
./scripts/manage-containers.sh restart dev
./scripts/manage-containers.sh restart nonprod
```

#### Health and Monitoring
```bash
# Perform comprehensive health check
./scripts/manage-containers.sh health dev
./scripts/manage-containers.sh health nonprod

# Start continuous monitoring
./scripts/manage-containers.sh monitor dev
./scripts/manage-containers.sh monitor nonprod --interval 30

# Diagnose container issues
./scripts/manage-containers.sh diagnose dev
./scripts/manage-containers.sh diagnose nonprod
```

#### Advanced Operations
```bash
# Redeploy containers (rebuild and restart)
./scripts/manage-containers.sh redeploy dev
./scripts/manage-containers.sh redeploy nonprod

# Show container logs
./scripts/manage-containers.sh logs dev
./scripts/manage-containers.sh logs nonprod

# Show container status
./scripts/manage-containers.sh status dev
./scripts/manage-containers.sh status nonprod
```

## Health Check Features

### Container Health Monitoring

The system performs comprehensive health checks on:

1. **Container Status**: Verifies containers are running and healthy
2. **WordPress Accessibility**: Checks if WordPress is accessible on the expected port
3. **Database Connectivity**: Tests database connections from WordPress containers
4. **WordPress Installation**: Verifies WordPress core files and plugin installation
5. **Plugin Activation**: Ensures the WP QR Trackr plugin is activated

### Automatic Recovery Actions

When issues are detected, the system automatically:

1. **Container Restart**: Restarts failed containers
2. **WordPress Reinstallation**: Reinstalls WordPress if core files are missing
3. **Plugin Reactivation**: Reactivates the plugin if it's deactivated
4. **Database Reconnection**: Attempts to restore database connectivity
5. **Log Analysis**: Provides detailed logs for manual intervention if needed

## Monitoring and Logging

### Continuous Monitoring

The monitoring system runs continuously and:

- Checks container health every 60 seconds (configurable)
- Automatically restarts containers on failure
- Logs all activities to `/tmp/wp-qr-trackr-container-manager.log`
- Provides real-time status updates

### Log Files

- **Container Manager Log**: `/tmp/wp-qr-trackr-container-manager.log`
- **Docker Compose Logs**: Available via `docker compose logs`
- **WordPress Debug Log**: Available in container at `/var/www/html/wp-content/debug.log`

### Log Levels

- **INFO**: Normal operations and successful checks
- **WARN**: Issues detected but recoverable
- **ERROR**: Critical failures requiring manual intervention

## Troubleshooting

### Common Issues and Solutions

#### 1. Container Won't Start

**Symptoms**: Container exits immediately or fails to start
**Solutions**:
```bash
# Check container logs
./scripts/manage-containers.sh logs dev

# Diagnose issues
./scripts/manage-containers.sh diagnose dev

# Redeploy containers
./scripts/manage-containers.sh redeploy dev
```

#### 2. WordPress Not Accessible

**Symptoms**: Cannot access WordPress on expected port
**Solutions**:
```bash
# Check container health
./scripts/manage-containers.sh health dev

# Restart containers
./scripts/manage-containers.sh restart dev

# Check WordPress installation
docker exec wordpress-dev wp core is-installed --path=/var/www/html
```

#### 3. Database Connection Issues

**Symptoms**: WordPress shows database connection errors
**Solutions**:
```bash
# Check database container
docker ps | grep db-dev

# Test database connectivity
docker exec wordpress-dev mysql -hdb-dev -uwpuser -pwppass wpdb -e "SELECT 1;"

# Restart database container
docker compose -f docker/docker-compose.dev.yml restart db-dev
```

#### 4. Plugin Not Working

**Symptoms**: WP QR Trackr plugin not functioning correctly
**Solutions**:
```bash
# Check plugin status
docker exec wordpress-dev wp plugin status wp-qr-trackr --path=/var/www/html

# Reactivate plugin
docker exec wordpress-dev wp plugin activate wp-qr-trackr --path=/var/www/html

# Check plugin files
docker exec wordpress-dev ls -la /var/www/html/wp-content/plugins/wp-qr-trackr
```

### Manual Recovery Steps

If automatic recovery fails:

1. **Stop all containers**:
   ```bash
   ./scripts/manage-containers.sh stop dev
   ```

2. **Clean up Docker resources**:
   ```bash
   docker system prune -f
   docker volume prune -f
   ```

3. **Redeploy from scratch**:
   ```bash
   ./scripts/manage-containers.sh redeploy dev
   ```

4. **Check logs for specific errors**:
   ```bash
   ./scripts/manage-containers.sh logs dev
   ```

## Best Practices

### Development Workflow

1. **Use Enhanced Setup**: Always use `setup-wordpress-enhanced.sh` for dev and nonprod environments
2. **Monitor Health**: Run health checks before starting development work
3. **Check Logs**: Review logs when issues occur
4. **Use Monitoring**: Start continuous monitoring for long development sessions

### Environment Management

1. **Separate Environments**: Keep dev and nonprod environments separate
2. **Regular Cleanup**: Periodically clean up Docker resources
3. **Backup Data**: Backup important data before major changes
4. **Version Control**: Keep Docker Compose files in version control

### Performance Optimization

1. **Resource Limits**: Monitor container resource usage
2. **Image Cleanup**: Regularly clean up unused Docker images
3. **Volume Management**: Use named volumes for persistent data
4. **Network Optimization**: Use Docker networks for container communication

## Integration with Existing Workflow

### Migration from Original Scripts

The enhanced scripts are designed to be drop-in replacements:

```bash
# Old way
./scripts/setup-wordpress.sh dev

# New way (recommended)
./scripts/setup-wordpress-enhanced.sh dev
```

### CI/CD Integration

The container management scripts can be integrated into CI/CD pipelines:

```yaml
# Example GitHub Actions step
- name: Setup Development Environment
  run: |
    ./scripts/setup-wordpress-enhanced.sh dev
    ./scripts/manage-containers.sh health dev
```

### Development Team Usage

For development teams:

1. **Onboarding**: New developers use enhanced setup scripts
2. **Daily Work**: Use health checks before starting work
3. **Troubleshooting**: Use diagnostic commands when issues arise
4. **Monitoring**: Use continuous monitoring for critical development sessions

## Configuration

### Environment Variables

The scripts use the following environment variables:

- `MAX_RETRY_ATTEMPTS`: Number of recovery attempts (default: 3)
- `HEALTH_CHECK_TIMEOUT`: Timeout for health checks (default: 30 seconds)
- `LOG_FILE`: Path to log file (default: `/tmp/wp-qr-trackr-container-manager.log`)

### Customization

You can customize the behavior by modifying:

- **Retry Attempts**: Change `MAX_RETRY_ATTEMPTS` in the scripts
- **Check Intervals**: Modify monitoring intervals
- **Health Checks**: Add custom health check functions
- **Recovery Actions**: Customize automatic recovery procedures

## Support and Maintenance

### Regular Maintenance

1. **Update Scripts**: Keep scripts updated with latest improvements
2. **Monitor Logs**: Regularly review log files for patterns
3. **Test Recovery**: Periodically test recovery procedures
4. **Update Documentation**: Keep this guide updated

### Getting Help

If you encounter issues:

1. **Check Logs**: Review all relevant log files
2. **Run Diagnostics**: Use diagnostic commands
3. **Check Documentation**: Review this guide and other project docs
4. **Create Issues**: Report bugs with detailed information

### Contributing

To improve the container management system:

1. **Test Changes**: Test all changes thoroughly
2. **Update Documentation**: Keep documentation current
3. **Follow Standards**: Follow project coding standards
4. **Add Tests**: Add tests for new features

## Conclusion

The enhanced container management system provides robust, automated management of development and testing environments. By using these tools, developers can focus on building features rather than troubleshooting environment issues.

For questions or contributions, please refer to the project documentation or create an issue in the repository. 