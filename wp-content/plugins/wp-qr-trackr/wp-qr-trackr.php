<?php
/**
 * Plugin Name: QR Trackr
 * Description: Professional QR code tracking for WordPress. Modular, secure, and standards-driven.
 * Version: 1.0.0
 * Author: Michael Erps
 * License: GPL2
 * Text Domain: qr-trackr
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
