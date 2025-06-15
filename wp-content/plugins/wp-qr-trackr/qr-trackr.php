<?php
/**
 * Plugin Name: QR Trackr
 * Description: Generate and track QR codes for WordPress pages and posts. Adds QR code generation to listings and edit screens, and tracks scans with stats overview.
 * Version: 1.0.1
 * Author: Your Name
 * Text Domain: qr-trackr
 *
 * @package QR_Trackr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define plugin constants.
define( 'QR_TRACKR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'QR_TRACKR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Note: Setting the default timezone is intentionally omitted to comply with WordPress coding standards.

// Include core files.
require_once QR_TRACKR_PLUGIN_DIR . 'qr-code.php';

// Include the QR_Trackr_List_Table class.
require_once QR_TRACKR_PLUGIN_DIR . 'includes/class-qr-trackr-list-table.php';

require_once QR_TRACKR_PLUGIN_DIR . 'includes/class-qr-trackr-cli-command.php';
require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-activation.php';
require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-admin.php';
require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-ajax.php';
require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-rewrite.php';
require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-debug.php';
require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-utility.php';

/**
 * Plugin activation callback.
 * Creates custom tables for QR code scans and tracking links.
 */
function qr_trackr_activate() {
	// Create custom table for QR code scans.
	global $wpdb;
	$table_name      = $wpdb->prefix . 'qr_trackr_scans';
	$charset_collate = $wpdb->get_charset_collate();
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table creation, variable interpolation is safe here.
	$sql = "CREATE TABLE $table_name (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        post_id BIGINT UNSIGNED NOT NULL,
        scan_time DATETIME NOT NULL,
        user_agent TEXT,
        ip_address VARCHAR(45),
        PRIMARY KEY  (id),
        KEY post_id (post_id)
    ) $charset_collate;";
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );

	// Create table for tracking links.
	$links_table = $wpdb->prefix . 'qr_trackr_links';
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table creation, variable interpolation is safe here.
	$sql_links = "CREATE TABLE $links_table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        post_id BIGINT UNSIGNED NOT NULL,
        destination_url TEXT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY post_id (post_id)
    ) $charset_collate;";
	dbDelta( $sql_links );
}
register_activation_hook( __FILE__, 'qr_trackr_activate' );

// On plugin activation, check for pretty permalinks and store a flag if not set.
// Register admin menu.
// --- New QR code creation form. ---
// --- End new QR code creation form. ---
// Render the new WP_List_Table.
// General stats.
// Render the new WP_List_Table.
// Enqueue admin scripts and styles, pass debug mode to JS.
// Inline script for expanding/collapsing update destination rows.
// Add QR Trackr quicklink to post/page list rows.
// Link to QR Trackr overview with ?new_post_id=ID to pre-select in the dropdown.
// Handle QR code generation action from list.
// Add scan count column to post/page list.
// Adds a scan count column to post/page list tables.
// Render QR Scans column content.
// Migration/verification for qr_trackr_links table schema.
// Try to add missing columns.
// On admin_init, check permalinks and show notice if needed.
