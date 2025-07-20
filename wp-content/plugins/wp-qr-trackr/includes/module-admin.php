<?php
/**
 * Admin functionality for the QR Coder plugin.
 *
 * @package WP_QR_TRACKR
 */

// Debug message to confirm module is loaded.
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
	error_log( 'QR Trackr: Admin module loaded' );
}

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

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
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
		qr_trackr_debug_log( 'QR Trackr: qrc_register_settings() called' );
	}

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
	$size = get_option( 'qr_trackr_qr_size', '150' );
	echo '<input type="number" name="qr_trackr_qr_size" value="' . esc_attr( $size ) . '" class="small-text" /> px';
}

/**
 * Callback for the tracking enabled field in settings.
 *
 * @since 1.0.0
 * @return void
 */
function qrc_tracking_enabled_field_callback() {
	$enabled = get_option( 'qr_trackr_tracking_enabled', '1' );
	echo '<input type="checkbox" name="qr_trackr_tracking_enabled" value="1" ' . checked( '1', $enabled, false ) . ' />';
	echo '<span class="description">' . esc_html__( 'Track QR code scans and store analytics.', 'wp-qr-trackr' ) . '</span>';
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

	// Load list table class if not already loaded.
	if ( ! class_exists( 'QRC_Links_List_Table' ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
			qr_trackr_debug_log( 'QR Trackr: Loading list table class.' );
		}
		require_once __DIR__ . '/class-qrc-links-list-table.php';
	}

	// Create an instance of our list table class.
	$list_table = new QRC_Links_List_Table();
	$list_table->prepare_items();

	// Include the admin page template.
	include dirname( __DIR__ ) . '/templates/admin-page.php';
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

	// Include the add new page template.
	include dirname( __DIR__ ) . '/templates/add-new-page.php';
}



/**
 * Enqueue admin scripts and styles.
 *
 * @since 1.0.0
 * @param string $hook The current admin page.
 * @return void
 */
function qrc_admin_enqueue_scripts( $hook ) {
	// Debug logging for script enqueuing.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( sprintf( 'QR Trackr: Script enqueue function called with hook: %s', $hook ) );
	}
	
	// Temporarily load on ALL admin pages for debugging.
	// Check if we're on a QR Trackr page by looking at the hook.
	$qr_trackr_hooks = array( 'toplevel_page_qr-code-links', 'qr-codes_page_qr-code-add-new', 'qr-codes_page_qr-code-settings' );
	
	// For debugging, load on any page that might be related.
	if ( ! in_array( $hook, $qr_trackr_hooks, true ) && strpos( $hook, 'qr-code' ) === false ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( 'QR Trackr: Script not loaded - not a QR Trackr page: %s', $hook ) );
		}
		return;
	}

	// Enqueue Select2 for searchable dropdowns.
	wp_enqueue_script( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array( 'jquery' ), '4.1.0', true );
	wp_enqueue_style( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0' );

	// Enqueue our custom admin script on ALL admin pages for debugging.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( sprintf( 'QR Trackr: Enqueuing admin script for hook: %s', $hook ) );
	}
	wp_enqueue_script( 'qr-trackr-admin', plugin_dir_url( __FILE__ ) . '../assets/qrc-admin.js', array( 'jquery', 'select2' ), QR_TRACKR_VERSION, true );
	
	// Localize script for AJAX and admin strings.
	wp_localize_script( 'qr-trackr-admin', 'qr_trackr_ajax', array(
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'qr_trackr_nonce' ),
	) );

	// Localize script for admin strings.
	wp_localize_script( 'qr-trackr-admin', 'qrcAdmin', array(
		'strings' => array(
			'qrCodeDetails'    => __( 'QR Code Details', 'wp-qr-trackr' ),
			'loading'          => __( 'Loading...', 'wp-qr-trackr' ),
			'statistics'       => __( 'Statistics', 'wp-qr-trackr' ),
			'totalScans'       => __( 'Total Scans', 'wp-qr-trackr' ),
			'recentScans'      => __( 'Recent Scans (30 days)', 'wp-qr-trackr' ),
			'lastAccessed'     => __( 'Last Accessed', 'wp-qr-trackr' ),
			'created'          => __( 'Created', 'wp-qr-trackr' ),
			'commonName'       => __( 'Common Name', 'wp-qr-trackr' ),
			'enterFriendlyName' => __( 'Enter a friendly name', 'wp-qr-trackr' ),
			'commonNameDesc'   => __( 'A friendly name to help you identify this QR code.', 'wp-qr-trackr' ),
			'referralCode'     => __( 'Referral Code', 'wp-qr-trackr' ),
			'enterReferralCode' => __( 'Enter a referral code', 'wp-qr-trackr' ),
			'referralCodeDesc' => __( 'A referral code for tracking and analytics.', 'wp-qr-trackr' ),
			'qrCode'           => __( 'QR Code', 'wp-qr-trackr' ),
			'qrUrl'            => __( 'QR URL', 'wp-qr-trackr' ),
			'destinationUrl'   => __( 'Destination URL', 'wp-qr-trackr' ),
			'linkedPost'       => __( 'Linked Post', 'wp-qr-trackr' ),
			'close'            => __( 'Close', 'wp-qr-trackr' ),
			'saveChanges'      => __( 'Save Changes', 'wp-qr-trackr' ),
		),
	) );
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

	// Include the settings page template.
	include dirname( __DIR__ ) . '/templates/settings-page.php';
}

