#!/bin/bash

# Exit on error
set -e

# Configuration
PLUGIN_NAME="wp-qr-trackr"
PLUGIN_DIR="."
VERSION=$(grep -E '^[[:space:]]*\*[[:space:]]*Version:' "wp-qr-trackr.php" | awk -F'Version:' '{gsub(/^[ \t]+|[ \t]+$/, "", $2); print $2}')
BUILD_DIR="build"
DIST_DIR="dist"
ZIP_NAME="$DIST_DIR/$PLUGIN_NAME-v$VERSION.zip"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Print with color
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Clean up previous builds
rm -rf "$BUILD_DIR" "$DIST_DIR"
mkdir -p "$BUILD_DIR/$PLUGIN_NAME" "$DIST_DIR"

# Copy essential plugin files to the correct folder structure
cp wp-qr-trackr.php "$BUILD_DIR/$PLUGIN_NAME/"
cp -r includes "$BUILD_DIR/$PLUGIN_NAME/"
cp -r assets "$BUILD_DIR/$PLUGIN_NAME/"
cp LICENSE "$BUILD_DIR/$PLUGIN_NAME/"
cp composer.json "$BUILD_DIR/$PLUGIN_NAME/"
if [ -f composer.lock ]; then
  cp composer.lock "$BUILD_DIR/$PLUGIN_NAME/"
fi

# Install only production dependencies in the plugin directory
cd "$BUILD_DIR/$PLUGIN_NAME"
composer install --no-dev --optimize-autoloader --no-scripts 2>/dev/null || {
    print_warning "Composer install had some warnings, but continuing with build..."
}
cd - > /dev/null

# Remove any leftover dev files from vendor
find "$BUILD_DIR/$PLUGIN_NAME/vendor" -type d -name 'tests' -o -name 'test' | xargs rm -rf 2>/dev/null || true
find "$BUILD_DIR/$PLUGIN_NAME/vendor" -name '*.md' -o -name 'phpunit*' -o -name '.phpcs*' -o -name 'phpcs*' | xargs rm -f 2>/dev/null || true

# Create zip with proper folder structure 
cd "$BUILD_DIR" && zip -r "../$ZIP_NAME" "$PLUGIN_NAME/"
cd - > /dev/null

# Info
if [ -f "$ZIP_NAME" ]; then
  print_status "Production build completed successfully!"
  print_status "Zip file created at: $ZIP_NAME"
else
  print_error "Build failed. Zip file not found."
fi 