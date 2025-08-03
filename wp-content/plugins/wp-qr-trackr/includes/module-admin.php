<?php
/**
 * Admin module for WP QR Trackr plugin.
 *
 * @package WP_QR_TRACKR
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Debug message to confirm module is loaded.
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only.
	error_log( 'QR Trackr: Admin module loaded' );
}

// Register admin menu hook directly to ensure it fires.
add_action( 'admin_menu', 'qrc_admin_menu' );

// Register settings and script enqueuing directly to ensure they fire.
add_action( 'admin_init', 'qrc_register_settings' );
add_action( 'admin_enqueue_scripts', 'qrc_admin_enqueue_scripts' );

// Only load admin functionality in admin context.
if ( ! is_admin() ) {
	return;
}

/**
 * Register admin menu items.
 *
 * @since 1.0.0
 * @return void
 */
function qrc_admin_menu() {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
		qr_trackr_debug_log( 'QR Trackr: qrc_admin_menu() called. Hook: ' . current_filter() . ', User: ' . get_current_user_id() );
	}

	// Add main menu item.
	$hook = add_menu_page(
		__( 'QR Code Links', 'wp-qr-trackr' ),
		__( 'QR Codes', 'wp-qr-trackr' ),
		'manage_options',
		'qr-code-links',
		'qrc_admin_page',
		'dashicons-admin-links'
	);
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
		qr_trackr_debug_log( 'QR Trackr: Menu page added with hook: ' . ( $hook ? $hook : 'failed' ) );
	}

	// Add submenu items.
	$add_new = add_submenu_page(
		'qr-code-links',
		__( 'Add New QR Code', 'wp-qr-trackr' ),
		__( 'Add New', 'wp-qr-trackr' ),
		'manage_options',
		'qr-code-add-new',
		'qrc_add_new_page'
	);

	$settings = add_submenu_page(
		'qr-code-links',
		__( 'Settings', 'wp-qr-trackr' ),
		__( 'Settings', 'wp-qr-trackr' ),
		'manage_options',
		'qr-code-settings',
		'qrc_settings_page'
	);
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
		qr_trackr_debug_log( 'QR Trackr: Settings page added with hook: ' . ( $settings ? $settings : 'failed' ) );
	}
}

/**
 * Register plugin settings.
 *
 * @since 1.0.0
 * @return void
 */
function qrc_register_settings() {
	// Register a new settings section.
	add_settings_section(
		'qr_trackr_general_settings',
		__( 'General Settings', 'wp-qr-trackr' ),
		'qrc_general_settings_section_callback',
		'qr_trackr_settings'
	);

	// Register settings fields.
	register_setting( 'qr_trackr_settings', 'qr_trackr_qr_size' );
	register_setting( 'qr_trackr_settings', 'qr_trackr_tracking_enabled' );

	// Add settings fields.
	add_settings_field(
		'qr_trackr_qr_size',
		__( 'QR Code Size', 'wp-qr-trackr' ),
		'qrc_qr_size_field_callback',
		'qr_trackr_settings',
		'qr_trackr_general_settings'
	);

	add_settings_field(
		'qr_trackr_tracking_enabled',
		__( 'Enable Tracking', 'wp-qr-trackr' ),
		'qrc_tracking_enabled_field_callback',
		'qr_trackr_settings',
		'qr_trackr_general_settings'
	);
}

/**
 * Callback for the general settings section description.
 *
 * @since 1.0.0
 * @return void
 */
function qrc_general_settings_section_callback() {
	echo '<p>' . esc_html__( 'Configure general settings for QR code generation and tracking.', 'wp-qr-trackr' ) . '</p>';
}

/**
 * Callback for the QR code size field in settings.
 *
 * @since 1.0.0
 * @return void
 */
function qrc_qr_size_field_callback() {
	$size = get_option( 'qr_trackr_qr_size', 'medium' );
	?>
	<select name="qr_trackr_qr_size">
		<option value="small" <?php selected( $size, 'small' ); ?>><?php esc_html_e( 'Small (100px)', 'wp-qr-trackr' ); ?>
		</option>
		<option value="medium" <?php selected( $size, 'medium' ); ?>>
			<?php esc_html_e( 'Medium (150px)', 'wp-qr-trackr' ); ?>
		</option>
		<option value="large" <?php selected( $size, 'large' ); ?>><?php esc_html_e( 'Large (200px)', 'wp-qr-trackr' ); ?>
		</option>
	</select>
	<p class="description"><?php esc_html_e( 'Choose the default size for generated QR codes.', 'wp-qr-trackr' ); ?></p>
	<?php
}

