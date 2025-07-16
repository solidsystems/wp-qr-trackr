<?php
/**
 * Plugin requirements check.
 *
 * @package WP_QR_TRACKR
 */
// phpcs:ignoreFile WordPress.Security.EscapeOutput.OutputNotEscaped -- False positive: no $item output in this file.

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Check if the server meets the plugin's requirements.
 *
 * @since 1.0.0
 * @return bool True if requirements are met, false otherwise.
 */
function qrc_requirements_met() {
	// Check for WordPress version.
	if ( version_compare( get_bloginfo( 'version' ), '5.0', '<' ) ) {
		return false;
	}

	// Check for PHP version.
	if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
		return false;
	}

	// Check for required extensions.
	if ( ! extension_loaded( 'gd' ) ) {
		return false;
	}

	return true;
}

/**
 * Display a notice if requirements are not met.
 *
 * @since 1.0.0
 * @return void
 */
function qrc_requirements_notice() {
	if ( false === qrc_requirements_met() ) {
		?>
		<div class="notice notice-error">
			<p>
				<?php
				esc_html_e( 'WP QR Trackr requires WordPress 5.0 or higher, PHP 7.4 or higher, and the GD extension to be enabled.', 'wp-qr-trackr' );
				?>
			</p>
		</div>
		<?php
	}
}
add_action( 'admin_notices', 'qrc_requirements_notice' );

/**
 * Deactivate the plugin if requirements are not met.
 *
 * @since 1.0.0
 * @return void
 */
function qrc_deactivate_self() {
	if ( false === qrc_requirements_met() ) {
		deactivate_plugins( plugin_basename( QRC_PLUGIN_FILE ) );
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
	}
}
add_action( 'admin_init', 'qrc_deactivate_self' ); 