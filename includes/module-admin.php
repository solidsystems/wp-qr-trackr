<?php
/**
 * Admin functionality for the QR Coder plugin.
 *
 * @package WP_QR_TRACKR
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Add the admin menu page.
 */
function qrc_add_admin_menu() {
	// Add settings page under Settings menu
	add_options_page(
		'QR Coder Settings',
		'QR Coder',
		'manage_options',
		'wp-qr-trackr',
		'qrc_options_page'
	);

	// Add main QR Codes menu
	$main_menu = add_menu_page(
		'QR Code Links',
		'QR Codes',
		'manage_options',
		'qrc-links',
		'qrc_links_page',
		'dashicons-camera',
		20
	);

	// Add submenus
	add_submenu_page(
		'qrc-links',
		'All QR Code Links',
		'All QR Codes',
		'manage_options',
		'qrc-links',
		'qrc_links_page'
	);

	$add_new_page = add_submenu_page(
		'qrc-links',
		'Add New QR Code',
		'Add New',
		'manage_options',
		'qrc-add-new',
		'qrc_add_new_page'
	);

	add_submenu_page(
		'qrc-links',
		'QR Code Settings',
		'Settings',
		'manage_options',
		'qrc-settings',
		'qrc_settings_page'
	);

	// Add debug submenu (only if WP_DEBUG is enabled or manual override)
	$options      = get_option( 'qrc_options', array() );
	$enable_debug = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) || ( isset( $options['force_debug'] ) && $options['force_debug'] );
	if ( $enable_debug ) {
		add_submenu_page(
			'qrc-links',
			'QR Code Debug',
			'Debug',
			'manage_options',
			'qrc-debug',
			'qrc_debug_page'
		);
	}

	// Add help and documentation submenu
	add_submenu_page(
		'qrc-links',
		'QR Code Help',
		'Help',
		'manage_options',
		'qrc-help',
		'qrc_help_page'
	);
}
add_action( 'admin_menu', 'qrc_add_admin_menu' );

// Enqueue admin scripts on admin pages.
add_action( 'admin_enqueue_scripts', 'qrc_enqueue_admin_scripts' );

/**
 * Enqueue admin scripts for QR code management.
 */
function qrc_enqueue_admin_scripts( $hook ) {
	// Only load scripts on the QR code admin pages.
	if ( false === strpos( $hook, 'qrc-' ) ) {
		return;
	}
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script(
		'qrc-admin',
		plugin_dir_url( QRC_PLUGIN_FILE ) . 'assets/qrc-admin.js',
		array( 'jquery' ),
		'1.2.8',
		true
	);

	wp_localize_script(
		'qrc-admin',
		'qrcAdmin',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'qr_trackr_nonce' ),
			'strings' => array(
				'qrCodeDetails'       => esc_html__( 'QR Code Details', 'wp-qr-trackr' ),
				'loading'             => esc_html__( 'Loading...', 'wp-qr-trackr' ),
				'saving'              => esc_html__( 'Saving...', 'wp-qr-trackr' ),
				'close'               => esc_html__( 'Close', 'wp-qr-trackr' ),
				'saveChanges'         => esc_html__( 'Save Changes', 'wp-qr-trackr' ),
				'statistics'          => esc_html__( 'Statistics', 'wp-qr-trackr' ),
				'totalScans'          => esc_html__( 'Total Scans', 'wp-qr-trackr' ),
				'recentScans'         => esc_html__( 'Recent Scans (30 days)', 'wp-qr-trackr' ),
				'lastAccessed'        => esc_html__( 'Last Accessed', 'wp-qr-trackr' ),
				'created'             => esc_html__( 'Created', 'wp-qr-trackr' ),
				'commonName'          => esc_html__( 'Common Name', 'wp-qr-trackr' ),
				'referralCode'        => esc_html__( 'Referral Code', 'wp-qr-trackr' ),
				'qrCode'              => esc_html__( 'QR Code', 'wp-qr-trackr' ),
				'qrUrl'               => esc_html__( 'QR URL', 'wp-qr-trackr' ),
				'destinationUrl'      => esc_html__( 'Destination URL', 'wp-qr-trackr' ),
				'linkedPost'          => esc_html__( 'Linked Post/Page', 'wp-qr-trackr' ),
				'enterFriendlyName'   => esc_html__( 'Enter a friendly name...', 'wp-qr-trackr' ),
				'enterReferralCode'   => esc_html__( 'Enter referral code...', 'wp-qr-trackr' ),
				'commonNameDesc'      => esc_html__( 'A user-friendly name to help identify this QR code.', 'wp-qr-trackr' ),
				'referralCodeDesc'    => esc_html__( 'A unique code for tracking and analytics (letters, numbers, hyphens, underscores only).', 'wp-qr-trackr' ),
				'notLinkedToPost'     => esc_html__( 'Not linked to a specific post/page', 'wp-qr-trackr' ),
				'noNameSet'           => esc_html__( 'No name set', 'wp-qr-trackr' ),
				'none'                => esc_html__( 'None', 'wp-qr-trackr' ),
				'errorLoadingDetails' => esc_html__( 'Error loading QR code details. Please try again.', 'wp-qr-trackr' ),
				'errorSavingDetails'  => esc_html__( 'Error saving QR code details. Please try again.', 'wp-qr-trackr' ),
			),
		)
	);

	// Legacy support for existing AJAX calls
	wp_localize_script(
		'qrc-admin',
		'qrcAjax',
		array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'qrc_admin_nonce' ),
		)
	);
}

