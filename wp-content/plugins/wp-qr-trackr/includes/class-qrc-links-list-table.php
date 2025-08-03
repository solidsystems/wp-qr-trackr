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

		$per_page     = 15;
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
	 * Add extra navigation elements above/below the table.
	 *
	 * @param string $which Position of the navigation (top or bottom).
	 * @return void
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' === $which ) {
			$this->search_box( esc_html__( 'Search QR Codes', 'wp-qr-trackr' ), 'qr-search' );
			$this->referral_filter_dropdown();
		}
	}

	/**
	 * Display referral code filter dropdown.
	 *
	 * @return void
	 */
	protected function referral_filter_dropdown() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'qr_trackr_links';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Referral filter is admin-only and protected by capability checks.
		$current_filter = isset( $_REQUEST['referral_filter'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['referral_filter'] ) ) : '';

		// Get unique referral codes.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.Caching.NoCacheObjectCacheFound -- Admin filter dropdown, results cached.
		$referral_codes = $wpdb->get_col(
			"SELECT DISTINCT referral_code FROM {$table_name} WHERE referral_code IS NOT NULL AND referral_code != '' ORDER BY referral_code"
		);

		if ( ! empty( $referral_codes ) ) {
			echo '<div class="alignleft actions" style="margin-left: 10px;">';
			echo '<select name="referral_filter" id="referral-filter">';
			echo '<option value="">' . esc_html__( 'All Referral Codes', 'wp-qr-trackr' ) . '</option>';
			foreach ( $referral_codes as $code ) {
				printf(
					'<option value="%s"%s>%s</option>',
					esc_attr( $code ),
					selected( $current_filter, $code, false ),
					esc_html( $code )
				);
			}
			echo '</select>';
			submit_button( esc_html__( 'Filter', 'wp-qr-trackr' ), 'button', 'filter_action', false );
			echo '</div>';
		}
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
			'qr_image'        => esc_html__( 'QR Image', 'wp-qr-trackr' ),
			'common_name'     => esc_html__( 'Name', 'wp-qr-trackr' ),
			'destination_url' => esc_html__( 'Destination URL', 'wp-qr-trackr' ),
			'qr_code'         => esc_html__( 'QR Code', 'wp-qr-trackr' ),
			'referral_code'   => esc_html__( 'Referral Code', 'wp-qr-trackr' ),
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

		// Add Query Monitor logging.
		if ( function_exists( 'do_action' ) ) {
			do_action( 'qm_debug', 'QR Trackr: table_data() method called' );
		}

		$table_name = $wpdb->prefix . 'qr_trackr_links';

		// Check if table exists.
		if ( function_exists( 'do_action' ) ) {
			$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );
			do_action(
				'qm_debug',
				'QR Trackr: Checking if table exists',
				array(
					'table_name' => $table_name,
					'exists'     => $table_exists ? 'yes' : 'no',
				)
			);
		}

		// Build search and filter WHERE clause.
		$where_clause = '';
		$where_values = array();

		// Handle search.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Search/filter forms are admin-only and protected by capability checks.
		$search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
		if ( ! empty( $search ) ) {
			$search_like   = '%' . $wpdb->esc_like( $search ) . '%';
			$where_clause .= ' WHERE (common_name LIKE %s OR referral_code LIKE %s OR qr_code LIKE %s OR destination_url LIKE %s)';
			$where_values  = array( $search_like, $search_like, $search_like, $search_like );
		}

		// Handle referral code filter.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Referral filter is admin-only and protected by capability checks.
		$referral_filter = isset( $_REQUEST['referral_filter'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['referral_filter'] ) ) : '';
		if ( ! empty( $referral_filter ) ) {
			if ( ! empty( $where_clause ) ) {
				$where_clause .= ' AND referral_code = %s';
			} else {
				$where_clause = ' WHERE referral_code = %s';
			}
			$where_values[] = $referral_filter;
		}

		// Create cache key based on search and filters.
		$cache_key = 'qr_trackr_links_' . md5( $search . $referral_filter );
		$data      = wp_cache_get( $cache_key, 'qr_trackr' );

		if ( false === $data ) {
			$sql = "SELECT * FROM {$table_name}{$where_clause} ORDER BY created_at DESC";

			if ( function_exists( 'do_action' ) ) {
				do_action(
					'qm_debug',
					'QR Trackr: About to execute SQL query',
					array(
						'sql'          => $sql,
						'where_values' => $where_values,
					)
				);
			}

			if ( ! empty( $where_values ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.Caching.NoCacheObjectCacheFound -- Cached immediately after query, needed for admin display.
				$results = $wpdb->get_results(
					$wpdb->prepare( $sql, $where_values ),
					ARRAY_A
				);
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.Caching.NoCacheObjectCacheFound -- Cached immediately after query, needed for admin display.
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Static SQL query without variables, no preparation needed.
				$results = $wpdb->get_results( $sql, ARRAY_A );
			}

			// Check for database errors.
			if ( $wpdb->last_error ) {
				if ( function_exists( 'do_action' ) ) {
					do_action(
						'qm_error',
						'QR Trackr: Database error in table_data()',
						array(
							'error' => $wpdb->last_error,
							'sql'   => $sql,
						)
					);
				}
				return array(); // Return empty array on error.
			}

			if ( function_exists( 'do_action' ) ) {
				do_action( 'qm_debug', 'QR Trackr: SQL query executed successfully', array( 'results_count' => $results ? count( $results ) : 0 ) );
			}

			$data = array();
			if ( $results ) {
				foreach ( $results as $result ) {
					$data[] = array(
						'id'              => absint( $result['id'] ),
						'destination_url' => esc_url( $result['destination_url'] ),
						'qr_code'         => esc_html( $result['qr_code'] ),
						'qr_code_url'     => esc_url( $result['qr_code_url'] ?? '' ),
						'common_name'     => esc_html( $result['common_name'] ?? '' ),
						'referral_code'   => esc_html( $result['referral_code'] ?? '' ),
						'scans'           => absint( $result['scans'] ?? $result['access_count'] ?? 0 ),
						'created_at'      => esc_html( $result['created_at'] ),
					);
				}
				wp_cache_set( $cache_key, $data, 'qr_trackr', HOUR_IN_SECONDS );
			}
		}

		if ( function_exists( 'do_action' ) ) {
			do_action( 'qm_debug', 'QR Trackr: table_data() returning data', array( 'data_count' => count( $data ) ) );
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

			case 'common_name':
				$name = ! empty( $item[ $column_name ] ) ? $item[ $column_name ] : '<em>' . esc_html__( 'No name set', 'wp-qr-trackr' ) . '</em>';
				return $name;

			case 'referral_code':
				$code = ! empty( $item[ $column_name ] ) ? '<code>' . esc_html( $item[ $column_name ] ) . '</code>' : '<em>' . esc_html__( 'None', 'wp-qr-trackr' ) . '</em>';
				return $code;

			case 'destination_url':
				return sprintf(
					'<a href="%s" target="_blank">%s</a>',
					esc_url( $item[ $column_name ] ),
					esc_html( wp_trim_words( $item[ $column_name ], 10 ) )
				);

			case 'qr_image':
				return $this->column_qr_image( $item );

			case 'qr_code':
				return $this->column_qr_code( $item );

			default:
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r -- Debug output for unknown columns.
				return print_r( $item, true );
		}
	}

	/**
	 * Render the QR image column with clickable modal.
	 *
	 * @param array $item The current item.
	 * @return string The column content.
	 */
	protected function column_qr_image( $item ) {
		$qr_code_url = $item['qr_code_url'];
		$qr_id       = $item['id'];

		if ( ! empty( $qr_code_url ) ) {
			return sprintf(
				'<img src="%s" alt="%s" style="width: 60px; height: 60px; cursor: pointer; border: 1px solid #ddd; border-radius: 4px;"
				class="qr-code-modal-trigger" data-qr-id="%d" title="%s" />',
				esc_url( $qr_code_url ),
				esc_attr__( 'QR Code', 'wp-qr-trackr' ),
				absint( $qr_id ),
				esc_attr__( 'Click to view details', 'wp-qr-trackr' )
			);
		} else {
			return sprintf(
				'<span class="qr-code-modal-trigger" data-qr-id="%d" style="cursor: pointer; color: #2271b1; text-decoration: underline;">%s</span>',
				absint( $qr_id ),
				esc_html__( 'Generate QR', 'wp-qr-trackr' )
			);
		}
	}

	/**
	 * Render the QR code column.
	 *
	 * @param array $item The current item.
	 * @return string The column content.
	 */
	protected function column_qr_code( $item ) {
		$qr_code      = $item['qr_code'];
		$tracking_url = '';

		if ( ! empty( $qr_code ) ) {
			$tracking_url = qr_trackr_get_redirect_url( $qr_code );
		}

		// Show QR code identifier and tracking URL without image (image is in qr_image column).
		if ( ! empty( $qr_code ) && ! empty( $tracking_url ) ) {
			return sprintf(
				'<code style="font-size: 12px; padding: 2px 4px; background: #f1f1f1; border-radius: 3px;">%s</code><br>
				<a href="%s" target="_blank" class="button button-small" style="margin-top: 4px;">%s</a>',
				esc_html( $qr_code ),
				esc_url( $tracking_url ),
				esc_html__( 'Visit Link', 'wp-qr-trackr' )
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