/**
 * Callback for the tracking enabled field in settings.
 *
 * @since 1.0.0
 * @return void
 */
function qrc_tracking_enabled_field_callback() {
	$enabled = get_option( 'qr_trackr_tracking_enabled', '1' );
	?>
	<input type="checkbox" name="qr_trackr_tracking_enabled" value="1" <?php checked( '1', $enabled ); ?> />
	<span class="description"><?php esc_html_e( 'Track QR code scans and store analytics.', 'wp-qr-trackr' ); ?></span>
	<?php
}

/**
 * Display the admin page content.
 *
 * @since 1.0.0
 * @return void
 */
function qrc_admin_page() {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
		qr_trackr_debug_log( 'QR Trackr: qrc_admin_page() called' );
	}

	// Add Query Monitor logging.
	if ( function_exists( 'do_action' ) ) {
		do_action( 'qm_debug', 'QR Trackr: qrc_admin_page() function called' );
	}

	// Check user capabilities.
	if ( ! current_user_can( 'manage_options' ) ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only.
		error_log( 'QR Trackr: User does not have manage_options capability' );
		do_action( 'qm_error', 'QR Trackr: User does not have manage_options capability in qrc_admin_page' );
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wp-qr-trackr' ) );
	}

	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only.
	error_log( 'QR Trackr: User capabilities check passed' );
	do_action( 'qm_debug', 'QR Trackr: User capabilities check passed' );

	// Load list table class if not already loaded.
	if ( ! class_exists( 'QRC_Links_List_Table' ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
			qr_trackr_debug_log( 'QR Trackr: Loading list table class.' );
		}
		do_action( 'qm_debug', 'QR Trackr: Loading QRC_Links_List_Table class' );
		require_once __DIR__ . '/class-qrc-links-list-table.php';
	}

	do_action( 'qm_debug', 'QR Trackr: About to create list table instance' );

	// Check if database table exists before creating list table.
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';

	// Use caching for table existence check.
	$cache_key    = 'qr_trackr_table_exists_' . $table_name;
	$table_exists = wp_cache_get( $cache_key );

	if ( false === $table_exists ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Table existence check with caching.
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );
		wp_cache_set( $cache_key, $table_exists ? 'exists' : 'missing', '', 300 );
	} else {
		$table_exists = ( 'exists' === $table_exists );
	}

	if ( ! $table_exists ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only.
		error_log( 'QR Trackr: Database table does not exist: ' . $table_name );
		do_action( 'qm_error', 'QR Trackr: Database table does not exist', array( 'table_name' => $table_name ) );

		// Try to create the table.
		if ( function_exists( 'qrc_activate' ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only.
			error_log( 'QR Trackr: Attempting to create database table' );
			do_action( 'qm_debug', 'QR Trackr: Attempting to create database table' );
			qrc_activate();

			// Clear the cache after table creation.
			wp_cache_delete( $cache_key );
		} else {
			wp_die( esc_html__( 'QR Trackr: Database table not found and cannot be created. Please deactivate and reactivate the plugin.', 'wp-qr-trackr' ) );
		}
	}

	// Create an instance of our list table class.
	try {
		$list_table = new QRC_Links_List_Table();
		do_action( 'qm_debug', 'QR Trackr: List table instance created successfully' );
	} catch ( Exception $e ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only.
		error_log( 'QR Trackr: Error creating list table instance: ' . $e->getMessage() );
		do_action( 'qm_error', 'QR Trackr: Error creating list table instance', array( 'exception' => $e ) );
		wp_die( esc_html__( 'QR Trackr: Error creating list table. Please check the logs.', 'wp-qr-trackr' ) );
	}

	do_action( 'qm_debug', 'QR Trackr: About to prepare list table items' );

	try {
		$list_table->prepare_items();
		do_action( 'qm_debug', 'QR Trackr: List table items prepared successfully' );
	} catch ( Exception $e ) {
		do_action( 'qm_error', 'QR Trackr: Error preparing list table items', array( 'exception' => $e ) );
		wp_die( esc_html__( 'QR Trackr: Error preparing list table items. Please check the logs.', 'wp-qr-trackr' ) );
	}

	// Include the admin page template with multiple fallback paths.
	$possible_paths = array(
		QR_TRACKR_PLUGIN_DIR . 'templates/admin-page.php',
		dirname( __DIR__ ) . '/templates/admin-page.php',
		plugin_dir_path( __FILE__ ) . '../templates/admin-page.php',
		ABSPATH . 'wp-content/plugins/wp-qr-trackr/templates/admin-page.php',
	);

	$template_found = false;
	$template_path  = '';

	// Debug all possible paths.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only.
		error_log( 'QR Trackr: QR_TRACKR_PLUGIN_DIR: ' . QR_TRACKR_PLUGIN_DIR );
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only.
		error_log( 'QR Trackr: __DIR__: ' . __DIR__ );
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only.
		error_log( 'QR Trackr: ABSPATH: ' . ABSPATH );
		foreach ( $possible_paths as $index => $path ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only.
			error_log( 'QR Trackr: Path ' . $index . ': ' . $path . ' (exists: ' . ( file_exists( $path ) ? 'true' : 'false' ) . ')' );
		}
	}

	do_action( 'qm_debug', 'QR Trackr: Checking template paths', array( 'paths' => $possible_paths ) );

	// Try each possible path.
	foreach ( $possible_paths as $path ) {
		if ( file_exists( $path ) ) {
			$template_path  = $path;
			$template_found = true;
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only.
				error_log( 'QR Trackr: Found template at: ' . $path );
			}
			do_action( 'qm_debug', 'QR Trackr: Found template at path', array( 'path' => $path ) );
			break;
		}
	}

	if ( $template_found ) {
		do_action( 'qm_debug', 'QR Trackr: About to include template', array( 'template_path' => $template_path ) );
		try {
			include $template_path;
			do_action( 'qm_debug', 'QR Trackr: Template included successfully' );
		} catch ( Exception $e ) {
			do_action(
				'qm_error',
				'QR Trackr: Error including template',
				array(
					'exception'     => $e,
					'template_path' => $template_path,
				)
			);
			wp_die( esc_html__( 'QR Trackr: Error loading template. Please check the logs.', 'wp-qr-trackr' ) );
		}
	} else {
		// Show detailed error with all attempted paths.
		$error_message = 'QR Trackr: Admin page template not found. Attempted paths:' . "\n";
		foreach ( $possible_paths as $index => $path ) {
			$error_message .= '- ' . $path . "\n";
		}
		$error_message .= "\nPlease check plugin installation and file permissions.";

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only.
			error_log( $error_message );
		}

		do_action( 'qm_error', 'QR Trackr: Template not found', array( 'attempted_paths' => $possible_paths ) );
		wp_die( esc_html__( 'QR Trackr: Admin page template not found. Please check plugin installation.', 'wp-qr-trackr' ) );
	}
}

