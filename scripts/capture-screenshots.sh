#!/bin/bash

# WP QR Trackr - Screenshot Capture Script for WordPress.org
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
PLUGIN_SLUG="wp-qr-trackr"

echo -e "${BLUE}📸 WP QR Trackr - Screenshot Capture for WordPress.org${NC}"
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

# Function to capture screenshot
capture_screenshot() {
    local name="$1"
    local url="$2"
    local selector="$3"
    local description="$4"
    
    echo -e "${YELLOW}📸 Capturing: $description${NC}"
    
    # Use Playwright to capture screenshot
    docker compose -f docker/docker-compose.playwright.yml run --rm playwright-runner \
        node -e "
        const { chromium } = require('playwright');
        
        (async () => {
            const browser = await chromium.launch({ headless: true });
            const context = await browser.newContext({
                viewport: { width: 1280, height: 960 }
            });
            const page = await context.newPage();
            
            try {
                // Navigate to the page
                await page.goto('$url', { waitUntil: 'networkidle' });
                
                // Wait for the page to load
                await page.waitForTimeout(2000);
                
                // Take screenshot
                await page.screenshot({
                    path: '/workspace/$SCREENSHOT_DIR/$name.png',
                    fullPage: false
                });
                
                console.log('Screenshot captured: $name.png');
            } catch (error) {
                console.error('Error capturing screenshot:', error);
                process.exit(1);
            } finally {
                await browser.close();
            }
        })();
        "
    
    if [ -f "$SCREENSHOT_DIR/$name.png" ]; then
        echo -e "${GREEN}✅ Screenshot saved: $name.png${NC}"
    else
        echo -e "${RED}❌ Failed to capture: $name.png${NC}"
        exit 1
    fi
}

# Function to login and capture admin screenshots
capture_admin_screenshot() {
    local name="$1"
    local path="$2"
    local selector="$3"
    local description="$4"
    
    echo -e "${YELLOW}📸 Capturing: $description${NC}"
    
    # Use Playwright to login and capture screenshot
    docker compose -f docker/docker-compose.playwright.yml run --rm playwright-runner \
        node -e "
        const { chromium } = require('playwright');
        
        (async () => {
            const browser = await chromium.launch({ headless: true });
            const context = await browser.newContext({
                viewport: { width: 1280, height: 960 }
            });
            const page = await context.newPage();
            
            try {
                // Login to WordPress admin
                await page.goto('$ADMIN_URL/wp-login.php');
                await page.fill('#user_login', '$ADMIN_USER');
                await page.fill('#user_pass', '$ADMIN_PASS');
                await page.click('#wp-submit');
                
                // Wait for login to complete
                await page.waitForURL('$ADMIN_URL/**');
                await page.waitForTimeout(2000);
                
                // Navigate to the specific page
                await page.goto('$ADMIN_URL$path');
                await page.waitForTimeout(3000);
                
                // Wait for specific element if selector provided
                if ('$selector' !== '') {
                    await page.waitForSelector('$selector', { timeout: 10000 });
                }
                
                // Take screenshot
                await page.screenshot({
                    path: '/workspace/$SCREENSHOT_DIR/$name.png',
                    fullPage: false
                });
                
                console.log('Screenshot captured: $name.png');
            } catch (error) {
                console.error('Error capturing screenshot:', error);
                process.exit(1);
            } finally {
                await browser.close();
            }
        })();
        "
    
    if [ -f "$SCREENSHOT_DIR/$name.png" ]; then
        echo -e "${GREEN}✅ Screenshot saved: $name.png${NC}"
    else
        echo -e "${RED}❌ Failed to capture: $name.png${NC}"
        exit 1
    fi
}

# Capture required screenshots for WordPress.org

echo -e "${BLUE}🎯 Capturing WordPress.org Required Screenshots${NC}"
echo "=================================================="

# 1. QR Code Management Dashboard
capture_admin_screenshot \
    "screenshot-1" \
    "/admin.php?page=qr-trackr" \
    ".wp-list-table" \
    "QR Code Management Dashboard - Main admin page with QR codes list"

# 2. Analytics Overview
capture_admin_screenshot \
    "screenshot-2" \
    "/admin.php?page=qr-trackr" \
    ".wp-list-table" \
    "Analytics Overview - Tracking and statistics display"

# 3. QR Code Generation Interface
capture_admin_screenshot \
    "screenshot-3" \
    "/admin.php?page=qr-trackr-add-new" \
    "#qr-code-form" \
    "QR Code Generation - Add new QR code interface"

# 4. Custom Styling Options
capture_admin_screenshot \
    "screenshot-4" \
    "/admin.php?page=qr-trackr-add-new" \
    ".qr-code-styling" \
    "Custom Styling Options - QR code customization interface"

# 5. Referral Code Management
capture_admin_screenshot \
    "screenshot-5" \
    "/admin.php?page=qr-trackr" \
    ".referral-code-column" \
    "Referral Code Management - Referral tracking interface"

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
        echo -e "${GREEN}  ✅ $screenshot (${size})${NC}"
    else
        echo -e "${RED}  ❌ $screenshot (MISSING)${NC}"
        all_captured=false
    fi
done

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
    echo -e "${RED}❌ Some screenshots failed to capture${NC}"
    echo "Please check the dev environment and try again."
    exit 1
fi
