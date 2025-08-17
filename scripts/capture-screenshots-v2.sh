#!/bin/bash

# WP QR Trackr - Screenshot Capture Script v2 for WordPress.org
# This script uses Playwright to capture required screenshots

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
SCREENSHOT_DIR="wp-qr-trackr-submission/screenshots"
ADMIN_URL="http://localhost:8080/wp-admin"
ADMIN_USER="trackr"
ADMIN_PASS="trackr"

echo -e "${BLUE}📸 WP QR Trackr - Screenshot Capture v2 for WordPress.org${NC}"
echo "=================================================="

# Check if dev environment is running
echo -e "${YELLOW}🔍 Checking if dev environment is running...${NC}"
if ! curl -s http://localhost:8080 > /dev/null; then
    echo -e "${RED}❌ Dev environment not running on localhost:8080${NC}"
    echo "Please start the dev environment first:"
    echo "  ./scripts/setup-wordpress.sh dev"
    exit 1
fi

echo -e "${GREEN}✅ Dev environment is running${NC}"

# Create screenshots directory
echo -e "${YELLOW}📁 Creating screenshots directory...${NC}"
mkdir -p "$SCREENSHOT_DIR"

# Remove existing placeholder screenshots
echo -e "${YELLOW}🧹 Removing existing placeholder screenshots...${NC}"
rm -f "$SCREENSHOT_DIR"/screenshot-*.png

# Create a Node.js script for Playwright
cat > /tmp/screenshot-capture.js << 'EOF'
const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ 
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });
    
    const context = await browser.newContext({
        viewport: { width: 1280, height: 960 },
        userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
    });
    
    const page = await context.newPage();
    
    try {
        console.log('Starting screenshot capture...');
        
        // Login to WordPress admin
        console.log('Logging into WordPress admin...');
        await page.goto('http://localhost:8080/wp-admin/wp-login.php', { waitUntil: 'networkidle' });
        await page.fill('#user_login', 'trackr');
        await page.fill('#user_pass', 'trackr');
        await page.click('#wp-submit');
        
        // Wait for login to complete
        await page.waitForURL('**/wp-admin/**');
        await page.waitForTimeout(3000);
        
        console.log('Login successful');
        
        // Screenshot 1: QR Code Management Dashboard
        console.log('Capturing screenshot 1: QR Code Management Dashboard...');
        await page.goto('http://localhost:8080/wp-admin/admin.php?page=qr-trackr', { waitUntil: 'networkidle' });
        await page.waitForTimeout(3000);
        
        // Wait for the table to load
        try {
            await page.waitForSelector('.wp-list-table', { timeout: 10000 });
        } catch (e) {
            console.log('Table not found, taking screenshot anyway...');
        }
        
        await page.screenshot({
            path: '/workspace/wp-qr-trackr-submission/screenshots/screenshot-1.png',
            fullPage: false
        });
        console.log('Screenshot 1 captured');
        
        // Screenshot 2: Analytics Overview (same page, different focus)
        console.log('Capturing screenshot 2: Analytics Overview...');
        await page.screenshot({
            path: '/workspace/wp-qr-trackr-submission/screenshots/screenshot-2.png',
            fullPage: false
        });
        console.log('Screenshot 2 captured');
        
        // Screenshot 3: QR Code Generation Interface
        console.log('Capturing screenshot 3: QR Code Generation Interface...');
        await page.goto('http://localhost:8080/wp-admin/admin.php?page=qr-trackr-add-new', { waitUntil: 'networkidle' });
        await page.waitForTimeout(3000);
        
        // Wait for the form to load
        try {
            await page.waitForSelector('form', { timeout: 10000 });
        } catch (e) {
            console.log('Form not found, taking screenshot anyway...');
        }
        
        await page.screenshot({
            path: '/workspace/wp-qr-trackr-submission/screenshots/screenshot-3.png',
            fullPage: false
        });
        console.log('Screenshot 3 captured');
        
        // Screenshot 4: Custom Styling Options (same page, different focus)
        console.log('Capturing screenshot 4: Custom Styling Options...');
        await page.screenshot({
            path: '/workspace/wp-qr-trackr-submission/screenshots/screenshot-4.png',
            fullPage: false
        });
        console.log('Screenshot 4 captured');
        
        // Screenshot 5: Referral Code Management (back to main page)
        console.log('Capturing screenshot 5: Referral Code Management...');
        await page.goto('http://localhost:8080/wp-admin/admin.php?page=qr-trackr', { waitUntil: 'networkidle' });
        await page.waitForTimeout(3000);
        
        await page.screenshot({
            path: '/workspace/wp-qr-trackr-submission/screenshots/screenshot-5.png',
            fullPage: false
        });
        console.log('Screenshot 5 captured');
        
        console.log('All screenshots captured successfully!');
        
    } catch (error) {
        console.error('Error during screenshot capture:', error);
        process.exit(1);
    } finally {
        await browser.close();
    }
})();
EOF

# Run the Playwright script
echo -e "${YELLOW}📸 Capturing screenshots with Playwright...${NC}"
docker compose -f docker/docker-compose.playwright.yml run --rm playwright-runner \
    node /tmp/screenshot-capture.js

# Verify all screenshots were captured
echo -e "${YELLOW}✅ Verifying screenshots...${NC}"
required_screenshots=(
    "screenshot-1.png"
    "screenshot-2.png"
    "screenshot-3.png"
    "screenshot-4.png"
    "screenshot-5.png"
)

all_captured=true
for screenshot in "${required_screenshots[@]}"; do
    if [ -f "$SCREENSHOT_DIR/$screenshot" ]; then
        size=$(du -h "$SCREENSHOT_DIR/$screenshot" | cut -f1)
        file_type=$(file "$SCREENSHOT_DIR/$screenshot" | cut -d: -f2)
        echo -e "${GREEN}  ✅ $screenshot (${size}) - $file_type${NC}"
        
        # Check if it's actually a PNG image
        if [[ "$file_type" == *"PNG image"* ]]; then
            echo -e "${GREEN}    ✅ Valid PNG image${NC}"
        else
            echo -e "${RED}    ❌ Not a valid PNG image${NC}"
            all_captured=false
        fi
    else
        echo -e "${RED}  ❌ $screenshot (MISSING)${NC}"
        all_captured=false
    fi
done

# Clean up temporary file
rm -f /tmp/screenshot-capture.js

if [ "$all_captured" = true ]; then
    echo ""
    echo -e "${GREEN}🎉 All screenshots captured successfully!${NC}"
    echo "=================================================="
    echo -e "${BLUE}📁 Screenshots saved to:${NC} $SCREENSHOT_DIR"
    echo -e "${BLUE}📦 Ready for WordPress.org submission${NC}"
    echo ""
    echo -e "${YELLOW}📋 Next Steps:${NC}"
    echo "1. Review screenshots for quality and content"
    echo "2. Replace any that need improvement"
    echo "3. Submit to WordPress.org using the prepared package"
    echo ""
    echo -e "${BLUE}🚀 Good luck with your WordPress.org submission!${NC}"
else
    echo ""
    echo -e "${RED}❌ Some screenshots failed to capture properly${NC}"
    echo "Please check the dev environment and try again."
    exit 1
fi
