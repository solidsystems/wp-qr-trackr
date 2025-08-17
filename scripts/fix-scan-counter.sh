#!/bin/bash

# WP QR Trackr - Scan Counter Fix Script
# This script diagnoses and fixes issues with QR code scan counters not updating

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

echo -e "${BLUE}🔧 WP QR Trackr - Scan Counter Fix${NC}"
echo "====================================="
echo -e "Environment: ${YELLOW}$ENVIRONMENT${NC}"
echo -e "Admin URL: ${YELLOW}$ADMIN_URL${NC}"
echo ""

# Function to run WP-CLI commands
run_wp() {
    ./scripts/wp-operations.sh $ENVIRONMENT "$@"
}

# Function to check database permissions
check_database_permissions() {
    echo -e "${BLUE}1. Checking database permissions...${NC}"

    # Test if we can read from the table
    READ_TEST=$(run_wp "eval 'global \$wpdb; \$result = \$wpdb->get_var(\"SELECT COUNT(*) FROM {\$wpdb->prefix}qr_trackr_links\"); echo \$result ? \"READ_OK\" : \"READ_FAILED\";'" 2>/dev/null || echo "ERROR")

    if [ "$READ_TEST" = "READ_OK" ]; then
        echo -e "   ${GREEN}✅ Database read permissions OK${NC}"
    else
        echo -e "   ${RED}❌ Database read permissions failed${NC}"
        echo "   This indicates a database connection or permission issue"
    fi

    # Test if we can update the table
    UPDATE_TEST=$(run_wp "eval 'global \$wpdb; \$result = \$wpdb->query(\"UPDATE {\$wpdb->prefix}qr_trackr_links SET updated_at = updated_at WHERE id = 1\"); echo \$result !== false ? \"UPDATE_OK\" : \"UPDATE_FAILED: \" . \$wpdb->last_error;'" 2>/dev/null || echo "ERROR")

    if [[ "$UPDATE_TEST" == *"UPDATE_OK"* ]]; then
        echo -e "   ${GREEN}✅ Database update permissions OK${NC}"
    else
        echo -e "   ${RED}❌ Database update permissions failed${NC}"
        echo "   Error: $UPDATE_TEST"
        echo "   This is likely the cause of scan counter issues!"
    fi
    echo ""
}

# Function to check table structure
check_table_structure() {
    echo -e "${BLUE}2. Checking table structure...${NC}"

    # Check if the scans column exists
    SCANS_COLUMN=$(run_wp "eval 'global \$wpdb; \$columns = \$wpdb->get_results(\"SHOW COLUMNS FROM {\$wpdb->prefix}qr_trackr_links LIKE \\\"scans\\\"\"); echo count(\$columns) > 0 ? \"EXISTS\" : \"MISSING\";'" 2>/dev/null || echo "ERROR")

    if [ "$SCANS_COLUMN" = "EXISTS" ]; then
        echo -e "   ${GREEN}✅ 'scans' column exists${NC}"
    else
        echo -e "   ${RED}❌ 'scans' column missing${NC}"
        echo "   This will prevent scan counters from updating!"
    fi

    # Check if the access_count column exists
    ACCESS_COUNT_COLUMN=$(run_wp "eval 'global \$wpdb; \$columns = \$wpdb->get_results(\"SHOW COLUMNS FROM {\$wpdb->prefix}qr_trackr_links LIKE \\\"access_count\\\"\"); echo count(\$columns) > 0 ? \"EXISTS\" : \"MISSING\";'" 2>/dev/null || echo "ERROR")

    if [ "$ACCESS_COUNT_COLUMN" = "EXISTS" ]; then
        echo -e "   ${GREEN}✅ 'access_count' column exists${NC}"
    else
        echo -e "   ${RED}❌ 'access_count' column missing${NC}"
    fi

    # Check if the last_accessed column exists
    LAST_ACCESSED_COLUMN=$(run_wp "eval 'global \$wpdb; \$columns = \$wpdb->get_results(\"SHOW COLUMNS FROM {\$wpdb->prefix}qr_trackr_links LIKE \\\"last_accessed\\\"\"); echo count(\$columns) > 0 ? \"EXISTS\" : \"MISSING\";'" 2>/dev/null || echo "ERROR")

    if [ "$LAST_ACCESSED_COLUMN" = "EXISTS" ]; then
        echo -e "   ${GREEN}✅ 'last_accessed' column exists${NC}"
    else
        echo -e "   ${RED}❌ 'last_accessed' column missing${NC}"
    fi
    echo ""
}

