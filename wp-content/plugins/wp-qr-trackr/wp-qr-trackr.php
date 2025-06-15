<?php
/**
 * Plugin Name: QR Trackr
 * Plugin URI: https://github.com/michaelerps/wp-qr-trackr
 * Description: A WordPress plugin for tracking QR code scans and managing QR code campaigns.
 * Version: ..1
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: Michael Erps
 * Author URI: https://github.com/michaelerps
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-qr-trackr
 * Domain Path: /languages
 * Update URI: https://github.com/michaelerps/wp-qr-trackr
 *
 * @package QR_Trackr
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load Composer autoloader.
require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

// Define plugin constants.
define( 'QR_TRACKR_VERSION', '1.0.2' );
define( 'QR_TRACKR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'QR_TRACKR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Fallback debug logging function in case debug module isn't loaded yet.
if ( ! function_exists( 'qr_trackr_debug_log' ) ) {
	/**
	 * Fallback debug logging function.
	 *
	 * @param string $message The message to log.
	 * @return void
	 */
	function qr_trackr_debug_log( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[QR Trackr] ' . $message );
		}
	}
}

/**
 * Bootstrap the plugin.
 *
 * @return void
 */
function qr_trackr_bootstrap() {
	// Load debug module first to ensure logging is available.
	require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-debug.php';

	// Load activation module to ensure tables are created.
	require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-activation.php';

	// Load requirements module.
	require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-requirements.php';

	// Check if requirements are met before loading remaining modules.
	if ( ! qr_trackr_requirements_met() ) {
		return;
	}

	// Load remaining modules.
	require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-admin.php';
	require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-ajax.php';
	require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-rewrite.php';
	require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-utility.php';
}

// Initialize plugin.
qr_trackr_bootstrap();

// Register WP-CLI command for running tests.
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once QR_TRACKR_PLUGIN_DIR . 'includes/class-qr-trackr-list-table.php';
	require_once QR_TRACKR_PLUGIN_DIR . 'includes/class-qr-trackr-cli-command.php';
}
