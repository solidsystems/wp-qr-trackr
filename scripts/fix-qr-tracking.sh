#!/bin/bash

# WP QR Trackr - QR Code Tracking Fix Script
# This script fixes common issues that prevent QR code scanning from updating counters

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

echo -e "${BLUE}🔧 WP QR Trackr - QR Code Tracking Fix${NC}"
echo "============================================="
echo -e "Environment: ${YELLOW}$ENVIRONMENT${NC}"
echo -e "Admin URL: ${YELLOW}$ADMIN_URL${NC}"
echo ""

# Function to run WP-CLI commands
run_wp() {
    ./scripts/wp-operations.sh $ENVIRONMENT "$@"
}

# Function to check and fix rewrite rules
fix_rewrite_rules() {
    echo -e "${BLUE}1. Checking and fixing rewrite rules...${NC}"

    # Check current rewrite rules
    REWRITE_RULES=$(run_wp "rewrite list" | grep -c "qr/" || echo "0")

    if [ "$REWRITE_RULES" -eq 0 ]; then
        echo -e "   ${YELLOW}⚠️  QR rewrite rules not found, adding them...${NC}"

        # Add rewrite rules manually
        run_wp "eval 'qr_trackr_add_rewrite_rules();'"

        # Flush rewrite rules
        run_wp "rewrite flush --hard"

        echo -e "   ${GREEN}✅ Rewrite rules added and flushed${NC}"
    else
        echo -e "   ${GREEN}✅ QR rewrite rules found ($REWRITE_RULES rules)${NC}"
    fi

    # Verify rewrite rules are working
    REWRITE_RULES_AFTER=$(run_wp "rewrite list" | grep -c "qr/" || echo "0")
    if [ "$REWRITE_RULES_AFTER" -gt 0 ]; then
        echo -e "   ${GREEN}✅ Rewrite rules verified${NC}"
    else
        echo -e "   ${RED}❌ Rewrite rules still not found after fix${NC}"
    fi
    echo ""
}

# Function to check and fix permalink structure
fix_permalinks() {
    echo -e "${BLUE}2. Checking and fixing permalink structure...${NC}"

    CURRENT_PERMALINK=$(run_wp "option get permalink_structure")

    if [ "$CURRENT_PERMALINK" = "" ]; then
        echo -e "   ${YELLOW}⚠️  Pretty permalinks not enabled, enabling them...${NC}"
        run_wp "rewrite structure '/%postname%/'"
        echo -e "   ${GREEN}✅ Pretty permalinks enabled${NC}"
    else
        echo -e "   ${GREEN}✅ Pretty permalinks already enabled: $CURRENT_PERMALINK${NC}"
    fi

    # Flush rewrite rules after permalink change
    run_wp "rewrite flush --hard"
    echo ""
}

# Function to check and fix database table
fix_database() {
    echo -e "${BLUE}3. Checking and fixing database table...${NC}"

    # Check if table exists
    TABLE_EXISTS=$(run_wp "eval 'global \$wpdb; echo \$wpdb->get_var(\"SHOW TABLES LIKE \\\"{\$wpdb->prefix}qr_trackr_links\\\"\") ? \"exists\" : \"missing\";'" 2>/dev/null || echo "error")

    if [ "$TABLE_EXISTS" = "missing" ]; then
        echo -e "   ${YELLOW}⚠️  Database table missing, creating it...${NC}"
        run_wp "eval 'qrc_activate();'"
        echo -e "   ${GREEN}✅ Database table created${NC}"
    elif [ "$TABLE_EXISTS" = "exists" ]; then
        echo -e "   ${GREEN}✅ Database table exists${NC}"
    else
        echo -e "   ${RED}❌ Could not check database table status${NC}"
    fi
    echo ""
}

# Function to check and fix plugin hooks
fix_plugin_hooks() {
    echo -e "${BLUE}4. Checking and fixing plugin hooks...${NC}"

    # Check if template_redirect hook is registered
    HOOK_REGISTERED=$(run_wp "eval 'echo has_action(\"template_redirect\", \"qr_trackr_handle_clean_urls\") ? \"registered\" : \"not registered\";'" 2>/dev/null || echo "error")

    if [ "$HOOK_REGISTERED" = "not registered" ]; then
        echo -e "   ${YELLOW}⚠️  Template redirect hook not registered, adding it...${NC}"
        run_wp "eval 'add_action(\"template_redirect\", \"qr_trackr_handle_clean_urls\");'"
        echo -e "   ${GREEN}✅ Template redirect hook registered${NC}"
    elif [ "$HOOK_REGISTERED" = "registered" ]; then
        echo -e "   ${GREEN}✅ Template redirect hook already registered${NC}"
    else
        echo -e "   ${RED}❌ Could not check hook registration status${NC}"
    fi

    # Check if query vars are registered
    QUERY_VARS_REGISTERED=$(run_wp "eval 'echo has_filter(\"query_vars\", \"qr_trackr_add_query_vars\") ? \"registered\" : \"not registered\";'" 2>/dev/null || echo "error")

    if [ "$QUERY_VARS_REGISTERED" = "not registered" ]; then
        echo -e "   ${YELLOW}⚠️  Query vars hook not registered, adding it...${NC}"
        run_wp "eval 'add_filter(\"query_vars\", \"qr_trackr_add_query_vars\");'"
        echo -e "   ${GREEN}✅ Query vars hook registered${NC}"
    elif [ "$QUERY_VARS_REGISTERED" = "registered" ]; then
        echo -e "   ${GREEN}✅ Query vars hook already registered${NC}"
    else
        echo -e "   ${RED}❌ Could not check query vars registration status${NC}"
    fi
    echo ""
}

