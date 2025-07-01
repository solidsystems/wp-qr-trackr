<?php
/**
 * Plugin Name: WP QR Trackr
 * Description: A comprehensive QR code generation and tracking plugin for WordPress with analytics, custom styling, and advanced management features.
 * Version: 1.2.18
 * Author: Solid Systems
 * Author URI: https://solidsystems.io
 * Plugin URI: https://github.com/solidsystems/wp-qr-trackr
 * Text Domain: wp-qr-trackr
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 *
 * @package WP_QR_TRACKR
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'QR_TRACKR_VERSION', '1.2.18' );
define( 'QR_TRACKR_PLUGIN_FILE', __FILE__ );
define( 'QR_TRACKR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'QR_TRACKR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'QR_TRACKR_DEBUG', defined( 'WP_DEBUG' ) && WP_DEBUG );

// Legacy constants for backward compatibility
define( 'QRC_PLUGIN_FILE', __FILE__ );
define( 'QRC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'QRC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin activation hook
 */
function qr_trackr_activate_plugin() {
	// Load required modules during activation
	if ( file_exists( QR_TRACKR_PLUGIN_DIR . 'includes/module-activation.php' ) ) {
		require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-activation.php';
		if ( function_exists( 'qrc_activate' ) ) {
			qrc_activate();
		}
	}
	
	// Load rewrite module so rules are registered before flushing
	if ( file_exists( QR_TRACKR_PLUGIN_DIR . 'includes/module-rewrite.php' ) ) {
		require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-rewrite.php';
	}
	
	// Now flush rewrite rules with our custom rules registered
	flush_rewrite_rules();
}

/**
 * Plugin deactivation hook
 */
function qr_trackr_deactivate_plugin() {
	// Only load activation module when actually deactivating
	if ( file_exists( QR_TRACKR_PLUGIN_DIR . 'includes/module-activation.php' ) ) {
		require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-activation.php';
		if ( function_exists( 'qrc_deactivate' ) ) {
			qrc_deactivate();
		}
	}
	
	// Flush rewrite rules
	flush_rewrite_rules();
}

/**
 * Initialize the plugin safely
 */
function qr_trackr_init_plugin() {
	// Load vendor autoloader if it exists
	$autoloader = QR_TRACKR_PLUGIN_DIR . 'vendor/autoload.php';
	if ( file_exists( $autoloader ) ) {
		require_once $autoloader;
	}

	// Load plugin modules safely with file existence checks
	$modules = array(
		'includes/module-requirements.php',
		'includes/module-utils.php',
		'includes/module-qr.php',
		'includes/class-qrc-links-list-table.php',
		'includes/module-admin.php',
		'includes/module-ajax.php',
		'includes/module-rewrite.php'
	);

	foreach ( $modules as $module ) {
		$module_path = QR_TRACKR_PLUGIN_DIR . $module;
		if ( file_exists( $module_path ) ) {
			require_once $module_path;
		}
	}

	// Load text domain for translations
	load_plugin_textdomain( 'wp-qr-trackr', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	// Check if rewrite rules need to be flushed after plugin update
	// IMPORTANT: This must happen AFTER modules are loaded so rewrite rules are registered
	qr_trackr_maybe_flush_rewrite_rules();
}

/**
 * Check if rewrite rules need to be flushed after plugin update.
 *
 * @since 1.2.14
 * @return void
 */
function qr_trackr_maybe_flush_rewrite_rules() {
	$stored_version = get_option( 'qr_trackr_version', '' );
	$current_version = QR_TRACKR_VERSION;
	
	// Check if version changed or if this is a fresh install
	if ( $stored_version !== $current_version ) {
		// Ensure rewrite module is loaded before flushing
		if ( function_exists( 'qr_trackr_add_rewrite_rules' ) ) {
			// Re-register our rewrite rules first
			qr_trackr_add_rewrite_rules();
			
			// Now flush all rewrite rules
			flush_rewrite_rules();
			
			// Update stored version
			update_option( 'qr_trackr_version', $current_version );
			
			if ( QR_TRACKR_DEBUG ) {
				$action = empty( $stored_version ) ? 'fresh install' : sprintf( 'version update from %s to %s', $stored_version, $current_version );
				error_log( sprintf( 'QR Trackr: Flushed rewrite rules after %s', $action ) );
			}
		} else {
			// Rewrite module not loaded yet - schedule for next request
			if ( QR_TRACKR_DEBUG ) {
				error_log( 'QR Trackr: Rewrite module not loaded, scheduling flush for next request' );
			}
			
			// Set a flag to flush on next request when modules are loaded
			update_option( 'qr_trackr_needs_flush', true );
		}
	}
	
	// Check if we have a pending flush from a previous request
	if ( get_option( 'qr_trackr_needs_flush' ) && function_exists( 'qr_trackr_add_rewrite_rules' ) ) {
		qr_trackr_add_rewrite_rules();
		flush_rewrite_rules();
		delete_option( 'qr_trackr_needs_flush' );
		update_option( 'qr_trackr_version', $current_version );
		
		if ( QR_TRACKR_DEBUG ) {
			error_log( 'QR Trackr: Completed deferred rewrite rules flush' );
		}
	}
}

// Register hooks
register_activation_hook( __FILE__, 'qr_trackr_activate_plugin' );
register_deactivation_hook( __FILE__, 'qr_trackr_deactivate_plugin' );
add_action( 'plugins_loaded', 'qr_trackr_init_plugin' );
