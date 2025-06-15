<?php
/**
 * PHPUnit bootstrap file for QR Trackr plugin.
 *
 * @package QR_Trackr
 *
 * @note PHPCS: File/class naming does not match standard; see remediation tracker for planned rename.
 */

// phpcs:disable WordPress.Files.FileName.NotClassFileName
/**
 * PHPUnit bootstrap file for QR Trackr plugin.
 *
 * @package QR_Trackr
 *
 * @note PHPCS: File/class naming does not match standard; see remediation tracker for planned rename.
 */

// 1. Define ABSPATH at the very top for compatibility with plugin files.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

// Ensure esc_html() is available for output escaping in test/debug context.
if ( ! function_exists( 'esc_html' ) ) {
	require_once dirname( __DIR__, 3 ) . '/wp-includes/formatting.php';
}

// 2. CI debug: confirm ABSPATH is set.
if ( getenv( 'CI_DEBUG' ) === 'true' ) {
	echo '[CI_DEBUG] ABSPATH is set to: ' . esc_html( ABSPATH ) . "\n";
}

// 3. Define dummy WP_List_Table for admin table tests (must be first).
if ( ! class_exists( 'WP_List_Table' ) ) {
	/**
	 * Dummy WP_List_Table class for admin table tests.
	 */
	class WP_List_Table {
		/**
		 * Constructor.
		 */
		public function __construct() {}
		/**
		 * Get items per page (dummy).
		 *
		 * @param string $option Option name.
		 * @param int    $default_value Default value.
		 * @return int Items per page.
		 */
		public function get_items_per_page( $option, $default_value = 20 ) {
			return $default_value;
		}
	}
}

// 4. Composer autoload (for test dependencies).
require_once dirname( __DIR__, 3 ) . '/vendor/autoload.php';

// 5. Define QR_TRACKR_PLUGIN_DIR for plugin file loading.
if ( ! defined( 'QR_TRACKR_PLUGIN_DIR' ) ) {
	define( 'QR_TRACKR_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
}

// 6. Yoast/wp-test-utils Brain Monkey bootstrap
// Note: Yoast bootstrap may try to define ABSPATH, but we've already defined it above
require_once dirname( __DIR__, 3 ) . '/vendor/yoast/wp-test-utils/src/BrainMonkey/bootstrap.php';

// 7. Check for and require the debug module (module-debug.php) before QR module.
$debug_module_path = __DIR__ . '/includes/module-debug.php';
if ( ! file_exists( $debug_module_path ) ) {
	die( '[ERROR] module-debug.php not found at: ' . esc_html( $debug_module_path ) . "\n" );
}
require_once $debug_module_path;
if ( getenv( 'CI_DEBUG' ) === 'true' ) {
	echo "[CI_DEBUG] includes/module-debug.php required successfully\n";
}
// 8. Check for and require the canonical QR module (module-qr.php).
$qr_module_path = __DIR__ . '/includes/module-qr.php';
if ( ! file_exists( $qr_module_path ) ) {
	die( '[ERROR] module-qr.php not found at: ' . esc_html( $qr_module_path ) . "\n" );
}
require_once $qr_module_path;
if ( getenv( 'CI_DEBUG' ) === 'true' ) {
	echo "[CI_DEBUG] includes/module-qr.php required successfully\n";
}
