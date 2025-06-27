# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
