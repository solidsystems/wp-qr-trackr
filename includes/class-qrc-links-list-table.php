<?php
/**
 * QR Code Links List Table
 *
 * Displays QR code links in a WordPress admin table format.
 *
 * @package WP_QR_TRACKR
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Load WordPress List Table if not already loaded.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * QRC_Links_List_Table class.
 *
 * Displays QR code links in an admin table.
 */
class QRC_Links_List_Table extends WP_List_Table {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => esc_html__( 'QR Code Link', 'wp-qr-trackr' ),
				'plural'   => esc_html__( 'QR Code Links', 'wp-qr-trackr' ),
				'ajax'     => false,
			)
		);
	}

	/**
	 * Prepare the items for the table to process.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function prepare_items() {
		$columns  = $this->get_columns();
		$hidden   = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();

		$data = $this->table_data();
		usort( $data, array( &$this, 'sort_data' ) );

		$per_page     = 10;
		$current_page = $this->get_pagenum();
		$total_items  = count( $data );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
			)
		);

		$data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );

		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items           = $data;
	}

	/**
	 * Override the parent columns method. Defines the columns to use in your listing table.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'id'              => esc_html__( 'ID', 'wp-qr-trackr' ),
			'destination_url' => esc_html__( 'Destination URL', 'wp-qr-trackr' ),
			'qr_code'         => esc_html__( 'QR Code', 'wp-qr-trackr' ),
			'scans'           => esc_html__( 'Scans', 'wp-qr-trackr' ),
			'created_at'      => esc_html__( 'Created', 'wp-qr-trackr' ),
		);

		return $columns;
	}

	/**
	 * Define which columns are hidden.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_hidden_columns() {
		return array();
	}

	/**
	 * Define the sortable columns.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'id'         => array( 'id', false ),
			'scans'      => array( 'scans', false ),
			'created_at' => array( 'created_at', false ),
		);
	}

	/**
	 * Get the table data.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	private function table_data() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'qr_trackr_links';
		
		// Try to get from cache first.
		$cache_key = 'qr_trackr_all_links_admin';
		$data      = wp_cache_get( $cache_key, 'qr_trackr' );

		if ( false === $data ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached immediately after query, needed for admin display.
			$results = $wpdb->get_results(
				"SELECT * FROM {$table_name} ORDER BY created_at DESC",
				ARRAY_A
			);

			$data = array();
			if ( $results ) {
				foreach ( $results as $result ) {
					$data[] = array(
						'id'              => absint( $result['id'] ),
						'destination_url' => esc_url( $result['destination_url'] ),
						'qr_code'         => esc_html( $result['qr_code'] ),
						'scans'           => absint( $result['scans'] ?? $result['access_count'] ?? 0 ),
						'created_at'      => esc_html( $result['created_at'] ),
					);
				}
				wp_cache_set( $cache_key, $data, 'qr_trackr', HOUR_IN_SECONDS );
			}
		}

		return $data;
	}

	/**
	 * Define what data to show on each column of the table.
	 *
	 * @since 1.0.0
	 * @param array  $item        Data.
	 * @param string $column_name Current column name.
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'id':
			case 'scans':
			case 'created_at':
				return $item[ $column_name ];

			case 'destination_url':
				return sprintf(
					'<a href="%s" target="_blank">%s</a>',
					esc_url( $item[ $column_name ] ),
					esc_html( wp_trim_words( $item[ $column_name ], 10 ) )
				);

			case 'qr_code':
				return $this->column_qr_code( $item );

			default:
				return print_r( $item, true );
		}
	}

	/**
	 * Render the QR code column.
	 *
	 * @param object $item The current item.
	 * @return string The column content.
	 */
	protected function column_qr_code( $item ) {
		// Handle both object and array formats
		$qr_code = is_object( $item ) ? $item->qr_code : $item['qr_code'];
		$qr_code_url = is_object( $item ) ? $item->qr_code_url : $item['qr_code_url'];
		$tracking_url = '';

		if ( ! empty( $qr_code ) ) {
			$tracking_url = home_url( '/qr/' . esc_attr( $qr_code ) );
		}

		// Use stored QR image URL if available
		if ( ! empty( $qr_code_url ) && ! is_wp_error( $qr_code_url ) ) {
			return sprintf(
				'<div class="qr-code-preview">
					<img src="%s" alt="%s" style="max-width: 80px; height: auto;" />
					<br>
					<small><a href="%s" target="_blank">%s</a></small>
				</div>',
				esc_url( $qr_code_url ),
				esc_attr__( 'QR Code', 'wp-qr-trackr' ),
				esc_url( $tracking_url ),
				esc_html( $qr_code )
			);
		}

		// Fallback: try to generate QR code image if not stored
		if ( ! empty( $qr_code ) ) {
			$qr_image_url = qr_trackr_generate_qr_image( $qr_code, array( 'size' => 80 ) );
			
			if ( ! is_wp_error( $qr_image_url ) && ! empty( $qr_image_url ) ) {
				// Update database with generated URL for future use
				global $wpdb;
				$item_id = is_object( $item ) ? $item->id : $item['id'];
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cache invalidated after update.
				$wpdb->update(
					"{$wpdb->prefix}qr_trackr_links",
					array( 'qr_code_url' => $qr_image_url ),
					array( 'id' => $item_id ),
					array( '%s' ),
					array( '%d' )
				);
				
				return sprintf(
					'<div class="qr-code-preview">
						<img src="%s" alt="%s" style="max-width: 80px; height: auto;" />
						<br>
						<small><a href="%s" target="_blank">%s</a></small>
					</div>',
					esc_url( $qr_image_url ),
					esc_attr__( 'QR Code', 'wp-qr-trackr' ),
					esc_url( $tracking_url ),
					esc_html( $qr_code )
				);
			}
		}

		// Final fallback: show tracking URL button
		if ( ! empty( $tracking_url ) ) {
			return sprintf(
				'<a href="%s" target="_blank" class="button button-small">%s</a><br><small>%s</small>',
				esc_url( $tracking_url ),
				esc_html__( 'View QR', 'wp-qr-trackr' ),
				esc_html( $qr_code )
			);
		}

		return '<span class="dashicons dashicons-warning" title="' . esc_attr__( 'QR code not available', 'wp-qr-trackr' ) . '"></span>';
	}

	/**
	 * Allows you to sort the data by the variables set in the $_GET.
	 *
	 * @since 1.0.0
	 * @param array $a First item.
	 * @param array $b Second item.
	 * @return int
	 */
	private function sort_data( $a, $b ) {
		// Set defaults.
		$orderby = 'id';
		$order   = 'desc';

		// If orderby is set, use this as the sort column.
		if ( ! empty( $_GET['orderby'] ) ) {
			$orderby = sanitize_text_field( wp_unslash( $_GET['orderby'] ) );
		}

		// If order is set use this as the order.
		if ( ! empty( $_GET['order'] ) ) {
			$order = sanitize_text_field( wp_unslash( $_GET['order'] ) );
		}

		$result = strcmp( $a[ $orderby ], $b[ $orderby ] );

		if ( 'asc' === $order ) {
			return $result;
		}

		return -$result;
	}
} 