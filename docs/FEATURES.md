# WP QR Trackr Features Documentation

## Overview

WP QR Trackr v1.2.20+ includes comprehensive QR code management features with a modern, modal-based interface, advanced search capabilities, and integrated debugging tools.

## Core Features

### QR Code Generation & Management

#### Basic QR Code Creation
- **Multi-source QR codes**: Create QR codes for WordPress posts, pages, or custom URLs
- **Automatic code generation**: Unique alphanumeric tracking codes generated automatically
- **URL validation**: Comprehensive validation for custom URLs with proper error handling
- **Immediate image generation**: QR code images generated and stored during creation

#### Enhanced QR Code Fields
- **Common Name**: User-friendly names for easy QR code identification and organization
- **Referral Code**: Custom codes for marketing campaigns and tracking purposes
- **Validation**: Real-time validation with format checking (alphanumeric, hyphens, underscores)
- **Uniqueness**: Automatic checking to prevent duplicate referral codes

### Modal-Based Admin Interface

#### Clickable QR Management
- **Interactive QR Images**: Click any QR code image in the admin list to open management modal
- **Real-Time Editing**: Edit common names and referral codes with instant feedback
- **AJAX Updates**: Changes saved immediately without page reload
- **Error Handling**: Comprehensive validation with user-friendly error messages

#### Modal Features
- **Mobile-Responsive**: Touch-friendly design optimized for all device sizes
- **ESC Key Support**: Close modals with keyboard shortcuts
- **Outside Click**: Click outside modal to close
- **Loading States**: Visual feedback during save operations
- **Success/Error Messages**: Clear feedback for all operations

#### Statistics Integration
- **Recent Scan Counts**: View 30-day scan statistics directly in modals
- **Live Updates**: Scan counts update in real-time after successful saves
- **Performance Metrics**: Quick access to QR code performance data

### Advanced Search & Filter System

#### Global Search Capabilities
- **Multi-field Search**: Search across common names, referral codes, QR codes, and destination URLs
- **Real-time Results**: Instant search results with debounced input
- **Cache Optimization**: Search-aware caching for improved performance
- **Highlighting**: Search terms highlighted in results (when applicable)

#### Filter System
- **Referral Code Filter**: Dropdown filter to quickly find QR codes by referral code
- **Combined Filtering**: Use search and filters together for precise results
- **URL Parameters**: Search and filter state preserved in URLs
- **Reset Functionality**: Easy reset to show all QR codes

#### Pagination & Organization
- **15 Items Per Page**: Optimized pagination for better performance
- **Smart Caching**: Cache keys include search and filter parameters
- **Efficient Queries**: Database queries optimized with proper indexing

### Debug Mode Integration

#### System Diagnostics
- **Database Health**: Check table existence, field verification, and data integrity
- **Rewrite Rules**: Validate registration status and pattern inspection
- **File System**: Check upload directory permissions and QR image counts
- **WordPress Info**: Display WordPress version, PHP version, and plugin status

#### Live Testing Tools
- **QR Generation Testing**: Test QR code generation with visual preview
- **Redirect Testing**: Validate QR code redirects and tracking
- **Rewrite Rule Testing**: Test custom URL patterns and query variables
- **Error Logging**: Comprehensive debug logging with clear messages

#### Troubleshooting Features
- **Force Flush**: Manual rewrite rule flushing for troubleshooting
- **Cache Management**: Clear and verify cache operations
- **Configuration Validation**: Check plugin settings and WordPress configuration
- **Debug Mode Toggle**: Enable/disable debug features without WP_DEBUG

#### Debug Page Access
- **Automatic Detection**: Debug menu appears when WP_DEBUG is enabled
- **Manual Override**: "Force Debug Mode" setting in plugin options
- **Security**: Debug features restricted to administrators only
- **Documentation**: Built-in help text and troubleshooting guidance

### Performance & Caching

#### Database Optimization
- **Proper Indexing**: Indexes on common_name and referral_code fields
- **Efficient Queries**: Optimized WHERE clauses with proper placeholders
- **Automatic Migrations**: Version-based database upgrades
- **Cache Invalidation**: Smart cache clearing after data changes

