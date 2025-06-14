// phpcs:disable WordPress.Files.FileName.NotClassFileName
// Exception: This file intentionally does not follow the class file naming convention because it is a test bootstrap and not a class file. See CODEGEN-REMEDIATION-TRACKING.md for context.

/**
 * PHPUnit bootstrap file for QR Trackr plugin.
 *
 * @package QR_Trackr
 *
 * @note PHPCS: File/class naming does not match standard; see remediation tracker for planned rename.
 */

// Define dummy WP_List_Table for admin table tests (must be first).
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
// Mock wp_upload_dir globally for tests.
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
// PHPUnit bootstrap for QR Trackr plugin.
require_once __DIR__ . '/../vendor/autoload.php';
// Define QR_TRACKR_PLUGIN_DIR for plugin file loading.
if ( ! defined( 'QR_TRACKR_PLUGIN_DIR' ) ) {
	define( 'QR_TRACKR_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
}
// Brain Monkey will be set up/teared down in each test class as needed.