/**
 * Display the QR code links page.
 */
function qrc_links_page() {
	$list_table = new QRC_Links_List_Table();
	$list_table->prepare_items();
	?>
	<div class="wrap">
		<h1>
			<?php esc_html_e( 'QR Code Links', 'wp-qr-trackr' ); ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=qrc-add-new' ) ); ?>" class="page-title-action">
				<?php esc_html_e( 'Add New', 'wp-qr-trackr' ); ?>
			</a>
		</h1>
		
		<?php if ( empty( $list_table->items ) ) : ?>
			<div class="notice notice-info">
				<p>
					<?php esc_html_e( 'No QR codes found. ', 'wp-qr-trackr' ); ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=qrc-add-new' ) ); ?>">
						<?php esc_html_e( 'Create your first QR code', 'wp-qr-trackr' ); ?>
					</a>
				</p>
			</div>
		<?php else : ?>
			<?php $list_table->display(); ?>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Display the options page.
 */
function qrc_options_page() {
	?>
	<div class="wrap">
		<h1>QR Coder Settings</h1>
		<form action="options.php" method="post">
			<?php
			settings_fields( 'qrc_options' );
			do_settings_sections( 'wp-qr-trackr' );
			submit_button();
			?>
		</form>
	</div>
	<?php
}

/**
 * Register the settings.
 */
function qrc_register_settings() {
	register_setting( 'qrc_options', 'qrc_options', 'qrc_options_validate' );

	add_settings_section(
		'qrc_settings_section',
		'General Settings',
		'qrc_settings_section_callback',
		'qrc-settings'
	);

	add_settings_field(
		'qrc_remove_data_on_deactivation',
		'Remove Data on Deactivation',
		'qrc_remove_data_on_deactivation_callback',
		'qrc-settings',
		'qrc_settings_section'
	);

	// Add default QR code size setting
	add_settings_field(
		'qrc_default_qr_size',
		'Default QR Code Size',
		'qrc_default_qr_size_callback',
		'qrc-settings',
		'qrc_settings_section'
	);

	// Add QR code tracking setting
	add_settings_field(
		'qrc_enable_tracking',
		'Enable Click Tracking',
		'qrc_enable_tracking_callback',
		'qrc-settings',
		'qrc_settings_section'
	);

	// Add force debug mode setting
	add_settings_field(
		'qrc_force_debug',
		'Force Debug Mode',
		'qrc_force_debug_callback',
		'qrc-settings',
		'qrc_settings_section'
	);
}
add_action( 'admin_init', 'qrc_register_settings' );

/**
 * Callback for the settings section.
 */
function qrc_settings_section_callback() {
	echo '<p>General settings for the QR Coder plugin.</p>';
}

/**
 * Callback for the remove data on deactivation setting.
 */
function qrc_remove_data_on_deactivation_callback() {
	// Only show this dangerous option when WP_DEBUG is enabled or manual override
	$options      = get_option( 'qrc_options', array() );
	$enable_debug = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) || ( isset( $options['force_debug'] ) && $options['force_debug'] );
	if ( ! $enable_debug ) {
		echo '<p><em>' . esc_html__( 'This option is only available in debug mode (WP_DEBUG = true or force debug enabled).', 'wp-qr-trackr' ) . '</em></p>';
		return;
	}

	$options = get_option( 'qrc_options' );
	?>
	<input type="checkbox" name="qrc_options[remove_data_on_deactivation]" value="1" <?php checked( 1, isset( $options['remove_data_on_deactivation'] ) ? $options['remove_data_on_deactivation'] : 0, true ); ?> />
	<label for="qrc_options[remove_data_on_deactivation]">
		<strong style="color: #d63638;"><?php esc_html_e( 'DANGER:', 'wp-qr-trackr' ); ?></strong> 
		<?php esc_html_e( 'Check this box to remove all plugin data when deactivating the plugin.', 'wp-qr-trackr' ); ?>
	</label>
	<p class="description" style="color: #d63638;">
		<?php esc_html_e( 'WARNING: This will permanently delete all QR codes, tracking data, and plugin settings. This action cannot be undone!', 'wp-qr-trackr' ); ?>
	</p>
	<?php
}

/**
 * Callback for the default QR code size setting.
 */
