# Changelog

All notable changes to this project will be documented in this file.

## [1.2.21] - 2025-07-03

### Enhanced
- **CODE QUALITY**: Comprehensive PHPCS compliance improvements across all plugin files
- **DOCUMENTATION**: Added comprehensive Cursor Plugin Development Guide with AI collaboration workflows
- **AUTOMATION**: Implemented TODO automation system with structured task management
- **TESTING**: Added end-to-end testing framework for release validation
- **DEVELOPMENT**: Enhanced project management with Cursor-specific development workflows

### Added
- **NEW GUIDE**: Cursor Plugin Development Guide (`docs/CURSOR_PLUGIN_DEVELOPMENT_GUIDE.md`)
- **NEW REFERENCE**: Cursor Quick Reference (`docs/CURSOR_QUICK_REFERENCE.md`)
- **NEW AUTOMATION**: TODO automation system with real-time project tracking
- **NEW SCRIPTS**: Automated TODO synchronization and GitHub Projects integration
- **NEW WORKFLOWS**: Proven AI collaboration patterns for WordPress plugin development

### Fixed
- Fixed 183 PHPCS code style violations automatically using PHPCBF
- Corrected inline comment punctuation throughout codebase
- Added proper PHPCS ignore comments for debug logging
- Enhanced code documentation and docblock completeness

### Technical
- Implemented structured TODO management with dependencies and status tracking
- Added comprehensive project management automation scripts
- Enhanced development workflow documentation with specific AI prompts
- Improved code quality standards enforcement with automated fixes

## [1.2.20] - 2024-12-29

### Fixed
- **CRITICAL**: Fixed fatal error during plugin activation due to rewrite rules being registered too early
- Fixed "Call to a member function add_rule() on null" error in WordPress rewrite system
- Moved rewrite rule registration to proper `init` hook timing
- Implemented deferred rewrite rules flushing to avoid timing issues

### Technical
- Updated `qr_trackr_maybe_flush_rewrite_rules()` to defer flush until `init` hook
- Modified activation hook to schedule rewrite flush instead of immediate execution
- Added `qr_trackr_init_rewrite_rules()` function for proper hook timing
- Enhanced error handling for rewrite system initialization

## [1.2.19] - 2024-12-29

### Enhanced
- **UI IMPROVEMENT**: Cleaned up duplicate QR code images in admin list table
- Streamlined QR Code column to show only code identifier and "Visit Link" button
- Eliminated visual duplication between QR Image and QR Code columns
- Improved admin interface clarity and professional appearance

### Technical
- Simplified `column_qr_code()` method to remove redundant image display
- Maintained QR Image column for modal functionality while cleaning up QR Code column
- Enhanced user experience with cleaner, less cluttered interface

## [1.2.18] - 2024-12-29

### Added
- **NEW FEATURE**: Clickable QR image modal with detailed views and editing capabilities
- **NEW FIELD**: Common Name field for user-friendly QR code identification
- **NEW FIELD**: Referral Code field for enhanced tracking and analytics
- **NEW FUNCTIONALITY**: Search and filter capabilities in admin QR code list
- **NEW UI**: Modern responsive modal interface for QR code management
- Real-time AJAX-powered QR code details editing with validation
- Comprehensive search across common names, referral codes, QR codes, and destination URLs
- Referral code filter dropdown for quick filtering
- Enhanced admin table with new columns and improved organization

### Enhanced
- Updated database schema with new `common_name` and `referral_code` fields
- Automatic database migration for existing installations
- Enhanced "Add New" form with validation for new fields
- Improved admin list table with 15 items per page and better column organization
- Mobile-responsive modal design with touch-friendly interactions
- Real-time form validation with user-friendly error messages
- Caching optimizations for search and filter operations

### Technical
- Added AJAX endpoints: `qr_trackr_get_qr_details` and `qr_trackr_update_qr_details`
- Enhanced JavaScript with comprehensive modal management system
- Added database indexes for new fields to improve query performance
- Implemented proper nonce verification for all AJAX operations
- Added referral code uniqueness validation during creation and editing
- Enhanced admin script localization with comprehensive string management

