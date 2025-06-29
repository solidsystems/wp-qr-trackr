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
 * Initialize the plugin.
 *
 * @return void
 */
function qr_trackr_init() {
	// Add settings link to plugin page.
	add_filter(
		'plugin_action_links_' . plugin_basename( QR_TRACKR_PLUGIN_FILE ),
		'qr_trackr_add_settings_link'
	);

	// Register activation and deactivation hooks.
	register_activation_hook( QR_TRACKR_PLUGIN_FILE, 'qr_trackr_activate' );
	register_deactivation_hook( QR_TRACKR_PLUGIN_FILE, 'qr_trackr_deactivate' );

	// Initialize rewrite rules.
	add_action( 'init', 'qr_trackr_add_rewrite_rules' );

	// Flush rewrite rules only when needed.
	if ( get_option( 'qr_trackr_flush_rewrite_rules' ) ) {
		add_action( 'init', 'qr_trackr_flush_rewrite_rules', 20 );
	}
}

/**
 * Add settings link to plugin page.
 *
 * @param array $links Existing plugin action links.
 * @return array Modified plugin action links.
 */
function qr_trackr_add_settings_link( $links ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return $links;
	}

	$settings_link = sprintf(
		'<a href="%s">%s</a>',
		esc_url( admin_url( 'admin.php?page=qr-trackr-settings' ) ),
		esc_html__( 'Settings', 'wp-qr-trackr' )
	);

	array_unshift( $links, $settings_link );
	return $links;
}

/**
 * Create necessary database tables and initialize plugin settings.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 * @return void
 * @throws Exception If table creation fails.
 */
function qr_trackr_activate() {
	global $wpdb;

	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	// Create or update the links table.
	$links_table     = $wpdb->prefix . 'qr_trackr_links';
	$charset_collate = $wpdb->get_charset_collate();

	$links_sql = "CREATE TABLE IF NOT EXISTS $links_table (
		id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		post_id bigint(20) UNSIGNED NULL,
		destination_url varchar(2048) NOT NULL,
		qr_code varchar(255) NOT NULL,
		scans bigint(20) UNSIGNED DEFAULT 0 NOT NULL,
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		KEY post_id (post_id),
		KEY qr_code (qr_code),
		KEY created_at (created_at)
	) $charset_collate;";

	// Create or update the scans table.
	$scans_table = $wpdb->prefix . 'qr_trackr_scans';
	$scans_sql   = "CREATE TABLE IF NOT EXISTS $scans_table (
		id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		link_id bigint(20) UNSIGNED NOT NULL,
		ip_address varchar(45) NOT NULL,
		user_agent varchar(255) NOT NULL,
		location varchar(255) DEFAULT NULL,
		scanned_at datetime DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		KEY link_id (link_id),
		KEY scanned_at (scanned_at),
		CONSTRAINT {$wpdb->prefix}qr_trackr_scans_link_id_fk
		FOREIGN KEY (link_id)
		REFERENCES $links_table(id)
		ON DELETE CASCADE
	) $charset_collate;";

	// Include WordPress upgrade functions.
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	try {
		// Start transaction.
		if ( ! $wpdb->query( 'START TRANSACTION' ) ) {
			throw new Exception( __( 'Could not start database transaction.', 'wp-qr-trackr' ) );
		}

		// Create/update tables.
		$links_result = dbDelta( $links_sql );
		$scans_result = dbDelta( $scans_sql );

		// Check for errors.
		if ( empty( $links_result ) || empty( $scans_result ) ) {
			throw new Exception( __( 'Failed to create database tables.', 'wp-qr-trackr' ) );
		}

		// Create upload directory.
		$upload_dir = wp_upload_dir();
		$qr_dir     = $upload_dir['basedir'] . '/qr-trackr';

		if ( ! file_exists( $qr_dir ) ) {
			if ( ! wp_mkdir_p( $qr_dir ) ) {
				throw new Exception( __( 'Failed to create QR code upload directory.', 'wp-qr-trackr' ) );
			}

			// Create .htaccess to protect the directory.
			$htaccess = $qr_dir . '/.htaccess';
			if ( ! file_put_contents( $htaccess, "Options -Indexes\nDeny from all\n" ) ) {
				throw new Exception( __( 'Failed to create .htaccess file.', 'wp-qr-trackr' ) );
			}
		}

		// Initialize plugin options.
		add_option( 'qr_trackr_version', QR_TRACKR_VERSION );
		add_option( 'qr_trackr_flush_rewrite_rules', true );
		add_option( 'qr_trackr_qr_size', 300 );
		add_option( 'qr_trackr_qr_margin', 10 );
		add_option( 'qr_trackr_qr_error_correction', 'L' );
		add_option( 'qr_trackr_track_location', false );
		add_option( 'qr_trackr_delete_data', false );

		// Commit transaction.
		if ( ! $wpdb->query( 'COMMIT' ) ) {
			throw new Exception( __( 'Could not commit database transaction.', 'wp-qr-trackr' ) );
		}
	} catch ( Exception $e ) {
		// Rollback on error.
		$wpdb->query( 'ROLLBACK' );

		// Log error.
		if ( function_exists( 'qr_trackr_debug_log' ) ) {
			qr_trackr_debug_log( 'Activation Error: ' . $e->getMessage() );
		}

		wp_die(
			esc_html( $e->getMessage() ),
			esc_html__( 'Plugin Activation Error', 'wp-qr-trackr' ),
			array(
				'response'  => 500,
				'back_link' => true,
			)
		);
	}
}

