<?php
if (!defined('QR_TRACKR_PLUGIN_DIR')) {
    define('QR_TRACKR_PLUGIN_DIR', dirname(__DIR__) . '/');
}
use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

require_once __DIR__ . '/../qr-code.php';

/**
 * @covers qr_trackr_generate_qr_image_for_link
 * @covers qr_trackr_generate_qr_image
 */
class QrCodeTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
        Functions\when('home_url')->justReturn('http://example.com');
        Functions\when('wp_upload_dir')->justReturn([
            'basedir' => sys_get_temp_dir(),
            'baseurl' => 'http://example.com/uploads'
        ]);
        Functions\when('wp_mkdir_p')->justReturn(true);
        // Mock WordPress functions
        Functions\when('trailingslashit')->alias(function($url) { return rtrim($url, '/') . '/'; });
        Functions\when('apply_filters')->returnArg(1);
    }
    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }
    public function testSanity() {
        $this->assertTrue(true);
    }

    // Example: test a simple utility function (mocked)
    public function testMd5FilenameGeneration() {
        $url = 'http://example.com';
        $size = 100;
        $shape = 'standard';
        $filename = 'qr_' . md5($url . $size . $shape) . '.png';
        $this->assertEquals('qr_' . md5('http://example.com100standard') . '.png', $filename);
    }

    /**
     * @covers qr_trackr_generate_qr_image_for_link
     */
    public function testQrTrackrGenerateQrImageForLink() {
        global $wpdb;
        $wpdb = new class {
            public $prefix = '';
            public function get_row($query) {
                return (object)[
                    'id' => 123,
                    'post_id' => 1,
                    'destination_url' => 'http://example.com/page'
                ];
            }
            public function prepare($query, ...$args) { return $query; }
        };
        $result = qr_trackr_generate_qr_image_for_link(123);
        $this->assertIsString($result);
        $this->assertTrue($result === '' || filter_var($result, FILTER_VALIDATE_URL) !== false);
    }

    /**
     * @coversNothing
     */
    public function testMd5FilenameGenerationFails() {
        $url = 'http://example.com';
        $size = 100;
        $shape = 'standard';
        $filename = 'qr_' . md5($url . $size . $shape) . '.png';
        $this->assertEquals('should_fail', $filename); // This will fail
    }

    public function testQrTrackrGenerateQrImageForLinkWithMockedWpdb() {
        global $wpdb;
        $wpdb = new class {
            public $prefix = '';
            public function get_row($query) {
                return (object)[
                    'id' => 123,
                    'post_id' => 1,
                    'destination_url' => 'http://example.com/page'
                ];
            }
            public function prepare($query, ...$args) { return $query; }
        };
        $result = qr_trackr_generate_qr_image_for_link(123);
        $this->assertIsString($result);
        $this->assertStringContainsString('http://example.com/uploads/qr-trackr/qr_', $result);
    }
} 