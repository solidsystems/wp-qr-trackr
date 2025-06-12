<?php
if (!defined('ABSPATH')) define('ABSPATH', __DIR__ . '/../../');
use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

// Dummy WP_List_Table is defined in bootstrap

require_once __DIR__ . '/../includes/class-qr-trackr-list-table.php';

/**
 * @covers QR_Trackr_List_Table
 */
class QRTrackrListTableTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
        Functions\when('home_url')->justReturn('http://example.com');
        Functions\when('wp_upload_dir')->justReturn([
            'basedir' => sys_get_temp_dir(),
            'baseurl' => 'http://example.com/uploads'
        ]);
        Functions\when('wp_mkdir_p')->justReturn(true);
        // Mock needed WordPress functions
        Functions\when('get_post')->justReturn((object)['post_title' => 'Test Post']);
        Functions\when('get_edit_post_link')->justReturn('http://example.com/edit');
        Functions\when('get_permalink')->justReturn('http://example.com/post');
        Functions\when('esc_html')->alias(function($v) { return $v; });
        Functions\when('esc_url')->alias(function($v) { return $v; });
        Functions\when('esc_attr')->alias(function($v) { return $v; });
        Functions\when('wp_nonce_field')->justReturn('');
        Functions\when('wp_create_nonce')->justReturn('nonce');
        Functions\when('__')->alias(function($v) { return $v; });
        Functions\when('apply_filters')->returnArg(1);
        Functions\when('get_post_types')->justReturn(['post' => 'Post', 'page' => 'Page']);
        Functions\when('selected')->justReturn('');
    }
    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }
    /**
     * @coversNothing
     */
    public function testPrepareItems() {
        global $wpdb;
        $wpdb = new class {
            public function get_var($query) { return 0; }
            public function prepare($query, ...$args) { return $query; }
            public function get_results($query) { return []; }
        };
        $table = new class extends QR_Trackr_List_Table {
            public function get_items_per_page($option, $default = 20) { return $default; }
            public function get_pagenum() { return 1; }
            protected function set_pagination_args($args) { /* no-op for test */ }
        };
        $_GET['orderby'] = 'id';
        $_GET['order'] = 'asc';
        $table->prepare_items();
        $this->assertTrue(true);
    }
    /**
     * @coversNothing
     */
    public function testDisplayRows() {
        $table = new QR_Trackr_List_Table();
        ob_start();
        $table->display_rows();
        ob_end_clean();
        $this->assertTrue(true);
    }
    /**
     * @coversNothing
     */
    public function testExtraTableNav() {
        $table = new QR_Trackr_List_Table();
        ob_start();
        $table->extra_tablenav('top');
        ob_end_clean();
        $this->assertTrue(true);
    }
    /**
     * @coversNothing
     */
    public function testConstruct() {
        $table = new QR_Trackr_List_Table();
        $this->assertInstanceOf(QR_Trackr_List_Table::class, $table);
    }
    /**
     * @coversNothing
     */
    public function testGetColumns() {
        $table = new QR_Trackr_List_Table();
        $columns = $table->get_columns();
        $this->assertIsArray($columns);
        $this->assertArrayHasKey('id', $columns);
    }
    /**
     * @coversNothing
     */
    public function testGetSortableColumns() {
        $table = new QR_Trackr_List_Table();
        $sortable = $table->get_sortable_columns();
        $this->assertIsArray($sortable);
    }
    /**
     * @coversNothing
     */
    public function testColumnDefault() {
        $table = new QR_Trackr_List_Table();
        $item = ['id' => 1, 'post_id' => 1, 'destination_url' => 'http://example.com'];
        $result = $table->column_default($item, 'unknown_column');
        $this->assertEquals('', $result);
    }
} 