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
	$table_name      = $wpdb->prefix . 'qr_trackr_links';

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

	// Create scans table.
	$scans_table = $wpdb->prefix . 'qr_trackr_scans';
	$sql         = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}qr_trackr_scans (
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

	// Run migration for qr_code_url column.
	qr_trackr_migrate_links_table();
}

/**
 * Migrate the links table to add qr_code column if missing.
 *
 * @return void
 */
function qr_trackr_migrate_links_table() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';
	$column     = $wpdb->get_results( $wpdb->prepare( "SHOW COLUMNS FROM `{$wpdb->prefix}qr_trackr_links` LIKE %s", 'qr_code' ) );
	if ( empty( $column ) ) {
		$wpdb->query( "ALTER TABLE `{$wpdb->prefix}qr_trackr_links` ADD COLUMN `qr_code` VARCHAR(32) UNIQUE DEFAULT NULL" );
		qr_trackr_debug_log( 'Migration: Added qr_code column to qr_trackr_links table.' );
	}
	// Backfill qr_code for existing rows if missing.
	$rows = $wpdb->get_results( "SELECT id, qr_code FROM `{$wpdb->prefix}qr_trackr_links` WHERE qr_code IS NULL OR qr_code = ''" );
	foreach ( $rows as $row ) {
		$qr_code = substr( str_shuffle( 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789' ), 0, 8 );
		$wpdb->update( $table_name, array( 'qr_code' => $qr_code ), array( 'id' => $row->id ), array( '%s' ), array( '%d' ) );
	}
}

/**
 * Plugin activation hook.
 *
 * @return void
 * @throws Exception|Throwable If activation fails.
 */
function qr_trackr_activate() {
	qr_trackr_debug_log( '--- QR Trackr activation started ---' );
	try {
		// Check requirements first.
		if ( ! function_exists( 'qr_trackr_check_requirements' ) ) {
			qr_trackr_debug_log( 'Requirements module not loaded during activation.' );
			wp_die( esc_html__( 'QR Trackr: Required module not loaded. Please deactivate and reactivate the plugin.', 'wp-qr-trackr' ) );
		}

		$requirements = qr_trackr_check_requirements();
		qr_trackr_debug_log( 'Requirements checked: ' . wp_json_encode( $requirements ) );

		// Store requirements status for later use.
		update_option( 'qr_trackr_requirements', $requirements );

		// Only block activation if library is missing.
		if ( ! $requirements['library'] ) {
			qr_trackr_debug_log( 'QR code library missing during activation.' );
			wp_die(
				esc_html__( 'QR Trackr: The QR code library is required but not available. Please install the required dependencies and try again.', 'wp-qr-trackr' ),
				'Plugin Activation Error',
				array( 'back_link' => true )
			);
		}

		// For other requirements, show admin notice but allow activation.
		if ( ! $requirements['permalinks'] ) {
			add_option( 'qr_trackr_permalinks_missing', true );
			qr_trackr_debug_log( 'Pretty permalinks not enabled during activation.' );
		}

		if ( ! $requirements['uploads'] ) {
			add_option( 'qr_trackr_uploads_missing', true );
			qr_trackr_debug_log( 'Uploads directory not writable during activation.' );
		}

		// Create database tables and run migrations.
		qr_trackr_debug_log( 'Creating database tables...' );
		qr_trackr_create_tables();
		qr_trackr_debug_log( 'Database tables created.' );

		// Register rewrite rules and flush them.
		qr_trackr_register_rewrite_rules();
		flush_rewrite_rules();
		qr_trackr_debug_log( 'Rewrite rules flushed.' );

		// Add version to options.
		add_option( 'qr_trackr_version', '1.0.2' );
		// Add default settings.
		add_option( 'qr_trackr_verify_destinations', '1' );

		// Check for QR code library.
		if ( ! function_exists( 'qr_trackr_check_library' ) || ! qr_trackr_check_library() ) {
			add_option( 'qr_trackr_library_missing', true );
			qr_trackr_debug_log( 'QR code library check failed during activation.' );
		}
		qr_trackr_debug_log( '--- QR Trackr activation complete ---' );
	} catch ( Exception $e ) {
		qr_trackr_debug_log( 'Error during activation: ' . $e->getMessage() );
		wp_die(
			esc_html__( 'QR Trackr: Error during activation: ', 'wp-qr-trackr' ) . esc_html( $e->getMessage() ),
			'Plugin Activation Error',
			array( 'back_link' => true )
		);
	} catch ( Throwable $e ) {
		qr_trackr_debug_log( 'Fatal error during activation: ' . $e->getMessage() );
		wp_die(
			esc_html__( 'QR Trackr: Fatal error during activation: ', 'wp-qr-trackr' ) . esc_html( $e->getMessage() ),
			'Plugin Activation Error',
			array( 'back_link' => true )
		);
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
		// Flush rewrite rules on deactivation.
		flush_rewrite_rules();
		qr_trackr_debug_log( 'Rewrite rules flushed on deactivation.' );

		// Cleanup if needed.
		// (Add any deactivation cleanup logic here.)
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
		// Drop the table.
		$table_name = $wpdb->prefix . 'qr_trackr_links';
		$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}qr_trackr_links`" );
		// Remove options.
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

// Register activation hook.
register_activation_hook( dirname( __DIR__ ) . '/wp-qr-trackr.php', 'qr_trackr_activate' );

// Register deactivation hook.
register_deactivation_hook( dirname( __DIR__ ) . '/wp-qr-trackr.php', 'qr_trackr_deactivate' );

// Register uninstall hook.
register_uninstall_hook( dirname( __DIR__ ) . '/wp-qr-trackr.php', 'qr_trackr_uninstall' );

if ( function_exists( 'qr_trackr_debug_log' ) ) {
	qr_trackr_debug_log( 'Loaded module-activation.php.' );
}
