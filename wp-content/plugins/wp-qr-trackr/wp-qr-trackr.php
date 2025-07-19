<?php
/**
 * Plugin Name: WP QR Trackr
 * Description: A comprehensive QR code generation and tracking plugin for WordPress with analytics, custom styling, and advanced management features.
 * Version: 1.2.24
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

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'QR_TRACKR_VERSION', '1.2.24' );
define( 'QR_TRACKR_PLUGIN_FILE', __FILE__ );
define( 'QR_TRACKR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'QR_TRACKR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'QR_TRACKR_DEBUG', defined( 'WP_DEBUG' ) && WP_DEBUG );

// Legacy constants for backward compatibility.
define( 'QRC_PLUGIN_FILE', __FILE__ );
define( 'QRC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'QRC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin activation hook
 */
function qr_trackr_activate_plugin() {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
		qr_trackr_debug_log( 'QR Trackr: Activation hook called' );
	}

	// Load required modules during activation.
	if ( file_exists( QR_TRACKR_PLUGIN_DIR . 'includes/module-activation.php' ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
			qr_trackr_debug_log( 'QR Trackr: Loading activation module' );
		}
		require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-activation.php';
		if ( function_exists( 'qrc_activate' ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
				qr_trackr_debug_log( 'QR Trackr: Running activation function' );
			}
			qrc_activate();
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
				qr_trackr_debug_log( 'QR Trackr: Activation function completed' );
			}
		} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
				qr_trackr_debug_log( 'QR Trackr: ERROR - qrc_activate function not found!' );
		}
	} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
			qr_trackr_debug_log( 'QR Trackr: ERROR - Activation module not found!' );
	}

	// Schedule rewrite rules flush for next request when WordPress is fully loaded.
	update_option( 'qr_trackr_needs_flush', true );
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
		qr_trackr_debug_log( 'QR Trackr: Scheduled rewrite rules flush' );
	}
}

/**
 * Plugin deactivation hook
 */
function qr_trackr_deactivate_plugin() {
	// Only load activation module when actually deactivating.
	if ( file_exists( QR_TRACKR_PLUGIN_DIR . 'includes/module-activation.php' ) ) {
		require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-activation.php';
		if ( function_exists( 'qrc_deactivate' ) ) {
			qrc_deactivate();
		}
	}

	// Flush rewrite rules.
	flush_rewrite_rules();
}

// Create a log file in the plugin directory.
$log_file = plugin_dir_path( __FILE__ ) . 'plugin-debug.log';
if ( ! empty( $log_file ) ) {
	file_put_contents( $log_file, gmdate( '[Y-m-d H:i:s] ' ) . "Plugin loading started\n", FILE_APPEND );
}

/**
 * Load core modules in the correct order.
 *
 * @since 1.2.21
 * @return void
 */
function qr_trackr_load_modules() {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
		qr_trackr_debug_log( 'QR Trackr: Loading core modules on hook: ' . current_filter() );
	}

	// Core modules that must be loaded in order.
	$core_modules = array(
		'module-requirements.php',
		'module-utils.php',
		'class-qrc-links-list-table.php',
		'module-activation.php',
		'module-admin.php',
		'module-ajax.php',
		'module-qr.php',
		'module-rewrite.php',
	);

	// Load core modules.
	foreach ( $core_modules as $module ) {
		$module_path = QR_TRACKR_PLUGIN_DIR . 'includes/' . $module;
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
			qr_trackr_debug_log( 'QR Trackr: Loading module: ' . $module );
		}

		if ( file_exists( $module_path ) ) {
			require_once $module_path;
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
				qr_trackr_debug_log( 'QR Trackr: Successfully loaded: ' . $module );
			}
		} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
				qr_trackr_debug_log( 'QR Trackr: ERROR - Module not found: ' . $module . ' at path: ' . $module_path );
		}
	}

	// Log WordPress state.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
		qr_trackr_debug_log( 'QR Trackr: WordPress state - is_admin(): ' . ( is_admin() ? 'true' : 'false' ) );
		qr_trackr_debug_log( 'QR Trackr: WordPress state - current_user_can(manage_options): ' . ( current_user_can( 'manage_options' ) ? 'true' : 'false' ) );
		qr_trackr_debug_log( 'QR Trackr: WordPress state - get_current_user_id(): ' . get_current_user_id() );
	}
}

// Load modules immediately.
qr_trackr_load_modules();

