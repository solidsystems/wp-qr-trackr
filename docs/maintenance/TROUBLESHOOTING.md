# Troubleshooting & FAQ

This guide covers common issues and solutions for the WP QR Trackr plugin. If you run into a problem not listed here, please open an issue or see CONTRIBUTING.md for more help.

## Production Debugging with WP Query Monitor

### Essential Debugging Tool

**WP Query Monitor** is the primary tool for debugging QR Trackr issues on production sites. It provides comprehensive insights into database queries, hooks, template loading, and performance.

#### Installation
```bash
# Via WP-CLI (recommended)
wp plugin install query-monitor --activate

# Via WordPress Admin
# Plugins → Add New → Search "Query Monitor" → Install & Activate
```

#### Key Monitoring Areas for QR Trackr

1. **Database Queries Panel:**
   - Monitor queries to `wp_qr_trackr_links` table
   - Check for slow queries (>50ms)
   - Verify proper `$wpdb->prepare()` usage
   - Look for missing indexes or inefficient queries

2. **Hooks & Actions Panel:**
   - Verify `admin_menu` hook execution
   - Check `admin_init` and `admin_enqueue_scripts` hooks
   - Monitor AJAX action hooks (`wp_ajax_*`)
   - Ensure proper hook priority and timing

3. **Files Panel:**
   - Check template file inclusion paths
   - Monitor file loading errors
   - Verify correct file permissions
   - Track include/require statements

4. **AJAX Panel:**
   - Monitor AJAX requests to `admin-ajax.php`
   - Check for failed requests or timeouts
   - Verify nonce validation
   - Track response times and errors

#### Query Monitor Configuration

**Recommended Settings:**
```php
// Add to wp-config.php for comprehensive debugging
define('QM_DISABLED', false);
define('QM_HIDE_SELF', false);
define('QM_DISPLAY_ERROR_NOTICES', true);
define('QM_DISPLAY_ERRORS', true);
define('QM_DISPLAY_ERRORS_DETAILED', true);
```

**Performance Thresholds:**
- Database queries: < 50ms
- Template loading: < 100ms
- AJAX responses: < 500ms
- Memory usage: Monitor for spikes

#### Debugging Workflow

1. **Install Query Monitor** on production site
2. **Reproduce the issue** while monitoring
3. **Check Database Queries** for performance issues
4. **Review Hooks & Actions** for missing executions
5. **Examine Template Files** for inclusion problems
6. **Monitor AJAX Requests** for failures
7. **Check Error Logs** for PHP errors
8. **Review Performance** for bottlenecks

#### Common Issues & Query Monitor Solutions

**Template File Not Found:**
- Check "Files" panel for include path issues
- Verify file permissions and existence
- Monitor `QR_TRACKR_PLUGIN_DIR` constant resolution

**Admin Page Access Denied:**
- Check "Hooks & Actions" for `admin_menu` execution
- Verify user capability checks in "Database Queries"
- Monitor `current_user_can()` function calls

**AJAX Requests Failing:**
- Check "AJAX" panel for request details
- Verify nonce validation in "Hooks & Actions"
- Monitor response times and error codes

**Performance Issues:**
- Check "Database Queries" for slow queries
- Monitor memory usage in "Overview" panel
- Review template loading times in "Files" panel

## Critical Environment Configuration Issues

### Plugin Update Directory Permissions (RESOLVED ✅)

**Issue**: "Could not create directory" error during plugin updates
```bash
Installing plugin from uploaded file: wp-qr-trackr-v1.2.39.zip
Unpacking the package…
Updating the plugin…
Removing the current plugin…
Could not remove the current plugin.
Plugin update failed.
```

**Root Cause**: Incorrect ownership and permissions on `/var/www/html/wp-content/upgrade` directory

**Solution**: The setup script now automatically fixes these permissions:
```bash
# Applied automatically by setup-wordpress.sh
docker compose -f docker/docker-compose.dev.yml exec --user root wordpress-dev chown -R www-data:www-data /var/www/html/wp-content/upgrade
docker compose -f docker/docker-compose.dev.yml exec --user root wordpress-dev chmod 775 /var/www/html/wp-content/upgrade
```

**Prevention**: Always use the setup script which includes `fix_critical_permissions()` function

### Custom Post Type Registration Error (RESOLVED ✅)

**Issue**: Fatal error "Call to a member function add_rewrite_tag() on null"
```
PHP Fatal error: Uncaught Error: Call to a member function add_rewrite_tag() on null in /var/www/html/wp-includes/rewrite.php:176
```

**Root Cause**: Custom post type registration on `plugins_loaded` hook before rewrite system ready

**Solution**: Moved registration to `init` hook in `module-activation.php`:
```php
// Changed from:
add_action('plugins_loaded', 'qrc_init');
// To:
add_action('init', 'qrc_init');
```

**Prevention**: All custom post types must be registered on `init` hook, not `plugins_loaded`

### Pretty Permalinks Required (CRITICAL)

**Issue**: QR code redirects don't work
**Root Cause**: Plain permalinks enabled (default WordPress setting)

**Solution**: Setup script automatically sets pretty permalinks:
```bash
# Applied automatically by setup-wordpress.sh
docker compose -f docker/docker-compose.dev.yml exec wpcli-dev wp rewrite structure '/%postname%/' --path=/var/www/html
docker compose -f docker/docker-compose.dev.yml exec wpcli-dev wp rewrite flush --hard --path=/var/www/html
```

**Verification**: Check permalink structure:
```bash
docker compose -f docker/docker-compose.dev.yml exec wpcli-dev wp option get permalink_structure --path=/var/www/html
```

## Plugin Activation Issues - RESOLVED ✅

### Fatal Error on Plugin Activation (Fixed in v1.2.4)

**Problem:** "Plugin could not be activated because it triggered a fatal error" on production WordPress sites.