### Security
- All user input properly sanitized and validated
- Referral code format validation (alphanumeric, hyphens, underscores only)
- Proper nonce verification for all form submissions and AJAX requests
- Enhanced SQL query preparation with proper placeholders

## [1.2.17] - 2024-12-29

### Fixed
- **CRITICAL**: Fixed query variable registration for QR code redirects
- Enhanced "Force Flush Rewrite Rules" to also re-register query variables
- Improved query variable detection in debug page to use correct global variable
- Fixed rewrite rules working but redirects still failing due to missing query vars

### Technical
- Updated query variable detection to use `$wp->public_query_vars` instead of `$wp_rewrite->query_vars`
- Added `$wp->add_query_var( 'qr_tracking_code' )` to force flush function
- Enhanced debug page to show "Force Flush" button when either rules or query vars are missing

## [1.2.16] - 2024-12-29

### Fixed
- **CRITICAL**: Fixed QR image generation by replacing deprecated Google Charts API with QR Server API
- **CRITICAL**: Enhanced rewrite rules registration with improved detection and manual flush capability
- Added "Force Flush Rewrite Rules" button to debug page for manual rule registration
- Improved rewrite rules checking with dedicated validation function
- Added debug logging for rewrite rule registration tracking

### Technical
- Replaced `https://chart.googleapis.com/chart` (deprecated since 2019) with `https://api.qrserver.com/v1/create-qr-code/`
- Updated QR generation parameters to match new API format
- Added `qr_trackr_check_rewrite_rules()` function for better rule detection
- Added `qr_trackr_force_flush_rewrite_rules()` function for manual rule management

## [1.2.15] - 2024-12-29

### Fixed
- **CRITICAL**: Fixed automatic rewrite rule flushing when plugin version changes
- Enhanced version-based detection system for rewrite rule updates
- Added automatic rule flushing during plugin upgrades
- Improved debug logging for rewrite rule troubleshooting

### Technical  
- Added version-based rewrite rule flushing in activation module
- Enhanced debug page with better rewrite rule diagnostics
- Added automatic detection of plugin version changes requiring rule flush

## [1.2.14] - 2024-12-29

### Added
- **NEW FEATURE**: Comprehensive debug page with system diagnostics
- Debug menu option (visible when WP_DEBUG enabled or force debug setting enabled)
- System information display (WordPress version, PHP version, plugin version, debug status)
- Database status verification (table existence, field verification, QR code statistics)
- Rewrite rules verification (registration status and pattern inspection)
- QR image generation testing (live test with visual preview)
- File system check (upload directory permissions, QR image counts)
- Redirect testing (sample QR code validation)

### Enhanced
- Enhanced security by moving "Remove Data on Deactivation" setting behind debug mode gate
- Added "Force Debug Mode" setting in plugin settings for enabling debug without WP_DEBUG
- Improved admin interface organization with conditional menu items

### Technical
- Added comprehensive system diagnostics and validation tools
- Enhanced debug logging capabilities throughout the plugin
- Added safe debug mode toggle for production environments

## [1.2.13] - 2024-12-29

### Fixed
- **CRITICAL**: Fixed QR code images not displaying in admin table
- Added `qr_code_url` field to database schema for storing generated QR image URLs
- Enhanced admin creation process to generate and store QR image URL during QR code creation
- Updated list table to use stored QR image URLs with smart fallback system for existing codes
- Improved performance by eliminating repeated QR image generation

### Enhanced
- Added automatic database field addition for existing installations
- Smart fallback system for QR codes created before this update
- Enhanced admin list table with proper QR image display

### Technical
- Database schema upgrade with `qr_code_url varchar(2048) DEFAULT NULL` field
- Enhanced QR creation workflow to store image URLs
- Improved caching and performance for admin table display

## [1.2.12] - 2024-12-29

### Fixed
- **CRITICAL**: Fixed rewrite rules not being registered due to overly restrictive `is_admin()` check
- Removed problematic conditional that was preventing rewrite rules from being registered during plugin activation
- Fixed activation order to load `module-rewrite.php` before calling `flush_rewrite_rules()`
- QR URLs (e.g., `/qr/Snezrw9t`) now work correctly and redirect to destination URLs

