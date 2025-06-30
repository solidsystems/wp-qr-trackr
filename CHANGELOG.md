# Changelog

All notable changes to this project will be documented in this file.

## [1.2.9] - 2024-Current

### Fixed
- **Database Table Creation**: Fixed plugin activation to properly create database tables automatically
- **Error Logging**: Added comprehensive error logging and debugging for activation issues
- **Table Verification**: Added verification step to ensure database table creation was successful
- **SQL Preparation**: Fixed incorrect SQL preparation in deactivation function

### Enhanced
- **Debugging**: Added debug logging for activation process when WP_DEBUG is enabled
- **Error Handling**: Added activation error tracking to help diagnose table creation issues
- **User Feedback**: Plugin now stores activation errors for admin notification

### Technical
- Fixed `$wpdb->prepare()` usage in deactivation function (removed invalid `%i` placeholder)
- Added table existence verification after creation attempt
- Enhanced error logging throughout activation process
- Improved database table creation reliability

## [1.2.8] - 2024-Current

### Fixed
- **Critical Fix**: QR codes now appear in the admin list immediately after creation (fixed cache invalidation issue)
- **Enhanced UX**: Add New QR Code page now has AJAX-powered post/page search instead of static dropdown
- **Performance**: Pre-generate QR code images during creation for faster display
- **User Experience**: Added detailed QR code preview with tracking code and URL after successful creation

### Added
- **AJAX Search**: Real-time post/page searching with debounced input (300ms delay)
- **Interactive Interface**: Click-to-select functionality with hover effects and loading states
- **Clear Selection**: Added clear button for easy post deselection
- **Error Handling**: Comprehensive error feedback and validation
- **Security**: Proper nonce verification and capability checks for all AJAX requests

### Technical Improvements
- **JavaScript**: Created dedicated `assets/qrc-admin.js` for admin functionality
- **Code Standards**: Fixed all PHPCS violations and maintained WordPress coding standards
- **Caching**: Improved cache management for better performance
- **Input Validation**: Enhanced security with proper sanitization and escaping

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