function qrc_default_qr_size_callback() {
	$options      = get_option( 'qrc_options' );
	$default_size = isset( $options['default_qr_size'] ) ? $options['default_qr_size'] : 200;
	?>
	<select name="qrc_options[default_qr_size]">
		<option value="100" <?php selected( $default_size, 100 ); ?>>100x100 px</option>
		<option value="150" <?php selected( $default_size, 150 ); ?>>150x150 px</option>
		<option value="200" <?php selected( $default_size, 200 ); ?>>200x200 px</option>
		<option value="300" <?php selected( $default_size, 300 ); ?>>300x300 px</option>
		<option value="400" <?php selected( $default_size, 400 ); ?>>400x400 px</option>
	</select>
	<p class="description">Default size for generated QR codes.</p>
	<?php
}

/**
 * Callback for the enable tracking setting.
 */
function qrc_enable_tracking_callback() {
	$options = get_option( 'qrc_options' );
	?>
	<input type="checkbox" name="qrc_options[enable_tracking]" value="1" <?php checked( 1, isset( $options['enable_tracking'] ) ? $options['enable_tracking'] : 1, true ); ?> />
	<label for="qrc_options[enable_tracking]">Enable click tracking and analytics for QR codes.</label>
	<?php
}

/**
 * Callback for the force debug mode setting.
 */
function qrc_force_debug_callback() {
	$options = get_option( 'qrc_options' );
	?>
	<input type="checkbox" name="qrc_options[force_debug]" value="1" <?php checked( 1, isset( $options['force_debug'] ) ? $options['force_debug'] : 0, true ); ?> />
	<label for="qrc_options[force_debug]">
		<strong style="color: #d63638;"><?php esc_html_e( 'Developer Mode:', 'wp-qr-trackr' ); ?></strong> 
		<?php esc_html_e( 'Enable debug menu and dangerous settings (even when WP_DEBUG is false).', 'wp-qr-trackr' ); ?>
	</label>
	<p class="description" style="color: #d63638;">
		<?php esc_html_e( 'WARNING: This enables access to potentially dangerous settings that can delete all plugin data. Only enable if you know what you are doing!', 'wp-qr-trackr' ); ?>
	</p>
	<?php
}

/**
 * Validate the options.
 *
 * @param array $input The input options.
 * @return array The validated options.
 */
function qrc_options_validate( $input ) {
	$new_input = array();

	// Validate remove data on deactivation setting.
	if ( isset( $input['remove_data_on_deactivation'] ) ) {
		$new_input['remove_data_on_deactivation'] = 1;
	} else {
		$new_input['remove_data_on_deactivation'] = 0;
	}

	// Validate default QR size setting.
	if ( isset( $input['default_qr_size'] ) ) {
		$size          = absint( $input['default_qr_size'] );
		$allowed_sizes = array( 100, 150, 200, 300, 400 );
		if ( in_array( $size, $allowed_sizes, true ) ) {
			$new_input['default_qr_size'] = $size;
		} else {
			$new_input['default_qr_size'] = 200; // Default fallback.
		}
	} else {
		$new_input['default_qr_size'] = 200; // Default fallback.
	}

	// Validate enable tracking setting.
	if ( isset( $input['enable_tracking'] ) ) {
		$new_input['enable_tracking'] = 1;
	} else {
		$new_input['enable_tracking'] = 0;
	}

	// Validate force debug setting.
	if ( isset( $input['force_debug'] ) ) {
		$new_input['force_debug'] = 1;
	} else {
		$new_input['force_debug'] = 0;
	}

	return $new_input;
}

/**
 * Display the add new QR code page.
 */
