This PR updates the QR Trackr plugin to version 1.0.1.

- Generates QR codes locally using endroid/qr-code
- No external services used for QR code generation or download
- All QR code images are served from the local uploads directory
- Maintains all tracking and admin features

Closes #1 if applicable.

## Documentation Updates & CI/CD Fixes

### 🔧 **Critical CI/CD Path Fix**
- **RESOLVED**: Fixed GitHub Actions workflow error causing `phpcs: No such file or directory`
- **Root cause**: Incorrect relative path from plugin directory to phpcs binary
- **Solution**: Updated path from `../../vendor/bin/phpcs` to `../../../vendor/bin/phpcs`
- **Additional improvements**:
  - Corrected phpcs config file path to `../../../.phpcs.xml`
  - Added `--extensions=php` flag to prevent memory issues with large JS files
  - Fixed both main directory and tests directory scanning commands

### 📝 **Documentation Harmonization**
- **README.md**: Eliminated duplicate content and improved consistency across sections
- **TROUBLESHOOTING.md**: Added comprehensive CI/CD troubleshooting section including:
  - Directory structure diagram showing path relationships
  - Step-by-step explanation of the path resolution issue
  - Complete workflow example with correct paths
  - Memory optimization and configuration notes

### 🚀 **Impact**
- GitHub Actions CI pipeline will now run successfully without path errors
- Improved developer experience with better troubleshooting documentation
- Consistent tone and structure across all documentation files
- Reduced friction for contributors encountering CI issues

### ✅ **Testing Verified**
- PHPCS binary path resolution works correctly
- Configuration file path resolution confirmed
- Both main and tests directory scanning functional
- Memory issues resolved with PHP-only file processing 