/**
 * Display the add new QR code page content.
 *
 * @since 1.0.0
 * @return void
 */
function qrc_add_new_page() {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
		qr_trackr_debug_log( 'QR Trackr: qrc_add_new_page() called' );
	}

	// Include the add new page template with multiple fallback paths.
	$possible_paths = array(
		QR_TRACKR_PLUGIN_DIR . 'templates/add-new-page.php',
		dirname( __DIR__ ) . '/templates/add-new-page.php',
		plugin_dir_path( __FILE__ ) . '../templates/add-new-page.php',
		ABSPATH . 'wp-content/plugins/wp-qr-trackr/templates/add-new-page.php',
	);

	$template_found = false;
	$template_path  = '';

	// Try each possible path.
	foreach ( $possible_paths as $path ) {
		if ( file_exists( $path ) ) {
			$template_path  = $path;
			$template_found = true;
			break;
		}
	}

	if ( $template_found ) {
		include $template_path;
	} else {
		wp_die( esc_html__( 'QR Trackr: Add new page template not found. Please check plugin installation.', 'wp-qr-trackr' ) );
	}
}



/**
 * Enqueue admin scripts and styles.
 *
 * @since 1.0.0
 * @param string $hook The current admin page.
 * @return void
 */
/**
 * Enqueue admin scripts and styles.
 *
 * @since 1.0.0
 * @param string $hook The current admin page hook.
 * @return void
 */
