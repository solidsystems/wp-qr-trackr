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
	$options = get_option( 'qrc_options' );
	?>
	<input type="checkbox" name="qrc_options[remove_data_on_deactivation]" value="1" <?php checked( 1, isset( $options['remove_data_on_deactivation'] ) ? $options['remove_data_on_deactivation'] : 0, true ); ?> />
	<label for="qrc_options[remove_data_on_deactivation]">Check this box to remove all plugin data when deactivating the plugin.</label>
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
			'scans'           => 0,
			'access_count'    => 0,
			'created_at'      => current_time( 'mysql', true ),
			'updated_at'      => current_time( 'mysql', true ),
		),
		array( '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s' )
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
