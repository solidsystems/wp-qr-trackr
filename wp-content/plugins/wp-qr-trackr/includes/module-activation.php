<?php
/**
 * Plugin activation module for QR Trackr.
 *
 * Handles database table creation and permalink checks on plugin activation.
 *
 * @package QR_Trackr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin activation: create DB tables and check permalinks.
 *
 * @return void
 */
function qr_trackr_activate() {
	global $wpdb;
	qr_trackr_debug_log( 'Plugin activation started.' );
	$table_name      = $wpdb->prefix . 'qr_trackr_scans';
	$charset_collate = $wpdb->get_charset_collate();
	$sql             = "CREATE TABLE $table_name (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        post_id BIGINT UNSIGNED NOT NULL,
        scan_time DATETIME NOT NULL,
        user_agent TEXT,
        ip_address VARCHAR(45),
        PRIMARY KEY  (id),
        KEY post_id (post_id)
    ) $charset_collate;";
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	qr_trackr_debug_log( 'Creating scans table', $sql );
	dbDelta( $sql );

	$links_table = $wpdb->prefix . 'qr_trackr_links';
	$sql_links   = "CREATE TABLE $links_table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        post_id BIGINT UNSIGNED NOT NULL,
        destination_url TEXT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY post_id (post_id)
    ) $charset_collate;";
	qr_trackr_debug_log( 'Creating links table', $sql_links );
	dbDelta( $sql_links );
	qr_trackr_debug_log( 'Plugin activation complete.' );
}
register_activation_hook( QR_TRACKR_PLUGIN_DIR . 'qr-trackr.php', 'qr_trackr_activate' );

register_activation_hook(
	QR_TRACKR_PLUGIN_DIR . 'qr-trackr.php',
	/**
	 * Check and update permalink option on plugin activation.
	 *
	 * @return void
	 */
	function () {
		qr_trackr_debug_log( 'Activation: Checking permalink structure.' );
		if ( '' === get_option( 'permalink_structure' ) ) {
			update_option( 'qr_trackr_permalinks_plain', '1' );
			qr_trackr_debug_log( 'Permalinks are plain. Option set.' );
		} else {
			delete_option( 'qr_trackr_permalinks_plain' );
			qr_trackr_debug_log( 'Permalinks are not plain. Option removed.' );
		}
	}
);