function qrc_admin_enqueue_scripts( $hook ) {
	// Check if we're on a QR Trackr page by looking at the hook.
	$qr_trackr_hooks = array( 'toplevel_page_qr-code-links', 'qr-code-links_page_qr-code-add-new', 'qr-code-links_page_qr-code-settings' );

	// For debugging, load on any page that might be related.
	if ( ! in_array( $hook, $qr_trackr_hooks, true ) && strpos( $hook, 'qr-code' ) === false ) {
		return;
	}

	wp_enqueue_script(
		'qr-trackr-admin',
		QR_TRACKR_PLUGIN_URL . 'assets/qrc-admin.js',
		array( 'jquery' ),
		QR_TRACKR_VERSION,
		true
	);

	// Localize the script with AJAX data.
	wp_localize_script(
		'qr-trackr-admin',
		'qr_trackr_ajax',
		array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'qr_trackr_nonce' ),
		)
	);

	// Localize script for admin strings.
	wp_localize_script(
		'qr-trackr-admin',
		'qrcAdmin',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'qr_trackr_nonce' ),
			'strings' => array(
				'qrCodeDetails'     => __( 'QR Code Details', 'wp-qr-trackr' ),
				'loading'           => __( 'Loading...', 'wp-qr-trackr' ),
				'statistics'        => __( 'Statistics', 'wp-qr-trackr' ),
				'totalScans'        => __( 'Total Scans', 'wp-qr-trackr' ),
				'recentScans'       => __( 'Recent Scans (30 days)', 'wp-qr-trackr' ),
				'lastAccessed'      => __( 'Last Accessed', 'wp-qr-trackr' ),
				'created'           => __( 'Created', 'wp-qr-trackr' ),
				'commonName'        => __( 'Common Name', 'wp-qr-trackr' ),
				'enterFriendlyName' => __( 'Enter a friendly name', 'wp-qr-trackr' ),
				'commonNameDesc'    => __( 'A friendly name to help you identify this QR code.', 'wp-qr-trackr' ),
				'referralCode'      => __( 'Referral Code', 'wp-qr-trackr' ),
				'enterReferralCode' => __( 'Enter a referral code', 'wp-qr-trackr' ),
				'referralCodeDesc'  => __( 'A referral code for tracking and analytics.', 'wp-qr-trackr' ),
				'qrCode'            => __( 'QR Code', 'wp-qr-trackr' ),
				'qrUrl'             => __( 'QR URL', 'wp-qr-trackr' ),
				'destinationUrl'    => __( 'Destination URL', 'wp-qr-trackr' ),
				'linkedPost'        => __( 'Linked Post', 'wp-qr-trackr' ),
				'close'             => __( 'Close', 'wp-qr-trackr' ),
				'saveChanges'       => __( 'Save Changes', 'wp-qr-trackr' ),
			),
		)
	);
}

/**
 * Display the settings page content.
 *
 * @since 1.0.0
 * @return void
 */
function qrc_settings_page() {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
		qr_trackr_debug_log( 'QR Trackr: qrc_settings_page() called' );
	}

	// Check user capabilities.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wp-qr-trackr' ) );
	}

	// Include the settings page template with multiple fallback paths.
	$possible_paths = array(
		QR_TRACKR_PLUGIN_DIR . 'templates/settings-page.php',
		dirname( __DIR__ ) . '/templates/settings-page.php',
		plugin_dir_path( __FILE__ ) . '../templates/settings-page.php',
		ABSPATH . 'wp-content/plugins/wp-qr-trackr/templates/settings-page.php',
	);

	$template_found = false;
	$template_path  = '';

	// Try each possible path.
	foreach ( $possible_paths as $path ) {
		if ( file_exists( $path ) ) {
			$template_path  = $path;
			$template_found = true;
			break;
		}
	}

	if ( $template_found ) {
		include $template_path;
	} else {
		wp_die( esc_html__( 'QR Trackr: Settings page template not found. Please check plugin installation.', 'wp-qr-trackr' ) );
	}
}

/**
 * Load test script if requested.
 *
 * @since 1.2.24
 * @return void
 */
function qrc_load_test_script() {
	if ( isset( $_GET['test_qr'] ) && current_user_can( 'manage_options' ) ) {
		include QR_TRACKR_PLUGIN_DIR . 'test-qr-generation.php';
	}
}



/**
 * Handle delete action for QR codes.
 *
 * @since 1.2.24
 * @return void
 */
