#!/bin/bash

# Exit on error
set -e

# Configuration
PLUGIN_NAME="wp-qr-trackr"
PLUGIN_DIR="wp-content/plugins/wp-qr-trackr"
VERSION=$(grep -E '^[[:space:]]*\*[[:space:]]*Version:' "$PLUGIN_DIR/wp-qr-trackr.php" | awk -F'Version:' '{gsub(/^[ \t]+|[ \t]+$/, "", $2); print $2}')
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
mkdir -p "$BUILD_DIR" "$DIST_DIR"

# Copy plugin files, excluding unnecessary files and folders, and vendor directory
rsync -av --exclude='.git' \
  --exclude='.github' \
  --exclude='node_modules' \
  --exclude='vendor' \
  --exclude='tests' \
  --exclude='test' \
  --exclude='docs' \
  --exclude='*.md' \
  --exclude='phpunit*' \
  --exclude='phpcs*' \
  --exclude='composer.lock' \
  --exclude='package.json' \
  --exclude='yarn.lock' \
  --exclude='build-release.sh' \
  --exclude='README.*' \
  --exclude='CHANGELOG.*' \
  "$PLUGIN_DIR/" "$BUILD_DIR/"

# Copy composer.json and composer.lock to build dir for dependency install
cp composer.json "$BUILD_DIR/"
if [ -f composer.lock ]; then
  cp composer.lock "$BUILD_DIR/"
fi

# Install only production dependencies in the build directory
cd "$BUILD_DIR"
composer install --no-dev --optimize-autoloader
cd -

# Remove any leftover dev files from vendor
find "$BUILD_DIR/vendor" -type d -name 'tests' -o -name 'test' | xargs rm -rf
find "$BUILD_DIR/vendor" -type f -name '*.md' -delete
find "$BUILD_DIR/vendor" -type f -name '*.dist' -delete

# Zip the build
cd "$BUILD_DIR" && zip -r "../$ZIP_NAME" .
cd - > /dev/null

# Info
if [ -f "$ZIP_NAME" ]; then
  echo "[INFO] Production build completed successfully!"
  echo "[INFO] Zip file created at: $ZIP_NAME"
else
  echo "[ERROR] Build failed. Zip file not found."
fi 