function qrc_add_new_page() {
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Add New QR Code', 'wp-qr-trackr' ); ?></h1>
		
		<form method="post" action="" id="qrc-add-new-form">
			<?php wp_nonce_field( 'qrc_add_new', 'qrc_nonce' ); ?>
			
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="destination_type"><?php esc_html_e( 'Destination Type', 'wp-qr-trackr' ); ?></label>
					</th>
					<td>
						<select name="destination_type" id="destination_type" class="regular-text">
							<option value="post"><?php esc_html_e( 'Post/Page', 'wp-qr-trackr' ); ?></option>
							<option value="external"><?php esc_html_e( 'External URL', 'wp-qr-trackr' ); ?></option>
							<option value="custom"><?php esc_html_e( 'Custom URL', 'wp-qr-trackr' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Choose what type of destination this QR code should link to.', 'wp-qr-trackr' ); ?></p>
					</td>
				</tr>
				<tr id="post-selector" style="display: none;">
					<th scope="row">
						<label for="post_search"><?php esc_html_e( 'Select Post/Page', 'wp-qr-trackr' ); ?></label>
					</th>
					<td>
						<input type="text" id="post_search" class="regular-text" placeholder="<?php esc_attr_e( 'Start typing to search posts/pages...', 'wp-qr-trackr' ); ?>" />
						<div id="post_search_results" style="display: none; border: 1px solid #ddd; max-height: 200px; overflow-y: auto; background: white; position: relative; z-index: 1000;"></div>
						<input type="hidden" name="post_id" id="post_id" value="" />
						<p class="description" id="selected_post_info" style="display: none;"></p>
					</td>
				</tr>
				<tr id="url-input">
					<th scope="row">
						<label for="destination_url"><?php esc_html_e( 'Destination URL', 'wp-qr-trackr' ); ?></label>
					</th>
					<td>
						<input type="url" name="destination_url" id="destination_url" class="regular-text" placeholder="https://example.com" />
						<p class="description"><?php esc_html_e( 'Enter the full URL where this QR code should redirect.', 'wp-qr-trackr' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="common_name"><?php esc_html_e( 'Common Name', 'wp-qr-trackr' ); ?></label>
					</th>
					<td>
						<input type="text" name="common_name" id="common_name" class="regular-text" placeholder="<?php esc_attr_e( 'Enter a friendly name...', 'wp-qr-trackr' ); ?>" />
						<p class="description"><?php esc_html_e( 'A user-friendly name to help identify this QR code (optional).', 'wp-qr-trackr' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="referral_code"><?php esc_html_e( 'Referral Code', 'wp-qr-trackr' ); ?></label>
					</th>
					<td>
						<input type="text" name="referral_code" id="referral_code" class="regular-text" placeholder="<?php esc_attr_e( 'Enter referral code...', 'wp-qr-trackr' ); ?>" pattern="[a-zA-Z0-9\-_]+" />
						<p class="description"><?php esc_html_e( 'A unique code for tracking and analytics. Letters, numbers, hyphens, and underscores only (optional).', 'wp-qr-trackr' ); ?></p>
					</td>
				</tr>
			</table>
			
			<?php submit_button( esc_html__( 'Generate QR Code', 'wp-qr-trackr' ) ); ?>
		</form>
		

	</div>
	<?php

	// Handle form submission
	if ( isset( $_POST['qrc_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['qrc_nonce'] ) ), 'qrc_add_new' ) ) {
		qrc_handle_add_new_submission();
	}
}

/**
 * Handle the add new QR code form submission.
 */
function qrc_handle_add_new_submission() {
	$destination_type = isset( $_POST['destination_type'] ) ? sanitize_text_field( wp_unslash( $_POST['destination_type'] ) ) : '';
	$destination_url  = '';
	$post_id          = 0;

	if ( 'post' === $destination_type ) {
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		if ( $post_id ) {
			$destination_url = get_permalink( $post_id );
		}
	} else {
		$destination_url = isset( $_POST['destination_url'] ) ? esc_url_raw( wp_unslash( $_POST['destination_url'] ) ) : '';
	}

	if ( empty( $destination_url ) ) {
		echo '<div class="notice notice-error"><p>' . esc_html__( 'Please provide a valid destination URL.', 'wp-qr-trackr' ) . '</p></div>';
		return;
	}

	// Get and validate the new fields.
	$common_name   = isset( $_POST['common_name'] ) ? sanitize_text_field( wp_unslash( $_POST['common_name'] ) ) : '';
	$referral_code = isset( $_POST['referral_code'] ) ? sanitize_text_field( wp_unslash( $_POST['referral_code'] ) ) : '';

	// Validate referral code format if provided.
	if ( ! empty( $referral_code ) && ! preg_match( '/^[a-zA-Z0-9\-_]+$/', $referral_code ) ) {
		echo '<div class="notice notice-error"><p>' . esc_html__( 'Referral code can only contain letters, numbers, hyphens, and underscores.', 'wp-qr-trackr' ) . '</p></div>';
		return;
	}

	// Check if referral code is unique if provided.
	if ( ! empty( $referral_code ) ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'qr_trackr_links';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Uniqueness check for validation.
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table_name} WHERE referral_code = %s",
				$referral_code
			)
		);

		if ( $existing ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Referral code already exists. Please choose a different one.', 'wp-qr-trackr' ) . '</p></div>';
			return;
		}
	}

	// Generate unique tracking code.
	$tracking_code = qr_trackr_generate_unique_qr_code();

	// Generate QR code image URL.
	$qr_image_url = qr_trackr_generate_qr_image( $tracking_code, array( 'size' => 200 ) );
	if ( is_wp_error( $qr_image_url ) ) {
		echo '<div class="notice notice-error"><p>' . esc_html__( 'Failed to generate QR code image.', 'wp-qr-trackr' ) . '</p></div>';
		return;
	}

	// Insert into database.
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';

	$result = $wpdb->insert(
		$table_name,
		array(
			'post_id'         => $post_id > 0 ? $post_id : null,
			'destination_url' => $destination_url,
			'qr_code'         => $tracking_code,
			'qr_code_url'     => $qr_image_url,
			'common_name'     => ! empty( $common_name ) ? $common_name : null,
			'referral_code'   => ! empty( $referral_code ) ? $referral_code : null,
			'scans'           => 0,
			'access_count'    => 0,
			'created_at'      => current_time( 'mysql', true ),
			'updated_at'      => current_time( 'mysql', true ),
		),
		array( '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s' )
	);

	if ( $result ) {
		// Invalidate the admin list cache so new QR codes appear immediately.
		wp_cache_delete( 'qr_trackr_all_links_admin', 'qr_trackr' );

		echo '<div class="notice notice-success"><p>' . esc_html__( 'QR code created successfully!', 'wp-qr-trackr' ) . '</p></div>';

		// Optionally pre-generate the QR code image for better performance.
		$qr_image_url = qr_trackr_generate_qr_image( $tracking_code, array( 'size' => 200 ) );

		// Show QR code preview if image generation was successful.
		if ( ! is_wp_error( $qr_image_url ) ) {
			echo '<div class="notice notice-info">';
			echo '<p><strong>' . esc_html__( 'QR Code Preview:', 'wp-qr-trackr' ) . '</strong></p>';
			echo '<p><img src="' . esc_url( $qr_image_url ) . '" alt="QR Code Preview" style="max-width: 150px; height: auto;" /></p>';
			echo '<p><strong>' . esc_html__( 'Tracking Code:', 'wp-qr-trackr' ) . '</strong> <code>' . esc_html( $tracking_code ) . '</code></p>';
			echo '<p><strong>' . esc_html__( 'QR URL:', 'wp-qr-trackr' ) . '</strong> <code>' . esc_url( home_url( '/qr/' . $tracking_code ) ) . '</code></p>';
			if ( ! empty( $common_name ) ) {
				echo '<p><strong>' . esc_html__( 'Common Name:', 'wp-qr-trackr' ) . '</strong> ' . esc_html( $common_name ) . '</p>';
			}
			if ( ! empty( $referral_code ) ) {
				echo '<p><strong>' . esc_html__( 'Referral Code:', 'wp-qr-trackr' ) . '</strong> <code>' . esc_html( $referral_code ) . '</code></p>';
			}
			echo '</div>';
		}
	} else {
		error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentional debug logging.
			sprintf(
				'Failed to create QR code: %s',
				$wpdb->last_error
			)
		);
		echo '<div class="notice notice-error"><p>' . esc_html__( 'Failed to create QR code.', 'wp-qr-trackr' ) . '</p></div>';
	}
}

/**
 * Display the QR code settings page.
 */
function qrc_settings_page() {
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'QR Code Settings', 'wp-qr-trackr' ); ?></h1>
		<form action="options.php" method="post">
			<?php
			settings_fields( 'qrc_options' );
			do_settings_sections( 'qrc-settings' );
			submit_button();
			?>
		</form>
	</div>
	<?php
}

