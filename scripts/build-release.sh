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

if [ $# -lt 1 ]; then
  echo "Usage: $0 [major|minor|patch|prerelease [type] N]"
  exit 1
fi

BUMP_TYPE="$1"

# Extract current version from plugin file
CURRENT_VERSION=$(grep "Version:" "$PLUGIN_FILE" | head -1 | sed 's/.*Version: *//' | sed 's/ .*//')
if [ -z "$CURRENT_VERSION" ]; then
  echo "Error: Could not find current version in $PLUGIN_FILE"
  exit 1
fi

if [ "$BUMP_TYPE" = "prerelease" ]; then
  # Usage: prerelease [type] N
  if [ $# -eq 2 ]; then
    PR_TYPE="rc"
    PR_NUM="$2"
  elif [ $# -eq 3 ]; then
    PR_TYPE="$2"
    PR_NUM="$3"
    if [[ ! "$PR_TYPE" =~ ^(rc|beta|alpha)$ ]]; then
      echo "Error: prerelease type must be 'rc', 'beta', or 'alpha'"
      exit 1
    fi
  else
    echo "Usage: $0 prerelease [type] N (where type is rc, beta, or alpha; N is the prerelease number)"
    exit 1
  fi
  NEW_VERSION="${CURRENT_VERSION}-${PR_TYPE}${PR_NUM}"
else
  IFS='.' read -r MAJOR MINOR PATCH <<< "$CURRENT_VERSION"
  case "$BUMP_TYPE" in
    major)
      MAJOR=$((MAJOR+1)); MINOR=0; PATCH=0;;
    minor)
      MINOR=$((MINOR+1)); PATCH=0;;
    patch)
      PATCH=$((PATCH+1));;
    *)
      echo "Usage: $0 [major|minor|patch|prerelease [type] N]"; exit 1;;
  esac
  NEW_VERSION="$MAJOR.$MINOR.$PATCH"
fi

# Update version in plugin file
sed -i.bak "s/Version: $CURRENT_VERSION/Version: $NEW_VERSION/" "$PLUGIN_FILE"
rm "$PLUGIN_FILE.bak"

echo "Bumped version: $CURRENT_VERSION -> $NEW_VERSION"

# Update CHANGELOG.md
if [ ! -f "$CHANGELOG" ]; then
  touch "$CHANGELOG"
fi
DATE=$(date +%Y-%m-%d)
CHANGELOG_ENTRY="\n## [$NEW_VERSION] - $DATE\n- Release $NEW_VERSION\n"
sed -i.bak "1s;^;$CHANGELOG_ENTRY\n;" "$CHANGELOG"
rm "$CHANGELOG.bak"

echo "Updated $CHANGELOG with new version entry."

# Use rsync to copy only the files not ignored by .distignore to a temp dir
TMP_DIR=$(mktemp -d)
rsync -av --exclude-from="$DISTIGNORE" "$PLUGIN_DIR/" "$TMP_DIR/wp-qr-trackr/"

# Zip the result
ZIP_NAME="wp-qr-trackr-v$NEW_VERSION.zip"
cd "$TMP_DIR"
zip -r "$OLDPWD/$ZIP_NAME" wp-qr-trackr
cd "$OLDPWD"

# Clean up
echo "Release zip created: $ZIP_NAME"
rm -rf "$TMP_DIR" 