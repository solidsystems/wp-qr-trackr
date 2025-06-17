#!/bin/bash
# build-release.sh
# Usage:
#   ./build-release.sh major                # Bump major version, update changelog, build release zip
#   ./build-release.sh minor                # Bump minor version, update changelog, build release zip
#   ./build-release.sh patch                # Bump patch version, update changelog, build release zip
#   ./build-release.sh prerelease [type] N  # Create prerelease (betaN, alphaN, rcN) for current version, update changelog, build release zip
#     [type] can be 'rc' (default), 'beta', or 'alpha'
# Outputs wp-qr-trackr-vX.Y.Z[-typeN].zip in the project root.

set -e

PLUGIN_DIR="wp-content/plugins/wp-qr-trackr"
PLUGIN_FILE="$PLUGIN_DIR/qr-trackr.php"
CHANGELOG="CHANGELOG.md"
DISTIGNORE=".distignore"

if ! command -v zip >/dev/null 2>&1; then
  echo "Error: zip is not installed. Please install zip and try again."
  exit 1
fi

if [ ! -f "$DISTIGNORE" ]; then
  echo "Error: .distignore file not found."
  exit 1
fi

if [ ! -f "$PLUGIN_FILE" ]; then
  echo "Error: Main plugin file not found: $PLUGIN_FILE"
  exit 1
fi

# Function to display usage
usage() {
    echo "Usage: $0 [major|minor|patch|prerelease [type] N]"
    exit 1
}

# Function to bump version
bump_version() {
    local version_file="wp-content/plugins/wp-qr-trackr/wp-qr-trackr.php"
    local current_version=$(grep -E '^\s*\*?\s*Version:\s*[0-9]+\.[0-9]+\.[0-9]+(-rc[0-9]+)?' "$version_file" | head -1 | sed -E 's/.*Version:\s*([0-9]+\.[0-9]+\.[0-9]+(-rc[0-9]+)?).*/\1/')
    local new_version=""

    case "$1" in
        major)
            new_version=$(echo "$current_version" | awk -F. '{$1++; $2=0; $3=0; print $1"."$2"."$3}')
            ;;
        minor)
            new_version=$(echo "$current_version" | awk -F. '{$2++; $3=0; print $1"."$2"."$3}')
            ;;
        patch)
            new_version=$(echo "$current_version" | awk -F. '{$3++; print $1"."$2"."$3}')
            ;;
        prerelease)
            if [ -z "$2" ] || [ -z "$3" ]; then
                usage
            fi
            new_version="${current_version}-${2}${3}"
            ;;
        *)
            usage
            ;;
    esac

    # Update version in plugin file
    sed -i '' -E "s/(Version:).*/\1 $new_version/" "$version_file"
    echo "Bumped version: $current_version -> $new_version"

    # Update CHANGELOG.md
    local today=$(date +%Y-%m-%d)
    local changelog_entry="## [$new_version] - $today\n\n### Changed\n- Release $new_version\n\n"
    sed -i '' "3i\\
$changelog_entry" CHANGELOG.md
    echo "Updated CHANGELOG.md with new version entry."
}

# Main script
if [ $# -lt 1 ]; then
    usage
fi

# Bump version
bump_version "$@"

# Create release directory
RELEASE_DIR="wp-qr-trackr"
rm -rf "$RELEASE_DIR"
mkdir -p "$RELEASE_DIR"

# Copy essential files
echo "Copying essential files..."
cp wp-content/plugins/wp-qr-trackr/wp-qr-trackr.php "$RELEASE_DIR/"
cp wp-content/plugins/wp-qr-trackr/qr-trackr.php "$RELEASE_DIR/"
cp wp-content/plugins/wp-qr-trackr/README.md "$RELEASE_DIR/"
cp wp-content/plugins/wp-qr-trackr/composer.json "$RELEASE_DIR/"

# Copy core directories
echo "Copying core directories..."
cp -r wp-content/plugins/wp-qr-trackr/includes "$RELEASE_DIR/"
cp -r wp-content/plugins/wp-qr-trackr/assets "$RELEASE_DIR/"

# Install production dependencies
echo "Installing production dependencies..."
cd "$RELEASE_DIR"
composer install --no-dev --optimize-autoloader
cd ..

# Create zip file
VERSION=$(grep -E '^\s*\*?\s*Version:\s*[0-9]+\.[0-9]+\.[0-9]+(-rc[0-9]+)?' "$RELEASE_DIR/wp-qr-trackr.php" | head -1 | sed -E 's/.*Version:\s*([0-9]+\.[0-9]+\.[0-9]+(-rc[0-9]+)?).*/\1/')
zip -r "wp-qr-trackr-v$VERSION.zip" "$RELEASE_DIR"
echo "Release zip created: wp-qr-trackr-v$VERSION.zip"

# Cleanup
rm -rf "$RELEASE_DIR" 