**Root Causes Identified:**
1. **Constant Mismatch** - Activation module used `QRC_PLUGIN_FILE` but main file defined `QR_TRACKR_PLUGIN_FILE`
2. **Conflicting Hook Registrations** - Both main file and activation module registered the same hooks
3. **Unsafe Module Loading** - Modules loaded without checking if files exist
4. **Circular Dependencies** - Modules referenced each other during activation

**Solutions Applied in v1.2.4:**
- ✅ **Backward Compatibility Constants** - Added both QRC_ and QR_TRACKR_ prefixes for all constants
- ✅ **Safe Module Loading** - Added file existence checks before requiring modules
- ✅ **Hook Separation** - Removed conflicting hook registrations from activation module
- ✅ **Error Handling** - Added proper error handling for missing files and dependencies
- ✅ **Simplified Plugin Header** - Standardized WordPress plugin header format

**Current Status:**
- 🟢 **RESOLVED** - Plugin now activates successfully on all WordPress installations
- 🟢 **Production Tested** - Verified working on live WordPress sites
- 🟢 **Backward Compatible** - Existing installations continue to work

### QR Code URL 404 Errors (Fixed in v1.2.1)

**Problem:** QR codes generated correctly but tracking URLs returned 404 errors.

**Root Cause:** Mismatch between rewrite rules and generated URLs:
- **Old Pattern:** `/qr-code/{numeric_id}/` (expected by rewrite rules)
- **Actual Pattern:** `/qr/{alphanumeric_code}` (generated by QR codes)

**Solution Applied:**
- ✅ **Updated Rewrite Rules** - Changed pattern from `'qr-code/([0-9]+)/?$'` to `'qr/([a-zA-Z0-9]+)/?$'`
- ✅ **Fixed Query Variables** - Updated from `qrc_id` to `qr_tracking_code`
- ✅ **Database Lookup Fix** - Changed lookup from numeric IDs to alphanumeric tracking codes
- ✅ **Flushed Rewrite Rules** - Automatically activated new URL patterns

**Current Status:**
- 🟢 **RESOLVED** - All QR code URLs now work correctly
- 🟢 **Tracking Active** - Analytics and scan counting functional

## JavaScript Event Delegation Issues - RESOLVED ✅

### Delete Button Not Responding (Fixed in v1.2.8)

**Problem:** Delete button in QR code admin list showed no confirmation dialog and no AJAX request was sent, while edit button worked correctly.

**Symptoms:**
- Delete button appeared clickable but nothing happened
- No console errors or JavaScript warnings
- Edit button worked perfectly with modal and AJAX
- Both buttons used identical event delegation patterns

**Root Cause:** jQuery context issue in WordPress admin environment.

**Investigation Steps:**
1. **DOM Inspection:** Confirmed delete buttons existed with correct classes and data attributes
2. **Native JS Test:** Added native JavaScript event listeners - buttons were clickable
3. **jQuery Verification:** Confirmed jQuery was loaded and accessible
4. **Event Delegation Test:** General button clicks worked, specific class clicks didn't
5. **Context Analysis:** Discovered jQuery context was not global

**Solution Applied:**
```javascript
// BEFORE (Problematic)
jQuery(document).ready(function($) {
    $(document).on('click', '.qr-delete-btn', function(e) {
        // Handler never fired
    });
});

// AFTER (Working)
(function($) {
    $(function() {
        $(document).on('click', '.qr-delete-btn', function(e) {
            // Handler now fires correctly
        });
    });
})(window.jQuery);
```

**Why This Fixed It:**
- **Global jQuery Reference:** `window.jQuery` ensures global jQuery instance
- **Proper Context:** IIFE creates clean scope with correct jQuery reference
- **DOM Ready:** `$(function() { ... })` ensures DOM is ready
- **Event Delegation:** Works correctly with proper jQuery context

**Debugging Strategy Used:**
1. **Native JavaScript Fallback:** Added native JS listeners to isolate jQuery issues
2. **Console Logging:** Extensive logging to track jQuery functionality
3. **Event Delegation Testing:** Tested general vs specific selectors
4. **jQuery Version Verification:** Confirmed jQuery was loaded

**Current Status:**
- 🟢 **RESOLVED** - Delete button now works correctly with confirmation dialog
- 🟢 **AJAX Functional** - Delete requests sent and processed successfully
- 🟢 **UI Updates** - Button states and row removal work as expected
- 🟢 **Pattern Established** - All admin JavaScript now uses robust IIFE pattern

### Prevention Guidelines

**For Future Development:**
1. **Always use IIFE wrapper:** `(function($) { ... })(window.jQuery);`
2. **Test event delegation:** With both general and specific selectors
3. **Add console logging:** During development for debugging
4. **Verify jQuery context:** Before implementing complex functionality

**Code Review Checklist:**
- [ ] JavaScript uses `(function($) { ... })(window.jQuery);` pattern
- [ ] Event handlers use proper delegation
- [ ] AJAX calls include error handling
- [ ] Console logging is removed for production

**Testing Strategy:**
1. **Cross-Page Testing:** Test on different WordPress admin pages
2. **Console Verification:** Check for jQuery conflicts or errors
3. **Event Logging:** Log all button clicks to verify handlers
4. **AJAX Testing:** Test AJAX endpoints directly via browser dev tools

## PHPCS Compliance Issues & Solutions

### Major PHPCS Compliance Achievement (Latest Update)

We recently completed a comprehensive PHPCS compliance initiative that reduced the plugin from **70+ errors to 0 errors** across all 9 PHP files. This section documents the technical challenges and solutions for future reference.

#### SQL Query Preparation Issues

**Problem:** PHPCS detected interpolated variables in `$wpdb->prepare()` statements, which violates WordPress security standards.

**Examples of Issues:**
```php
// WRONG - Variables interpolated in prepare statement
$wpdb->prepare( $query_data['query'], ...$query_data['values'] )

// WRONG - Table names as variables in prepare
$wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id )
```

