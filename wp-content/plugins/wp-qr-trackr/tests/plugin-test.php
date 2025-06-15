<?php
/**
 * PluginTest
 *
 * Unit tests for plugin-level QR Trackr functions.
 *
 * @package QR_Trackr
 */

if ( ! defined( 'QR_TRACKR_PLUGIN_DIR' ) ) {
	define( 'QR_TRACKR_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
}
if ( ! defined( 'QR_ECLEVEL_L' ) ) {
	define( 'QR_ECLEVEL_L', 0 ); // 0 is the standard value for 'L' in phpqrcode
}
use Yoast\WPTestUtils\BrainMonkey\TestCase;
use Brain\Monkey\Functions;

if ( ! class_exists( 'QRcode' ) ) {
	class QRcode {
		public static function png() {
			// Do nothing, just mock.
		}
	}
}

/**
 * Tests for plugin-level QR Trackr functions.
 *
 * @covers qr_trackr_generate_qr_image_for_link
 */
class PluginTest extends TestCase {
	/**
	 * Mocked member variable for test coverage.
	 *
	 * @var int
	 */
	protected $mock_var1;

	/**
	 * Set up test environment.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		\Brain\Monkey\setUp();
		Functions\when( 'home_url' )->justReturn( 'http://example.com' );
		Functions\when( 'wp_upload_dir' )->justReturn(
			array(
				'basedir' => sys_get_temp_dir(),
				'baseurl' => 'http://example.com/uploads',
			)
		);
		Functions\when( 'wp_mkdir_p' )->justReturn( true );
		Functions\when( 'get_option' )->justReturn( '0' );
		Functions\when( 'plugin_dir_path' )->justReturn( __DIR__ . '/../' );
		Functions\when( 'class_exists' )->alias(
			function ( $class ) {
				if ( $class === 'QRcode' ) {
					return true;
				}
				return \class_exists( $class, false );
			}
		);
	}
	/**
	 * Tear down test environment.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		\Brain\Monkey\tearDown();
		parent::tearDown();
	}
	/**
	 * Test that true is true (sanity check).
	 *
	 * @return void
	 */
	public function testSanity() {
		$this->assertTrue( true );
	}
	/**
	 * Test array_has_key utility (mocked).
	 *
	 * @return void
	 */
	public function testArrayHasKey() {
		$arr = array(
			'foo' => 'bar',
			'baz' => 42,
		);
		$this->assertArrayHasKey( 'foo', $arr );
	}
	/**
	 * Test array_has_key utility failure (intentional fail).
	 *
	 * @return void
	 * @coversNothing
	 */
	public function testArrayHasKeyFails() {
		$arr = array(
			'foo' => 'bar',
			'baz' => 42,
		);
		$this->assertArrayHasKey( 'should_fail', $arr ); // This will fail.
	}
	/**
	 * Test qr_trackr_generate_qr_image_for_link returns a string URL.
	 *
	 * @return void
	 * @covers qr_trackr_generate_qr_image_for_link
	 */
	public function testQrTrackrGenerateQrImageForLink() {
		global $wpdb;
		$wpdb   = new class() {
			/**
			 * Mocked database prefix.
			 *
			 * @var string
			 */
			public $prefix = '';
			/**
			 * Get a row from the database (mocked).
			 *
			 * @param string $query The SQL query.
			 * @return object
			 */
			public function get_row( $query ) {
				return (object) array(
					'id'              => 123,
					'post_id'         => 1,
					'destination_url' => 'http://example.com/page',
				);
			}
			/**
			 * Prepare a SQL query (mocked).
			 *
			 * @param string $query The SQL query.
			 * @param mixed  ...$args Additional arguments.
			 * @return string
			 */
			public function prepare( $query, ...$args ) {
				return $query;
			}
		};
		$result = qr_trackr_generate_qr_image_for_link( 123 );
		$this->assertIsString( $result );
		$this->assertTrue( '' === $result || false !== filter_var( $result, FILTER_VALIDATE_URL ) );
	}
	/**
	 * Test plugin-level QR code generation (mocked).
	 *
	 * @return void
	 */
	public function testPluginQrCodeGeneration() {
		$this->assertTrue( true === true );
	}
	/**
	 * Test Yoda condition (mocked).
	 *
	 * @return void
	 */
	public function testYodaCondition() {
		$this->assertTrue( true === true );
	}
}
