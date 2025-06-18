<?php
/**
 * Plugin Name: QR Trackr
 * Plugin URI: https://github.com/michaelerps/wp-qr-trackr
 * Description: A powerful WordPress plugin for creating, managing, and tracking QR codes with detailed analytics.
 * Version: 1.1.0-rc
 * Author: Michael Erps
 * Author URI: https://github.com/michaelerps
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-qr-trackr
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

// Define plugin constants
define( 'QR_TRACKR_VERSION', '1.1.0-rc' );
define( 'QR_TRACKR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'QR_TRACKR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'QR_TRACKR_DEBUG', defined( 'WP_DEBUG' ) && WP_DEBUG );

// Robust error handling and logging for plugin load
try {
	qr_trackr_debug_log('--- QR Trackr plugin file loaded ---');

	// Load Composer autoloader.
	if ( ! file_exists( plugin_dir_path( __FILE__ ) . 'vendor/autoload.php' ) ) {
		qr_trackr_debug_log('Composer autoload.php missing. Plugin cannot function.');
		return;
	}
	require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

	// Load required files in correct order
	$modules = [
		'includes/module-requirements.php',
		'includes/module-utils.php',
		'includes/module-debug.php',
		'includes/module-activation.php',
		'includes/module-qr.php',
		'includes/module-admin.php',
		'includes/module-ajax.php',
		'includes/module-rewrite.php',
	];
	foreach ( $modules as $mod ) {
		$mod_path = QR_TRACKR_PLUGIN_DIR . $mod;
		if ( ! file_exists( $mod_path ) ) {
			qr_trackr_debug_log('Required module missing: ' . $mod);
			return;
		}
		require_once $mod_path;
	}

	qr_trackr_debug_log('All core modules loaded.');
} catch (Throwable $e) {
	qr_trackr_debug_log('Fatal error during plugin file load: ' . $e->getMessage());
	return;
}

/**
 * Load plugin text domain.
 *
 * @return void
 */
function qr_trackr_load_textdomain() {
	load_plugin_textdomain(
		'wp-qr-trackr',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);
}
add_action( 'init', 'qr_trackr_load_textdomain' );

/**
 * Initialize the plugin
 */
function qr_trackr_init() {
	try {
		qr_trackr_debug_log('Initializing QR Trackr plugin...');
		// Load text domain
		load_plugin_textdomain( 'wp-qr-trackr', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		
		// Check requirements
		if ( ! function_exists( 'qr_trackr_check_requirements' ) ) {
			qr_trackr_debug_log( 'Requirements module not loaded' );
			return;
		}
		
		$requirements = qr_trackr_check_requirements();
		
		// Store requirements status
		update_option('qr_trackr_requirements', $requirements);
		
		// Only block initialization if library is missing
		if ( ! $requirements['library'] ) {
			qr_trackr_debug_log( 'QR code library not available' );
			return;
		}
		
		// For other requirements, show admin notice but continue initialization
		if ( ! $requirements['permalinks'] ) {
			add_option('qr_trackr_permalinks_missing', true);
			qr_trackr_debug_log( 'Pretty permalinks not enabled' );
		}
		
		if ( ! $requirements['uploads'] ) {
			add_option('qr_trackr_uploads_missing', true);
			qr_trackr_debug_log( 'Uploads directory not writable' );
		}
		
		// Initialize admin module
		if ( is_admin() && function_exists( 'qr_trackr_admin_init' ) ) {
			qr_trackr_admin_init();
		}
		
		// Initialize AJAX module
		if ( function_exists( 'qr_trackr_ajax_init' ) ) {
			qr_trackr_ajax_init();
		}
		
		// Initialize rewrite module only on frontend
		if ( ! is_admin() && ! wp_doing_ajax() && function_exists( 'qr_trackr_rewrite_init' ) ) {
			qr_trackr_rewrite_init();
		}
		qr_trackr_debug_log('QR Trackr plugin initialization complete.');
	} catch (Exception $e) {
		qr_trackr_debug_log('Error during plugin initialization: ' . $e->getMessage());
	} catch (Throwable $e) {
		qr_trackr_debug_log('Fatal error during plugin initialization: ' . $e->getMessage());
	}
}
add_action( 'plugins_loaded', 'qr_trackr_init' );

// Register WP-CLI command for running tests.
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once QR_TRACKR_PLUGIN_DIR . 'includes/class-qr-trackr-list-table.php';
	require_once QR_TRACKR_PLUGIN_DIR . 'includes/class-qr-trackr-cli-command.php';
}

qr_trackr_debug_log('--- QR Trackr plugin file END ---');