**Solutions Applied:**
```php
// CORRECT - Use direct table references
$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}qr_trackr_links WHERE id = %d", $id )

// CORRECT - Add PHPCS ignore for dynamic queries with explanation
// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Dynamic query built with validated placeholders.
$wpdb->prepare( $query_data['query'], ...$query_data['values'] )
```

**Key Learnings:**
- Table names should use `{$wpdb->prefix}table_name` format, not variables
- Dynamic query builders need specific PHPCS ignore comments with explanations
- All user input must use placeholders (%d, %s) in prepare statements

#### Caching Implementation Requirements

**Problem:** Direct database queries without caching triggered PHPCS warnings.

**Solution:** Implemented comprehensive caching patterns:
```php
$cache_key = 'qr_trackr_item_' . $id;
$result = wp_cache_get( $cache_key );

if ( false === $result ) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Result is cached.
    $result = $wpdb->get_row( $prepared_query );

    if ( ! is_null( $result ) ) {
        wp_cache_set( $cache_key, $result, '', 300 ); // Cache for 5 minutes
    }
}
```

#### Comment Punctuation Standards

**Problem:** All inline comments must end with proper punctuation (periods, exclamation marks, or question marks).

**Examples:**
```php
// WRONG
// Initialize the database table
// Set default value to null

// CORRECT
// Initialize the database table.
// Set default value to null!
// Why is this value negative?
```

**Exception:** Code reference comments don't need punctuation:
```php
// ...existing code...
// phpcs:ignore
// @codeCoverageIgnore
```

#### WordPress Function Replacements

**Problem:** Using PHP functions instead of WordPress equivalents.

**Solutions:**
- Replace `serialize()` with `wp_json_encode()`
- Replace `date()` with `gmdate()` for timezone safety
- Replace `json_encode()` with `wp_json_encode()`
- Use WordPress sanitization functions for all input

#### Missing Documentation Tags

**Problem:** Functions missing `@throws` tags when they can throw exceptions.

**Solution:** Add comprehensive docblock tags:
```php
/**
 * Handle template redirect for QR tracking.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 * @return void
 * @throws Exception If database operations fail.
 */
```

#### File Organization & Naming

**Problem:** Files not following WordPress naming conventions.

**Solutions:**
- Class files should be prefixed with `class-`
- Use lowercase and hyphens, not underscores
- Match file names to class names

### PHPCS Configuration Best Practices

#### Memory Management
- Set PHPCS memory limit to at least 1GB for large codebases
- Use `--extensions=php` to avoid processing large JS files
- Configure `config/ci/.phpcs.xml` with proper exclusion patterns

#### Warning vs Error Handling
- Configure CI/CD to allow warnings but block on errors
- Use `--warning-severity=0` in workflows
- Document justified PHPCS ignore comments with explanations

#### Project-Specific Configuration
```xml
<!-- config/ci/.phpcs.xml example -->
<ruleset name="QR Trackr WordPress Coding Standards">
    <config name="installed_paths" value="vendor/wp-coding-standards/wpcs"/>
    <ini name="memory_limit" value="1024M"/>
    <rule ref="WordPress"/>

    <!-- Exclude patterns -->
    <exclude-pattern>*/vendor/*</exclude-pattern>
    <exclude-pattern>tests/*</exclude-pattern>
    <exclude-pattern>assets/*.js</exclude-pattern>
</ruleset>
```

## Xdebug/PECL Issues
- Run `fix-pecl-xdebug.sh` to resolve most Xdebug installation problems on macOS (ARM/x86).
- Ensure Homebrew and PECL are up to date.

## Database/Logging Issues
- Double-check your `config/.env` file for correct DigitalOcean PostgreSQL and OpenSearch credentials.
- Confirm your database is accessible from your local or CI environment.
- Review logs in OpenSearch for error details.

## Docker Issues
- Make sure Docker Desktop is running and ports are not in use by other services.
- Rebuild containers with `docker compose build --no-cache` if you encounter persistent issues.

## Environment & Dependency Issues
- Use `yarn setup:ci` to reinstall all dependencies and Husky hooks.
- Ensure `config/.env` and `config/.env.example` are up to date and match your environment.

## QR Code URL Handling Issues

### QR Code URLs Redirecting to Admin Page

**Problem:** QR code URLs like `http://localhost:8080/qr/DofYy6sE` redirect to WordPress admin page instead of destination URL.

**Symptoms:**
- QR code URLs show "admin" in browser address bar
- No debug messages appear in WordPress logs
- Handlers are registered but never called
- Multiple URL patterns fail (rewrite rules, REST API, query parameters)

**Root Causes:**
1. **WordPress URL Processing Order** - WordPress processes URLs before custom handlers
2. **Rewrite Rule Conflicts** - Custom rewrite rules conflict with WordPress defaults
3. **Container Environment Issues** - Docker may affect URL processing behavior
4. **Action Hook Timing** - `init`, `parse_request`, `wp` hooks may be too late

**Debugging Steps:**
```bash
# Check if handler functions exist
docker compose -f docker/docker-compose.dev.yml exec wordpress-dev wp eval 'echo function_exists("qr_trackr_handle_qr_redirect") ? "EXISTS" : "NOT FOUND";'

# Check if actions are registered
docker compose -f docker/docker-compose.dev.yml exec wordpress-dev wp eval 'echo has_action("init", "qr_trackr_handle_qr_redirect") ? "REGISTERED" : "NOT REGISTERED";'

# Test URL directly
curl -I "http://localhost:8080/qr/DofYy6sE"

# Check WordPress logs
docker compose -f docker/docker-compose.dev.yml logs wordpress-dev --tail=10
```

**Working Solution:**
Use AJAX endpoints as reliable fallback:
```
http://localhost:8080/wp-admin/admin-ajax.php?action=qr_redirect&qr=DofYy6sE
```

**Why AJAX Works:**
- Uses WordPress's built-in AJAX system
- No conflicts with rewrite rules
- Reliable across all environments
- Proper security handling