### Technical
- Removed `is_admin()` and `wp_doing_ajax()` checks from rewrite rule registration
- Improved module loading order in activation process
- Enhanced rewrite rule registration reliability

## [1.2.11] - 2024-12-29

### Fixed
- Attempted fix for rewrite rules not being registered properly
- Enhanced rewrite rule debugging and validation

## [1.2.10] - 2024-12-29

### Added
- **NEW FEATURE**: Permalink structure validation during plugin activation
- Admin warning system for incompatible permalink settings (plain permalinks)
- Automatic detection and user guidance for permalink configuration
- Enhanced activation process with permalink compatibility checking

### Enhanced
- Improved user experience with clear instructions for permalink setup
- Added automatic permalink structure change detection
- Enhanced activation workflow with comprehensive checks

### Technical
- Added `qr_trackr_check_permalink_structure()` function
- Added `qr_trackr_permalink_admin_notice()` for user warnings
- Added `qr_trackr_permalink_structure_changed()` hook handler

## [1.2.9] - 2024-12-29

### Enhanced
- Improved database table creation with better error handling
- Enhanced activation process with comprehensive validation
- Added debug logging for database operations
- Improved error reporting for troubleshooting

### Technical
- Enhanced `qrc_activate()` function with better error handling
- Added table existence verification during activation
- Improved debug logging throughout activation process

## [1.2.8] - 2024-12-29

### Fixed
- **CRITICAL**: Fixed cache invalidation issue preventing new QR codes from appearing in admin list
- **CRITICAL**: Implemented real-time AJAX post/page search functionality for "Add New" page
- Added comprehensive error handling and user feedback for QR code creation
- Fixed static dropdown being replaced with dynamic, searchable interface

### Added
- Real-time post/page search with debounced input for optimal performance
- Click-to-select interface for intuitive post/page selection
- Enhanced user experience with loading states and clear feedback
- Dedicated `assets/qrc-admin.js` file for admin functionality

### Enhanced
- Improved admin interface responsiveness and usability
- Added proper cache invalidation after successful QR code creation
- Enhanced form validation and error messaging
- Mobile-friendly interface improvements

### Technical
- Added `qrc_search_posts_ajax()` function for AJAX post search
- Implemented proper cache management with `wp_cache_delete()`
- Enhanced JavaScript with debouncing and error handling
- Added comprehensive AJAX error handling and user feedback

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.7] - 2024-12-19

### Added
- **Submenus for QR Codes Admin Menu**: Added proper submenus (All QR Codes, Add New, Settings, Help) that appear on hover
- **QR Code Preview Generation**: Implemented automatic QR code image generation and preview in admin listing
- **Add New QR Code Interface**: Created comprehensive form for adding new QR codes with multiple destination types
- **Enhanced Settings Page**: Added QR code size and tracking options to settings
- **Help Documentation**: Added built-in help page with usage instructions

### Fixed
- **Missing Submenus**: QR Codes menu now properly shows submenus on hover
- **Empty QR Code Previews**: QR code images are now automatically generated and displayed in admin listing
- **Admin Navigation**: Improved admin interface with proper page linking and navigation

### Enhanced
- **QR Code Generation**: Added robust QR code image generation using Google Charts API
- **Error Handling**: Improved fallback handling when QR code generation fails
- **Caching**: Implemented proper caching for QR code images and data
- **User Interface**: Modern, responsive admin interface with proper WordPress styling

### Technical
- Added utility functions for unique QR code generation
- Implemented proper WordPress admin menu structure
- Added comprehensive error handling and validation
- Enhanced database schema compatibility

## [1.2.6] - 2024-12-30

### Fixed
- **Critical: Fixed 500 error on QR Codes admin page** - Added missing `QRC_Links_List_Table` class that was causing fatal errors
- **Fixed database schema inconsistencies** - Unified table name from `qr_code_links` to `qr_trackr_links` across all modules
- **Added missing database columns** - Added `qr_code`, `scans`, `updated_at`, `last_accessed`, and `metadata` columns to support all features
- **Fixed admin menu functionality** - QR Codes admin page now loads correctly without errors

### Added
- **Complete WordPress List Table implementation** - Professional admin interface for managing QR code links
- **Proper database indexing** - Added indexes on `qr_code` and `post_id` columns for better performance
- **Backward compatibility** - Maintained both `scans` and `access_count` columns for compatibility

