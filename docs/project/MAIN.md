# WP QR Trackr - Main Project Documentation

## Overview

WP QR Trackr is a comprehensive WordPress plugin for QR code generation and tracking with advanced analytics, custom styling, and management features.

## Key Features

- **QR Code Generation**: Generate QR codes with custom styling and branding
- **Scan Tracking**: Track QR code scans and access analytics
- **Admin Interface**: User-friendly WordPress admin interface
- **Custom Styling**: Customize QR code appearance and colors
- **Analytics Dashboard**: View scan statistics and trends
- **Bulk Operations**: Manage multiple QR codes efficiently

## Architecture

### Core Components

- **Module System**: Modular architecture with separate files for different functionality
- **Database Integration**: Custom table for QR code storage and tracking
- **Rewrite Rules**: Clean URL support for QR code tracking
- **AJAX Handlers**: Dynamic QR code generation and management
- **Admin Interface**: WordPress admin integration

### File Structure

```
plugin/
├── wp-qr-trackr.php          # Main plugin file
├── includes/                 # Core functionality modules
│   ├── module-activation.php # Plugin activation/deactivation
│   ├── module-admin.php      # Admin interface
│   ├── module-ajax.php       # AJAX handlers
│   ├── module-qr.php         # QR code generation
│   ├── module-rewrite.php    # URL rewrite rules
│   ├── module-utils.php      # Utility functions
│   └── class-qrc-links-list-table.php # Admin list table
├── templates/                # Admin page templates
├── assets/                   # JavaScript and CSS files
└── vendor/                   # Composer dependencies
```

## Development Environment

### Requirements

- Docker Desktop
- Git
- WordPress development environment

### Setup

```bash
# Clone the repository
git clone https://github.com/solidsystems/wp-qr-trackr.git
cd wp-qr-trackr

# Start development environment
./scripts/setup-wordpress.sh dev

# Access WordPress
# URL: http://localhost:8080
# Admin: trackr/trackr
```

### Control Scripts

- `./scripts/setup-wordpress.sh dev` - Start development environment
- `./scripts/wp-operations.sh dev` - WordPress CLI operations
- `./scripts/debug.sh dev` - Debug and health checks
- `make validate` - Run all validation checks

## Recent Major Issues and Solutions

### QR Code Scan Counter Issue (v1.2.66 - v1.2.72)

**Problem**: QR code scans were not updating visit counters, while direct link clicks worked correctly.

**Root Cause**: QR codes generated before v1.2.66 were embedding destination URLs instead of tracking URLs, causing scans to bypass the tracking system entirely.

**Solution Timeline**:
1. **v1.2.66**: Fixed QR code generation to use tracking URLs
2. **v1.2.70**: Added regeneration tool for existing QR codes
3. **v1.2.72**: Simplified regeneration approach for reliability

**Lessons Learned**:
- QR code URL embedding is critical for tracking functionality
- Complex detection logic is fragile and error-prone
- User-friendly admin tools are essential for fixing production issues
- Dependencies must be available in all execution contexts

**Prevention Strategies**:
- Automated testing for QR code generation
- Validation checks for URL embedding
- Monitoring for tracking bypass patterns
- Comprehensive documentation and troubleshooting guides

### Delete QR Functionality Issue

**Problem**: Delete QR functionality was failing in both development and production.

**Root Cause**: Missing WordPress hook registration for the delete handler function.

**Solution**: Added `add_action('admin_init', 'qrc_handle_delete_action')` to ensure the delete handler is called.

**Lesson**: Always verify WordPress hooks are properly registered and called.

### Google Fonts SSL Certificate Errors

**Problem**: SSL certificate warnings when loading Google Fonts in production.

**Root Cause**: External font loading causing SSL validation issues.

**Solution**: Disabled Google Fonts loading universally to prevent SSL warnings.

**Lesson**: External dependencies can cause SSL issues in production environments.

## Quality Assurance

### Code Standards

- **PHPCS Compliance**: All code must pass WordPress coding standards
- **Security**: Proper sanitization, validation, and escaping
- **Documentation**: Complete docblocks and inline comments
- **Testing**: Unit tests and integration tests

### Validation Commands

```bash
# Run all validation checks
make validate

# Code style check
make lint

# Run tests
make test

# Build release
./scripts/build-release.sh
```

## Release Process

### Version Management

1. Update version in `plugin/wp-qr-trackr.php`
2. Update `QR_TRACKR_VERSION` constant
3. Add changelog entry in `docs/CHANGELOG.md`
4. Commit and push changes
5. Create GitHub release
6. Upload plugin zip file

### Release Automation

- GitHub Actions workflows for automated releases
- Version bumping and changelog updates
- Asset upload and distribution

## Troubleshooting

### Common Issues

1. **QR Code Scans Not Tracking**: Use regeneration tool in admin
2. **Delete Functionality Failing**: Check WordPress hook registration
3. **SSL Certificate Errors**: Disable external dependencies
4. **Vendor Directory Issues**: Ensure proper .gitignore configuration

### Debug Tools

- `./scripts/debug.sh dev health` - Environment health check
- `./scripts/debug.sh dev wordpress` - WordPress status check
- `./scripts/wp-operations.sh dev eval` - Execute PHP code

## Contributing

### Development Workflow

1. Create feature branch
2. Make changes following coding standards
3. Test thoroughly in development environment
4. Create pull request
5. Code review and approval
6. Merge to main branch

### Code Review Checklist

- [ ] PHPCS compliance
- [ ] Security best practices
- [ ] Proper error handling
- [ ] Documentation updates
- [ ] Test coverage

## Support and Maintenance

### Documentation

- User guides in `docs/user-guide/`
- Development guides in `docs/dev-guide/`
- Troubleshooting guides in `docs/troubleshooting/`
- Architecture documentation in `docs/architecture/`

### Issue Tracking

- GitHub Issues for bug reports and feature requests
- Comprehensive troubleshooting guides
- Regular maintenance and updates

## Future Roadmap

### Planned Features

- Enhanced analytics dashboard
- QR code versioning system
- Automated migration scripts
- Health check monitoring
- Advanced customization options

### Technical Improvements

- Performance optimization
- Enhanced security measures
- Better error handling
- Improved user experience
- Comprehensive testing suite

---

**Last Updated**: August 17, 2025
**Version**: 1.2.72
**Status**: Active Development 