# Function to test scan counter function
test_scan_counter_function() {
    echo -e "${BLUE}3. Testing scan counter function...${NC}"

    # Check if the function exists
    FUNCTION_EXISTS=$(run_wp "eval 'echo function_exists(\"qr_trackr_update_scan_count_immediate\") ? \"EXISTS\" : \"MISSING\";'" 2>/dev/null || echo "ERROR")

    if [ "$FUNCTION_EXISTS" = "EXISTS" ]; then
        echo -e "   ${GREEN}✅ Scan counter function exists${NC}"
    else
        echo -e "   ${RED}❌ Scan counter function missing${NC}"
        echo "   This will prevent scan counters from updating!"
    fi

    # Get a test QR code ID
    TEST_QR_ID=$(run_wp "eval 'global \$wpdb; echo \$wpdb->get_var(\"SELECT id FROM {\$wpdb->prefix}qr_trackr_links LIMIT 1\");'" 2>/dev/null || echo "0")

    if [ "$TEST_QR_ID" != "0" ] && [ -n "$TEST_QR_ID" ]; then
        echo -e "   Testing with QR ID: ${YELLOW}$TEST_QR_ID${NC}"

        # Get current scan count
        CURRENT_SCANS=$(run_wp "eval 'global \$wpdb; echo \$wpdb->get_var(\"SELECT scans FROM {\$wpdb->prefix}qr_trackr_links WHERE id = $TEST_QR_ID\");'" 2>/dev/null || echo "0")
        echo -e "   Current scan count: ${YELLOW}$CURRENT_SCANS${NC}"

        # Test the scan counter function
        TEST_RESULT=$(run_wp "eval 'global \$wpdb; qr_trackr_update_scan_count_immediate($TEST_QR_ID); \$new_scans = \$wpdb->get_var(\"SELECT scans FROM {\$wpdb->prefix}qr_trackr_links WHERE id = $TEST_QR_ID\"); echo \"NEW_SCANS:\" . \$new_scans;'" 2>/dev/null || echo "ERROR")

        if [[ "$TEST_RESULT" == *"NEW_SCANS:"* ]]; then
            NEW_SCANS=$(echo "$TEST_RESULT" | grep -o "NEW_SCANS:[0-9]*" | cut -d: -f2)
            if [ "$NEW_SCANS" -gt "$CURRENT_SCANS" ]; then
                echo -e "   ${GREEN}✅ Scan counter function working (incremented from $CURRENT_SCANS to $NEW_SCANS)${NC}"
            else
                echo -e "   ${RED}❌ Scan counter function failed (count didn't increment)${NC}"
            fi
        else
            echo -e "   ${RED}❌ Scan counter function failed (error: $TEST_RESULT)${NC}"
        fi
    else
        echo -e "   ${YELLOW}⚠️  No QR codes found to test${NC}"
    fi
    echo ""
}

# Function to check for database errors
check_database_errors() {
    echo -e "${BLUE}4. Checking for database errors...${NC}"

    # Check WordPress debug log for database errors
    DEBUG_LOG=$(run_wp "eval 'echo defined(\"WP_DEBUG_LOG\") && WP_DEBUG_LOG ? \"enabled\" : \"disabled\";'")
    echo -e "   WordPress debug logging: ${YELLOW}$DEBUG_LOG${NC}"

    if [ "$DEBUG_LOG" = "enabled" ]; then
        # Look for QR-related database errors
        QR_ERRORS=$(run_wp "eval 'if (file_exists(WP_CONTENT_DIR . \"/debug.log\")) { \$lines = file(WP_CONTENT_DIR . \"/debug.log\"); \$qr_errors = array_filter(array_slice(\$lines, -100), function(\$line) { return stripos(\$line, \"qr\") !== false && (stripos(\$line, \"error\") !== false || stripos(\$line, \"failed\") !== false); }); foreach (array_slice(\$qr_errors, -5) as \$error) { echo \"   - \" . trim(\$error) . \"\\n\"; } }'" 2>/dev/null || echo "No errors found")

        if [ "$QR_ERRORS" != "No errors found" ]; then
            echo -e "   ${RED}❌ Recent QR-related errors found:${NC}"
            echo "$QR_ERRORS"
        else
            echo -e "   ${GREEN}✅ No recent QR-related errors found${NC}"
        fi
    fi
    echo ""
}