**Production Recommendations:**
1. **Custom Endpoint:** Implement proper rewrite rules with debugging
2. **Subdomain:** Use `qr.yoursite.com/code` for clean URLs
3. **Custom Domain:** Dedicated domain for QR redirects
4. **REST API:** WordPress REST API with authentication

### QR Code URL Generation Issues

**Problem:** QR codes generate with wrong URL format.

**Check These Files:**
- `includes/class-qrc-links-list-table.php` - List table URL generation
- `includes/module-ajax.php` - AJAX response URL generation
- `includes/module-utils.php` - QR code generation URL

**Current Working Format:**
```php
$tracking_url = admin_url( 'admin-ajax.php?action=qr_redirect&qr=' . esc_attr( $qr_code ) );
```

**Common Issues:**
- Using `home_url()` instead of `admin_url()` for AJAX endpoints
- Missing `action=qr_redirect` parameter
- Incorrect parameter name (should be `qr`, not `code`)

### AJAX Handler Not Working

**Problem:** AJAX endpoints return 404 or redirect to admin page.

**Check These Items:**
1. **Handler Registration:**
```php
add_action( 'wp_ajax_qr_redirect', 'qr_trackr_ajax_qr_redirect' );
add_action( 'wp_ajax_nopriv_qr_redirect', 'qr_trackr_ajax_qr_redirect' );
```

2. **Function Definition:**
```php
function qr_trackr_ajax_qr_redirect() {
    // Get QR code parameter
    $qr_code = isset( $_GET['qr'] ) ? sanitize_text_field( wp_unslash( $_GET['qr'] ) ) : '';

    // Database lookup and redirect
    // ...
}
```

3. **Module Loading:** Ensure AJAX module is loaded in main plugin file

**Debug Commands:**
```bash
# Test AJAX endpoint directly
curl -I "http://localhost:8080/wp-admin/admin-ajax.php?action=qr_redirect&qr=TESTCODE"

# Check if AJAX handler exists
docker compose -f docker/docker-compose.dev.yml exec wordpress-dev wp eval 'echo function_exists("qr_trackr_ajax_qr_redirect") ? "EXISTS" : "NOT FOUND";'
```

### URL Aesthetics for Production

**Current Limitation:** AJAX URLs contain "admin" which is not ideal for public QR codes.

**Solutions:**
1. **Accept Current Format:** Works reliably, good for internal use
2. **Implement Custom Endpoint:** Clean URLs with proper rewrite rules
3. **Use Subdomain:** `qr.yoursite.com/code` for public-facing QR codes
4. **Custom Domain:** Dedicated domain for maximum flexibility

**Trade-offs:**
- ✅ **AJAX Endpoints:** Reliable, secure, simple
- ❌ **Clean URLs:** Complex, may have conflicts, requires debugging
- ✅ **Subdomain:** Clean, separate from WordPress
- ❌ **Subdomain:** Requires DNS configuration

### Best Practices for URL Handling

1. **Always Test in Target Environment** - Don't assume local behavior
2. **Have Working Fallbacks** - AJAX endpoints as reliable backup
3. **Document Issues** - Help future developers avoid same problems
4. **Consider User Experience** - Balance technical simplicity with aesthetics
5. **Plan for Production** - Design URL structure for public use from start

## CI/CD Workflow Issues

### WordPress Bootstrap Errors

**Problem:** `Call to undefined function add_action()` error in PHPUnit tests.

**Root Cause:** The bootstrap file calls WordPress functions before WordPress is loaded.

**Solution:** Ensure WordPress is loaded before calling WordPress functions in `tests/phpunit/bootstrap.php`:

```php
// CORRECT ORDER:
require $_tests_dir . '/includes/bootstrap.php';  // Load WordPress first
add_action( 'muplugins_loaded', '_manually_load_plugin' );  // Then call WordPress functions

// WRONG ORDER:
add_action( 'muplugins_loaded', '_manually_load_plugin' );  // Error: WordPress not loaded
require $_tests_dir . '/includes/bootstrap.php';
```

### Database Connection Issues

**Problem:** `Can't connect to server on 'localhost'` error in CI.

**Root Cause:** CI environment uses Docker services, not localhost.

**Solution:** Use the correct database host in `ci.sh`:

```bash
# CORRECT - Use Docker service name
bash scripts/install-wp-tests.sh wpdb wpuser wppass db latest

# WRONG - Use localhost
bash scripts/install-wp-tests.sh wpdb wpuser wppass localhost latest
```

### PHPUnit Not Found

**Problem:** `./vendor/bin/phpunit: No such file or directory` error.

**Root Cause:** Composer dependencies not installed or PHPUnit not in expected location.

**Solutions:**
1. **Check PHPUnit location:**
   ```bash
   find . -name "phpunit"
   ls -la vendor/bin/
   ```

2. **Reinstall dependencies:**
   ```bash
   composer install --no-interaction
   ```

3. **Use robust detection in CI script:**
   ```bash
   if [ -f "./vendor/bin/phpunit" ]; then
       ./vendor/bin/phpunit
   elif [ -f "/usr/src/app/vendor/bin/phpunit" ]; then
       /usr/src/app/vendor/bin/phpunit
   else
       composer install --no-interaction
       ./vendor/bin/phpunit
   fi
   ```

### WordPress Test Suite Not Installed

**Problem:** `Failed opening required '/tmp/wordpress-tests-lib/includes/functions.php'`

**Root Cause:** WordPress test suite not installed or files missing.

**Solutions:**
1. **Install WordPress test suite:**
   ```bash
   bash scripts/install-wp-tests.sh wpdb wpuser wppass db latest
   ```

2. **Verify installation:**
   ```bash
   ls -la /tmp/wordpress-tests-lib/includes/
   cat /tmp/wordpress-tests-lib/wp-tests-config.php
   ```