/**
 * Display the help page.
 */
function qrc_help_page() {
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'QR Code Help & Documentation', 'wp-qr-trackr' ); ?></h1>
		
		<div class="card">
			<h2><?php esc_html_e( 'Getting Started', 'wp-qr-trackr' ); ?></h2>
			<p><?php esc_html_e( 'Welcome to WP QR Trackr! This plugin allows you to create, manage, and track QR codes for your WordPress site.', 'wp-qr-trackr' ); ?></p>
			
			<h3><?php esc_html_e( 'Creating QR Codes', 'wp-qr-trackr' ); ?></h3>
			<ol>
				<li><?php esc_html_e( 'Go to QR Codes > Add New', 'wp-qr-trackr' ); ?></li>
				<li><?php esc_html_e( 'Choose your destination type (Post/Page, External URL, or Custom URL)', 'wp-qr-trackr' ); ?></li>
				<li><?php esc_html_e( 'Enter the destination details', 'wp-qr-trackr' ); ?></li>
				<li><?php esc_html_e( 'Click "Generate QR Code"', 'wp-qr-trackr' ); ?></li>
			</ol>
			
			<h3><?php esc_html_e( 'QR Code URLs', 'wp-qr-trackr' ); ?></h3>
			<p>
				<?php
				/* translators: %s: Site home URL */
				printf( esc_html__( 'Your QR codes will be accessible at: %s/qr/{tracking_code}', 'wp-qr-trackr' ), esc_url( home_url() ) );
				?>
			</p>
			
			<h3><?php esc_html_e( 'Support', 'wp-qr-trackr' ); ?></h3>
			<p><?php esc_html_e( 'For support and documentation, please visit the plugin GitHub repository.', 'wp-qr-trackr' ); ?></p>
		</div>
	</div>
	<?php
}

/**
 * Display the debug page.
 */
