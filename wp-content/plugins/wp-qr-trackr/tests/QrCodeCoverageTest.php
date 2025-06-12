<?php
if (!defined('QR_TRACKR_PLUGIN_DIR')) {
    define('QR_TRACKR_PLUGIN_DIR', dirname(__DIR__) . '/');
}
use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

require_once __DIR__ . '/../qr-code.php';

/**
 * @covers qr_trackr_get_or_create_tracking_link
 * @covers qr_trackr_get_available_shapes
 * @covers qr_trackr_generate_qr_image
 * @covers qr_trackr_generate_qr_image_for_link
 * @covers qr_trackr_get_all_tracking_links_for_post
 */
class QrCodeCoverageTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
        Functions\when('home_url')->justReturn('http://example.com');
        Functions\when('wp_upload_dir')->justReturn([
            'basedir' => sys_get_temp_dir(),
            'baseurl' => 'http://example.com/uploads'
        ]);
        Functions\when('wp_mkdir_p')->justReturn(true);
        // Mock common WordPress functions
        Functions\when('trailingslashit')->alias(function($url) { return rtrim($url, '/') . '/'; });
        Functions\when('esc_url_raw')->alias(function($url) { return $url; });
        Functions\when('__')->alias(function($text) { return $text; });
        Functions\when('apply_filters')->alias(function($tag, $val) {
            if ($tag === 'qr_trackr_qr_shapes') return $val;
            return $val;
        });
        Functions\when('get_permalink')->justReturn('http://example.com/page');
    }
    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }
    public function testGetOrCreateTrackingLinkReturnsExisting() {
        global $wpdb;
        $wpdb = new class {
            public $prefix = '';
            public function get_row($query) {
                return (object)[
                    'id' => 1,
                    'post_id' => 42,
                    'destination_url' => 'http://example.com/page'
                ];
            }
            public function prepare($query, ...$args) { return $query; }
        };
        $result = qr_trackr_get_or_create_tracking_link(42);
        $this->assertIsObject($result);
        $this->assertEquals(42, $result->post_id);
    }
    public function testGetOrCreateTrackingLinkCreatesNew() {
        global $wpdb;
        $wpdb = new class {
            public $prefix = '';
            public $insert_id = 2;
            public $inserted = false;
            public function get_row($query) {
                if (strpos($query, 'WHERE post_id') !== false) return null;
                return (object)[
                    'id' => 2,
                    'post_id' => 99,
                    'destination_url' => 'http://example.com/page'
                ];
            }
            public function insert($table, $data) { $this->inserted = true; return true; }
            public function prepare($query, ...$args) { return $query; }
        };
        $result = qr_trackr_get_or_create_tracking_link(99);
        $this->assertIsObject($result);
        $this->assertEquals(99, $result->post_id);
    }
    public function testGetAvailableShapesDefault() {
        Functions\when('apply_filters')->alias(function($tag, $val) {
            if ($tag === 'qr_trackr_qr_shapes') return $val;
            return $val;
        });
        $shapes = qr_trackr_get_available_shapes();
        $this->assertIsArray($shapes);
        $this->assertArrayHasKey('standard', $shapes);
    }
    public function testGetAvailableShapesFiltered() {
        Functions\when('apply_filters')->alias(function($tag, $val) {
            return array_merge($val, ['custom' => 'Custom']);
        });
        $shapes = qr_trackr_get_available_shapes();
        $this->assertArrayHasKey('custom', $shapes);
    }
    public function testGenerateQrImageWithUrl() {
        $url = 'http://example.com/test';
        $result = qr_trackr_generate_qr_image($url, 100, null, 'standard');
        $this->assertIsString($result);
        $this->assertStringContainsString('http://example.com/uploads/qr-trackr/qr_', $result);
    }
    public function testGenerateQrImageWithPostId() {
        global $wpdb;
        $wpdb = new class {
            public $prefix = '';
            public function get_row($query) {
                return (object)[
                    'id' => 3,
                    'post_id' => 77,
                    'destination_url' => 'http://example.com/page'
                ];
            }
            public function prepare($query, ...$args) { return $query; }
        };
        $result = qr_trackr_generate_qr_image('ignored', 100, 77, 'standard');
        $this->assertIsString($result);
        $this->assertStringContainsString('http://example.com/uploads/qr-trackr/qr_', $result);
    }
    public function testGenerateQrImageForLink() {
        global $wpdb;
        $wpdb = new class {
            public $prefix = '';
            public function get_row($query) {
                return (object)[
                    'id' => 4,
                    'post_id' => 88,
                    'destination_url' => 'http://example.com/page'
                ];
            }
            public function prepare($query, ...$args) { return $query; }
        };
        $result = qr_trackr_generate_qr_image_for_link(4);
        $this->assertIsString($result);
        $this->assertStringContainsString('http://example.com/uploads/qr-trackr/qr_', $result);
    }
    public function testGetAllTrackingLinksForPost() {
        global $wpdb;
        $wpdb = new class {
            public $prefix = '';
            public function get_results($query) {
                return [
                    (object)['id' => 1, 'post_id' => 5, 'destination_url' => 'http://example.com/page']
                ];
            }
            public function prepare($query, $post_id) { return $query; }
        };
        $results = qr_trackr_get_all_tracking_links_for_post(5);
        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $this->assertEquals(5, $results[0]->post_id);
    }
} 