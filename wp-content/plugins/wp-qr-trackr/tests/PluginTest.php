<?php
if (!defined('QR_TRACKR_PLUGIN_DIR')) {
    define('QR_TRACKR_PLUGIN_DIR', dirname(__DIR__) . '/');
}
use PHPUnit\Framework\TestCase;
use Brain\Monkey\Functions;

/**
 * @covers qr_trackr_generate_qr_image_for_link
 */
class PluginTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        \Brain\Monkey\setUp();
        Functions\when('home_url')->justReturn('http://example.com');
        Functions\when('wp_upload_dir')->justReturn([
            'basedir' => sys_get_temp_dir(),
            'baseurl' => 'http://example.com/uploads'
        ]);
        Functions\when('wp_mkdir_p')->justReturn(true);
    }

    protected function tearDown(): void {
        \Brain\Monkey\tearDown();
        parent::tearDown();
    }

    public function testSanity() {
        $this->assertTrue(true);
    }

    // Example: test a simple utility function (mocked)
    public function testArrayHasKey() {
        $arr = ['foo' => 'bar', 'baz' => 42];
        $this->assertArrayHasKey('foo', $arr);
    }

    /**
     * @coversNothing
     */
    public function testArrayHasKeyFails() {
        $arr = ['foo' => 'bar', 'baz' => 42];
        $this->assertArrayHasKey('should_fail', $arr); // This will fail
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
} 