function qrc_handle_delete_action() {
	// Check if we're on the QR codes page and action is delete.
	if ( ! isset( $_GET['page'] ) || 'qr-code-links' !== sanitize_text_field( wp_unslash( $_GET['page'] ) ) || ! isset( $_GET['action'] ) || 'delete' !== sanitize_text_field( wp_unslash( $_GET['action'] ) ) ) {
		return;
	}

	// Debug logging (only to error log, not output).
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only.
		error_log( sprintf( 'QR Trackr: Delete action handler called. Page: %s, Action: %s', isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : 'not set', isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : 'not set' ) );
	}

	// Check user capabilities.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to delete QR codes.', 'wp-qr-trackr' ) );
	}

	// Get and validate QR code ID.
	$qr_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	if ( 0 === $qr_id ) {
		wp_die( esc_html__( 'Invalid QR code ID.', 'wp-qr-trackr' ) );
	}

	// Verify nonce with proper sanitization.
	$nonce_action = 'delete_qr_code_' . $qr_id;
	$nonce_field  = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

	if ( empty( $nonce_field ) || ! wp_verify_nonce( $nonce_field, $nonce_action ) ) {
		wp_die( esc_html__( 'Security check failed.', 'wp-qr-trackr' ) );
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';

	// Get QR code details before deletion for logging with caching.
	$cache_key = 'qr_trackr_details_' . $qr_id;
	$qr_code   = wp_cache_get( $cache_key );

	if ( false === $qr_code ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Single record lookup with caching.
		$qr_code = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}qr_trackr_links WHERE id = %d",
				$qr_id
			)
		);

		if ( $qr_code ) {
			wp_cache_set( $cache_key, $qr_code, '', 300 );
		}
	}

	if ( ! $qr_code ) {
		wp_die( esc_html__( 'QR code not found.', 'wp-qr-trackr' ) );
	}

	// Delete the QR code.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Delete operation with cache invalidation.
	$result = $wpdb->delete(
		$table_name,
		array( 'id' => $qr_id ),
		array( '%d' )
	);

	if ( false === $result ) {
		wp_die( esc_html__( 'Failed to delete QR code.', 'wp-qr-trackr' ) );
	}

	// Log the deletion for debugging.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
		qr_trackr_debug_log(
			sprintf(
				'QR Trackr: QR code deleted. ID: %d, QR Code: %s, Destination: %s',
				$qr_id,
				$qr_code->qr_code,
				$qr_code->destination_url
			)
		);
	}

	// Clear relevant caches.
	wp_cache_delete( 'qr_trackr_details_' . $qr_id );
	wp_cache_delete( 'qr_trackr_all_links_admin', 'qr_trackr' );

	// Delete QR code image file if it exists using WordPress file functions.
	if ( ! empty( $qr_code->qr_code_url ) ) {
		$upload_dir   = wp_upload_dir();
		$qr_file_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $qr_code->qr_code_url );

		if ( file_exists( $qr_file_path ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Using WordPress upload directory structure.
			unlink( $qr_file_path );
		}
	}

	// Redirect back to the QR codes list with success message.
	$redirect_url = add_query_arg(
		array(
			'page'    => 'qr-code-links',
			'deleted' => '1',
		),
		admin_url( 'admin.php' )
	);

	// Ensure no output has been sent before redirecting.
	if ( ! headers_sent() ) {
		wp_safe_redirect( $redirect_url );
		exit;
	} else {
		// If headers were already sent, use JavaScript redirect as fallback.
		echo '<script>window.location.href = "' . esc_js( $redirect_url ) . '";</script>';
		exit;
	}
}



/**
 * Display admin notices for delete action.
 *
 * @since 1.2.24
 * @return void
 */
function qrc_admin_notices() {
	// Check if we're on the QR codes page.
	if ( ! isset( $_GET['page'] ) || 'qr-code-links' !== $_GET['page'] ) {
		return;
	}

	// Display success message for deleted QR code.
	if ( isset( $_GET['deleted'] ) && '1' === $_GET['deleted'] ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'QR code deleted successfully.', 'wp-qr-trackr' ) . '</p></div>';
	}
}
add_action( 'admin_notices', 'qrc_admin_notices' );

// Register hooks on admin_init to ensure WordPress admin is ready.
// add_action('admin_init', 'qrc_register_admin_hooks'); // This line is removed as per the edit hint.

// TEMPORARY: Direct registration of admin script enqueuing
// add_action('admin_enqueue_scripts', 'qrc_admin_enqueue_scripts'); // This line is removed as per the edit hint.
