#!/bin/bash

# WP QR Trackr - Production Scan Counter Fix
# This script fixes scan counter issues in production by ensuring proper database structure

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🔧 WP QR Trackr - Production Scan Counter Fix${NC}"
echo "=================================================="
echo ""

# Function to run WP-CLI commands
run_wp() {
    ./scripts/wp-operations.sh nonprod "$@"
}

# Function to check and fix database table structure
fix_database_structure() {
    echo -e "${BLUE}1. Checking and fixing database table structure...${NC}"

    # Check if required columns exist
    SCANS_EXISTS=$(run_wp "eval 'global \$wpdb; \$columns = \$wpdb->get_results(\"SHOW COLUMNS FROM {\$wpdb->prefix}qr_trackr_links LIKE \\\"scans\\\"\"); echo count(\$columns) > 0 ? \"EXISTS\" : \"MISSING\";'" 2>/dev/null || echo "ERROR")
    ACCESS_COUNT_EXISTS=$(run_wp "eval 'global \$wpdb; \$columns = \$wpdb->get_results(\"SHOW COLUMNS FROM {\$wpdb->prefix}qr_trackr_links LIKE \\\"access_count\\\"\"); echo count(\$columns) > 0 ? \"EXISTS\" : \"MISSING\";'" 2>/dev/null || echo "ERROR")
    LAST_ACCESSED_EXISTS=$(run_wp "eval 'global \$wpdb; \$columns = \$wpdb->get_results(\"SHOW COLUMNS FROM {\$wpdb->prefix}qr_trackr_links LIKE \\\"last_accessed\\\"\"); echo count(\$columns) > 0 ? \"EXISTS\" : \"MISSING\";'" 2>/dev/null || echo "ERROR")

    echo "   Current table structure:"
    echo "   - scans column: $SCANS_EXISTS"
    echo "   - access_count column: $ACCESS_COUNT_EXISTS"
    echo "   - last_accessed column: $LAST_ACCESSED_EXISTS"

    # If any columns are missing, run the activation function to fix them
    if [ "$SCANS_EXISTS" = "MISSING" ] || [ "$ACCESS_COUNT_EXISTS" = "MISSING" ] || [ "$LAST_ACCESSED_EXISTS" = "MISSING" ]; then
        echo -e "   ${YELLOW}⚠️  Missing required columns. Running database fix...${NC}"

        # Run the activation function to add missing columns
        run_wp "eval 'qrc_activate(); echo \"Database table structure updated\";'" 2>/dev/null || echo "   Database update failed"

        # Verify the fix
        echo -e "   ${BLUE}Verifying table structure after fix...${NC}"
        SCANS_EXISTS_AFTER=$(run_wp "eval 'global \$wpdb; \$columns = \$wpdb->get_results(\"SHOW COLUMNS FROM {\$wpdb->prefix}qr_trackr_links LIKE \\\"scans\\\"\"); echo count(\$columns) > 0 ? \"EXISTS\" : \"MISSING\";'" 2>/dev/null || echo "ERROR")
        ACCESS_COUNT_EXISTS_AFTER=$(run_wp "eval 'global \$wpdb; \$columns = \$wpdb->get_results(\"SHOW COLUMNS FROM {\$wpdb->prefix}qr_trackr_links LIKE \\\"access_count\\\"\"); echo count(\$columns) > 0 ? \"EXISTS\" : \"MISSING\";'" 2>/dev/null || echo "ERROR")
        LAST_ACCESSED_EXISTS_AFTER=$(run_wp "eval 'global \$wpdb; \$columns = \$wpdb->get_results(\"SHOW COLUMNS FROM {\$wpdb->prefix}qr_trackr_links LIKE \\\"last_accessed\\\"\"); echo count(\$columns) > 0 ? \"EXISTS\" : \"MISSING\";'" 2>/dev/null || echo "ERROR")

        if [ "$SCANS_EXISTS_AFTER" = "EXISTS" ] && [ "$ACCESS_COUNT_EXISTS_AFTER" = "EXISTS" ] && [ "$LAST_ACCESSED_EXISTS_AFTER" = "EXISTS" ]; then
            echo -e "   ${GREEN}✅ All required columns now exist${NC}"
        else
            echo -e "   ${RED}❌ Some columns still missing after fix${NC}"
            echo "   - scans: $SCANS_EXISTS_AFTER"
            echo "   - access_count: $ACCESS_COUNT_EXISTS_AFTER"
            echo "   - last_accessed: $LAST_ACCESSED_EXISTS_AFTER"
        fi
    else
        echo -e "   ${GREEN}✅ All required columns already exist${NC}"
    fi
    echo ""
}