/**
 * Load test script if requested.
 *
 * @since 1.2.24
 * @return void
 */
function qrc_load_test_script() {
	if ( isset( $_GET['test_qr'] ) && current_user_can( 'manage_options' ) ) {
		include dirname( __DIR__ ) . '/test-qr-generation.php';
	}
}



/**
 * Register admin hooks.
 *
 * @since 1.0.0
 * @return void
 */
function qrc_register_admin_hooks() {
	// Hook to load test script.
	add_action( 'admin_notices', 'qrc_load_test_script' );

	// Register admin menu items.
	add_action( 'admin_menu', 'qrc_admin_menu' );
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'QR Trackr: Added admin_menu action' );
	}

	// Register settings.
	add_action( 'admin_init', 'qrc_register_settings' );
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'QR Trackr: Added admin_init action' );
	}

	// Enqueue admin scripts and styles.
	add_action( 'admin_enqueue_scripts', 'qrc_admin_enqueue_scripts' );
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'QR Trackr: Added admin_enqueue_scripts action' );
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
	if ( ! isset( $_GET['page'] ) || 'qr-code-links' !== $_GET['page'] || ! isset( $_GET['action'] ) || 'delete' !== $_GET['action'] ) {
		return;
	}

	// Debug logging (only to error log, not output).
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( sprintf( 'QR Trackr: Delete action handler called. Page: %s, Action: %s', isset( $_GET['page'] ) ? $_GET['page'] : 'not set', isset( $_GET['action'] ) ? $_GET['action'] : 'not set' ) );
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

	// Verify nonce.
	$nonce_action = 'delete_qr_code_' . $qr_id;
	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), $nonce_action ) ) {
		wp_die( esc_html__( 'Security check failed.', 'wp-qr-trackr' ) );
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';

	// Get QR code details before deletion for logging.
	$qr_code = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}qr_trackr_links WHERE id = %d",
			$qr_id
		)
	);

	if ( ! $qr_code ) {
		wp_die( esc_html__( 'QR code not found.', 'wp-qr-trackr' ) );
	}

	// Delete the QR code.
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

	// Delete QR code image file if it exists.
	if ( ! empty( $qr_code->qr_code_url ) ) {
		$upload_dir = wp_upload_dir();
		$qr_file_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $qr_code->qr_code_url );
		
		if ( file_exists( $qr_file_path ) ) {
			unlink( $qr_file_path );
		}
	}

	// Redirect back to the QR codes list with success message.
	$redirect_url = add_query_arg(
		array(
			'page' => 'qr-code-links',
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

// Register hooks on init to ensure WordPress is ready.
add_action( 'init', 'qrc_register_admin_hooks' );
