#!/bin/bash

# WP QR Trackr - QR Code Tracking Diagnostic Script
# This script helps diagnose why QR code scanning is not updating scan counters

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
ENVIRONMENT=${1:-"dev"}
ADMIN_URL="http://localhost:8080"
if [ "$ENVIRONMENT" = "nonprod" ]; then
    ADMIN_URL="http://localhost:8081"
fi

echo -e "${BLUE}🔍 WP QR Trackr - QR Code Tracking Diagnostic${NC}"
echo "=================================================="
echo -e "Environment: ${YELLOW}$ENVIRONMENT${NC}"
echo -e "Admin URL: ${YELLOW}$ADMIN_URL${NC}"
echo ""

# Function to run WP-CLI commands
run_wp() {
    ./scripts/wp-operations.sh $ENVIRONMENT "$@"
}

# Function to check if environment is running
check_environment() {
    echo -e "${BLUE}1. Checking environment status...${NC}"

    if curl -s "$ADMIN_URL" > /dev/null; then
        echo -e "   ${GREEN}✅ Environment is running${NC}"
    else
        echo -e "   ${RED}❌ Environment is not running${NC}"
        echo "   Please start the environment first:"
        echo "   ./scripts/setup-wordpress.sh $ENVIRONMENT"
        exit 1
    fi
    echo ""
}

# Function to check plugin status
check_plugin() {
    echo -e "${BLUE}2. Checking plugin status...${NC}"

    PLUGIN_STATUS=$(run_wp "plugin list --name=wp-qr-trackr --format=json" | jq -r '.[0].status')
    PLUGIN_VERSION=$(run_wp "plugin list --name=wp-qr-trackr --format=json" | jq -r '.[0].version')

    if [ "$PLUGIN_STATUS" = "active" ]; then
        echo -e "   ${GREEN}✅ Plugin is active${NC}"
        echo -e "   Version: ${YELLOW}$PLUGIN_VERSION${NC}"
    else
        echo -e "   ${RED}❌ Plugin is not active (status: $PLUGIN_STATUS)${NC}"
    fi
    echo ""
}

# Function to check database table
check_database() {
    echo -e "${BLUE}3. Checking database table...${NC}"

    TABLE_EXISTS=$(run_wp "db query \"SHOW TABLES LIKE '%qr_trackr_links%'\" --format=json" | jq -r '.[0]')

    if [ "$TABLE_EXISTS" != "null" ]; then
        echo -e "   ${GREEN}✅ Database table exists${NC}"

        # Check table structure
        TABLE_STRUCTURE=$(run_wp "db query \"DESCRIBE wp_qr_trackr_links\" --format=json")
        echo "   Table structure:"
        echo "$TABLE_STRUCTURE" | jq -r '.[] | "   - \(.Field): \(.Type)"'

        # Check for QR codes
        QR_COUNT=$(run_wp "db query \"SELECT COUNT(*) as count FROM wp_qr_trackr_links\" --format=json" | jq -r '.[0].count')
        echo -e "   QR codes in database: ${YELLOW}$QR_COUNT${NC}"

        if [ "$QR_COUNT" -gt 0 ]; then
            # Show sample QR codes
            echo "   Sample QR codes:"
            run_wp "db query \"SELECT id, qr_code, destination_url, scans, access_count, last_accessed FROM wp_qr_trackr_links LIMIT 3\" --format=table"
        fi
    else
        echo -e "   ${RED}❌ Database table does not exist${NC}"
    fi
    echo ""
}

# Function to check rewrite rules
check_rewrite_rules() {
    echo -e "${BLUE}4. Checking rewrite rules...${NC}"

    # Check if rewrite rules are registered
    REWRITE_RULES=$(run_wp "rewrite list --format=json" | jq -r '.[] | select(.match | contains("qr/")) | .match')

    if [ -n "$REWRITE_RULES" ]; then
        echo -e "   ${GREEN}✅ QR rewrite rules found:${NC}"
        echo "$REWRITE_RULES" | while read -r rule; do
            echo "   - $rule"
        done
    else
        echo -e "   ${RED}❌ QR rewrite rules not found${NC}"
        echo "   This could be why scanning is not working!"
    fi

    # Check permalink structure
    PERMALINK_STRUCTURE=$(run_wp "option get permalink_structure")
    echo -e "   Permalink structure: ${YELLOW}$PERMALINK_STRUCTURE${NC}"
    echo ""
}

# Function to check query variables
check_query_vars() {
    echo -e "${BLUE}5. Checking query variables...${NC}"

    # This is harder to check via WP-CLI, but we can check if the plugin is loaded
    PLUGIN_LOADED=$(run_wp "eval 'echo function_exists(\"qr_trackr_add_query_vars\") ? \"true\" : \"false\";'")

    if [ "$PLUGIN_LOADED" = "true" ]; then
        echo -e "   ${GREEN}✅ Query var registration function exists${NC}"
    else
        echo -e "   ${RED}❌ Query var registration function not found${NC}"
    fi
    echo ""
}