### Technical
- Added `includes/class-qrc-links-list-table.php` with proper WordPress standards
- Updated database schema in `module-activation.php` with all required columns
- Fixed table name references across `module-ajax.php` and other modules
- Added proper caching and pagination to admin list table

## [1.2.5] - 2024-12-30

### Fixed
- **Critical: Fixed PHP code display in admin head** - Added missing PHP opening tag to `module-rewrite.php`
- **Fixed admin interface corruption** - Resolved issue where rewrite module code was output as plain text
- **Plugin activation now works correctly** - No more PHP code appearing in WordPress admin pages

### Technical
- Added proper `<?php` opening tag to `includes/module-rewrite.php`
- Fixed file parsing issues that caused code to be treated as plain text
- Improved plugin module loading reliability

## [1.2.4] - 2024-12-30

### Fixed
- **Fixed fatal error on plugin activation** - Resolved constant mismatch issues between QRC_ and QR_TRACKR_ prefixes
- **Fixed conflicting hook registrations** - Prevented duplicate activation/deactivation hook registrations
- **Fixed module loading conflicts** - Removed automatic module loading from activation module
- **Added backward compatibility constants** - Defined legacy QRC_ constants for existing module compatibility
- **Added safe module loading** - Added file existence checks before requiring modules
- **Fixed activation/deactivation flow** - Proper separation of concerns between main file and activation module

### Technical
- Added legacy constant definitions for backward compatibility
- Implemented safe module loading with file existence checks
- Removed conflicting require_once statements from activation module
- Fixed hook registration conflicts between main file and modules
- Added proper error handling for missing files during activation

## [1.2.1] - 2024-12-30
## [1.2.3] - 2024-12-30
### Fixed
- Fixed QR code URL rewrite rules to handle correct URL pattern `/qr/{tracking_code}`
- Updated rewrite rule from `/qr-code/{numeric_id}/` to `/qr/{alphanumeric_code}` pattern
- Fixed database lookup to use tracking codes instead of numeric IDs
- Resolved 404 errors for all QR code tracking URLs
- Updated template redirect function to properly handle alphanumeric tracking codes

### Technical
- Updated rewrite rule pattern to match actual URL generation format
- Fixed query variable handling for tracking code parameters
- Improved URL pattern documentation and error handling

## [1.2.2] - 2024-12-30

### Fixed
- **Fixed invalid plugin header** - Corrected WordPress plugin header format and structure
- **Updated plugin constants** - Changed from QRC_ to QR_TRACKR_ prefix for consistency
- **Fixed module loading order** - Proper autoloader loading before QR code library usage
- **Improved plugin initialization** - Better error handling and dependency management

### Technical
- Recreated main plugin file with proper WordPress standards
- Fixed constant naming conventions throughout the plugin
- Improved module loading sequence and error handling
- Added proper plugin metadata for WordPress compatibility

## [1.2.1] - 2024-12-30

### Fixed
- Fixed QR code URL rewrite rules to handle correct URL pattern `/qr/{tracking_code}`
- Updated rewrite rule from `/qr-code/{numeric_id}/` to `/qr/{alphanumeric_code}` pattern
- Fixed database lookup to use tracking codes instead of numeric IDs
- Resolved 404 errors for all QR code tracking URLs
- Updated template redirect function to properly handle alphanumeric tracking codes

### Technical
- Updated rewrite rule pattern to match actual URL generation format
- Fixed query variable handling for tracking code parameters
- Improved URL pattern documentation and error handling

## [1.2.0] - 2024-12-30

### Added
- Complete QR code creation and display functionality
- Endroid QR Code library integration with PNG and SVG support
- Comprehensive AJAX handlers for QR code management
- Real-time post/page search with Select2 integration
- QR code image generation with caching and optimization
- Proper autoloader management for Composer dependencies

### Fixed
- Fixed autoloader loading order to ensure QR code library availability
- Resolved database column name mismatches (scans vs access_count)
- Fixed variable assignment issues in admin display code
- Corrected cache key consistency across modules
- Fixed QR code image accessibility with proper .htaccess configuration
- Resolved nonce verification issues in AJAX handlers

