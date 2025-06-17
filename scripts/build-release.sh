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

if ! command -v rsync >/dev/null 2>&1; then
  echo "Error: rsync is not installed. Please install rsync and try again."
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

# Use rsync with .distignore as exclude list to copy only needed files
# .distignore should list all files/directories to exclude from the release (like .gitignore)
# Example .distignore entries: .git/ node_modules/ tests/ .DS_Store *.md

# Copy plugin files using rsync and .distignore
# The --delete flag ensures the release dir is clean
rsync -av --delete --exclude-from="$DISTIGNORE" "$PLUGIN_DIR/" "$RELEASE_DIR/"

# Always include root-level README.md and composer.json in the release (required for verification and best practice)
cp README.md "$RELEASE_DIR/" 2>/dev/null || true
cp composer.json "$RELEASE_DIR/" 2>/dev/null || true

# Copy composer.lock for reproducible installs
cp "$PLUGIN_DIR/composer.lock" "$RELEASE_DIR/" 2>/dev/null || true

# Install production dependencies in the release dir
if [ -f "$RELEASE_DIR/composer.json" ]; then
  echo "Installing production dependencies in release dir..."
  (cd "$RELEASE_DIR" && composer install --no-dev --optimize-autoloader)
fi

# Remove dev-only files from vendor if any slipped through
find "$RELEASE_DIR/vendor" -type d -name "tests" -prune -exec rm -rf '{}' + 2>/dev/null || true
find "$RELEASE_DIR/vendor" -type d -name "test" -prune -exec rm -rf '{}' + 2>/dev/null || true

# Create zip file
VERSION=$(grep -E '^\s*\*?\s*Version:\s*[0-9]+\.[0-9]+\.[0-9]+(-rc[0-9]+)?' "$RELEASE_DIR/wp-qr-trackr.php" | head -1 | sed -E 's/.*Version:\s*([0-9]+\.[0-9]+\.[0-9]+(-rc[0-9]+)?).*/\1/')
ZIP_FILE="wp-qr-trackr-v$VERSION.zip"
zip -r "$ZIP_FILE" "$RELEASE_DIR"
echo "Release zip created: $ZIP_FILE"

# Automated release verification
VERIFY_DIR="verify-release-tmp"
rm -rf "$VERIFY_DIR"
mkdir "$VERIFY_DIR"
unzip -q "$ZIP_FILE" -d "$VERIFY_DIR"
PLUGIN_VERIFY_DIR="$VERIFY_DIR/wp-qr-trackr"

# Required files/directories
REQUIRED=(
  "wp-qr-trackr.php"
  "qr-trackr.php"
  "README.md"
  "composer.json"
  "vendor"
  "includes"
  "assets"
)

echo "\nVerifying release contents..."
FAIL=0
for f in "${REQUIRED[@]}"; do
  if [ ! -e "$PLUGIN_VERIFY_DIR/$f" ]; then
    echo "ERROR: Required file or directory missing: $f"
    FAIL=1
  fi
done

# Forbidden files/directories
FORBIDDEN=(
  ".git"
  ".env"
  ".env.example"
  "node_modules"
  "tests"
  ".DS_Store"
  "scripts"
  "docker-compose.yml"
  "php.ini"
)

for f in "${FORBIDDEN[@]}"; do
  if find "$PLUGIN_VERIFY_DIR" -name "$f" | grep -q .; then
    echo "ERROR: Forbidden file or directory found in release: $f"
    FAIL=1
  fi
done

if [ "$FAIL" -eq 0 ]; then
  echo "Release verification PASSED."
else
  echo "Release verification FAILED."
  rm -rf "$VERIFY_DIR"
  exit 2
fi

# Clean up
rm -rf "$VERIFY_DIR"

# Cleanup
rm -rf "$RELEASE_DIR"

# Notes for maintainers:
# - To add or remove files from the release, edit .distignore (see README.dev.md for details)
# - This script uses rsync for speed and reliability; .distignore is the single source of truth for exclusions
# - Only production dependencies are included; dev/test files are pruned 