function qrc_debug_page() {
	$options      = get_option( 'qrc_options', array() );
	$enable_debug = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) || ( isset( $options['force_debug'] ) && $options['force_debug'] );
	if ( ! $enable_debug ) {
		wp_die( esc_html__( 'Debug mode is not enabled.', 'wp-qr-trackr' ) );
	}

	// Handle force flush rewrite rules action.
	if ( isset( $_POST['action'] ) && 'flush_rewrite_rules' === $_POST['action'] ) {
		if ( ! isset( $_POST['qr_trackr_flush_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['qr_trackr_flush_nonce'] ) ), 'qr_trackr_flush_rules' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'wp-qr-trackr' ) );
		}

		qr_trackr_force_flush_rewrite_rules();
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Rewrite rules have been flushed and re-registered.', 'wp-qr-trackr' ) . '</p></div>';
	}

	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'QR Trackr Debug Information', 'wp-qr-trackr' ); ?></h1>
		
		<div class="card">
			<h2><?php esc_html_e( '🔍 System Information', 'wp-qr-trackr' ); ?></h2>
			<?php qrc_display_system_info(); ?>
		</div>

		<div class="card">
			<h2><?php esc_html_e( '🗄️ Database Status', 'wp-qr-trackr' ); ?></h2>
			<?php qrc_display_database_status(); ?>
		</div>

		<div class="card">
			<h2><?php esc_html_e( '🔧 Rewrite Rules', 'wp-qr-trackr' ); ?></h2>
			<?php qrc_display_rewrite_status(); ?>
		</div>

		<div class="card">
			<h2><?php esc_html_e( '🖼️ QR Image Generation Test', 'wp-qr-trackr' ); ?></h2>
			<?php qrc_display_qr_image_test(); ?>
		</div>

		<div class="card">
			<h2><?php esc_html_e( '📂 File System Check', 'wp-qr-trackr' ); ?></h2>
			<?php qrc_display_filesystem_status(); ?>
		</div>

		<div class="card">
			<h2><?php esc_html_e( '🧪 Test QR Code Redirect', 'wp-qr-trackr' ); ?></h2>
			<?php qrc_display_redirect_test(); ?>
		</div>
	</div>
	<?php
}

/**
 * Display system information for debugging.
 */
function qrc_display_system_info() {
	$upload_dir          = wp_upload_dir();
	$permalink_structure = get_option( 'permalink_structure' );

	?>
	<table class="widefat">
		<tbody>
			<tr>
				<td><strong><?php esc_html_e( 'WordPress Version:', 'wp-qr-trackr' ); ?></strong></td>
				<td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'PHP Version:', 'wp-qr-trackr' ); ?></strong></td>
				<td><?php echo esc_html( PHP_VERSION ); ?></td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'Plugin Version:', 'wp-qr-trackr' ); ?></strong></td>
				<td><?php echo esc_html( QR_TRACKR_VERSION ); ?></td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'WP_DEBUG:', 'wp-qr-trackr' ); ?></strong></td>
				<td><?php echo ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? '✅ Enabled' : '❌ Disabled'; ?></td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'Permalink Structure:', 'wp-qr-trackr' ); ?></strong></td>
				<td>
					<?php if ( empty( $permalink_structure ) ) : ?>
						<span style="color: #d63638;">❌ Plain permalinks (QR redirects will NOT work)</span>
					<?php else : ?>
						<span style="color: #00a32a;">✅ Pretty permalinks: <?php echo esc_html( $permalink_structure ); ?></span>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'Upload Directory:', 'wp-qr-trackr' ); ?></strong></td>
				<td>
					<?php if ( isset( $upload_dir['error'] ) && ! empty( $upload_dir['error'] ) ) : ?>
						<span style="color: #d63638;">❌ Error: <?php echo esc_html( $upload_dir['error'] ); ?></span>
					<?php else : ?>
						<span style="color: #00a32a;">✅ <?php echo esc_html( $upload_dir['basedir'] ); ?></span>
					<?php endif; ?>
				</td>
			</tr>
		</tbody>
	</table>
	<?php
}

/**
 * Display database status for debugging.
 */
function qrc_display_database_status() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';

	// Check if table exists
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Debug query for table existence check.
	$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );

	?>
	<table class="widefat">
		<tbody>
			<tr>
				<td><strong><?php esc_html_e( 'Table Name:', 'wp-qr-trackr' ); ?></strong></td>
				<td><?php echo esc_html( $table_name ); ?></td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'Table Exists:', 'wp-qr-trackr' ); ?></strong></td>
				<td>
					<?php if ( $table_exists === $table_name ) : ?>
						<span style="color: #00a32a;">✅ Yes</span>
					<?php else : ?>
						<span style="color: #d63638;">❌ No - Run plugin activation!</span>
					<?php endif; ?>
				</td>
			</tr>
			<?php if ( $table_exists === $table_name ) : ?>
				<?php
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Debug query for record count.
				$total_qr_codes = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );

				// Check for qr_code_url field
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Debug query for column existence check.
				$columns         = $wpdb->get_results( "SHOW COLUMNS FROM {$table_name}" );
				$has_qr_code_url = false;
				foreach ( $columns as $column ) {
					if ( 'qr_code_url' === $column->Field ) {
						$has_qr_code_url = true;
						break;
					}
				}
				?>
				<tr>
					<td><strong><?php esc_html_e( 'Total QR Codes:', 'wp-qr-trackr' ); ?></strong></td>
					<td><?php echo esc_html( $total_qr_codes ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'QR Image URL Field:', 'wp-qr-trackr' ); ?></strong></td>
					<td>
						<?php if ( $has_qr_code_url ) : ?>
							<span style="color: #00a32a;">✅ Present</span>
						<?php else : ?>
							<span style="color: #d63638;">❌ Missing - Need to update plugin!</span>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'QR Codes with Images:', 'wp-qr-trackr' ); ?></strong></td>
					<td>
						<?php if ( $has_qr_code_url ) : ?>
							<?php
							// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Debug query for image count.
							$qr_codes_with_images = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name} WHERE qr_code_url IS NOT NULL AND qr_code_url != ''" );
							echo esc_html( $qr_codes_with_images . ' / ' . $total_qr_codes );
							if ( $qr_codes_with_images < $total_qr_codes ) {
								echo ' <span style="color: #d63638;">(Some missing images)</span>';
							}
							?>
						<?php else : ?>
							<span style="color: #d63638;">N/A (Field missing)</span>
						<?php endif; ?>
					</td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
	<?php
}

