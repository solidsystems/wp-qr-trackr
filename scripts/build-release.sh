#!/bin/bash

# Exit on error
set -e

# Configuration
PLUGIN_NAME="wp-qr-trackr"
PLUGIN_DIR="plugin"
# Read version from plugin main file in restructured layout
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
mkdir -p "$BUILD_DIR/$PLUGIN_NAME" "$DIST_DIR"

# Copy essential plugin files to the correct folder structure
cp "$PLUGIN_DIR/wp-qr-trackr.php" "$BUILD_DIR/$PLUGIN_NAME/"
cp -r "$PLUGIN_DIR/includes" "$BUILD_DIR/$PLUGIN_NAME/"
cp -r "$PLUGIN_DIR/assets" "$BUILD_DIR/$PLUGIN_NAME/"
cp -r "$PLUGIN_DIR/templates" "$BUILD_DIR/$PLUGIN_NAME/"
cp LICENSE "$BUILD_DIR/$PLUGIN_NAME/"
cp "$PLUGIN_DIR/composer.json" "$BUILD_DIR/$PLUGIN_NAME/"
if [ -f "$PLUGIN_DIR/composer.lock" ]; then
  cp "$PLUGIN_DIR/composer.lock" "$BUILD_DIR/$PLUGIN_NAME/"
fi

# Install only production dependencies in the plugin directory
cd "$BUILD_DIR/$PLUGIN_NAME"
composer install --no-dev --optimize-autoloader --no-scripts 2>/dev/null || {
    print_warning "Composer install had some warnings, but continuing with build..."
}
cd - > /dev/null

# Ensure admin-page.php template is present (fallback to nested plugin path if missing)
if [ ! -f "$BUILD_DIR/$PLUGIN_NAME/templates/admin-page.php" ]; then
  if [ -f "wp-content/plugins/wp-qr-trackr/templates/admin-page.php" ]; then
    mkdir -p "$BUILD_DIR/$PLUGIN_NAME/templates"
    cp "wp-content/plugins/wp-qr-trackr/templates/admin-page.php" "$BUILD_DIR/$PLUGIN_NAME/templates/admin-page.php"
  fi
fi

# Validate required templates exist
missing_templates=()
for f in admin-page.php add-new-page.php settings-page.php; do
  if [ ! -f "$BUILD_DIR/$PLUGIN_NAME/templates/$f" ]; then
    missing_templates+=("$f")
  fi
done
if [ ${#missing_templates[@]} -ne 0 ]; then
  print_warning "Missing templates: ${missing_templates[*]}"
fi

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

# Dry run notice and guard for future publishing/tagging steps
if [ "$DRY_RUN" = "true" ]; then
  print_warning "Dry run enabled: No publishing, tagging, or pushing will be performed."
  # Place any future publishing/tagging steps below, wrapped in:
  # if [ "$DRY_RUN" != "true" ]; then ... fi
fi
