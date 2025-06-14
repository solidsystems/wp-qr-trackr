<?php
/**
 * PHPUnit bootstrap file for QR Trackr plugin.
 *
 * @package QR_Trackr
 */

// Define dummy WP_List_Table for admin table tests (must be first)
if ( ! class_exists( 'WP_List_Table' ) ) {
	class WP_List_Table {
		public function __construct() {}
		public function get_items_per_page( $option, $default = 20 ) {
			return $default; }
	}
}
// Mock wp_upload_dir globally for tests
if ( class_exists( 'Brain\Monkey\Functions' ) ) {
	Brain\Monkey\Functions\when( 'wp_upload_dir' )->justReturn(
		array(
			'basedir' => sys_get_temp_dir(),
			'baseurl' => 'http://example.com/uploads',
		)
	);
	Brain\Monkey\Functions\when( 'get_post_types' )->justReturn(
		array(
			'post' => 'Post',
			'page' => 'Page',
		)
	);
}
// PHPUnit bootstrap for QR Trackr plugin
require_once __DIR__ . '/../vendor/autoload.php';
// Define QR_TRACKR_PLUGIN_DIR for plugin file loading
if ( ! defined( 'QR_TRACKR_PLUGIN_DIR' ) ) {
	define( 'QR_TRACKR_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
}
// Brain Monkey will be set up/teared down in each test class as needed