# Function to test scan counter functionality
test_scan_counter() {
    echo -e "${BLUE}2. Testing scan counter functionality...${NC}"

    # Get a test QR code
    TEST_QR=$(run_wp "eval 'global \$wpdb; \$qr = \$wpdb->get_row(\"SELECT id, qr_code, scans FROM {\$wpdb->prefix}qr_trackr_links LIMIT 1\"); if (\$qr) { echo \"ID:{\$qr->id},CODE:{\$qr->qr_code},SCANS:{\$qr->scans}\"; } else { echo \"NO_QR\"; }'" 2>/dev/null || echo "ERROR")

    if [[ "$TEST_QR" == *"ID:"* ]]; then
        QR_ID=$(echo "$TEST_QR" | grep -o "ID:[0-9]*" | cut -d: -f2)
        QR_CODE=$(echo "$TEST_QR" | grep -o "CODE:[^,]*" | cut -d: -f2)
        CURRENT_SCANS=$(echo "$TEST_QR" | grep -o "SCANS:[0-9]*" | cut -d: -f2)

        echo -e "   Testing with QR code: ${YELLOW}$QR_CODE${NC} (ID: $QR_ID, Current scans: $CURRENT_SCANS)"

        # Test the scan counter function
        TEST_RESULT=$(run_wp "eval 'global \$wpdb; qr_trackr_update_scan_count_immediate($QR_ID); \$new_scans = \$wpdb->get_var(\"SELECT scans FROM {\$wpdb->prefix}qr_trackr_links WHERE id = $QR_ID\"); echo \"NEW_SCANS:\" . \$new_scans;'" 2>/dev/null || echo "ERROR")

        if [[ "$TEST_RESULT" == *"NEW_SCANS:"* ]]; then
            NEW_SCANS=$(echo "$TEST_RESULT" | grep -o "NEW_SCANS:[0-9]*" | cut -d: -f2)
            if [ "$NEW_SCANS" -gt "$CURRENT_SCANS" ]; then
                echo -e "   ${GREEN}✅ Scan counter working (incremented from $CURRENT_SCANS to $NEW_SCANS)${NC}"
            else
                echo -e "   ${RED}❌ Scan counter failed (count didn't increment)${NC}"
            fi
        else
            echo -e "   ${RED}❌ Scan counter test failed (error: $TEST_RESULT)${NC}"
        fi
    else
        echo -e "   ${YELLOW}⚠️  No QR codes found to test${NC}"
    fi
    echo ""
}

# Function to test QR code redirect and scan counting
test_qr_redirect() {
    echo -e "${BLUE}3. Testing QR code redirect and scan counting...${NC}"

    # Get a test QR code
    TEST_QR=$(run_wp "eval 'global \$wpdb; \$qr = \$wpdb->get_row(\"SELECT id, qr_code, scans FROM {\$wpdb->prefix}qr_trackr_links LIMIT 1\"); if (\$qr) { echo \"ID:{\$qr->id},CODE:{\$qr->qr_code},SCANS:{\$qr->scans}\"; } else { echo \"NO_QR\"; }'" 2>/dev/null || echo "ERROR")

    if [[ "$TEST_QR" == *"ID:"* ]]; then
        QR_ID=$(echo "$TEST_QR" | grep -o "ID:[0-9]*" | cut -d: -f2)
        QR_CODE=$(echo "$TEST_QR" | grep -o "CODE:[^,]*" | cut -d: -f2)
        CURRENT_SCANS=$(echo "$TEST_QR" | grep -o "SCANS:[0-9]*" | cut -d: -f2)

        echo -e "   Testing QR code: ${YELLOW}$QR_CODE${NC} (Current scans: $CURRENT_SCANS)"

        # Test AJAX redirect
        REDIRECT_RESULT=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost:8081/wp-admin/admin-ajax.php?action=qr_trackr_redirect&qr=$QR_CODE" 2>/dev/null || echo "ERROR")

        if [ "$REDIRECT_RESULT" = "302" ]; then
            echo -e "   ${GREEN}✅ AJAX redirect working (HTTP 302)${NC}"

            # Check if scan count was incremented
            sleep 1
            NEW_SCANS=$(run_wp "eval 'global \$wpdb; echo \$wpdb->get_var(\"SELECT scans FROM {\$wpdb->prefix}qr_trackr_links WHERE id = $QR_ID\");'" 2>/dev/null || echo "ERROR")

            if [ "$NEW_SCANS" -gt "$CURRENT_SCANS" ]; then
                echo -e "   ${GREEN}✅ Scan counter incremented (from $CURRENT_SCANS to $NEW_SCANS)${NC}"
            else
                echo -e "   ${RED}❌ Scan counter not incremented (still $CURRENT_SCANS)${NC}"
            fi
        else
            echo -e "   ${RED}❌ AJAX redirect failed (HTTP $REDIRECT_RESULT)${NC}"
        fi
    else
        echo -e "   ${YELLOW}⚠️  No QR codes found to test${NC}"
    fi
    echo ""
}

# Function to provide production deployment instructions
production_deployment() {
    echo -e "${BLUE}4. Production deployment instructions...${NC}"
    echo ""
    echo "To fix scan counter issues in production:"
    echo ""
    echo "1. ${YELLOW}Database Structure Fix:${NC}"
    echo "   - Run this script on the production server"
    echo "   - Or manually run: wp eval 'qrc_activate();'"
    echo ""
    echo "2. ${YELLOW}Verify Table Structure:${NC}"
    echo "   - Ensure wp_qr_trackr_links table has these columns:"
    echo "     * scans (int)"
    echo "     * access_count (int)"
    echo "     * last_accessed (datetime)"
    echo ""
    echo "3. ${YELLOW}Test Functionality:${NC}"
    echo "   - Test QR code scanning via AJAX endpoint"
    echo "   - Verify scan counters increment in database"
    echo "   - Check error logs for any issues"
    echo ""
    echo "4. ${YELLOW}Common Production Issues:${NC}"
    echo "   - Database permissions (ensure UPDATE access)"
    echo "   - Object caching conflicts (clear cache if needed)"
    echo "   - Plugin conflicts (test with other plugins disabled)"
    echo ""
}

# Main execution
main() {
    fix_database_structure
    test_scan_counter
    test_qr_redirect
    production_deployment

    echo -e "${GREEN}✅ Production scan counter fix complete!${NC}"
    echo ""
    echo "Next steps for production:"
    echo "1. Run this script on the production server"
    echo "2. Test QR code scanning functionality"
    echo "3. Monitor scan counter updates"
    echo "4. Check error logs if issues persist"
}

# Run the fix
main