# Function to test QR code URL
test_qr_url() {
    echo -e "${BLUE}6. Testing QR code URL...${NC}"

    # Get a sample QR code
    SAMPLE_QR=$(run_wp "db query \"SELECT qr_code FROM wp_qr_trackr_links LIMIT 1\" --format=json" | jq -r '.[0].qr_code')

    if [ "$SAMPLE_QR" != "null" ] && [ -n "$SAMPLE_QR" ]; then
        echo -e "   Testing with QR code: ${YELLOW}$SAMPLE_QR${NC}"

        # Test the QR URL
        QR_URL="$ADMIN_URL/qr/$SAMPLE_QR/"
        echo -e "   Testing URL: ${YELLOW}$QR_URL${NC}"

        # Make a test request
        HTTP_RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" "$QR_URL")

        if [ "$HTTP_RESPONSE" = "302" ]; then
            echo -e "   ${GREEN}✅ QR URL returns 302 redirect (expected)${NC}"
        elif [ "$HTTP_RESPONSE" = "404" ]; then
            echo -e "   ${YELLOW}⚠️  QR URL returns 404 (might be expected if QR code is invalid)${NC}"
        else
            echo -e "   ${RED}❌ QR URL returns unexpected status: $HTTP_RESPONSE${NC}"
        fi
    else
        echo -e "   ${YELLOW}⚠️  No QR codes found to test${NC}"
    fi
    echo ""
}

# Function to check error logs
check_error_logs() {
    echo -e "${BLUE}7. Checking error logs...${NC}"

    # Check WordPress debug log
    DEBUG_LOG=$(run_wp "eval 'echo defined(\"WP_DEBUG_LOG\") && WP_DEBUG_LOG ? \"enabled\" : \"disabled\";'")
    echo -e "   WordPress debug logging: ${YELLOW}$DEBUG_LOG${NC}"

    # Check if there are recent QR-related errors
    if [ "$DEBUG_LOG" = "enabled" ]; then
        ERROR_COUNT=$(run_wp "eval 'echo file_exists(WP_CONTENT_DIR . \"/debug.log\") ? \"exists\" : \"not found\";'")
        if [ "$ERROR_COUNT" = "exists" ]; then
            echo -e "   ${GREEN}✅ Debug log file exists${NC}"
            echo "   Recent QR-related errors:"
            run_wp "eval 'if (file_exists(WP_CONTENT_DIR . \"/debug.log\")) { $lines = file(WP_CONTENT_DIR . \"/debug.log\"); $qr_errors = array_filter(array_slice($lines, -50), function($line) { return stripos($line, \"qr\") !== false; }); foreach (array_slice($qr_errors, -5) as $error) { echo \"   - \" . trim($error) . \"\\n\"; } }'"
        else
            echo -e "   ${YELLOW}⚠️  Debug log file not found${NC}"
        fi
    fi
    echo ""
}

# Function to check caching
check_caching() {
    echo -e "${BLUE}8. Checking caching...${NC}"

    # Check if object caching is enabled
    OBJECT_CACHE=$(run_wp "eval 'echo wp_using_ext_object_cache() ? \"enabled\" : \"disabled\";'")
    echo -e "   Object caching: ${YELLOW}$OBJECT_CACHE${NC}"

    # Check if transients are working
    TRANSIENT_TEST=$(run_wp "eval 'set_transient(\"qr_trackr_test\", \"test_value\", 60); echo get_transient(\"qr_trackr_test\") ? \"working\" : \"not working\";'")
    echo -e "   Transients: ${YELLOW}$TRANSIENT_TEST${NC}"
    echo ""
}

# Function to provide recommendations
provide_recommendations() {
    echo -e "${BLUE}9. Recommendations...${NC}"

    echo "   If scanning is not working, try these steps:"
    echo ""
    echo "   1. ${YELLOW}Flush rewrite rules:${NC}"
    echo "      ./scripts/wp-operations.sh $ENVIRONMENT rewrite flush --hard"
    echo ""
    echo "   2. ${YELLOW}Check if pretty permalinks are enabled:${NC}"
    echo "      ./scripts/wp-operations.sh $ENVIRONMENT rewrite structure '/%postname%/'"
    echo ""
    echo "   3. ${YELLOW}Test a QR code manually:${NC}"
    echo "      curl -I $ADMIN_URL/qr/YOUR_QR_CODE/"
    echo ""
    echo "   4. ${YELLOW}Enable debug logging temporarily:${NC}"
    echo "      Add to wp-config.php: define('WP_DEBUG', true); define('WP_DEBUG_LOG', true);"
    echo ""
    echo "   5. ${YELLOW}Check server error logs:${NC}"
    echo "      Look for PHP errors or 500 status codes"
    echo ""
    echo "   6. ${YELLOW}Verify database permissions:${NC}"
    echo "      Ensure the database user has UPDATE permissions on wp_qr_trackr_links"
    echo ""
}

# Main execution
main() {
    check_environment
    check_plugin
    check_database
    check_rewrite_rules
    check_query_vars
    test_qr_url
    check_error_logs
    check_caching
    provide_recommendations

    echo -e "${GREEN}✅ Diagnostic complete!${NC}"
    echo ""
    echo "If you're still having issues, please check:"
    echo "1. Server error logs"
    echo "2. WordPress debug logs"
    echo "3. Database permissions"
    echo "4. Plugin file permissions"
}

# Run the diagnostic
main