// Also hook into plugins_loaded for good measure.
add_action( 'plugins_loaded', 'qr_trackr_load_modules', 10 );

// Initialize the plugin.
/**
 * Initialize the plugin.
 *
 * @since 1.2.21
 * @return void
 */
function qr_trackr_init() {
	global $log_file;
	if ( ! empty( $log_file ) ) {
		file_put_contents( $log_file, gmdate( '[Y-m-d H:i:s] ' ) . "Plugin initialization started\n", FILE_APPEND );
	}

	// Load translations.
	load_plugin_textdomain( 'wp-qr-trackr', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

/**
 * Check if rewrite rules need to be flushed after plugin update.
 *
 * @since 1.2.14
 * @return void
 */
function qr_trackr_maybe_flush_rewrite_rules() {
	$stored_version  = get_option( 'qr_trackr_version', '' );
	$current_version = QR_TRACKR_VERSION;

	// Check if version changed or if this is a fresh install.
	if ( $stored_version !== $current_version ) {
		// Set a flag to flush rewrite rules on the init hook when rewrite system is ready.
		update_option( 'qr_trackr_needs_flush', true );
		update_option( 'qr_trackr_version', $current_version );

		if ( QR_TRACKR_DEBUG ) {
			$action = empty( $stored_version ) ? 'fresh install' : sprintf( 'version update from %s to %s', $stored_version, $current_version );
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only.
			error_log( sprintf( 'QR Trackr: Scheduled rewrite rules flush after %s', $action ) );
		}
	}
}

// Register hooks.
register_activation_hook( __FILE__, 'qr_trackr_activate_plugin' );
register_deactivation_hook( __FILE__, 'qr_trackr_deactivate_plugin' );
add_action( 'init', 'qr_trackr_init' ); // Run early.

// Register query var early.
add_action(
	'init',
	function () {
		global $wp;
		if ( isset( $wp->public_query_vars ) ) {
			if ( ! in_array( 'qr_tracking_code', $wp->public_query_vars, true ) ) {
				$wp->public_query_vars[] = 'qr_tracking_code';

				if ( defined( 'QR_TRACKR_DEBUG' ) && QR_TRACKR_DEBUG ) {
					error_log( 'QR Trackr: Added qr_tracking_code to public_query_vars directly' );
				}
			}
		}
	},
	1
);

// Ensure query var is registered.
add_filter(
	'query_vars',
	function ( $vars ) {
		if ( ! in_array( 'qr_tracking_code', $vars, true ) ) {
			$vars[] = 'qr_tracking_code';

			if ( defined( 'QR_TRACKR_DEBUG' ) && QR_TRACKR_DEBUG ) {
				error_log( 'QR Trackr: Added qr_tracking_code via query_vars filter' );
			}
		}
		return $vars;
	},
	1
);

// Add a late check to ensure query var is registered.
add_action(
	'wp_loaded',
	function () {
		global $wp;
		if ( isset( $wp->public_query_vars ) && ! in_array( 'qr_tracking_code', $wp->public_query_vars, true ) ) {
			$wp->public_query_vars[] = 'qr_tracking_code';

			if ( defined( 'QR_TRACKR_DEBUG' ) && QR_TRACKR_DEBUG ) {
				error_log( 'QR Trackr: Late registration of qr_tracking_code query var' );
			}
		}
	},
	1
);

// Add a very late check to ensure query var is registered.
add_action(
	'parse_request',
	function ( $wp ) {
		if ( ! in_array( 'qr_tracking_code', $wp->public_query_vars, true ) ) {
			$wp->public_query_vars[] = 'qr_tracking_code';

			if ( defined( 'QR_TRACKR_DEBUG' ) && QR_TRACKR_DEBUG ) {
				error_log( 'QR Trackr: Very late registration of qr_tracking_code query var' );
			}
		}
	},
	1
);

// Add a final check to ensure query var is registered.
add_action(
	'wp',
	function () {
		global $wp;
		if ( isset( $wp->public_query_vars ) && ! in_array( 'qr_tracking_code', $wp->public_query_vars, true ) ) {
			$wp->public_query_vars[] = 'qr_tracking_code';

			if ( defined( 'QR_TRACKR_DEBUG' ) && QR_TRACKR_DEBUG ) {
				error_log( 'QR Trackr: Final registration of qr_tracking_code query var' );
			}
		}
	},
	1
);
