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

// Define plugin constants.
define( 'QR_TRACKR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'QR_TRACKR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'QR_TRACKR_VERSION', '1.0.4' );

// Load Composer autoloader.
require_once QR_TRACKR_PLUGIN_DIR . 'vendor/autoload.php';

/**
 * Bootstrap the QR Trackr plugin by requiring all modules.
 *
 * @return void
 */
function qr_trackr_bootstrap() {
	// Load activation module first to ensure tables are created.
	require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-activation.php';

	// Load remaining modules.
	require_once QR_TRACKR_PLUGIN_DIR . 'qr-trackr.php';
	require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-utils.php';
	require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-qr.php';
	require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-rewrite.php';
	require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-admin.php';
	require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-ajax.php';
	require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-debug.php';
}
qr_trackr_bootstrap();

// Register WP-CLI command for running tests.
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once QR_TRACKR_PLUGIN_DIR . 'includes/class-qr-trackr-list-table.php';
	require_once QR_TRACKR_PLUGIN_DIR . 'includes/class-qr-trackr-cli-command.php';
}
