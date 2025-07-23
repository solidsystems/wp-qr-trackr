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
 * Register admin menu items.
 *
 * @since 1.0.0
 * @return void
 */
function qrc_admin_menu() {
	error_log( 'QR Trackr: qrc_admin_menu() called. Hook: ' . current_filter() . ', User: ' . get_current_user_id() );

	// Add main menu item.
	$hook = add_menu_page(
		__( 'QR Code Links', 'wp-qr-trackr' ),
		__( 'QR Codes', 'wp-qr-trackr' ),
		'manage_options',
		'qrc-links',  // Changed from qr-code-links to qrc-links
		'qrc_admin_page',
		'dashicons-admin-links'
	);

	error_log( 'QR Trackr: Menu page added with hook: ' . ( $hook ? $hook : 'failed' ) );

	// Add submenu items.
	add_submenu_page(
		'qrc-links',  // Changed from qr-code-links to qrc-links
		__( 'All QR Codes', 'wp-qr-trackr' ),
		__( 'All QR Codes', 'wp-qr-trackr' ),
		'manage_options',
		'qrc-links',  // Changed from qr-code-links to qrc-links
		'qrc_admin_page'
	);

	// Add "Add New" page.
	$add_new = add_submenu_page(
		'qrc-links',  // Changed from qr-code-links to qrc-links
		__( 'Add New QR Code', 'wp-qr-trackr' ),
		__( 'Add New', 'wp-qr-trackr' ),
		'manage_options',
		'qr-code-add-new',
		'qrc_add_new_page'
	);

	error_log( 'QR Trackr: Add New page added with hook: ' . ( $add_new ? $add_new : 'failed' ) );

	// Add settings page.
	$settings = add_submenu_page(
		'qrc-links',  // Changed from qr-code-links to qrc-links
		__( 'Settings', 'wp-qr-trackr' ),
		__( 'Settings', 'wp-qr-trackr' ),
		'manage_options',
		'qrc-settings',  // Changed from qr-code-settings to qrc-settings
		'qrc_settings_page'
	);

	error_log( 'QR Trackr: Settings page added with hook: ' . ( $settings ? $settings : 'failed' ) );
}

/**
 * Initialize admin functionality.
 *
 * @since 1.0.0
 * @return void
 */
function qrc_admin_init() {
	error_log( 'QR Trackr: qrc_admin_init() called' );

	// Register settings.
	register_setting( 'qr_trackr_settings', 'qrc_options' );

	// Add settings sections.
	add_settings_section(
		'qrc_general_settings',
		__( 'General Settings', 'wp-qr-trackr' ),
		'qrc_general_settings_section',
		'qrc-settings'  // Changed from qr-code-settings to qrc-settings
	);

	// Add settings fields.
	add_settings_field(
		'qrc_default_redirect',
		__( 'Default Redirect', 'wp-qr-trackr' ),
		'qrc_default_redirect_field',
		'qrc-settings',  // Changed from qr-code-settings to qrc-settings
		'qrc_general_settings'
	);
}

/**
 * Render the admin page.
 *
 * @since 1.0.0
 * @return void
 */
function qrc_admin_page() {
	// Check user capabilities.
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Get the list table.
	$list_table = new QRC_Links_List_Table();
	$list_table->prepare_items();

	// Display the page.
	include QRC_PLUGIN_DIR . 'templates/admin-page.php';
}

/**
 * Render the add new page.
 *
 * @since 1.0.0
 * @return void
 */
function qrc_add_new_page() {
	// Check user capabilities.
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Display the page.
	include QRC_PLUGIN_DIR . 'templates/add-new-page.php';
}

/**
 * Render the settings page.
 *
 * @since 1.0.0
 * @return void
 */
function qrc_settings_page() {
	// Check user capabilities.
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Display the page.
	include QRC_PLUGIN_DIR . 'templates/settings-page.php';
}

/**
 * Render the general settings section.
 *
 * @since 1.0.0
 * @return void
 */
function qrc_general_settings_section() {
	echo '<p>' . esc_html__( 'Configure general plugin settings.', 'wp-qr-trackr' ) . '</p>';
}

/**
 * Render the default redirect field.
 *
 * @since 1.0.0
 * @return void
 */
function qrc_default_redirect_field() {
	$options          = get_option( 'qrc_options' );
	$default_redirect = isset( $options['default_redirect'] ) ? $options['default_redirect'] : home_url();
	?>
	<input type="url" name="qrc_options[default_redirect]" value="<?php echo esc_url( $default_redirect ); ?>" class="regular-text" />
	<p class="description"><?php esc_html_e( 'The default URL to redirect to when a QR code is not found.', 'wp-qr-trackr' ); ?></p>
	<?php
}

// Register admin menu hooks with debug logging
if ( defined( 'QR_TRACKR_DEBUG' ) && QR_TRACKR_DEBUG ) {
	error_log( 'QR Trackr: Registering admin menu hooks' );
}

add_action( 'admin_menu', 'qrc_admin_menu' );
add_action( 'admin_init', 'qrc_admin_init' );

// Register settings link
add_filter( 'plugin_action_links_' . plugin_basename( QR_TRACKR_PLUGIN_FILE ), 'qrc_add_settings_link' );

if ( defined( 'QR_TRACKR_DEBUG' ) && QR_TRACKR_DEBUG ) {
	error_log( 'QR Trackr: Admin module loaded successfully' );
}
