<?php
/**
 * Admin functionality for the QR Coder plugin.
 *
 * @package WP_QR_TRACKR
 */

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
	qr_trackr_log_page_load( 'admin_menu_registration' );

	// Add main menu item.
	$hook = add_menu_page(
		__( 'QR Code Links', 'wp-qr-trackr' ),
		__( 'QR Codes', 'wp-qr-trackr' ),
		'manage_options',
		'qr-code-links',
		'qrc_admin_page',
		'dashicons-admin-links'
	);

	qr_trackr_log_element_creation(
		'menu_page',
		array(
			'hook' => $hook,
			'page' => 'qr-code-links',
		),
		'admin_menu'
	);

	// Add submenu items.
	$add_new = add_submenu_page(
		'qr-code-links',
		__( 'Add New QR Code', 'wp-qr-trackr' ),
		__( 'Add New', 'wp-qr-trackr' ),
		'manage_options',
		'qr-code-add-new',
		'qrc_add_new_page'
	);

	qr_trackr_log_element_creation(
		'submenu_page',
		array(
			'hook' => $add_new,
			'page' => 'qr-code-add-new',
		),
		'admin_menu'
	);

	// Add edit page (hidden from menu but accessible via direct URL).
	$edit_page = add_submenu_page(
		null, // No parent menu.
		__( 'Edit QR Code', 'wp-qr-trackr' ),
		__( 'Edit QR Code', 'wp-qr-trackr' ),
		'manage_options',
		'qr-code-edit',
		'qrc_edit_page'
	);

	qr_trackr_log_element_creation(
		'submenu_page',
		array(
			'hook' => $edit_page,
			'page' => 'qr-code-edit',
		),
		'admin_menu'
	);

	$settings = add_submenu_page(
		'qr-code-links',
		__( 'Settings', 'wp-qr-trackr' ),
		__( 'Settings', 'wp-qr-trackr' ),
		'manage_options',
		'qr-code-settings',
		'qrc_settings_page'
	);

	qr_trackr_log_element_creation(
		'submenu_page',
		array(
			'hook' => $settings,
			'page' => 'qr-code-settings',
		),
		'admin_menu'
	);

	// Add settings page under main plugin slug for direct access.
	$main_settings = add_submenu_page(
		null, // No parent menu.
		__( 'QR Code Settings', 'wp-qr-trackr' ),
		__( 'QR Code Settings', 'wp-qr-trackr' ),
		'manage_options',
		'wp-qr-trackr',
		'qrc_settings_page'
	);

	qr_trackr_log_element_creation(
		'submenu_page',
		array(
			'hook' => $main_settings,
			'page' => 'wp-qr-trackr',
		),
		'admin_menu'
	);

	// Add settings page to WordPress Settings menu.
	$wp_settings = add_options_page(
		__( 'QR Code Settings', 'wp-qr-trackr' ),
		__( 'QR Codes', 'wp-qr-trackr' ),
		'manage_options',
		'wp-qr-trackr',
		'qrc_settings_page'
	);

	qr_trackr_log_element_creation(
		'options_page',
		array(
			'hook' => $wp_settings,
			'page' => 'wp-qr-trackr',
		),
		'admin_menu'
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
	qr_trackr_log_page_load( 'settings_registration' );

	if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
		qr_trackr_debug_log( 'QR Trackr: qrc_register_settings() called' );
	}

	// Debug output for troubleshooting.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only.
		error_log( 'QR Trackr: qrc_register_settings() called' );
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

	qr_trackr_log_element_creation( 'settings_fields', array( 'fields' => array( 'qr_trackr_qr_size', 'qr_trackr_tracking_enabled' ) ), 'settings_registration' );
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
	qr_trackr_log_page_load( 'admin_page', array( 'page' => 'qr-code-links' ) );

	if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
		qr_trackr_debug_log( 'QR Trackr: qrc_admin_page() called' );
	}

	// Check user capabilities.
	if ( ! current_user_can( 'manage_options' ) ) {
		qr_trackr_log( 'Access denied to admin page - insufficient permissions', 'warning', array( 'user_id' => get_current_user_id() ) );
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wp-qr-trackr' ) );
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

	qr_trackr_log_element_creation( 'list_table', array( 'class' => 'QRC_Links_List_Table' ), 'admin_page' );

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

	// Try each possible path.
	foreach ( $possible_paths as $path ) {
		if ( file_exists( $path ) ) {
			$template_path  = $path;
			$template_found = true;
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only.
				error_log( 'QR Trackr: Found template at: ' . $path );
			}
			break;
		}
	}

	if ( $template_found ) {
		qr_trackr_log_element_creation(
			'template',
			array(
				'template' => 'admin-page.php',
				'path'     => $template_path,
			),
			'admin_page'
		);
		include $template_path;
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

		qr_trackr_log( 'Admin page template not found', 'error', array( 'attempted_paths' => $possible_paths ) );

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
	qr_trackr_log_page_load( 'add_new_page', array( 'page' => 'qr-code-add-new' ) );

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
		qr_trackr_log_element_creation(
			'template',
			array(
				'template' => 'add-new-page.php',
				'path'     => $template_path,
			),
			'add_new_page'
		);
		include $template_path;
	} else {
		qr_trackr_log( 'Add new page template not found', 'error', array( 'attempted_paths' => $possible_paths ) );
		wp_die( esc_html__( 'QR Trackr: Add new page template not found. Please check plugin installation.', 'wp-qr-trackr' ) );
	}
}

