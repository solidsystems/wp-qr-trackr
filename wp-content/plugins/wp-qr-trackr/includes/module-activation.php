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

	// Create/update tables using dbDelta (WordPress handles transactions internally).
	$links_result = dbDelta( $links_sql );
	$scans_result = dbDelta( $scans_sql );

	// Log table creation results for debugging.
	if ( function_exists( 'qr_trackr_debug_log' ) ) {
		qr_trackr_debug_log( 'Links table creation result: ' . wp_json_encode( $links_result ) );
		qr_trackr_debug_log( 'Scans table creation result: ' . wp_json_encode( $scans_result ) );
	}

	// Create upload directory.
	$upload_dir = wp_upload_dir();
	$qr_dir     = $upload_dir['basedir'] . '/qr-trackr';

	if ( ! file_exists( $qr_dir ) ) {
		if ( ! wp_mkdir_p( $qr_dir ) ) {
			if ( function_exists( 'qr_trackr_debug_log' ) ) {
				qr_trackr_debug_log( 'Failed to create QR code upload directory: ' . $qr_dir );
			}
			wp_die(
				esc_html__( 'Failed to create QR code upload directory. Please check file permissions.', 'wp-qr-trackr' ),
				esc_html__( 'Plugin Activation Error', 'wp-qr-trackr' ),
				array(
					'response'  => 500,
					'back_link' => true,
				)
			);
		}

		// Create .htaccess to allow image access but prevent directory browsing.
		$htaccess_content = "# QR Trackr - Allow image access, prevent directory browsing\n";
		$htaccess_content .= "Options -Indexes\n";
		$htaccess_content .= "<FilesMatch \"\\.(png|jpg|jpeg|gif|svg)$\">\n";
		$htaccess_content .= "    Allow from all\n";
		$htaccess_content .= "</FilesMatch>\n";

		$htaccess = $qr_dir . '/.htaccess';
		if ( ! file_put_contents( $htaccess, $htaccess_content ) ) {
			if ( function_exists( 'qr_trackr_debug_log' ) ) {
				qr_trackr_debug_log( 'Failed to create .htaccess file: ' . $htaccess );
			}
			// Don't fail activation for .htaccess creation failure.
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
	add_option( 'qr_trackr_migration_completed', true );

	// Log successful activation.
	if ( function_exists( 'qr_trackr_debug_log' ) ) {
		qr_trackr_debug_log( 'QR Trackr plugin activated successfully' );
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

		// Drop tables (WordPress handles this safely).
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
		delete_option( 'qr_trackr_migration_completed' );

		// Delete QR code files.
		$upload_dir = wp_upload_dir();
		$qr_dir     = $upload_dir['basedir'] . '/qr-trackr';

		if ( file_exists( $qr_dir ) ) {
			$files = glob( $qr_dir . '/*' );
			if ( $files ) {
				foreach ( $files as $file ) {
					if ( is_file( $file ) ) {
						wp_delete_file( $file );
					}
				}
			}
			rmdir( $qr_dir );
		}

		// Clear caches.
		wp_cache_flush();

		// Log successful cleanup.
		if ( function_exists( 'qr_trackr_debug_log' ) ) {
			qr_trackr_debug_log( 'QR Trackr plugin data cleaned up successfully' );
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

	// Create/update tables using dbDelta.
	$links_result = dbDelta( $links_sql );
	$scans_result = dbDelta( $scans_sql );

	// Log results for debugging.
	if ( function_exists( 'qr_trackr_debug_log' ) ) {
		qr_trackr_debug_log( 'Links table creation result: ' . wp_json_encode( $links_result ) );
		qr_trackr_debug_log( 'Scans table creation result: ' . wp_json_encode( $scans_result ) );
	}

	// Check if tables exist after creation.
	$links_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}qr_trackr_links'" );
	$scans_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}qr_trackr_scans'" );

	if ( $links_exists && $scans_exists ) {
		if ( function_exists( 'qr_trackr_debug_log' ) ) {
			qr_trackr_debug_log( 'Database tables verified successfully.' );
		}
		return true;
	} else {
		if ( function_exists( 'qr_trackr_debug_log' ) ) {
			qr_trackr_debug_log( 'Database table verification failed. Links: ' . ( $links_exists ? 'exists' : 'missing' ) . ', Scans: ' . ( $scans_exists ? 'exists' : 'missing' ) );
		}
		return false;
	}
}

// Initialize the plugin.
qr_trackr_init();

if ( function_exists( 'qr_trackr_debug_log' ) ) {
	qr_trackr_debug_log( 'Loaded module-activation.php.' );
}