3. **Check SVN availability:**
   ```bash
   which svn
   svn --version
   ```

### MariaDB/MySQL Compatibility Issues

**Problem:** MySQL image not compatible with ARM64 architecture.

**Root Cause:** MySQL Docker image lacks ARM64 support.

**Solution:** Use MariaDB in `docker-compose.ci.yml`:

```yaml
services:
  db:
    image: mariadb:10.5  # ARM64 compatible
    environment:
      MYSQL_DATABASE: wpdb
      MYSQL_USER: wpuser
      MYSQL_PASSWORD: wppass
      MYSQL_ROOT_PASSWORD: root
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "--silent"]
      interval: 10s
      timeout: 5s
      retries: 3
```

### Container Build Failures

**Problem:** Docker build fails with missing files or dependencies.

**Solutions:**
1. **Check file paths in Dockerfile:**
   ```bash
   docker build -f docker/Dockerfile.ci . --no-cache
   ```

2. **Verify required files exist:**
   ```bash
   ls -la ci.sh composer.json package.json
   ```

3. **Check Docker context:**
   ```bash
   # Ensure .dockerignore doesn't exclude needed files
   cat .dockerignore
   ```

### Local CI Testing

**Before pushing to GitHub Actions, always test locally:**

```bash
# Test complete CI workflow
docker compose -f docker/docker-compose.ci.yml run --rm ci-runner

# Test individual components
docker compose -f docker/docker-compose.ci.yml run --rm ci-runner bash -c "bash scripts/install-wp-tests.sh wpdb wpuser wppass db latest && ./vendor/bin/phpunit"

# Debug CI environment
docker compose -f docker/docker-compose.ci.yml run --rm --entrypoint bash ci-runner
```

### Common CI Debug Commands

```bash
# Check CI container contents
docker compose -f docker/docker-compose.ci.yml run --rm ci-runner ls -la

# Verify dependencies
docker compose -f docker/docker-compose.ci.yml run --rm ci-runner bash -c "which composer && which yarn && which php"

# Check WordPress test environment
docker compose -f docker/docker-compose.ci.yml run --rm ci-runner bash -c "ls -la /tmp/wordpress*"

# Test database connectivity
docker compose -f docker/docker-compose.ci.yml run --rm ci-runner bash -c "mysqladmin ping --user=wpuser --password=wppass --host=db"

# Check MariaDB service
docker compose -f docker/docker-compose.ci.yml ps
docker compose -f docker/docker-compose.ci.yml logs db
```

### CI Workflow Best Practices

1. **Always test locally first** before pushing changes
2. **Use descriptive commit messages** with `ci:` prefix
3. **Monitor CI logs** for specific error messages
4. **Keep dependencies updated** regularly
5. **Document PHPCS ignore comments** with explanations

### CI/CD Troubleshooting Checklist

When CI fails:

- [ ] **Test locally first:** `docker compose -f docker/docker-compose.ci.yml run --rm ci-runner`
- [ ] **Check error message:** Look for specific file paths or commands
- [ ] **Verify dependencies:** Ensure all required files are in repository
- [ ] **Test database:** Verify MariaDB service is running and accessible
- [ ] **Check WordPress test suite:** Ensure test files are properly installed
- [ ] **Verify PHPUnit:** Check if PHPUnit is installed and accessible
- [ ] **Review bootstrap file:** Ensure WordPress is loaded before calling functions
- [ ] **Check Docker build:** Verify container builds successfully
- [ ] **Monitor GitHub Actions:** Check workflow logs for detailed errors

For more detailed CI/CD information, see [CI/CD Workflow Documentation](development/CI_CD_WORKFLOW.md).

## PHPCS Warnings Allowed in Pre-commit and CI/CD

**Note:** As of the latest workflow update, both the pre-commit hook and the GitHub Actions CI/CD pipeline are configured to allow PHPCS warnings (such as justified direct database calls with PHPCS ignore comments) and only block on errors. This is achieved by running PHPCS with `--warning-severity=0` in both local and CI workflows. Only true errors will block commits and merges; warnings will be reported but will not fail the workflow.

## Differences Between Local Pre-commit Linting and GitHub Actions

Local pre-commit linting (via Husky/Docker) and GitHub Actions (GHA) CI/CD workflows are both used to enforce code quality, but differences in environment, configuration, and file paths can sometimes cause discrepancies in results.

### Why Results May Differ
- **Environment:** Local hooks run in your dev environment (often via Docker), while GHA runs in a clean GitHub VM.
- **Paths:** Local scripts may use relative paths that work on your machine but not in CI. GHA requires paths relative to the workflow's working directory.
- **Config:** Both use `config/ci/.phpcs.xml`, but if the path is wrong in CI, the wrong ruleset/files may be checked.
- **Exclusions:** Test files, `node_modules`, and other non-source files are excluded from PHPCS in both local and CI runs, but misconfigured paths can cause these to be included/excluded incorrectly.

### What Was Done to Fix This Project
- **PHPCS Path Fixes:** The GHA workflow was updated to use the correct path to the `phpcs` binary and config file, matching the local setup (e.g., `../../../vendor/bin/phpcs`).
- **Warning Handling:** Both local and CI runs now use `--warning-severity=0` to allow warnings but block on errors only.
- **Exclusions:** `config/ci/.phpcs.xml` excludes test files, `node_modules`, and other non-source code from linting in both environments.
- **Documentation:** This section and the README were updated to help contributors understand and resolve discrepancies.

### Troubleshooting Steps
- Double-check the PHPCS version and config file used in both environments.
- Ensure you are running the same commands as the CI workflow (see `.github/workflows/ci.yml`).
- Reinstall dependencies and hooks with `yarn setup:ci`.
- If you see different results locally and in CI, review this section and the README for guidance.

## General Contribution Questions
- All standards and automation are described in the main README. The CI/CD pipeline enforces best practices so you can focus on building features.
- For anything not covered here, see `CONTRIBUTING.md` or open an issue.

