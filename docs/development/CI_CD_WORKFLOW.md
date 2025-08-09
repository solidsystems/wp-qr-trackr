# CI/CD Workflow Documentation

## Overview

The WP QR Trackr project uses a robust, containerized CI/CD pipeline that ensures consistent testing and deployment across all environments. This document details the workflow, recent fixes, and troubleshooting procedures.

## Architecture

### CI Environment Components

- **CI Runner Container:** Self-contained testing environment with PHP and Composer (Node present, no Playwright in CI).
- **MariaDB Service:** Database for WordPress test suite (ARM64 compatible).
- **WordPress Test Suite:** Automated WordPress environment setup.
- **PHPUnit Integration:** WordPress plugin testing framework (skipped when no tests present).
- **Docker Compose:** Orchestration for local and CI testing.

### Key Files

- `ci.sh` - Main CI execution script
- `docker/Dockerfile.ci` - CI container definition
- `docker/docker-compose.ci.yml` - CI environment orchestration
- `.github/workflows/ci.yml` - GitHub Actions workflow
- `scripts/install-wp-tests.sh` - WordPress test suite installer

## CI Pipeline Steps

### 1. Build CI Image
```bash
# Build the CI container with all dependencies
docker build -f docker/Dockerfile.ci -t ci-runner .
```

### 2. Install Dependencies
- Composer packages (PHP dependencies).
- Node/Yarn are available but JS dev deps (Playwright) are NOT installed in CI.

### 3. Setup WordPress Test Suite
```bash
# Install WordPress test environment
bash scripts/install-wp-tests.sh wpdb wpuser wppass db latest
```

### 4. Database Setup
- MariaDB service starts with health checks
- Test database created automatically
- WordPress test configuration updated

### 5. Run Tests
- PHPCS runs using `config/ci/.phpcs.xml`.
- Warnings do not fail CI; errors do.
- PHPUnit runs only if `config/testing/phpunit.xml.dist` exists and test files are present; otherwise it is skipped.

## Recent Fixes and Improvements

### 1. WordPress Bootstrap Fix

**Problem:** `Call to undefined function add_action()` error in PHPUnit tests.

**Root Cause:** The bootstrap file was calling `add_action()` before WordPress was loaded.

**Solution:** Updated `wp-content/plugins/wp-qr-trackr/tests/phpunit/bootstrap.php`:

```php
// OLD (causing error):
add_action( 'muplugins_loaded', '_manually_load_plugin' );
require $_tests_dir . '/includes/bootstrap.php';

// NEW (fixed):
require $_tests_dir . '/includes/bootstrap.php';
// Load the plugin after WordPress is loaded.
add_action( 'muplugins_loaded', '_manually_load_plugin' );
```

### 2. Database Host Configuration

**Problem:** WordPress test suite couldn't connect to database using `localhost`.

**Root Cause:** CI environment uses Docker services, not localhost.

**Solution:** Updated `ci.sh` to use the `db` service:

```bash
# OLD:
bash scripts/install-wp-tests.sh wpdb wpuser wppass localhost latest

# NEW:
bash scripts/install-wp-tests.sh wpdb wpuser wppass db latest
```

### 3. PHPUnit Detection and Installation

**Problem:** `./vendor/bin/phpunit: No such file or directory` error in CI.

**Root Cause:** Composer dependencies not installed or PHPUnit not found.

**Solution:** Added robust PHPUnit detection in `ci.sh`:

```bash
# Check if PHPUnit exists and run it
if [ -f "./vendor/bin/phpunit" ]; then
    echo "Found PHPUnit at ./vendor/bin/phpunit"
    ./vendor/bin/phpunit
elif [ -f "/usr/src/app/vendor/bin/phpunit" ]; then
    echo "Found PHPUnit at /usr/src/app/vendor/bin/phpunit"
    /usr/src/app/vendor/bin/phpunit
else
    echo "PHPUnit not found. Checking vendor directory..."
    ls -la vendor/bin/ || echo "vendor/bin directory not found"
    echo "Installing Composer dependencies..."
    composer install --no-interaction
    if [ -f "./vendor/bin/phpunit" ]; then
        ./vendor/bin/phpunit
    else
        echo "ERROR: PHPUnit still not found after composer install"
        exit 1
    fi
fi
```

### 4. MariaDB Integration

**Problem:** MySQL image not compatible with ARM64 architecture.

**Root Cause:** MySQL Docker image lacks ARM64 support.

**Solution:** Switched to MariaDB in `docker/docker-compose.ci.yml`:

```yaml
services:
  db:
    image: mariadb:10.5  # ARM64 compatible
    environment:
      MYSQL_DATABASE: wpdb
      MYSQL_USER: wpuser
      MYSQL_PASSWORD: wppass
      MYSQL_ROOT_PASSWORD: root
    ports:
      - 3306:3306
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "--silent"]
      interval: 10s
      timeout: 5s
      retries: 3
```

### 5. WordPress Test Suite Integration

**Problem:** CI script wasn't setting up WordPress test environment.

**Root Cause:** Missing WordPress test suite installation step.

**Solution:** Added WordPress test suite installation to `ci.sh`:

```bash
# Install WordPress test suite
echo "Installing WordPress test suite..."
bash scripts/install-wp-tests.sh wpdb wpuser wppass db latest
```

## Local Testing

### Test Complete CI Workflow

```bash
# Run the full CI workflow locally
docker compose -f docker/docker-compose.ci.yml run --rm ci-runner
```

### Test Individual Components

```bash
# Test WordPress test suite installation
docker compose -f docker/docker-compose.ci.yml run --rm ci-runner bash -c "bash scripts/install-wp-tests.sh wpdb wpuser wppass db latest"

# Test PHPUnit after installation
docker compose -f docker/docker-compose.ci.yml run --rm ci-runner bash -c "bash scripts/install-wp-tests.sh wpdb wpuser wppass db latest && ./vendor/bin/phpunit"

# Check WordPress test files
docker compose -f docker/docker-compose.ci.yml run --rm ci-runner bash -c "ls -la /tmp/wordpress-tests-lib/includes/"
```

### Debug CI Environment

```bash
# Access CI container shell
docker compose -f docker/docker-compose.ci.yml run --rm --entrypoint bash ci-runner

# Check database connection
docker compose -f docker/docker-compose.ci.yml run --rm ci-runner bash -c "mysqladmin ping --user=wpuser --password=wppass --host=db"
```

## Troubleshooting

### Common Issues and Solutions

#### 1. PHPUnit Not Found

**Symptoms:** `./vendor/bin/phpunit: No such file or directory`

**Solutions:**
- Check if Composer dependencies are installed: `ls -la vendor/bin/`
- Reinstall dependencies: `composer install --no-interaction`
- Verify PHPUnit location: `find . -name "phpunit"`

#### 2. Database Connection Failed

**Symptoms:** `Can't connect to server on 'localhost'`

**Solutions:**
- Verify MariaDB service is running: `docker compose -f docker/docker-compose.ci.yml ps`
- Check database host configuration in `ci.sh`
- Ensure health checks pass: `docker compose -f docker/docker-compose.ci.yml logs db`

#### 3. WordPress Test Suite Not Installed

**Symptoms:** `Failed opening required '/tmp/wordpress-tests-lib/includes/functions.php'`

**Solutions:**
- Run WordPress test suite installation: `bash scripts/install-wp-tests.sh wpdb wpuser wppass db latest`
- Check if test files exist: `ls -la /tmp/wordpress-tests-lib/includes/`
- Verify SVN is available in container

#### 4. Bootstrap File Errors

**Symptoms:** `Call to undefined function add_action()`

**Solutions:**
- Ensure WordPress is loaded before calling WordPress functions
- Check bootstrap file order in `tests/phpunit/bootstrap.php`
- Verify WordPress test configuration is correct

#### 5. Container Build Issues

**Symptoms:** Docker build failures or missing files

**Solutions:**
- Check file paths in Dockerfile: `docker build -f docker/Dockerfile.ci .`
- Verify all required files are in repository
- Check Docker context and .dockerignore settings

### Debug Commands

```bash
# Check CI container contents
docker compose -f docker/docker-compose.ci.yml run --rm ci-runner ls -la

# Verify dependencies
docker compose -f docker/docker-compose.ci.yml run --rm ci-runner bash -c "which composer && which yarn && which php"

# Check WordPress test environment
docker compose -f docker/docker-compose.ci.yml run --rm ci-runner bash -c "ls -la /tmp/wordpress*"

# Test database connectivity
docker compose -f docker/docker-compose.ci.yml run --rm ci-runner bash -c "mysqladmin ping --user=wpuser --password=wppass --host=db"
```

## Best Practices

### 1. Always Test Locally First

Before pushing changes that affect CI:

```bash
# Test the complete workflow
docker compose -f docker/docker-compose.ci.yml run --rm ci-runner

# If successful, push to trigger GitHub Actions
git push
```

### 2. Use Descriptive Commit Messages

Include the type of fix in commit messages:

```bash
git commit -m "ci: fix WordPress bootstrap file to load WordPress before calling add_action"
git commit -m "ci: add robust PHPUnit detection and fallback installation"
git commit -m "ci: switch from MySQL to MariaDB for ARM64 compatibility"
```

### 3. Monitor CI Logs

When CI fails:
1. Check the specific error message
2. Test the failing step locally
3. Apply the fix
4. Test locally again
5. Push and monitor

### 4. Keep Dependencies Updated

Regularly update:
- Docker base images
- Composer dependencies
- Yarn packages
- WordPress test suite version

## Future Improvements

### Planned Enhancements

1. **Re-enable Playwright Tests locally only:** E2E testing remains local by project policy.
2. **Performance Optimization:** Cache dependencies and test artifacts.
3. **Multi-Platform Testing:** Test on different architectures.
4. **Security Scanning:** Add vulnerability scanning to CI pipeline.

### Monitoring and Metrics

- Track CI build times
- Monitor test coverage
- Alert on CI failures
- Track dependency updates

## Related Documentation

- [Development Setup](GETTING_STARTED.md)
- [Troubleshooting Guide](../TROUBLESHOOTING.md)
- [Architecture Overview](../ARCHITECTURE.md)
- [Contributing Guidelines](../CONTRIBUTING.md)
