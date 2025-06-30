<?php
/**
 * Plugin Name: QR Trackr
 * Plugin URI: https://github.com/michaelerps/wp-qr-trackr
 * Description: A powerful WordPress plugin for creating, managing, and tracking QR codes with detailed analytics.
 * Version: 1.2.0
 * Author: Michael Erps
 * Author URI: https://github.com/michaelerps
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-qr-trackr
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 *
 * Main plugin file for QR Trackr.
 *
 * @package QR_Trackr
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'QR_TRACKR_VERSION', '1.2.0' );
define( 'QR_TRACKR_PLUGIN_FILE', __FILE__ );
define( 'QR_TRACKR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'QR_TRACKR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'QR_TRACKR_DEBUG', defined( 'WP_DEBUG' ) && WP_DEBUG );

// Load activation module immediately for hooks.
require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-activation.php';

/**
 * The main plugin initialization function.
 *
 * @return void
 */
function qr_trackr_init_plugin() {
	// Load Composer autoloader.
	if ( ! file_exists( plugin_dir_path( __FILE__ ) . 'vendor/autoload.php' ) ) {
		// Display an admin notice if the autoloader is missing.
		add_action(
			'admin_notices',
			function () {
				echo '<div class="notice notice-error"><p>';
				echo esc_html__( 'QR Trackr: Composer autoloader not found. The plugin cannot function. Please run `composer install`.', 'wp-qr-trackr' );
				echo '</p></div>';
			}
		);
		return;
	}
	require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

	// Load modules after autoloader, in the correct order.
	require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-debug.php';
	require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-requirements.php';
	require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-utils.php';
	require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-qr.php';
	require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-admin.php';
	require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-ajax.php';
	require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-rewrite.php';

	// Load text domain for localization.
	load_plugin_textdomain( 'wp-qr-trackr', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	// Initialize rewrite rules on activation.
	register_activation_hook( __FILE__, 'qr_trackr_flush_rewrite_rules' );

	// Initialize modules.
	if ( is_admin() ) {
		qr_trackr_admin_init();
	}

	qr_trackr_rewrite_init();

	// Register WP-CLI command.
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		require_once QR_TRACKR_PLUGIN_DIR . 'includes/class-qr-trackr-cli-command.php';
	}
}
add_action( 'plugins_loaded', 'qr_trackr_init_plugin' );