#### Caching Strategy
- **Search-Aware Caching**: Cache keys include search and filter parameters
- **Multi-Level Caching**: WordPress object cache with fallbacks
- **Intelligent Expiration**: Appropriate timeouts for different data types
- **Performance Monitoring**: Built-in performance tracking in debug mode

### Security Features

#### Input Validation
- **Comprehensive Sanitization**: All user input properly sanitized
- **WordPress Standards**: Uses WordPress sanitization functions
- **Nonce Verification**: CSRF protection for all form submissions and AJAX
- **Capability Checks**: Proper user permission verification

#### Output Escaping
- **Context-Aware Escaping**: Appropriate escaping for HTML, URLs, and attributes
- **Translation Safety**: Safe handling of translated strings
- **JavaScript Security**: Proper escaping for JavaScript variables
- **SQL Security**: Prepared statements for all database queries

### User Experience Enhancements

#### Interface Improvements
- **Clean UI**: Removed duplicate QR code images for better clarity
- **Consistent Design**: WordPress admin standards compliance
- **Mobile-First**: Responsive design for all screen sizes
- **Accessibility**: Proper ARIA labels and keyboard navigation

#### Error Handling
- **User-Friendly Messages**: Clear, actionable error messages
- **Progressive Enhancement**: Graceful fallbacks for JavaScript failures
- **Validation Feedback**: Real-time validation with helpful suggestions
- **Recovery Options**: Clear paths to resolve errors

### Technical Features

#### Modular Architecture
- **Separation of Concerns**: Distinct modules for admin, AJAX, rewrite, and utilities
- **Clean Code**: WordPress coding standards compliance
- **Extensibility**: Hooks and filters for customization
- **Maintainability**: Clear documentation and consistent patterns

#### Error Handling & Logging
- **Comprehensive Logging**: Debug logging throughout all modules
- **Error Recovery**: Graceful handling of failures
- **Performance Monitoring**: Track database queries and response times
- **Development Tools**: Enhanced debugging for developers

## Version History

### v1.2.20 (Current)
- Fixed critical rewrite rule timing issues
- Cleaned up duplicate QR images in admin interface
- Enhanced error handling and logging

### v1.2.19
- Removed duplicate QR code images from list table
- Improved admin interface clarity
- Streamlined QR Code column display

### v1.2.18
- Added modal-based QR code management system
- Introduced common name and referral code fields
- Implemented search and filter capabilities
- Enhanced admin interface with modern design

### v1.2.14 - v1.2.17
- Introduced comprehensive debug mode
- Added system diagnostics and troubleshooting tools
- Fixed query variable registration issues
- Improved rewrite rule handling

## Future Enhancements

### Planned Features
- **Bulk Operations**: Select and modify multiple QR codes at once
- **Advanced Analytics**: Detailed scan analytics with charts and graphs
- **Export/Import**: Backup and restore QR code data
- **API Integration**: REST API endpoints for external integrations
- **Custom Fields**: Additional user-defined fields for QR codes

### Performance Improvements
- **Background Processing**: Move heavy operations to background tasks
- **CDN Integration**: Automatic CDN support for QR code images
- **Advanced Caching**: Redis/Memcached support for large installations
- **Database Optimization**: Query optimization for high-volume sites

## Support & Documentation

### Getting Help
- Check the Debug page for system diagnostics
- Review the troubleshooting guide in docs/TROUBLESHOOTING.md
- Search existing issues on GitHub
- Create new issues with detailed information

### Best Practices
- Enable debug mode during development and troubleshooting
- Use descriptive common names for better organization
- Implement consistent referral code conventions
- Regular database backups before major updates
- Test QR codes after plugin updates

## Migration Notes

### Upgrading from Earlier Versions
- Database migrations run automatically during plugin updates
- No manual intervention required for schema changes
- Existing QR codes remain fully functional
- New fields are added with default values

### Compatibility
- WordPress 5.0+ required
- PHP 7.4+ required
- MySQL 5.6+ or MariaDB 10.0+ recommended
- Works with most WordPress themes and plugins 