/**
 * Display rewrite rules status for debugging.
 */
function qrc_display_rewrite_status() {
	global $wp_rewrite;
	$rewrite_rules = get_option( 'rewrite_rules' );
	$qr_rule_found = qr_trackr_check_rewrite_rules();

	?>
	<table class="widefat">
		<tbody>
			<tr>
				<td><strong><?php esc_html_e( 'QR Rewrite Rule Registered:', 'wp-qr-trackr' ); ?></strong></td>
				<td>
					<?php if ( $qr_rule_found ) : ?>
						<span style="color: #00a32a;">✅ Yes</span>
					<?php else : ?>
						<span style="color: #d63638;">❌ No - Check module-rewrite.php loading</span>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'Total Rewrite Rules:', 'wp-qr-trackr' ); ?></strong></td>
				<td><?php echo esc_html( is_array( $rewrite_rules ) ? count( $rewrite_rules ) : 0 ); ?></td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'Query Vars:', 'wp-qr-trackr' ); ?></strong></td>
				<td>
					<?php
					global $wp;
					$query_vars_registered = false;
					if ( isset( $wp->public_query_vars ) && in_array( 'qr_tracking_code', $wp->public_query_vars, true ) ) {
						$query_vars_registered = true;
					}

					if ( $query_vars_registered ) {
						echo '<span style="color: #00a32a;">✅ qr_tracking_code registered</span>';
					} else {
						echo '<span style="color: #d63638;">❌ qr_tracking_code NOT registered</span>';
					}
					?>
				</td>
			</tr>
		</tbody>
	</table>

	<?php if ( $qr_rule_found && is_array( $rewrite_rules ) ) : ?>
		<h4><?php esc_html_e( 'QR-Related Rewrite Rules:', 'wp-qr-trackr' ); ?></h4>
		<table class="widefat">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Pattern', 'wp-qr-trackr' ); ?></th>
					<th><?php esc_html_e( 'Replacement', 'wp-qr-trackr' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $rewrite_rules as $pattern => $replacement ) : ?>
					<?php if ( false !== strpos( $pattern, 'qr' ) || false !== strpos( $replacement, 'qr_tracking_code' ) ) : ?>
						<tr>
							<td><code><?php echo esc_html( $pattern ); ?></code></td>
							<td><code><?php echo esc_html( $replacement ); ?></code></td>
						</tr>
					<?php endif; ?>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
	
	<?php
	global $wp;
	$query_vars_registered = false;
	if ( isset( $wp->public_query_vars ) && in_array( 'qr_tracking_code', $wp->public_query_vars, true ) ) {
		$query_vars_registered = true;
	}
	?>
	
	<?php if ( ! $qr_rule_found || ! $query_vars_registered ) : ?>
		<div style="margin-top: 10px;">
			<form method="post" style="display: inline;">
				<?php wp_nonce_field( 'qr_trackr_flush_rules', 'qr_trackr_flush_nonce' ); ?>
				<input type="hidden" name="action" value="flush_rewrite_rules" />
				<input type="submit" class="button button-secondary" value="<?php esc_attr_e( 'Force Flush Rewrite Rules', 'wp-qr-trackr' ); ?>" />
			</form>
			<p style="margin-top: 5px;"><em><?php esc_html_e( 'Click this button to force re-register and flush rewrite rules and query variables if they appear to be missing.', 'wp-qr-trackr' ); ?></em></p>
		</div>
	<?php endif; ?>
	<?php
}

/**
 * Display QR image generation test.
 */
function qrc_display_qr_image_test() {
	$test_code = 'DEBUG123';
	$test_url  = home_url( '/qr/' . $test_code );

	?>
	<p><?php esc_html_e( 'Testing QR image generation with sample data...', 'wp-qr-trackr' ); ?></p>
	
	<table class="widefat">
		<tbody>
			<tr>
				<td><strong><?php esc_html_e( 'Test QR Code:', 'wp-qr-trackr' ); ?></strong></td>
				<td><?php echo esc_html( $test_code ); ?></td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'Test URL:', 'wp-qr-trackr' ); ?></strong></td>
				<td><a href="<?php echo esc_url( $test_url ); ?>" target="_blank"><?php echo esc_html( $test_url ); ?></a></td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'QR Image Generation:', 'wp-qr-trackr' ); ?></strong></td>
				<td>
					<?php
					$test_image = qr_trackr_generate_qr_image( $test_code, array( 'size' => 100 ) );
					if ( is_wp_error( $test_image ) ) {
						echo '<span style="color: #d63638;">❌ Failed: ' . esc_html( $test_image->get_error_message() ) . '</span>';
					} else {
						echo '<span style="color: #00a32a;">✅ Success</span><br>';
						echo '<img src="' . esc_url( $test_image ) . '" alt="Test QR Code" style="max-width: 100px; border: 1px solid #ddd; margin-top: 5px;" />';
					}
					?>
				</td>
			</tr>
		</tbody>
	</table>
	<?php
}

