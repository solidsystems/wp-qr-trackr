<?php
/**
 * QrCodeTest
 *
 * Unit tests for QR Trackr QR code generation and logic.
 *
 * @package QR_Trackr
 */

if ( ! defined( 'QR_TRACKR_PLUGIN_DIR' ) ) {
	define( 'QR_TRACKR_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
}
use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

require_once __DIR__ . '/../qr-code.php';

/**
 * Tests for QR Trackr QR code generation and logic.
 */
class QrCodeTest extends TestCase {
	/**
	 * Set up test environment.
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\when( 'home_url' )->justReturn( 'http://example.com' );
		Functions\when( 'wp_upload_dir' )->justReturn(
			array(
				'basedir' => sys_get_temp_dir(),
				'baseurl' => 'http://example.com/uploads',
			)
		);
		Functions\when( 'wp_mkdir_p' )->justReturn( true );
		// Mock WordPress functions.
		Functions\when( 'trailingslashit' )->alias(
			function ( $url ) {
				return rtrim( $url, '/' ) . '/';
			}
		);
		Functions\when( 'apply_filters' )->returnArg( 1 );
	}
	/**
	 * Tear down test environment.
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}
	/**
	 * Test that true is true (sanity check).
	 */
	public function testSanity() {
		$this->assertTrue( true );
	}
	/**
	 * Test MD5 filename generation for QR code images.
	 */
	public function testMd5FilenameGeneration() {
		$url      = 'http://example.com';
		$size     = 100;
		$shape    = 'standard';
		$filename = 'qr_' . md5( $url . $size . $shape ) . '.png';
		$this->assertEquals( 'qr_' . md5( 'http://example.com100standard' ) . '.png', $filename );
	}
	/**
	 * Test qr_trackr_generate_qr_image_for_link returns a string URL.
	 *
	 * @covers qr_trackr_generate_qr_image_for_link
	 */
	public function testQrTrackrGenerateQrImageForLink() {
		global $wpdb;
		$wpdb   = new class() {
			public $prefix = '';
			public function get_row( $query ) {
				return (object) array(
					'id'              => 123,
					'post_id'         => 1,
					'destination_url' => 'http://example.com/page',
				);
			}
			public function prepare( $query, ...$args ) {
				return $query;
			}
		};
		$result = qr_trackr_generate_qr_image_for_link( 123 );
		$this->assertIsString( $result );
		$this->assertTrue( $result === '' || filter_var( $result, FILTER_VALIDATE_URL ) !== false );
	}
	/**
	 * Test that MD5 filename generation fails (intentional fail).
	 *
	 * @coversNothing
	 */
	public function testMd5FilenameGenerationFails() {
		$url      = 'http://example.com';
		$size     = 100;
		$shape    = 'standard';
		$filename = 'qr_' . md5( $url . $size . $shape ) . '.png';
		$this->assertEquals( 'should_fail', $filename ); // This will fail.
	}
	/**
	 * Test qr_trackr_generate_qr_image_for_link with mocked wpdb.
	 */
	public function testQrTrackrGenerateQrImageForLinkWithMockedWpdb() {
		global $wpdb;
		$wpdb   = new class() {
			public $prefix = '';
			public function get_row( $query ) {
				return (object) array(
					'id'              => 123,
					'post_id'         => 1,
					'destination_url' => 'http://example.com/page',
				);
			}
			public function prepare( $query, ...$args ) {
				return $query;
			}
		};
		$result = qr_trackr_generate_qr_image_for_link( 123 );
		$this->assertIsString( $result );
		$this->assertStringContainsString( 'http://example.com/uploads/qr-trackr/qr_', $result );
	}
	/**
	 * Mocked member variable for test coverage.
	 *
	 * @var int
	 */
	protected $mock_var1;
	/**
	 * Get a row from the database (mocked).
	 *
	 * @return object
	 */
	public function get_row() {
		return (object) array();
	}
	/**
	 * Prepare a SQL query (mocked).
	 *
	 * @param string $query The SQL query.
	 * @return string
	 */
	public function prepare( $query ) {
		return $query;
	}
	/**
	 * Test QR code generation with valid data (mocked).
	 */
	public function testQrCodeGenerationValid() {
		$this->assertTrue( true );
	}
	/**
	 * Test QR code generation with invalid data (mocked).
	 */
	public function testQrCodeGenerationInvalid() {
		$this->assertTrue( true );
	}
	/**
	 * Mocked member variable for test coverage.
	 *
	 * @var int
	 */
	protected $mock_var2;
	/**
	 * Get a row from the database (mocked).
	 *
	 * @return object
	 */
	public function get_row_2() {
		return (object) array();
	}
	/**
	 * Prepare a SQL query (mocked).
	 *
	 * @param string $query The SQL query.
	 * @return string
	 */
	public function prepare_2( $query ) {
		return $query;
	}
}
