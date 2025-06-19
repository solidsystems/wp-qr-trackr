<?php
/**
 * QR Trackr Core Module
 *
 * Core functionality for QR code generation and tracking.
 *
 * @package QR_Trackr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Note: Setting the default timezone is intentionally omitted to comply with WordPress coding standards.

// Include core files.
require_once QR_TRACKR_PLUGIN_DIR . 'includes/class-qr-trackr-list-table.php';
require_once QR_TRACKR_PLUGIN_DIR . 'includes/class-qr-trackr-cli-command.php';
require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-activation.php';
require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-qr.php';
require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-admin.php';
require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-ajax.php';
require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-rewrite.php';
require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-debug.php';
require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-utils.php';

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