# Function to create a test QR code
create_test_qr() {
    echo -e "${BLUE}5. Creating test QR code for verification...${NC}"

    # Check if there are any existing QR codes
    QR_COUNT=$(run_wp "eval 'global \$wpdb; echo \$wpdb->get_var(\"SELECT COUNT(*) FROM {\$wpdb->prefix}qr_trackr_links\");'" 2>/dev/null || echo "0")

    if [ "$QR_COUNT" = "0" ] || [ "$QR_COUNT" = "" ]; then
        echo -e "   ${YELLOW}⚠️  No QR codes found, creating a test QR code...${NC}"

        # Create a test QR code
        run_wp "eval '
        global \$wpdb;
        \$table_name = \$wpdb->prefix . \"qr_trackr_links\";
        \$qr_code = \"test_qr_\" . wp_generate_password(8, false);
        \$result = \$wpdb->insert(
            \$table_name,
            array(
                \"destination_url\" => \"https://google.com\",
                \"qr_code\" => \$qr_code,
                \"common_name\" => \"Test QR Code\",
                \"created_at\" => current_time(\"mysql\"),
                \"updated_at\" => current_time(\"mysql\")
            ),
            array(\"%s\", \"%s\", \"%s\", \"%s\", \"%s\")
        );
        if (\$result) {
            echo \"Test QR code created: \" . \$qr_code;
        } else {
            echo \"Failed to create test QR code: \" . \$wpdb->last_error;
        }
        '"

        echo -e "   ${GREEN}✅ Test QR code created${NC}"
    else
        echo -e "   ${GREEN}✅ QR codes already exist ($QR_COUNT found)${NC}"
    fi
    echo ""
}

# Function to test QR code functionality
test_qr_functionality() {
    echo -e "${BLUE}6. Testing QR code functionality...${NC}"

    # Get a test QR code
    TEST_QR=$(run_wp "eval 'global \$wpdb; echo \$wpdb->get_var(\"SELECT qr_code FROM {\$wpdb->prefix}qr_trackr_links LIMIT 1\");'" 2>/dev/null || echo "")

    if [ -n "$TEST_QR" ] && [ "$TEST_QR" != "null" ]; then
        echo -e "   Testing with QR code: ${YELLOW}$TEST_QR${NC}"

        # Test the QR URL
        QR_URL="$ADMIN_URL/qr/$TEST_QR/"
        echo -e "   Testing URL: ${YELLOW}$QR_URL${NC}"

        # Make a test request
        HTTP_RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" "$QR_URL" 2>/dev/null || echo "error")

        if [ "$HTTP_RESPONSE" = "302" ]; then
            echo -e "   ${GREEN}✅ QR URL returns 302 redirect (working correctly)${NC}"
        elif [ "$HTTP_RESPONSE" = "404" ]; then
            echo -e "   ${YELLOW}⚠️  QR URL returns 404 (might need rewrite flush)${NC}"
        elif [ "$HTTP_RESPONSE" = "error" ]; then
            echo -e "   ${RED}❌ Could not test QR URL (connection error)${NC}"
        else
            echo -e "   ${RED}❌ QR URL returns unexpected status: $HTTP_RESPONSE${NC}"
        fi
    else
        echo -e "   ${YELLOW}⚠️  No QR codes available for testing${NC}"
    fi
    echo ""
}

# Function to check and fix caching
fix_caching() {
    echo -e "${BLUE}7. Checking and fixing caching...${NC}"

    # Clear any existing caches
    run_wp "eval 'wp_cache_flush();'"
    run_wp "eval 'delete_transient(\"qr_trackr_*\");'"

    echo -e "   ${GREEN}✅ Caches cleared${NC}"
    echo ""
}

# Function to provide production recommendations
production_recommendations() {
    echo -e "${BLUE}8. Production site recommendations...${NC}"

    echo "   For production sites, also check:"
    echo ""
    echo "   1. ${YELLOW}Server Configuration:${NC}"
    echo "      - Ensure mod_rewrite is enabled"
    echo "      - Check .htaccess file permissions"
    echo "      - Verify Apache/Nginx rewrite rules"
    echo ""
    echo "   2. ${YELLOW}Database Permissions:${NC}"
    echo "      - Ensure database user has UPDATE permissions"
    echo "      - Check for any database connection issues"
    echo ""
    echo "   3. ${YELLOW}WordPress Configuration:${NC}"
    echo "      - Verify WP_DEBUG is disabled in production"
    echo "      - Check for any conflicting plugins"
    echo "      - Ensure proper file permissions"
    echo ""
    echo "   4. ${YELLOW}Testing Steps:${NC}"
    echo "      - Test QR code URLs directly in browser"
    echo "      - Check server error logs for PHP errors"
    echo "      - Monitor database for scan count updates"
    echo ""
}

# Main execution
main() {
    fix_rewrite_rules
    fix_permalinks
    fix_database
    fix_plugin_hooks
    create_test_qr
    test_qr_functionality
    fix_caching
    production_recommendations

    echo -e "${GREEN}✅ QR code tracking fix complete!${NC}"
    echo ""
    echo "If scanning is still not working on production:"
    echo "1. Check server error logs"
    echo "2. Verify database permissions"
    echo "3. Test QR URLs manually"
    echo "4. Contact hosting provider about mod_rewrite"
}

# Run the fix
main
