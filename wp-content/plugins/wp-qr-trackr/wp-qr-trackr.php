<?php
/**
 * Plugin Name: QR Trackr
 * Plugin URI: https://github.com/michaelerps/wp-qr-trackr
 * Description: A WordPress plugin for tracking QR code scans and managing QR code campaigns.
 * Version: 1.0.2
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

/**
 * Bootstrap the QR Trackr plugin by requiring all modules.
 *
 * @return void
 */
function qr_trackr_bootstrap() {
	require_once __DIR__ . '/includes/module-utility.php';
	require_once __DIR__ . '/includes/module-qr.php';
	require_once __DIR__ . '/includes/module-rewrite.php';
	require_once __DIR__ . '/includes/module-admin.php';
	require_once __DIR__ . '/includes/module-ajax.php';
}
qr_trackr_bootstrap();

// Register WP-CLI command for running tests.
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once __DIR__ . '/includes/class-qr-trackr-list-table.php';
	require_once __DIR__ . '/includes/class-qr-trackr-cli-command.php';
}