/**
 * Clean up plugin data on deactivation.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 * @return void
 */
function qr_trackr_deactivate() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	// Clear scheduled hooks.
	wp_clear_scheduled_hook( 'qr_trackr_cleanup_old_codes' );

	// Only delete data if the option is set.
	if ( get_option( 'qr_trackr_delete_data' ) ) {
		global $wpdb;

		// Start transaction.
		$wpdb->query( 'START TRANSACTION' );

		try {
			// Drop tables.
			$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}qr_trackr_scans" );
			$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}qr_trackr_links" );

			// Delete options.
			delete_option( 'qr_trackr_version' );
			delete_option( 'qr_trackr_flush_rewrite_rules' );
			delete_option( 'qr_trackr_qr_size' );
			delete_option( 'qr_trackr_qr_margin' );
			delete_option( 'qr_trackr_qr_error_correction' );
			delete_option( 'qr_trackr_track_location' );
			delete_option( 'qr_trackr_delete_data' );

			// Delete QR code files.
			$upload_dir = wp_upload_dir();
			$qr_dir     = $upload_dir['basedir'] . '/qr-trackr';

			if ( file_exists( $qr_dir ) ) {
				$files = glob( $qr_dir . '/*' );
				foreach ( $files as $file ) {
					if ( is_file( $file ) ) {
						wp_delete_file( $file );
					}
				}
				rmdir( $qr_dir );
			}

			// Clear caches.
			wp_cache_flush();

			// Commit transaction.
			$wpdb->query( 'COMMIT' );

		} catch ( Exception $e ) {
			// Rollback on error.
			$wpdb->query( 'ROLLBACK' );

			// Log error.
			if ( function_exists( 'qr_trackr_debug_log' ) ) {
				qr_trackr_debug_log( 'Deactivation Error: ' . $e->getMessage() );
			}
		}
	}

	// Flush rewrite rules.
	flush_rewrite_rules();
}

/**
 * Flush rewrite rules and delete the flush flag.
 *
 * @return void
 */
function qr_trackr_flush_rewrite_rules() {
	flush_rewrite_rules();
	delete_option( 'qr_trackr_flush_rewrite_rules' );
}

/**
 * Create database tables for QR Trackr.
 *
 * This function can be called independently for migrations or repairs.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 * @return bool True on success, false on failure.
 * @throws Exception If table creation fails.
 */
function qr_trackr_create_tables() {
	global $wpdb;

	// Create or update the links table.
	$links_table     = $wpdb->prefix . 'qr_trackr_links';
	$charset_collate = $wpdb->get_charset_collate();

	$links_sql = "CREATE TABLE IF NOT EXISTS $links_table (
		id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		post_id bigint(20) UNSIGNED NULL,
		destination_url varchar(2048) NOT NULL,
		qr_code varchar(255) NOT NULL,
		scans bigint(20) UNSIGNED DEFAULT 0 NOT NULL,
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		KEY post_id (post_id),
		KEY qr_code (qr_code),
		KEY created_at (created_at)
	) $charset_collate;";

	// Create or update the scans table.
	$scans_table = $wpdb->prefix . 'qr_trackr_scans';
	$scans_sql   = "CREATE TABLE IF NOT EXISTS $scans_table (
		id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		link_id bigint(20) UNSIGNED NOT NULL,
		ip_address varchar(45) NOT NULL,
		user_agent varchar(255) NOT NULL,
		location varchar(255) DEFAULT NULL,
		scanned_at datetime DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		KEY link_id (link_id),
		KEY scanned_at (scanned_at),
		CONSTRAINT {$wpdb->prefix}qr_trackr_scans_link_id_fk
		FOREIGN KEY (link_id)
		REFERENCES $links_table(id)
		ON DELETE CASCADE
	) $charset_collate;";

	// Include WordPress upgrade functions.
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	try {
		// Create/update tables.
		$links_result = dbDelta( $links_sql );
		$scans_result = dbDelta( $scans_sql );

		// Check for errors.
		if ( empty( $links_result ) || empty( $scans_result ) ) {
			throw new Exception( __( 'Failed to create database tables.', 'wp-qr-trackr' ) );
		}

		// Log success if debug is enabled.
		if ( function_exists( 'qr_trackr_debug_log' ) ) {
			qr_trackr_debug_log( 'Database tables created successfully.' );
		}

		return true;

	} catch ( Exception $e ) {
		// Log error.
		if ( function_exists( 'qr_trackr_debug_log' ) ) {
			qr_trackr_debug_log( 'Table Creation Error: ' . $e->getMessage() );
		}

		return false;
	}
}

// Initialize the plugin.
qr_trackr_init();

if ( function_exists( 'qr_trackr_debug_log' ) ) {
	qr_trackr_debug_log( 'Loaded module-activation.php.' );
}