### Enhanced
- Improved error handling and user feedback in QR code creation
- Enhanced database query caching and performance
- Better module loading architecture with proper dependency management
- Streamlined admin interface with better UX
- Comprehensive debugging and logging capabilities

### Security
- Maintained WordPress security standards with proper nonce verification
- Enhanced input sanitization and output escaping
- Secure file handling for QR code image generation

## [1.1.4] - 2024-03-21

### Fixed
- Improved SQL query preparation in list table and utils modules
- Fixed duplicate record_count method in list table
- Removed unnecessary PHPCS ignore comments
- Enhanced table name handling in SQL queries
- Standardized SQL query formatting

## [1.1.3] - 2024-03-21

### Security
- Switched to wp_safe_redirect() for better redirect security
- Improved SQL query preparation with proper RETURNING clause
- Replaced serialize() with wp_json_encode() for safer cache key generation

### Performance
- Enhanced caching implementation for QR code destination URLs
- Optimized database queries with proper caching strategies
- Added appropriate caching for frequently accessed data

### Code Quality
- Fixed whitespace and alignment issues across all files
- Added proper PHPCS ignore comments with explanations
- Improved code documentation and inline comments
- Fixed file formatting and newline issues
- Added @package tags to file headers

### Added
- Fallback QR code generation service using QRServer.com
- Comprehensive input validation for QR code parameters
- Detailed error logging for QR code generation failures
- API response validation and error handling

### Changed
- Enhanced QR code generation with better error handling
- Improved color validation for QR code customization
- Updated documentation with detailed parameter descriptions

### Fixed
- QR code generation failures now handled gracefully with fallback service
- Invalid color codes now return proper error messages
- Improved error messages for better user feedback

## [1.1.2] - 2024-03-20

### Security
- Fixed SQL injection vulnerabilities across all database queries using $wpdb->prepare()
- Added comprehensive nonce verification for all admin actions
- Improved input sanitization and output escaping
- Enhanced error handling with proper logging
- All admin AJAX actions (edit, delete, regenerate) now use separate, localized nonces

### Performance
- Implemented caching for database queries
- Added cache invalidation on relevant actions
- Standardized cache group names across modules
- Optimized bulk action handling

### Code Quality
- Fixed Yoda conditions throughout the codebase
- Added proper comment punctuation
- Enhanced documentation with @throws tags
- Fixed empty ELSE statements
- Added translators comments for i18n
- Improved code style and whitespace consistency

### Added
- Comprehensive module documentation
- Enhanced error logging capabilities
- Improved debug UI with better capability checks
- Added proper log rotation and file management

## [1.1.0] - 2024-03-20

### Security
- Fixed SQL injection vulnerabilities across all database queries using $wpdb->prepare()
- Added comprehensive nonce verification for all admin actions
- Improved input sanitization and output escaping
- Enhanced error handling with proper logging
- All admin AJAX actions (edit, delete, regenerate) now use separate, localized nonces

### Performance
- Implemented caching for database queries
- Added cache invalidation on relevant actions
- Standardized cache group names across modules
- Optimized bulk action handling

### Code Quality
- Fixed Yoda conditions throughout the codebase
- Added proper comment punctuation
- Enhanced documentation with @throws tags
- Fixed empty ELSE statements
- Added translators comments for i18n
- Improved code style and whitespace consistency
- PHPCS memory requirement documented: at least 1GB, 4GB recommended for large codebases

### Added
- Comprehensive module documentation
- Enhanced error logging capabilities
- Improved debug UI with capability checks
- Added proper transaction support for database operations
- Enhanced IP and location tracking
- Added log rotation functionality

## [1.0.2] - 2024-06-15

### Changed
- Updated plugin header metadata for better WordPress.org compatibility
- Improved plugin description and documentation
- Enhanced code quality and standards compliance
- Added automated release process with build script

## [1.0.1] - 2024-03-19

### Added
- Initial public release
- QR code generation and tracking functionality
- Admin interface for managing QR codes
- Scan analytics and reporting
- Mobile-first responsive design
- WordPress coding standards compliance
- Comprehensive documentation
- Security best practices implementation