/**
 * Display the edit QR code page content.
 *
 * @since 1.0.0
 * @return void
 */
function qrc_edit_page() {
	qr_trackr_log_page_load( 'edit_page', array( 'page' => 'qr-code-edit' ) );

	if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
		qr_trackr_debug_log( 'QR Trackr: qrc_edit_page() called' );
	}

	// Check user capabilities.
	if ( ! current_user_can( 'manage_options' ) ) {
		qr_trackr_log( 'Access denied to edit page - insufficient permissions', 'warning', array( 'user_id' => get_current_user_id() ) );
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wp-qr-trackr' ) );
	}

	// Get QR code ID from URL.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only page load with capability check; no state change occurs here.
	$qr_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	if ( 0 === $qr_id ) {
		qr_trackr_log( 'Invalid QR code ID in edit page', 'error', array( 'qr_id' => $qr_id ) );
		wp_die( esc_html__( 'Invalid QR code ID.', 'wp-qr-trackr' ) );
	}

	// Get QR code data.
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Single record fetch for edit; cached by ID below.
	$cache_key = 'qr_trackr_edit_qr_' . absint( $qr_id );
	$qr_code   = wp_cache_get( $cache_key, 'qr_trackr' );
	if ( false === $qr_code ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached immediately after query.
		$qr_code = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE id = %d",
				$qr_id
			)
		);
		if ( $qr_code ) {
			wp_cache_set( $cache_key, $qr_code, 'qr_trackr', 300 );
		}
	}

	if ( ! $qr_code ) {
		qr_trackr_log( 'QR code not found in edit page', 'error', array( 'qr_id' => $qr_id ) );
		wp_die( esc_html__( 'QR code not found.', 'wp-qr-trackr' ) );
	}

	// Handle form submission.
	if ( isset( $_POST['submit'] ) && isset( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'edit_qr_code_' . $qr_id ) ) {
		$common_name     = sanitize_text_field( wp_unslash( $_POST['common_name'] ?? '' ) );
		$referral_code   = sanitize_text_field( wp_unslash( $_POST['referral_code'] ?? '' ) );
		$destination_url = isset( $_POST['destination_url'] ) ? esc_url_raw( wp_unslash( $_POST['destination_url'] ) ) : $qr_code->destination_url;

		$form_data = array(
			'common_name'     => $common_name,
			'referral_code'   => $referral_code,
			'destination_url' => $destination_url,
			'qr_id'           => $qr_id,
		);

		qr_trackr_log_form_submission( 'edit_qr_code', $form_data, 'update' );

		// Enforce unique referral code on update if provided.
		$referral_conflict = 0;
		if ( ! empty( $referral_code ) ) {
			$referral_conflict = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$table_name} WHERE referral_code = %s AND id != %d",
					$referral_code,
					$qr_id
				)
			);
		}

		if ( $referral_conflict > 0 ) {
			$error_message = __( 'Referral code is already in use. Please choose another.', 'wp-qr-trackr' );
		} else {
			$result = $wpdb->update(
				$table_name,
				array(
					'common_name'     => $common_name,
					'referral_code'   => $referral_code,
					'destination_url' => $destination_url,
				),
				array( 'id' => $qr_id ),
				array( '%s', '%s', '%s' ),
				array( '%d' )
			);

			$update_data = array(
				'common_name'     => $common_name,
				'referral_code'   => $referral_code,
				'destination_url' => $destination_url,
			);

			qr_trackr_log_db_operation( 'update', $table_name, $update_data, false !== $result );

			if ( false !== ( $result ?? false ) ) {
				// Clear cache.
				wp_cache_delete( 'qr_trackr_details_' . $qr_id );
				wp_cache_delete( 'qr_trackr_all_links_admin', 'qr_trackr' );

				// Redirect with success message.
				$redirect_url = add_query_arg(
					array(
						'page'    => 'qr-code-links',
						'updated' => '1',
					),
					admin_url( 'admin.php' )
				);

				qr_trackr_log(
					'QR code updated successfully, redirecting to listing page',
					'info',
					array(
						'qr_id'        => $qr_id,
						'redirect_url' => $redirect_url,
					)
				);

				if ( ! headers_sent() ) {
					wp_safe_redirect( $redirect_url );
					exit;
				}
				// Fallback if headers already sent (e.g., by another plugin): use JS redirect.
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Safe output of known URL via wp_json_encode.
				echo '<script>window.location.href = ' . wp_json_encode( $redirect_url ) . ';</script>';
				echo '<noscript><meta http-equiv="refresh" content="0;url=' . esc_url( $redirect_url ) . '"></noscript>';
				exit;
			} elseif ( empty( $error_message ) ) {
				qr_trackr_log(
					'Failed to update QR code',
					'error',
					array(
						'qr_id'    => $qr_id,
						'db_error' => $wpdb->last_error,
					)
				);
				$error_message = __( 'Failed to update QR code.', 'wp-qr-trackr' );
			}
		}
	}

	// Include the edit page template with multiple fallback paths.
	$possible_paths = array(
		QR_TRACKR_PLUGIN_DIR . 'templates/edit-page.php',
		dirname( __DIR__ ) . '/templates/edit-page.php',
		plugin_dir_path( __FILE__ ) . '../templates/edit-page.php',
		ABSPATH . 'wp-content/plugins/wp-qr-trackr/templates/edit-page.php',
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
		// Make QR code data available to template.
		$GLOBALS['qr_code_data'] = $qr_code;

		qr_trackr_log_element_creation(
			'template',
			array(
				'template' => 'edit-page.php',
				'path'     => $template_path,
				'qr_id'    => $qr_id,
			),
			'edit_page'
		);

		include $template_path;
	} else {
		qr_trackr_log( 'Edit page template not found', 'error', array( 'attempted_paths' => $possible_paths ) );
		wp_die( esc_html__( 'QR Trackr: Edit page template not found. Please check plugin installation.', 'wp-qr-trackr' ) );
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
	// Log enqueue call and hook for production diagnostics.
    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Temporary debug logging to confirm enqueue conditions in production.
	error_log( 'QR Trackr: admin_enqueue_scripts hook: ' . $hook );

	// Restrict enqueues to QR Trackr admin pages only.
	$qr_trackr_hooks = array( 'toplevel_page_qr-code-links', 'qr-code-links_page_qr-code-add-new', 'qr-code-links_page_qr-code-settings', 'qr-code-links_page_qr-code-edit' );
	if ( ! in_array( $hook, $qr_trackr_hooks, true ) && strpos( $hook, 'qr-code' ) === false ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only.
			error_log( sprintf( 'QR Trackr: Assets not enqueued, not our page: %s', $hook ) );
		}
		return;
	}

	// Enqueue Select2 locally (CSS + JS), available on all plugin admin pages.
	wp_enqueue_style( 'select2', plugin_dir_url( __FILE__ ) . '../assets/select2.min.css', array(), '4.1.0' );
	wp_enqueue_script( 'select2', plugin_dir_url( __FILE__ ) . '../assets/select2.min.js', array( 'jquery' ), '4.1.0', true );

	// Enqueue our custom admin script.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only.
		error_log( sprintf( 'QR Trackr: Enqueuing admin script for hook: %s', $hook ) );
	}
	wp_enqueue_script( 'qr-trackr-admin', plugin_dir_url( __FILE__ ) . '../assets/qrc-admin.js', array( 'jquery' ), QR_TRACKR_VERSION, true );

	// Localize AJAX params (used by multiple admin UIs).
	wp_localize_script(
		'qr-trackr-admin',
		'qrcAjax',
		array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'qr_trackr_nonce' ),
		)
	);

	// Back-compat for older inline scripts that may reference qr_trackr_ajax.
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
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'nonce'       => wp_create_nonce( 'qr_trackr_nonce' ),
			'currentHook' => $hook,
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only exposure of current page to JS for UI behavior; no state change.
			'currentPage' => isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '',
			'strings'     => array(
				'qrCodeDetails'       => __( 'QR Code Details', 'wp-qr-trackr' ),
				'loading'             => __( 'Loading...', 'wp-qr-trackr' ),
				'statistics'          => __( 'Statistics', 'wp-qr-trackr' ),
				'totalScans'          => __( 'Total Scans', 'wp-qr-trackr' ),
				'recentScans'         => __( 'Recent Scans (30 days)', 'wp-qr-trackr' ),
				'lastAccessed'        => __( 'Last Accessed', 'wp-qr-trackr' ),
				'created'             => __( 'Created', 'wp-qr-trackr' ),
				'commonName'          => __( 'Common Name', 'wp-qr-trackr' ),
				'enterFriendlyName'   => __( 'Enter a friendly name', 'wp-qr-trackr' ),
				'commonNameDesc'      => __( 'A friendly name to help you identify this QR code.', 'wp-qr-trackr' ),
				'referralCode'        => __( 'Referral Code', 'wp-qr-trackr' ),
				'enterReferralCode'   => __( 'Enter a referral code', 'wp-qr-trackr' ),
				'referralCodeDesc'    => __( 'A referral code for tracking and analytics.', 'wp-qr-trackr' ),
				'qrCode'              => __( 'QR Code', 'wp-qr-trackr' ),
				'qrUrl'               => __( 'QR URL', 'wp-qr-trackr' ),
				'destinationUrl'      => __( 'Destination URL', 'wp-qr-trackr' ),
				'linkedPost'          => __( 'Linked Post', 'wp-qr-trackr' ),
				'close'               => __( 'Close', 'wp-qr-trackr' ),
				'saveChanges'         => __( 'Save Changes', 'wp-qr-trackr' ),
				'saving'              => __( 'Saving...', 'wp-qr-trackr' ),
				'noNameSet'           => __( 'No name set', 'wp-qr-trackr' ),
				'none'                => __( 'None', 'wp-qr-trackr' ),
				'errorLoadingDetails' => __( 'Failed to load QR code details.', 'wp-qr-trackr' ),
				'errorSavingDetails'  => __( 'Failed to save QR code details.', 'wp-qr-trackr' ),
				'notLinkedToPost'     => __( 'Not linked to a post', 'wp-qr-trackr' ),
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
	qr_trackr_log_page_load( 'settings_page', array( 'page' => 'qr-code-settings' ) );

	if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
		qr_trackr_debug_log( 'QR Trackr: qrc_settings_page() called' );
	}

	// Check user capabilities.
	if ( ! current_user_can( 'manage_options' ) ) {
		qr_trackr_log( 'Access denied to settings page - insufficient permissions', 'warning', array( 'user_id' => get_current_user_id() ) );
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
		qr_trackr_log_element_creation(
			'template',
			array(
				'template' => 'settings-page.php',
				'path'     => $template_path,
			),
			'settings_page'
		);
		include $template_path;
	} else {
		qr_trackr_log( 'Settings page template not found', 'error', array( 'attempted_paths' => $possible_paths ) );
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
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only.
		error_log( 'QR Trackr: Added admin_menu action' );
	}

	// Register settings.
	add_action( 'admin_init', 'qrc_register_settings' );
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only.
		error_log( 'QR Trackr: Added admin_init action' );
	}

	// Enqueue admin scripts and styles.
	add_action( 'admin_enqueue_scripts', 'qrc_admin_enqueue_scripts' );
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only.
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
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is a routing check prior to nonce verification.
	if ( ! isset( $_GET['page'] ) || 'qr-code-links' !== sanitize_text_field( wp_unslash( $_GET['page'] ) ) || ! isset( $_GET['action'] ) || 'delete' !== sanitize_text_field( wp_unslash( $_GET['action'] ) ) ) {
		return;
	}

	qr_trackr_log_page_load(
		'delete_action',
		array(
			'page'   => sanitize_text_field( wp_unslash( $_GET['page'] ) ),
			'action' => sanitize_text_field( wp_unslash( $_GET['action'] ) ),
		)
	);

	// Debug logging (only to error log, not output).
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only.
		error_log( sprintf( 'QR Trackr: Delete action handler called. Page: %s, Action: %s', isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : 'not set', isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : 'not set' ) );
	}

	// Check user capabilities.
	if ( ! current_user_can( 'manage_options' ) ) {
		qr_trackr_log( 'Access denied to delete action - insufficient permissions', 'warning', array( 'user_id' => get_current_user_id() ) );
		wp_die( esc_html__( 'You do not have sufficient permissions to delete QR codes.', 'wp-qr-trackr' ) );
	}

	// Get and validate QR code ID.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- ID is validated and nonce-verified immediately after.
	$qr_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	if ( 0 === $qr_id ) {
		qr_trackr_log( 'Invalid QR code ID in delete action', 'error', array( 'qr_id' => $qr_id ) );
		wp_die( esc_html__( 'Invalid QR code ID.', 'wp-qr-trackr' ) );
	}

	// Verify nonce.
	$nonce_action = 'delete_qr_code_' . $qr_id;
	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), $nonce_action ) ) {
		qr_trackr_log(
			'Security check failed in delete action',
			'error',
			array(
				'qr_id'        => $qr_id,
				'nonce_action' => $nonce_action,
			)
		);
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
		qr_trackr_log( 'QR code not found for deletion', 'error', array( 'qr_id' => $qr_id ) );
		wp_die( esc_html__( 'QR code not found.', 'wp-qr-trackr' ) );
	}

	// Log the deletion attempt.
	$delete_data = array(
		'qr_code'         => $qr_code->qr_code,
		'destination_url' => $qr_code->destination_url,
	);
	qr_trackr_log_form_submission( 'delete_qr_code', $delete_data, 'delete' );

	// Delete the QR code.
	$result = $wpdb->delete(
		$table_name,
		array( 'id' => $qr_id ),
		array( '%d' )
	);

	qr_trackr_log_db_operation( 'delete', $table_name, array( 'id' => $qr_id ), false !== $result );

	if ( false === $result ) {
		qr_trackr_log(
			'Failed to delete QR code from database',
			'error',
			array(
				'qr_id'    => $qr_id,
				'db_error' => $wpdb->last_error,
			)
		);
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
		$upload_dir   = wp_upload_dir();
		$qr_file_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $qr_code->qr_code_url );

		if ( file_exists( $qr_file_path ) ) {
			// Use WordPress file deletion helper for compatibility.
			$file_deleted = wp_delete_file( $qr_file_path );
			qr_trackr_log(
				'QR code image file deletion attempt',
				'info',
				array(
					'file_path' => $qr_file_path,
					'deleted'   => $file_deleted,
				)
			);
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

	qr_trackr_log(
		'QR code deleted successfully, redirecting to listing page',
		'info',
		array(
			'qr_id'        => $qr_id,
			'redirect_url' => $redirect_url,
		)
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
	if ( ! isset( $_GET['page'] ) || 'qr-code-links' !== sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {
		return;
	}

	// Display success message for deleted QR code.
	if ( isset( $_GET['deleted'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['deleted'] ) ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'QR code deleted successfully.', 'wp-qr-trackr' ) . '</p></div>';
	}

	// Display success message for updated QR code (single-source notice).
	if ( isset( $_GET['updated'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['updated'] ) ) ) {
		if ( ! did_action( 'qr_trackr_updated_notice_rendered' ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'QR code updated successfully.', 'wp-qr-trackr' ) . '</p></div>';
			do_action( 'qr_trackr_updated_notice_rendered' );
		}
	}
}
add_action( 'admin_notices', 'qrc_admin_notices' );

// Register hooks on init to ensure WordPress is ready.
add_action( 'init', 'qrc_register_admin_hooks' );
