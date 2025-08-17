#!/bin/bash

# WP QR Trackr - WordPress.org Submission Preparation Script
# This script prepares the plugin for submission to WordPress.org

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PLUGIN_NAME="wp-qr-trackr"
PLUGIN_VERSION="1.2.47"
SUBMISSION_DIR="wp-qr-trackr-submission"
PLUGIN_DIR="plugin"

echo -e "${BLUE}🚀 WP QR Trackr - WordPress.org Submission Preparation${NC}"
echo "=================================================="

# Check if we're in the right directory
if [ ! -d "$PLUGIN_DIR" ]; then
    echo -e "${RED}❌ Error: $PLUGIN_DIR directory not found.${NC}"
    echo "Please run this script from the project root directory."
    exit 1
fi

# Create submission directory
echo -e "${YELLOW}📁 Creating submission directory...${NC}"
if [ -d "$SUBMISSION_DIR" ]; then
    echo -e "${YELLOW}⚠️  Submission directory already exists. Removing...${NC}"
    rm -rf "$SUBMISSION_DIR"
fi
mkdir -p "$SUBMISSION_DIR"

# Copy plugin files
echo -e "${YELLOW}📋 Copying plugin files...${NC}"
cp -r "$PLUGIN_DIR"/* "$SUBMISSION_DIR/"

# Remove development files
echo -e "${YELLOW}🧹 Removing development files...${NC}"
cd "$SUBMISSION_DIR"

# Remove vendor directory (dependencies)
if [ -d "vendor" ]; then
    echo "  - Removing vendor directory"
    rm -rf vendor/
fi

# Remove composer files
if [ -f "composer.json" ]; then
    echo "  - Removing composer.json"
    rm -f composer.json
fi

if [ -f "composer.lock" ]; then
    echo "  - Removing composer.lock"
    rm -f composer.lock
fi

# Remove any git directories
find . -name ".git" -type d -exec rm -rf {} + 2>/dev/null || true

# Remove any development scripts
find . -name "*.sh" -type f -delete 2>/dev/null || true

# Remove any test files
find . -name "*test*" -type f -delete 2>/dev/null || true
find . -name "*Test*" -type f -delete 2>/dev/null || true

# Remove any documentation files (except readme.txt)
find . -name "*.md" -type f ! -name "readme.txt" -delete 2>/dev/null || true

# Remove any IDE files
find . -name ".vscode" -type d -exec rm -rf {} + 2>/dev/null || true
find . -name ".idea" -type d -exec rm -rf {} + 2>/dev/null || true

# Create screenshots directory
echo -e "${YELLOW}📸 Creating screenshots directory...${NC}"
mkdir -p screenshots

# Create placeholder screenshots (you'll need to replace these with actual screenshots)
echo -e "${YELLOW}📝 Creating placeholder screenshot files...${NC}"
for i in {1..5}; do
    if [ ! -f "screenshots/screenshot-$i.png" ]; then
        echo "  - Creating placeholder: screenshots/screenshot-$i.png"
        # Create a simple placeholder (you should replace these with actual screenshots)
        echo "Placeholder screenshot $i - Replace with actual 1280x960px PNG screenshot" > "screenshots/screenshot-$i.png"
    fi
done

# Verify required files
echo -e "${YELLOW}✅ Verifying required files...${NC}"
required_files=(
    "wp-qr-trackr.php"
    "readme.txt"
    "license.txt"
)

for file in "${required_files[@]}"; do
    if [ -f "$file" ]; then
        echo -e "${GREEN}  ✅ $file${NC}"
    else
        echo -e "${RED}  ❌ $file (MISSING)${NC}"
    fi
done

# Check file structure
echo -e "${YELLOW}📂 Checking file structure...${NC}"
if [ -d "includes" ]; then
    echo -e "${GREEN}  ✅ includes/ directory${NC}"
else
    echo -e "${RED}  ❌ includes/ directory (MISSING)${NC}"
fi

if [ -d "templates" ]; then
    echo -e "${GREEN}  ✅ templates/ directory${NC}"
else
    echo -e "${RED}  ❌ templates/ directory (MISSING)${NC}"
fi

if [ -d "assets" ]; then
    echo -e "${GREEN}  ✅ assets/ directory${NC}"
else
    echo -e "${RED}  ❌ assets/ directory (MISSING)${NC}"
fi

# Create ZIP file
echo -e "${YELLOW}📦 Creating submission ZIP file...${NC}"
cd ..
zip_file="${PLUGIN_NAME}-${PLUGIN_VERSION}-wordpress-org.zip"
if [ -f "$zip_file" ]; then
    echo -e "${YELLOW}⚠️  ZIP file already exists. Removing...${NC}"
    rm -f "$zip_file"
fi

zip -r "$zip_file" "$SUBMISSION_DIR" -x "*.DS_Store" "*/.*"

# Verify ZIP file
if [ -f "$zip_file" ]; then
    zip_size=$(du -h "$zip_file" | cut -f1)
    echo -e "${GREEN}✅ ZIP file created: $zip_file (${zip_size})${NC}"
else
    echo -e "${RED}❌ Failed to create ZIP file${NC}"
    exit 1
fi

# Display submission information
echo ""
echo -e "${GREEN}🎉 WordPress.org Submission Package Ready!${NC}"
echo "=================================================="
echo -e "${BLUE}📁 Submission Directory:${NC} $SUBMISSION_DIR"
echo -e "${BLUE}📦 ZIP File:${NC} $zip_file"
echo -e "${BLUE}📋 Plugin Version:${NC} $PLUGIN_VERSION"
echo ""
echo -e "${YELLOW}📋 Next Steps:${NC}"
echo "1. Replace placeholder screenshots with actual 1280x960px PNG files"
echo "2. Test the plugin in a clean WordPress installation"
echo "3. Create WordPress.org account at https://wordpress.org/support/register.php"
echo "4. Request plugin repository access from plugins@wordpress.org"
echo "5. Submit the ZIP file at https://wordpress.org/plugins/developers/add/"
echo ""
echo -e "${YELLOW}⚠️  Important Notes:${NC}"
echo "- All features must be free (no premium features)"
echo "- No external service dependencies"
echo "- Must follow WordPress coding standards"
echo "- Review process takes 2-4 weeks"
echo ""
echo -e "${GREEN}📚 Documentation:${NC}"
echo "- WordPress.org Submission Guide: docs/development/WORDPRESS_ORG_SUBMISSION.md"
echo "- Plugin Handbook: https://developer.wordpress.org/plugins/"
echo "- Coding Standards: https://make.wordpress.org/core/handbook/best-practices/coding-standards/"
echo ""
echo -e "${BLUE}🚀 Good luck with your submission!${NC}"
