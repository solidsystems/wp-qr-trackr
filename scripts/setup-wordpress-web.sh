#!/bin/bash

# WordPress Web Setup Instructions
# Since WP-CLI has memory constraints, use this for web-based setup

ENVIRONMENT=${1:-dev}

case $ENVIRONMENT in
    "dev")
        SITE_URL="http://localhost:8080"
        ;;
    "nonprod")
        SITE_URL="http://localhost:8081"
        ;;
    *)
        echo "Usage: $0 [dev|nonprod]"
        echo "Environment must be 'dev' or 'nonprod'"
        exit 1
        ;;
esac

echo "🚀 WordPress $ENVIRONMENT Environment Setup"
echo "=============================================="
echo ""
echo "📍 Site URL: $SITE_URL"
echo ""
echo "If WordPress setup is required, use these credentials:"
echo "👤 Username: trackr"
echo "🔑 Password: trackr"
echo "📧 Email: admin@example.com"
echo "🏠 Site Title: QR Trackr Development"
echo ""
echo "📝 Setup Steps:"
echo "1. Open: $SITE_URL"
echo "2. If you see the WordPress setup page:"
echo "   - Select language: English (United States)"
echo "   - Database should auto-connect (already configured)"
echo "   - Use the credentials above"
echo "3. After setup, go to Settings → Permalinks"
echo "4. Select 'Post name' structure for QR redirects"
echo "5. Save changes"
echo ""

# Check if site is accessible
echo "🔍 Checking site status..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$SITE_URL" 2>/dev/null || echo "000")

case $HTTP_CODE in
    "200")
        echo "✅ Site is accessible and likely set up"
        ;;
    "302")
        echo "🔄 Site redirects (may need setup or already configured)"
        ;;
    "500")
        echo "❌ Server error - check container logs"
        ;;
    "000")
        echo "❌ Site not accessible - check if containers are running"
        ;;
    *)
        echo "⚠️  Site status: HTTP $HTTP_CODE"
        ;;
esac

echo ""
echo "🌐 Opening site in browser..."
echo "If the browser doesn't open automatically, visit: $SITE_URL"

# Try to open in browser (macOS)
if command -v open >/dev/null 2>&1; then
    open "$SITE_URL" 2>/dev/null || true
fi

echo ""
echo "📱 Quick Access URLs:"
echo "🏠 Site: $SITE_URL"
echo "🔧 Admin: $SITE_URL/wp-admin"
echo "⚙️  Plugins: $SITE_URL/wp-admin/plugins.php"
echo "" 