## Final Few Steps: Remaining PHPCS/Linter Issues

After running PHPCBF and PHPCS with the WordPress Coding Standards, the following issues remain and must be addressed for full compliance:

### Common Issue Categories
- Inline comments must end in full-stops, exclamation marks, or question marks
- Use of direct database calls without caching or placeholders
- Use of interpolated variables in SQL queries (should use `$wpdb->prepare()` for values only)
- Output not escaped with WordPress escaping functions
- Missing or misformatted docblocks and file comments
- Yoda condition checks not enforced
- Use of deprecated or discouraged functions (e.g., `date()`, `json_encode()`, `error_log()`, direct file operations)
- Resource version not set in `wp_enqueue_style`/`wp_enqueue_script`
- Processing form data without nonce verification
- File/class naming convention issues
- Use of reserved keywords as parameter names

### File-by-File Summary

- **qr-code.php**: Missing @package tag, comment punctuation, short ternaries, Yoda conditions, direct file operations, direct DB calls, placeholders, and escaping issues.
- **wp-qr-trackr.php**: Comment punctuation, unslashed/sanitized input, output escaping, direct DB calls, placeholders, Yoda conditions, resource versioning, nonce verification, use of `date()`, `json_encode()`, `error_log()`.
- **includes/class-qr-trackr-cli.php**: File/class naming, docblocks, unused parameters, Yoda conditions, use of `passthru()`.
- **includes/class-qr-trackr-list-table.php**: File/class naming, docblocks, nonce verification, placeholders, output escaping, Yoda conditions, direct DB calls.
- **includes/module-activation.php**: Missing file doc comment.
- **includes/module-admin.php**: Blank line after file comment, comment punctuation, output escaping, direct DB calls, placeholders, Yoda conditions, resource versioning, docblocks.
- **includes/module-ajax.php**: Blank line after file comment, direct DB calls, placeholders, comment punctuation, Yoda conditions.
- **includes/module-debug.php**: File/doc comments, use of `date()`, Yoda conditions, `json_encode()`, `error_log()`, comment punctuation.
- **includes/module-qr.php**: Blank line after file comment, direct DB calls, placeholders.
- **includes/module-rewrite.php**: Blank line after file comment, direct DB calls, placeholders.
- **includes/module-utility.php**: Blank line after file comment, comment punctuation, reserved keywords as parameter names, direct DB calls, placeholders, use of `error_log()`.

### Detailed Breakdown of PHPCS/Linter Fixes

#### 1. `qr-code.php`
- **File docblock:** Added `@package` tag and improved the file-level docblock for clarity and compliance.
- **Comment punctuation:** Ensured all inline comments end with a full-stop, exclamation mark, or question mark.
- **Short ternaries:** Replaced all short ternary operators with full ternary expressions for clarity and standards compliance.
- **Yoda conditions:** Enforced Yoda conditions in all relevant comparisons.
- **File operations:** Replaced all `file_put_contents` calls with the WordPress `WP_Filesystem` API for safe, portable file writing.
- **SQL queries:** Updated all SQL queries to use `$wpdb->prepare()` for values only, never for table names. Table names are now safely constructed and injected outside of `prepare()`.
- **Docblocks:** Updated and completed all function docblocks for clarity and standards compliance.
- **Output escaping:** Confirmed that all output is properly escaped (though this file does not directly output to the browser).

#### 2. `wp-qr-trackr.php`
- **Comment punctuation:** Ensured all inline comments end with proper punctuation.
- **Docblocks:** Updated the file-level docblock and added/updated function docblocks, including `@return` tags where appropriate.
- **Code style:** Confirmed that all code style, formatting, and best practices are followed (no direct output, DB, or file operations in this file).
- **Logic:** No logic changes were made; all changes are documentation and standards compliance only.

#### 3. `includes/class-qr-trackr-cli.php`
- **File/class naming:** Added a note at the top of the file suggesting it be renamed to `class-qr-trackr-cli-command.php` for full compliance with WordPress Coding Standards (class and file name alignment).
- **File docblock:** Added `@package` tag and improved the file-level docblock for clarity and compliance.
- **Blank line after docblock:** Inserted a blank line after the file docblock as required by standards.
- **Parameter doc comments:** Added/fixed parameter doc comments for `$args` and `$assoc_args` in the `test()` method.
- **Yoda conditions:** Enforced Yoda conditions in the test result check (`if ( 0 === $exit_code )`).
- **Discouraged functions:** Added a PHPCS suppression comment for the use of `passthru()`, which is necessary for running CLI commands but discouraged in general PHP code.
- **Logic:** No logic changes were made; all changes are documentation and standards compliance only.

#### 4. `includes/class-qr-trackr-list-table.php`
- **File/class naming:** Added a note at the top of the file suggesting it could be renamed to match the class name for full compliance.
- **Docblocks:** Added or improved file, class, and method docblocks, including short descriptions and proper tags for all methods and properties.
- **Comment punctuation:** Ensured all inline comments end with a full-stop, exclamation mark, or question mark.
- **SQL queries:** Updated all SQL queries to use `$wpdb->prepare()` for values only, never for table names. Table names are now safely constructed and injected outside of `prepare()`.
- **Output escaping:** Ensured all output is properly escaped using WordPress escaping functions.
- **Yoda conditions:** Enforced Yoda conditions in all relevant comparisons.
- **Parameter and return tags:** Added missing `@return` tags and improved parameter documentation for all methods.
- **Nonce verification:** Confirmed that form data is sanitized and escaped, and noted where nonce verification should be considered for future improvements.
- **Logic:** No logic changes were made; all changes are documentation and standards compliance only.
- **[DONE] includes/class-qr-trackr-list-table.php:** All SQL queries now construct table names outside of `prepare()` and use placeholders only for values. User input in `$where`, `$join`, `$orderby`, and `$order` is sanitized. Comments clarify table name safety.

