<?php
/**
 * Plugin Name: WP QR Trackr
 * Description: A comprehensive QR code generation and tracking plugin for WordPress with analytics, custom styling, and advanced management features.
 * Version: 1.2.70
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
define( 'QR_TRACKR_VERSION', '1.2.70' );
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
	// Load required modules during activation.
	if ( file_exists( QR_TRACKR_PLUGIN_DIR . 'includes/module-activation.php' ) ) {
		require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-activation.php';
		if ( function_exists( 'qrc_activate' ) ) {
			qrc_activate();
		}
	}

	// Schedule rewrite rules flush for next request when WordPress is fully loaded.
	update_option( 'qr_trackr_needs_flush', true );
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

/**
 * Initialize the plugin safely
 */
function qr_trackr_init_plugin() {
	// Load vendor autoloader if it exists.
	$autoloader = QR_TRACKR_PLUGIN_DIR . 'vendor/autoload.php';
	if ( file_exists( $autoloader ) ) {
		require_once $autoloader;
	}

	// Load plugin modules safely with file existence checks.
	$modules = array(
		'includes/module-requirements.php',
		'includes/module-utils.php',
		'includes/module-qr.php',
		'includes/class-qrc-links-list-table.php',
		'includes/module-admin.php',
		'includes/module-ajax.php',
		'includes/module-rewrite.php',
	);

	foreach ( $modules as $module ) {
		$module_path = QR_TRACKR_PLUGIN_DIR . $module;
		if ( file_exists( $module_path ) ) {
			require_once $module_path;
		}
	}

	// Load text domain for translations.
	load_plugin_textdomain( 'wp-qr-trackr', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	// Check if rewrite rules need to be flushed after plugin update.
	// IMPORTANT: This must happen AFTER modules are loaded so rewrite rules are registered.
	qr_trackr_maybe_flush_rewrite_rules();
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

/**
 * Load plugin modules safely with error handling.
 *
 * @since 1.2.14
 * @return void
 */
function qr_trackr_load_modules() {
	static $loaded = false;

	// Prevent double loading.
	if ( $loaded ) {
		return;
	}

	// Check if includes directory exists.
	$includes_dir = QR_TRACKR_PLUGIN_DIR . 'includes';
	if ( ! file_exists( $includes_dir ) || ! is_dir( $includes_dir ) ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Critical error logging for missing includes directory.
		error_log( 'QR Trackr: ERROR - Includes directory not found at: ' . $includes_dir );
		add_action(
			'admin_notices',
			function () use ( $includes_dir ) {
				$message = sprintf(
					/* translators: %s: Directory path */
					__( 'QR Trackr Error: Required includes directory is missing: %s. Please reinstall the plugin.', 'wp-qr-trackr' ),
					esc_html( $includes_dir )
				);
				printf( '<div class="notice notice-error"><p>%s</p></div>', wp_kses_post( $message ) );
			}
		);
		return;
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

	// Check if all required modules exist first.
	$missing_modules = array();
	foreach ( $core_modules as $module ) {
		$module_path = $includes_dir . '/' . $module;
		if ( ! file_exists( $module_path ) ) {
			$missing_modules[] = $module;

		}
	}

	// If any required modules are missing, show admin notice and return.
	if ( ! empty( $missing_modules ) ) {
		add_action(
			'admin_notices',
			function () use ( $missing_modules ) {
				$message = sprintf(
					/* translators: %s: List of missing module files */
					__( 'QR Trackr Error: Required module files are missing: %s. Please reinstall the plugin.', 'wp-qr-trackr' ),
					implode( ', ', $missing_modules )
				);
				printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $message ) );
			}
		);
		return;
	}

	// Load core modules.
	foreach ( $core_modules as $module ) {
		$module_path = $includes_dir . '/' . $module;
		require_once $module_path;
	}

	$loaded = true;

	// Disable Google Fonts to prevent SSL certificate issues in development and production.
// This prevents SSL certificate warnings when loading fonts from Google Fonts.
add_filter( 'wp_resource_hints', function( $hints, $relation_type ) {
	if ( 'dns-prefetch' === $relation_type ) {
		$hints = array_filter( $hints, function( $hint ) {
			return ! str_contains( $hint, 'fonts.googleapis.com' );
		} );
	}
	return $hints;
}, 10, 2 );

add_filter( 'wp_enqueue_scripts', function() {
	wp_dequeue_style( 'google-fonts' );
	wp_deregister_style( 'google-fonts' );
}, 999 );

// Also remove Google Fonts from wp_head if they're being added directly.
add_action( 'wp_head', function() {
	ob_start();
}, 0 );

add_action( 'wp_head', function() {
	$content = ob_get_clean();
	// Remove Google Fonts links from wp_head output.
	$content = preg_replace( '/<link[^>]*fonts\.googleapis\.com[^>]*>/i', '', $content );
	echo $content;
}, 999 );
}

// Only load modules on plugins_loaded hook with high priority.
add_action( 'plugins_loaded', 'qr_trackr_load_modules', 5 );

/**
 * Initialize the plugin
 */
function qr_trackr_init() {

	// Load translations.
	load_plugin_textdomain( 'wp-qr-trackr', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	// Check if we need to flush rewrite rules.
	qr_trackr_maybe_flush_rewrite_rules();
}

// Add init hook with high priority.
add_action( 'init', 'qr_trackr_init', 5 );

// Remove old action to prevent double loading.
remove_action( 'plugins_loaded', 'qr_trackr_init_plugin' );

// Add initialization on plugins_loaded with high priority.
add_action( 'plugins_loaded', 'qr_trackr_init_plugin', 5 );

/**
 * Flush rewrite rules after version updates when needed.
 *
 * Runs late on init so that rewrite rules have been registered by modules.
 *
 * @since 1.2.60
 * @return void
 */
function qr_trackr_flush_rewrites_if_needed() {
	$needs_flush = get_option( 'qr_trackr_needs_flush', false );
	if ( $needs_flush ) {
		// Ensure our rewrite rules are registered before flushing.
		if ( function_exists( 'qr_trackr_add_rewrite_rules' ) ) {
			qr_trackr_add_rewrite_rules();
		}

		flush_rewrite_rules();
		delete_option( 'qr_trackr_needs_flush' );

		if ( QR_TRACKR_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only.
			error_log( 'QR Trackr: Flushed rewrite rules after version change.' );
		}
	}
}
add_action( 'init', 'qr_trackr_flush_rewrites_if_needed', 20 );

// Register activation and deactivation hooks.
register_activation_hook( QR_TRACKR_PLUGIN_FILE, 'qr_trackr_activate_plugin' );
register_deactivation_hook( QR_TRACKR_PLUGIN_FILE, 'qr_trackr_deactivate_plugin' );