# Function to fix common issues
fix_common_issues() {
    echo -e "${BLUE}5. Fixing common issues...${NC}"

    # Clear object cache
    echo -e "   Clearing object cache..."
    run_wp "eval 'wp_cache_flush();'" 2>/dev/null || echo "   Cache flush failed"

    # Clear transients
    echo -e "   Clearing transients..."
    run_wp "eval 'delete_transient(\"qr_trackr_*\");'" 2>/dev/null || echo "   Transient clear failed"

    # Re-register rewrite rules
    echo -e "   Re-registering rewrite rules..."
    run_wp "eval 'qr_trackr_add_rewrite_rules();'" 2>/dev/null || echo "   Rewrite rules failed"

    # Flush rewrite rules
    echo -e "   Flushing rewrite rules..."
    run_wp "rewrite flush --hard" 2>/dev/null || echo "   Rewrite flush failed"

    echo -e "   ${GREEN}✅ Common fixes applied${NC}"
    echo ""
}

# Function to create enhanced scan counter function
create_enhanced_scan_counter() {
    echo -e "${BLUE}6. Creating enhanced scan counter function...${NC}"

    # Create a more robust scan counter function
    ENHANCED_FUNCTION=$(run_wp "eval '
    if (!function_exists(\"qr_trackr_update_scan_count_enhanced\")) {
        function qr_trackr_update_scan_count_enhanced(\$link_id) {
            global \$wpdb;
            \$table_name = \$wpdb->prefix . \"qr_trackr_links\";

            // Validate input
            if (!is_numeric(\$link_id) || \$link_id <= 0) {
                error_log(\"QR Trackr: Invalid link_id provided: \" . \$link_id);
                return false;
            }

            // Check if record exists
            \$exists = \$wpdb->get_var(\$wpdb->prepare(\"SELECT id FROM {\$table_name} WHERE id = %d\", \$link_id));
            if (!\$exists) {
                error_log(\"QR Trackr: Link ID {\$link_id} not found in database\");
                return false;
            }

            // Update with error handling
            \$result = \$wpdb->query(\$wpdb->prepare(
                \"UPDATE {\$table_name} SET access_count = access_count + 1, scans = scans + 1, last_accessed = %s, updated_at = %s WHERE id = %d\",
                current_time(\"mysql\", true),
                current_time(\"mysql\", true),
                \$link_id
            ));

            if (\$result === false) {
                error_log(\"QR Trackr: Database update failed for link ID {\$link_id}: \" . \$wpdb->last_error);
                return false;
            }

            // Clear caches
            wp_cache_delete(\"qr_trackr_details_\" . \$link_id);
            wp_cache_delete(\"qr_trackr_all_links_admin\", \"qr_trackr\");
            wp_cache_delete(\"qrc_link_\" . \$link_id, \"qrc_links\");

            error_log(\"QR Trackr: Successfully updated scan count for link ID {\$link_id}\");
            return true;
        }
        echo \"Enhanced function created\";
    } else {
        echo \"Enhanced function already exists\";
    }
    '" 2>/dev/null || echo "Function creation failed")

    echo -e "   $ENHANCED_FUNCTION"
    echo ""
}

# Function to provide production recommendations
production_recommendations() {
    echo -e "${BLUE}7. Production recommendations...${NC}"

    echo "   For production sites with scan counter issues:"
    echo ""
    echo "   1. ${YELLOW}Database Permissions:${NC}"
    echo "      - Ensure database user has UPDATE permissions on wp_qr_trackr_links"
    echo "      - Check for any database connection limits or timeouts"
    echo ""
    echo "   2. ${YELLOW}WordPress Configuration:${NC}"
    echo "      - Enable WP_DEBUG and WP_DEBUG_LOG temporarily"
    echo "      - Check for any conflicting plugins or themes"
    echo "      - Verify database connection settings"
    echo ""
    echo "   3. ${YELLOW}Server Configuration:${NC}"
    echo "      - Check PHP memory limits and execution time"
    echo "      - Verify MySQL/PostgreSQL connection limits"
    echo "      - Check for any server-level caching"
    echo ""
    echo "   4. ${YELLOW}Testing Steps:${NC}"
    echo "      - Test QR code scanning manually"
    echo "      - Check database directly for scan count updates"
    echo "      - Monitor error logs during scanning"
    echo ""
}

# Main execution
main() {
    check_database_permissions
    check_table_structure
    test_scan_counter_function
    check_database_errors
    fix_common_issues
    create_enhanced_scan_counter
    production_recommendations

    echo -e "${GREEN}✅ Scan counter fix complete!${NC}"
    echo ""
    echo "If scan counters are still not working:"
    echo "1. Check database permissions and connection"
    echo "2. Enable debug logging and monitor errors"
    echo "3. Test the enhanced scan counter function"
    echo "4. Contact hosting provider about database issues"
}

# Run the fix
main
