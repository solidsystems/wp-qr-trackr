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
	add_options_page(
		'QR Coder Settings',
		'QR Coder',
		'manage_options',
		'wp-qr-trackr',
		'qrc_options_page'
	);

	add_menu_page(
		'QR Code Links',
		'QR Codes',
		'manage_options',
		'qrc-links',
		'qrc_links_page',
		'dashicons-camera',
		20
	);
}
add_action( 'admin_menu', 'qrc_add_admin_menu' );

/**
 * Display the QR code links page.
 */
function qrc_links_page() {
	$list_table = new QRC_Links_List_Table();
	$list_table->prepare_items();
	?>
	<div class="wrap">
		<h1>QR Code Links</h1>
		<?php $list_table->display(); ?>
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
		'wp-qr-trackr'
	);

	add_settings_field(
		'qrc_remove_data_on_deactivation',
		'Remove Data on Deactivation',
		'qrc_remove_data_on_deactivation_callback',
		'wp-qr-trackr',
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
 * Validate the options.
 *
 * @param array $input The input options.
 * @return array The validated options.
 */
function qrc_options_validate( $input ) {
	$new_input = array();
	if ( isset( $input['remove_data_on_deactivation'] ) ) {
		$new_input['remove_data_on_deactivation'] = 1;
	} else {
		$new_input['remove_data_on_deactivation'] = 0;
	}
	return $new_input;
} 