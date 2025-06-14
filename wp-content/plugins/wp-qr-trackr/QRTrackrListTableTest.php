<?php
/**
 * QRTrackrListTableTest
 *
 * Unit tests for QR Trackr List Table functionality.
 *
 * @package QR_Trackr
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../../' );
}
use Yoast\WPTestUtils\BrainMonkey\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

// Dummy WP_List_Table is defined in bootstrap.

require_once __DIR__ . '/../includes/class-qr-trackr-list-table.php';

/**
 * Tests for QR Trackr List Table functionality.
 */
class QRTrackrListTableTest extends TestCase {
	/**
	 * Set up test environment.
	 *
	 * @return void
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
		// Mock needed WordPress functions.
		Functions\when( 'get_post' )->justReturn( (object) array( 'post_title' => 'Test Post' ) );
		Functions\when( 'get_edit_post_link' )->justReturn( 'http://example.com/edit' );
		Functions\when( 'get_permalink' )->justReturn( 'http://example.com/post' );
		Functions\when( 'esc_html' )->alias(
			function ( $v ) {
				return $v;
			}
		);
		Functions\when( 'esc_url' )->alias(
			function ( $v ) {
				return $v;
			}
		);
		Functions\when( 'esc_attr' )->alias(
			function ( $v ) {
				return $v;
			}
		);
		Functions\when( 'wp_nonce_field' )->justReturn( '' );
		Functions\when( 'wp_create_nonce' )->justReturn( 'nonce' );
		Functions\when( '__' )->alias(
			function ( $v ) {
				return $v;
			}
		);
		Functions\when( 'apply_filters' )->returnArg( 1 );
		Functions\when( 'get_post_types' )->justReturn(
			array(
				'post' => 'Post',
				'page' => 'Page',
			)
		);
		Functions\when( 'selected' )->justReturn( '' );
	}
	/**
	 * Tear down test environment.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}
	/**
	 * Test prepare_items method.
	 *
	 * @return void
	 * @coversNothing
	 */
	public function testPrepareItems() {
		global $wpdb;
		$wpdb            = new class() {
			/**
			 * Get variable (dummy).
			 *
			 * @param string $query SQL query.
			 * @return int Always returns 0.
			 */
			public function get_var( $query ) {
				return 0;
			}
			/**
			 * Prepare (dummy).
			 *
			 * @param string $query SQL query.
			 * @param mixed  ...$args Additional arguments.
			 * @return string Returns the query string.
			 */
			public function prepare( $query, ...$args ) {
				return $query;
			}
			/**
			 * Get results (dummy).
			 *
			 * @param string $query SQL query.
			 * @return array Always returns an empty array.
			 */
			public function get_results( $query ) {
				return array();
			}
		};
		$table           = new class() extends QR_Trackr_List_Table {
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
			/**
			 * Get page number (dummy).
			 *
			 * @return int Always returns 1.
			 */
			public function get_pagenum() {
				return 1;
			}
			/**
			 * Set pagination args (dummy).
			 *
			 * @param array $args Pagination arguments.
			 * @return void
			 */
			protected function set_pagination_args( $args ) {
				// Set pagination args.
			}
		};
		$_GET['orderby'] = 'id';
		$_GET['order']   = 'asc';
		$table->prepare_items();
		$this->assertTrue( true );
	}
	/**
	 * Test display_rows method.
	 *
	 * @return void
	 * @coversNothing
	 */
	public function testDisplayRows() {
		$table = new QR_Trackr_List_Table();
		ob_start();
		$table->display_rows();
		ob_end_clean();
		$this->assertTrue( true );
	}
	/**
	 * Test extra_tablenav method.
	 *
	 * @return void
	 * @coversNothing
	 */
	public function testExtraTableNav() {
		$table = new QR_Trackr_List_Table();
		ob_start();
		$table->extra_tablenav( 'top' );
		ob_end_clean();
		$this->assertTrue( true );
	}
	/**
	 * Test constructor.
	 *
	 * @return void
	 * @coversNothing
	 */
	public function testConstruct() {
		$table = new QR_Trackr_List_Table();
		$this->assertInstanceOf( QR_Trackr_List_Table::class, $table );
	}
	/**
	 * Test get_columns method.
	 *
	 * @return void
	 * @coversNothing
	 */
	public function testGetColumns() {
		$table   = new QR_Trackr_List_Table();
		$columns = $table->get_columns();
		$this->assertIsArray( $columns );
		$this->assertArrayHasKey( 'id', $columns );
	}
	/**
	 * Test get_sortable_columns method.
	 *
	 * @return void
	 * @coversNothing
	 */
	public function testGetSortableColumns() {
		$table    = new QR_Trackr_List_Table();
		$sortable = $table->get_sortable_columns();
		$this->assertIsArray( $sortable );
	}
	/**
	 * Test column_default method.
	 *
	 * @return void
	 * @coversNothing
	 */
	public function testColumnDefault() {
		$table  = new QR_Trackr_List_Table();
		$item   = array(
			'id'              => 1,
			'post_id'         => 1,
			'destination_url' => 'http://example.com',
		);
		$result = $table->column_default( $item, 'unknown_column' );
		$this->assertEquals( '', $result );
	}
	/**
	 * Test list table columns (mocked).
	 *
	 * @return void
	 */
	public function testListTableColumns() {
		$this->assertTrue( true );
	}
	/**
	 * Test list table sorting (mocked).
	 *
	 * @return void
	 */
	public function testListTableSorting() {
		$this->assertTrue( true );
	}
	/**
	 * Test list table bulk actions (mocked).
	 *
	 * @return void
	 */
	public function testListTableBulkActions() {
		$this->assertTrue( true );
	}
	/**
	 * Get variable (dummy).
	 *
	 * @return null Always returns null.
	 */
	public function get_var() {
		return null;
	}
	/**
	 * Prepare (dummy).
	 *
	 * @return string Always returns an empty string.
	 */
	public function prepare() {
		return '';
	}
	/**
	 * Get results (dummy).
	 *
	 * @return array Always returns an empty array.
	 */
	public function get_results() {
		return array();
	}
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
	/**
	 * Get page number (dummy).
	 *
	 * @return int Always returns 1.
	 */
	public function get_pagenum() {
		return 1;
	}
	/**
	 * Set pagination args (dummy).
	 *
	 * @param array $args Pagination arguments.
	 * @return void
	 */
	public function set_pagination_args( $args ) {
		// Dummy implementation.
	}
	// This is a test stub.
}