/**
 * Display filesystem status for debugging.
 */
function qrc_display_filesystem_status() {
	$upload_dir      = wp_upload_dir();
	$qr_dir          = '';
	$qr_dir_exists   = false;
	$qr_dir_writable = false;

	if ( ! isset( $upload_dir['error'] ) || empty( $upload_dir['error'] ) ) {
		$qr_dir          = wp_normalize_path( trailingslashit( $upload_dir['basedir'] ) . 'qr-codes' );
		$qr_dir_exists   = file_exists( $qr_dir );
		$qr_dir_writable = $qr_dir_exists && is_writable( $qr_dir );
	}

	?>
	<table class="widefat">
		<tbody>
			<tr>
				<td><strong><?php esc_html_e( 'QR Codes Directory:', 'wp-qr-trackr' ); ?></strong></td>
				<td><?php echo esc_html( $qr_dir ); ?></td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'Directory Exists:', 'wp-qr-trackr' ); ?></strong></td>
				<td>
					<?php if ( $qr_dir_exists ) : ?>
						<span style="color: #00a32a;">✅ Yes</span>
					<?php else : ?>
						<span style="color: #d63638;">❌ No - Will be created on first QR generation</span>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'Directory Writable:', 'wp-qr-trackr' ); ?></strong></td>
				<td>
					<?php if ( $qr_dir_writable ) : ?>
						<span style="color: #00a32a;">✅ Yes</span>
					<?php elseif ( $qr_dir_exists ) : ?>
						<span style="color: #d63638;">❌ No - Check permissions</span>
					<?php else : ?>
						<span style="color: #f0ad4e;">⚠️ Unknown (directory doesn't exist)</span>
					<?php endif; ?>
				</td>
			</tr>
			<?php if ( $qr_dir_exists ) : ?>
				<tr>
					<td><strong><?php esc_html_e( 'QR Images Count:', 'wp-qr-trackr' ); ?></strong></td>
					<td>
						<?php
						$qr_files = glob( $qr_dir . '/qr-*.png' );
						echo esc_html( is_array( $qr_files ) ? count( $qr_files ) : 0 );
						?>
					</td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
	<?php
}

/**
 * Display redirect test for debugging.
 */
function qrc_display_redirect_test() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';

	// Get a sample QR code from database
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Debug query for sample data.
	$sample_qr = $wpdb->get_row( "SELECT * FROM {$table_name} LIMIT 1" );

	?>
	<table class="widefat">
		<tbody>
			<?php if ( $sample_qr ) : ?>
				<?php $test_url = home_url( '/qr/' . $sample_qr->qr_code ); ?>
				<tr>
					<td><strong><?php esc_html_e( 'Sample QR URL:', 'wp-qr-trackr' ); ?></strong></td>
					<td>
						<a href="<?php echo esc_url( $test_url ); ?>" target="_blank"><?php echo esc_html( $test_url ); ?></a>
						<br><small><?php esc_html_e( 'Should redirect to:', 'wp-qr-trackr' ); ?> <code><?php echo esc_html( $sample_qr->destination_url ); ?></code></small>
					</td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'QR Code:', 'wp-qr-trackr' ); ?></strong></td>
					<td><?php echo esc_html( $sample_qr->qr_code ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Destination:', 'wp-qr-trackr' ); ?></strong></td>
					<td><a href="<?php echo esc_url( $sample_qr->destination_url ); ?>" target="_blank"><?php echo esc_html( $sample_qr->destination_url ); ?></a></td>
				</tr>
			<?php else : ?>
				<tr>
					<td colspan="2">
						<span style="color: #f0ad4e;">⚠️ <?php esc_html_e( 'No QR codes found in database. Create a QR code first to test redirects.', 'wp-qr-trackr' ); ?></span>
					</td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
	
	<p>
		<strong><?php esc_html_e( 'Manual Test Instructions:', 'wp-qr-trackr' ); ?></strong><br>
		<?php esc_html_e( '1. Create a new QR code via "Add New"', 'wp-qr-trackr' ); ?><br>
		<?php esc_html_e( '2. Click the QR tracking URL from the "All QR Codes" page', 'wp-qr-trackr' ); ?><br>
		<?php esc_html_e( '3. It should redirect to the destination URL', 'wp-qr-trackr' ); ?><br>
		<?php esc_html_e( '4. If you get a 404, check the permalink structure and rewrite rules above', 'wp-qr-trackr' ); ?>
	</p>
	<?php
}
