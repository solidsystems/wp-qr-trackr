<?php
/**
 * Activation Module for QR Trackr plugin.
 *
 * Handles plugin activation, deactivation, and database setup.
 *
 * @package QR_Trackr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( function_exists( 'qr_trackr_debug_log' ) ) {
	qr_trackr_debug_log( 'Loading module-activation.php...' );
}

/**
 * Create or update the database tables for QR Trackr.
 *
 * @return void
 */
function qr_trackr_create_tables() {
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}qr_trackr_links (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		post_id bigint(20) NOT NULL,
		destination_url varchar(255) NOT NULL,
		qr_code_url varchar(255) DEFAULT NULL,
		created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		access_count bigint(20) NOT NULL DEFAULT 0,
		last_accessed datetime DEFAULT NULL,
		PRIMARY KEY  (id),
		KEY post_id (post_id),
		KEY destination_url (destination_url(191))
	) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );

	$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}qr_trackr_scans (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		link_id bigint(20) NOT NULL,
		user_agent varchar(255) DEFAULT NULL,
		ip_address varchar(45) DEFAULT NULL,
		scanned_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		KEY link_id (link_id),
		KEY scanned_at (scanned_at)
	) $charset_collate;";

	dbDelta( $sql );

	qr_trackr_migrate_links_table();
}

/**
 * Migrate the links table to add qr_code column if missing.
 *
 * @return void
 */
function qr_trackr_migrate_links_table() {
	global $wpdb;
	$column = $wpdb->get_results( $wpdb->prepare( "SHOW COLUMNS FROM `{$wpdb->prefix}qr_trackr_links` LIKE %s", 'qr_code' ) );
	if ( empty( $column ) ) {
		$wpdb->query( "ALTER TABLE `{$wpdb->prefix}qr_trackr_links` ADD COLUMN `qr_code` VARCHAR(32) UNIQUE DEFAULT NULL" );
		qr_trackr_debug_log( 'Migration: Added qr_code column to qr_trackr_links table.' );
	}
	$rows = $wpdb->get_results( "SELECT id, qr_code FROM `{$wpdb->prefix}qr_trackr_links` WHERE qr_code IS NULL OR qr_code = ''" );
	foreach ( $rows as $row ) {
		$qr_code = substr( str_shuffle( 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789' ), 0, 8 );
		$wpdb->update( $wpdb->prefix . 'qr_trackr_links', array( 'qr_code' => $qr_code ), array( 'id' => $row->id ), array( '%s' ), array( '%d' ) );
	}
}

/**
 * Plugin activation hook.
 *
 * @param bool $network_wide Whether the plugin is being activated network-wide.
 * @return void
 */
function qr_trackr_activate( $network_wide ) {
	if ( is_multisite() && $network_wide ) {
		global $wpdb;
		$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
		foreach ( $blog_ids as $blog_id ) {
			switch_to_blog( $blog_id );
			qr_trackr_create_tables();
			restore_current_blog();
		}
	} else {
		qr_trackr_create_tables();
	}

	// Flush rewrite rules to ensure our custom endpoint is active.
	qr_trackr_flush_rewrite_rules();
}

/**
 * Flush rewrite rules to apply changes.
 *
 * This function is typically called on plugin activation to ensure the new QR
 * code rewrite rules are recognized by WordPress. It checks for an admin context
 * to avoid running on the frontend.
 */
function qr_trackr_flush_rewrite_rules() {
	// Ensure this only runs in the admin context.
	if ( ! is_admin() ) {
		// Ensure rewrite rules are flushed.
		flush_rewrite_rules();
	}
}

/**
 * Plugin deactivation hook.
 *
 * @return void
 */
function qr_trackr_deactivate() {
	qr_trackr_debug_log( '--- QR Trackr deactivation started ---' );
	try {
		flush_rewrite_rules();
		qr_trackr_debug_log( 'Rewrite rules flushed on deactivation.' );

		// (Add any deactivation cleanup logic here).
		qr_trackr_debug_log( '--- QR Trackr deactivation complete ---' );
	} catch ( Exception $e ) {
		qr_trackr_debug_log( 'Error during deactivation: ' . $e->getMessage() );
	} catch ( Throwable $e ) {
		qr_trackr_debug_log( 'Fatal error during deactivation: ' . $e->getMessage() );
	}
}

/**
 * Plugin uninstall hook.
 *
 * @return void
 */
function qr_trackr_uninstall() {
	qr_trackr_debug_log( '--- QR Trackr uninstall started ---' );
	global $wpdb;
	try {
		$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}qr_trackr_links`" );
		$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}qr_trackr_scans`" );
		delete_option( 'qr_trackr_version' );
		delete_option( 'qr_trackr_verify_destinations' );
		delete_option( 'qr_trackr_library_missing' );
		qr_trackr_debug_log( '--- QR Trackr uninstall complete ---' );
	} catch ( Exception $e ) {
		qr_trackr_debug_log( 'Error during uninstall: ' . $e->getMessage() );
	} catch ( Throwable $e ) {
		qr_trackr_debug_log( 'Fatal error during uninstall: ' . $e->getMessage() );
	}
}

register_activation_hook( QR_TRACKR_PLUGIN_DIR . 'wp-qr-trackr.php', 'qr_trackr_activate' );

register_deactivation_hook( QR_TRACKR_PLUGIN_DIR . 'wp-qr-trackr.php', 'qr_trackr_deactivate' );

register_uninstall_hook( QR_TRACKR_PLUGIN_DIR . 'wp-qr-trackr.php', 'qr_trackr_uninstall' );

if ( function_exists( 'qr_trackr_debug_log' ) ) {
	qr_trackr_debug_log( 'Loaded module-activation.php.' );
}
