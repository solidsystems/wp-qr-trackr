#!/bin/bash

# Usage: ./update-release-notes.sh v1.0.2

TAG="$1"
NOTES_FILE="release-notes.txt"

cat > "$NOTES_FILE" <<EOF
Changes

- Added comprehensive post-foundation changes documentation
- Fixed PHPCS errors in main plugin file
- Improved code quality and maintainability
- Updated README with detailed feature documentation

## Technical Details

- Added proper punctuation to inline comments
- Removed trailing whitespace
- Enhanced documentation structure
- Improved code organization
EOF

gh release edit "$TAG" --notes-file "$NOTES_FILE" 