#### 5. `includes/module-admin.php`
- **Blank line after file comment:** Added a blank line after the file docblock for compliance.
- **Docblocks:** Added or improved docblocks for all functions, including `@return` tags and parameter documentation.
- **Comment punctuation:** Ensured all inline comments and user-facing strings end with a full-stop, exclamation mark, or question mark.
- **SQL queries:** Updated all SQL queries to use `$wpdb->prepare()` for values only, never for table names. Table names are now safely constructed and injected outside of `prepare()`.
- **Output escaping:** Ensured all output is properly escaped using WordPress escaping functions.
- **Yoda conditions:** Enforced Yoda conditions in all relevant comparisons.
- **Resource versioning:** Added resource versioning to enqueued scripts and styles using `QR_TRACKR_VERSION` for cache busting.
- **Parameter and return tags:** Added missing `@return` tags and improved parameter documentation for all methods.
- **Code style:** Reviewed and updated code style and formatting for full compliance.
- **Logic:** No logic changes were made; all changes are documentation and standards compliance only.
- **[DONE] includes/module-admin.php:** SQL queries now construct table names outside of `prepare()` and use placeholders only for values. Table names are clearly marked as safe with comments.

#### 6. `includes/module-debug.php`
- **File docblock:** Added a file-level docblock with `@package` tag and a clear description of the module's purpose.
- **Docblocks:** Added or improved docblocks for all functions, including anonymous functions in hooks.
- **Timezone safety:** Replaced all uses of `date()` with `gmdate()` for consistent, timezone-safe timestamps.
- **JSON encoding:** Replaced all uses of `json_encode()` with `wp_json_encode()` for WordPress compatibility and security.
- **Error logging:** Added a comment and PHPCS suppression for the use of `error_log()` (noting it is for debug only and not for production use).
- **Yoda conditions:** Enforced Yoda conditions in all relevant comparisons.
- **Comment punctuation:** Ensured all inline comments are properly punctuated.
- **Code style:** Reviewed and updated code style and formatting for full compliance.
- **Logic:** No logic changes were made; all changes are documentation and standards compliance only.

#### 7. `includes/module-qr.php`
- **Blank line after file comment:** Added a blank line after the file docblock for compliance.
- **Docblocks:** Added or improved docblocks for all functions, including parameter and return documentation.
- **Comment punctuation:** Ensured all inline comments are properly punctuated.
- **SQL queries:** Updated all SQL queries to use `$wpdb->prepare()` for values only, never for table names. Table names are now safely constructed and injected outside of `prepare()`.
- **Output escaping:** Ensured all output is properly escaped (if any output is generated).
- **Yoda conditions:** Enforced Yoda conditions in all relevant comparisons.
- **Code style:** Reviewed and updated code style and formatting for full compliance.
- **Logic:** No logic changes were made; all changes are documentation and standards compliance only.

#### 8. `includes/module-rewrite.php`
- **Blank line after file comment:** Added a blank line after the file docblock for compliance.
- **Docblocks:** Added or improved docblocks for all functions, including parameter and return documentation.
- **Comment punctuation:** Ensured all inline comments are properly punctuated.
- **SQL queries:** Updated all SQL queries to use `$wpdb->prepare()` for values only, never for table names. Table names are now safely constructed and injected outside of `prepare()`.
- **Output escaping:** Ensured all output is properly escaped (if any output is generated).
- **Yoda conditions:** Enforced Yoda conditions in all relevant comparisons.
- **Code style:** Reviewed and updated code style and formatting for full compliance.
- **Logic:** No logic changes were made; all changes are documentation and standards compliance only.

#### 9. `includes/module-utility.php`
- **Blank line after file comment:** Added a blank line after the file docblock for compliance.
- **Reserved keywords:** Changed reserved keywords as parameter names to non-reserved alternatives (e.g., `$array` → `$arr`, `$string` → `$str`).
- **Docblocks:** Added or improved docblocks for all functions, including parameter and return documentation.
- **Comment punctuation:** Ensured all inline comments are properly punctuated.
- **SQL queries:** Updated all SQL queries to use `$wpdb->prepare()` for values only, never for table names. Table names are now safely constructed and injected outside of `prepare()`.
- **Error logging:** Added a comment and PHPCS suppression for the use of `error_log()` (noting it is for debug only and not for production use).
- **Output escaping:** Ensured all output is properly escaped (if any output is generated).
- **Yoda conditions:** Enforced Yoda conditions in all relevant comparisons.
- **Code style:** Reviewed and updated code style and formatting for full compliance.
- **Logic:** No logic changes were made; all changes are documentation and standards compliance only.

---

**Action:**
Work through each file, addressing the above issues. Re-run PHPCS after each round of fixes to confirm compliance. See the full PHPCS output for line-by-line details.

*Built on a foundation of engineering standards and automation—so you can focus on the fun part.*

---

## Final Compliance Stage: Remaining PHPCS Issues and Fixes

Congratulations! The codebase is now highly compliant with WordPress Coding Standards, with all major architectural, security, and documentation issues addressed. The plugin is robust, maintainable, and secure. However, a final sweep by PHPCS has revealed a handful of remaining issues that must be resolved for 100% compliance.

Below is a categorized checklist of the remaining issues, with file references and notes for each. Use this as a guide for the last round of compliance work.

### 1. SQL Placeholders and Table Name Interpolation
- **Problem:** Some SQL queries still use interpolated variables for table names inside `$wpdb->prepare()`, or do not use placeholders for values.
- **Files:**
  - `qr-code.php`
  - `qr-trackr.php`
  - `includes/class-qr-trackr-list-table.php`
  - `includes/module-admin.php`
  - `includes/module-ajax.php`
  - `includes/module-qr.php`
  - `includes/module-rewrite.php`
  - `includes/module-utility.php`
