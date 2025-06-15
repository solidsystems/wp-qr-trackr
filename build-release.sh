#!/bin/bash

# Exit on error
set -e

# Configuration
PLUGIN_NAME="wp-qr-trackr"
VERSION=$(grep -E '^[[:space:]]*\*[[:space:]]*Version:' wp-content/plugins/wp-qr-trackr/wp-qr-trackr.php | awk -F'Version:' '{gsub(/^[ \t]+|[ \t]+$/, "", $2); print $2}')
BUILD_DIR="build"
DIST_DIR="dist"
ZIP_NAME="${PLUGIN_NAME}-v${VERSION}.zip"

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
cleanup() {
    print_status "Cleaning up previous builds..."
    rm -rf "$BUILD_DIR" "$DIST_DIR"
    rm -f "${PLUGIN_NAME}-v"*.zip
}

# Create build directory
create_build_dir() {
    print_status "Creating build directory..."
    mkdir -p "$BUILD_DIR"
}

# Copy files to build directory
copy_files() {
    print_status "Copying files to build directory..."
    
    # Read .distignore and create exclusion list
    if [ -f .distignore ]; then
        EXCLUDES=$(cat .distignore | grep -v '^#' | grep -v '^$' | sed 's/^/--exclude=/')
    else
        EXCLUDES=""
    fi
    
    # Copy files while respecting .distignore
    rsync -av --delete $EXCLUDES \
        --exclude='.git*' \
        --exclude='build-release.sh' \
        --exclude='build' \
        --exclude='dist' \
        --exclude='node_modules' \
        --exclude='vendor' \
        --exclude='*.zip' \
        ./ "$BUILD_DIR/$PLUGIN_NAME/"
}

# Create zip file
create_zip() {
    print_status "Creating zip file..."
    mkdir -p "$DIST_DIR"
    cd "$BUILD_DIR"
    zip -r "../$DIST_DIR/$ZIP_NAME" "$PLUGIN_NAME"
    cd ..
}

# Main execution
main() {
    print_status "Starting build process for version $VERSION..."
    
    cleanup
    create_build_dir
    copy_files
    create_zip
    
    print_status "Build completed successfully!"
    print_status "Zip file created at: $DIST_DIR/$ZIP_NAME"
}

# Run main function
main 