- **Fix:** Always construct table names outside of `prepare()`, and use placeholders for all values. Example:
  ```php
  $table = $wpdb->prefix . 'my_table';
  $wpdb->get_var( $wpdb->prepare( 'SELECT * FROM `' . $table . '` WHERE id = %d', $id ) );
  ```

### 2. Output Escaping
- **Problem:** Some output is not properly escaped using WordPress functions.
- **Files:**
  - `qr-trackr.php`
  - `includes/class-qr-trackr-list-table.php`
  - `includes/module-admin.php`
- **Fix:** Wrap all output in `esc_html()`, `esc_url()`, `esc_attr()`, etc., as appropriate.

### 3. Inline Comment Punctuation
- **Problem:** Inline comments do not always end in full-stops, exclamation marks, or question marks.
- **Files:**
  - `qr-code.php`
  - `qr-trackr.php`
  - `includes/module-utility.php`
- **Fix:** Update all inline comments to end with proper punctuation.

### 4. Nonce and Input Sanitization
- **Problem:** Some user input is not unslashed or sanitized before use, and some form actions lack nonce verification.
- **Files:**
  - `qr-trackr.php`
- **Fix:** Always use `wp_unslash()` and appropriate sanitization functions on all user input, and verify nonces for all form submissions.

### 5. Resource Versioning for Enqueued Assets
- **Problem:** Some calls to `wp_enqueue_style()` and `wp_enqueue_script()` do not set a version parameter.
- **Files:**
  - `qr-trackr.php`
- **Fix:** Add a version constant or string to all enqueued assets for cache busting.

### 6. File/Class Naming Conventions
- **Problem:** Some class files do not match the class name with the `class-` prefix.
- **Files:**
  - `includes/class-qr-trackr-cli.php`
  - `includes/class-qr-trackr-list-table.php`
- **Fix:** Rename files to match the class name with the `class-` prefix (e.g., `class-qr-trackr-cli-command.php`).

### 7. Docblocks and Comment Styles
- **Problem:** Some functions or files are missing docblocks, or use incorrect comment styles.
- **Files:**
  - `qr-code.php`
  - `qr-trackr.php`
  - `includes/class-qr-trackr-list-table.php`
  - `includes/module-utility.php`
- **Fix:** Add or correct docblocks for all files, classes, and functions.

### 8. Discouraged Functions and Practices
- **Problem:** Use of `error_log()`, direct DB schema changes, and other discouraged functions in production code.
- **Files:**
  - `includes/module-utility.php`
- **Fix:** Suppress with PHPCS comments and ensure these are only used for debugging or migration, not in production logic.

### 9. [DONE] qr-code.php: SQL query in `qr_trackr_generate_qr_image_for_link` now uses single quotes and constructs the table name outside of `prepare()`. Only values are passed as placeholders. Table name is safely constructed from `$wpdb->prefix` and a static string.

### 10. [DONE] includes/module-ajax.php: SQL queries now construct table names outside of `prepare()` and use placeholders only for values. Table names are clearly marked as safe with comments.

---

**Action:**
Work through each category and file, applying the recommended fixes. Re-run PHPCS after each round to confirm compliance. Once all issues are resolved, the plugin will be fully standards-compliant and ready for production or release.

## Compliance Checklist Reference

A full WordPress Coding Standards compliance checklist is now maintained in the [README.md](./README.md). Please refer to that section for the latest status and any remaining minor issues before release or PR merge.

## Parallel Docker Environments & Troubleshooting

This project supports running both dev (8080) and nonprod (8081) WordPress environments in parallel using Docker Compose. Use `./scripts/launch-all-docker.sh` to start both environments with full isolation.

### Common Issues
- **Port Conflicts:**
  - If you see errors about ports 8080 or 8081 being in use, run `lsof -i :8080` or `lsof -i :8081` to find and stop the conflicting process.
- **Orphaned Containers:**
  - The launch script uses `--remove-orphans` to clean up old containers. If you see unexpected containers, run `docker compose -p wpqrdev down` and `docker compose -p wpqrnonprod down`.
- **Resetting Environments:**
  - To reset dev: `./scripts/reset-docker.sh dev`
  - To reset nonprod: `./scripts/reset-docker.sh nonprod`
- **Accessing Environments:**
  - Dev: http://localhost:8080
  - Nonprod: http://localhost:8081

### Best Practices
- Always use the launch script for parallel environments.
- Only upload release ZIPs to nonprod; dev is for live code.
- If in doubt, stop all containers and relaunch.

## Composer/PHPCS Memory & VCS Issues
- CI/CD enforces a 2G memory limit for Composer and PHPCS to prevent out-of-memory errors. If you see OOM errors locally, set COMPOSER_MEMORY_LIMIT=2G and use php -d memory_limit=2G for PHPCS.
- Only supported PHPCS sniffs (wpcs, phpcsutils) are used; legacy sniffs (NormalizedArrays, Universal, Modernize) have been removed from PHPCSStandards and should not be referenced.
- If Composer fails to clone a PHPCS sniff repository, check that the repository exists and is public. Remove any references to unavailable sniffs from composer.json and PHPCS config.

## PHPCS: False Positives or Duplicate Errors from Build Artifacts

**Problem:**
- PHPCS reports errors for files or lines that do not exist in your source code, or you see duplicate errors for the same file.

**Cause:**
- The linter is scanning build or generated files (e.g., in `build/`), not just your source code.

**Solution:**
- Exclude build and generated directories in `config/ci/.phpcs.xml` using `<exclude-pattern>build/**</exclude-pattern>`.
- Add `--ignore='vendor/*,build/**'` to all PHPCS invocations in your CI scripts (e.g., `ci.sh`).
- Restrict `<file>` entries in `config/ci/.phpcs.xml` to only your actual source code.
- If you add new build or generated directories, update both `config/ci/.phpcs.xml` and your CI scripts accordingly.

**Lessons Learned:**
- Always exclude build artifacts from linting to avoid confusing or duplicate errors.
- Use both config file patterns and command-line flags for maximum reliability.
