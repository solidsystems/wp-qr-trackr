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
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filter for list table; no state change.
		$current_filter = isset( $_REQUEST['referral_filter'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['referral_filter'] ) ) : '';

		// Get unique referral codes with caching to avoid repeat queries.
		$cache_key      = 'qr_trackr_referral_codes_dropdown';
		$referral_codes = wp_cache_get( $cache_key, 'qr_trackr' );
		if ( false === $referral_codes ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached immediately after query.
			$referral_codes = $wpdb->get_col(
				"SELECT DISTINCT referral_code FROM {$table_name} WHERE referral_code IS NOT NULL AND referral_code != '' ORDER BY referral_code"
			);
			wp_cache_set( $cache_key, $referral_codes, 'qr_trackr', HOUR_IN_SECONDS );
		}

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
	 * Get a list of columns for the list table.
	 *
	 * @since 1.0.0
	 * @return array Columns array.
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
			'actions'         => esc_html__( 'Actions', 'wp-qr-trackr' ), // New column for actions.
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

		// Build search and filter WHERE clause.
		$where_clause = '';
		$where_values = array();

		// Handle search.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filter input for admin table; no state change occurs.
		$search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
		if ( ! empty( $search ) ) {
			$search_like   = '%' . $wpdb->esc_like( $search ) . '%';
			$where_clause .= ' WHERE (common_name LIKE %s OR referral_code LIKE %s OR qr_code LIKE %s OR destination_url LIKE %s)';
			$where_values  = array( $search_like, $search_like, $search_like, $search_like );
		}

		// Handle referral code filter.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filter input for admin table; no state change occurs.
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

			if ( ! empty( $where_values ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached immediately after query, needed for admin display.
				$results = $wpdb->get_results(
					$wpdb->prepare( $sql, $where_values ),
					ARRAY_A
				);
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached immediately after query, needed for admin display.
				$results = $wpdb->get_results( $sql, ARRAY_A );
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
			if ( is_wp_error( $tracking_url ) ) {
				// Log the error and fall back to no URL to avoid passing WP_Error to esc_url().
				if ( function_exists( 'qr_trackr_log' ) ) {
					qr_trackr_log(
						'QR redirect URL generation failed in list table.',
						'error',
						array(
							'qr_code'       => $qr_code,
							'error_message' => $tracking_url->get_error_message(),
						)
					);
				}
				$tracking_url = '';
			}
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
	 * Render the Actions column for each row.
	 *
	 * @since 1.0.0
	 * @param array $item The current item.
	 * @return string HTML for actions.
	 */
	public function column_actions( $item ) {
		$edit_url   = esc_url( admin_url( 'admin.php?page=qr-code-edit&id=' . absint( $item['id'] ) ) );
		$delete_url = esc_url( wp_nonce_url( admin_url( 'admin.php?page=qr-code-links&action=delete&id=' . absint( $item['id'] ) ), 'qrc_delete_qr_code_' . absint( $item['id'] ) ) );
		$actions    = array();
		$actions[]  = '<a href="' . $edit_url . '">' . esc_html__( 'Edit', 'wp-qr-trackr' ) . '</a>';
		$actions[]  = '<a href="' . $delete_url . '" onclick="return confirm(\'' . esc_js( __( 'Are you sure you want to delete this QR code?', 'wp-qr-trackr' ) ) . '\');">' . esc_html__( 'Delete', 'wp-qr-trackr' ) . '</a>';
		return implode( ' | ', $actions );
	}

	/**
	 * Render the Name column (without row actions).
	 *
	 * @since 1.0.0
	 * @param array $item The current item.
	 * @return string Name value.
	 */
	public function column_common_name( $item ) {
		if ( empty( $item['common_name'] ) ) {
			return '<em>' . esc_html__( 'No name set', 'wp-qr-trackr' ) . '</em>';
		}
		return esc_html( $item['common_name'] );
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
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Sorting parameters are read-only and do not modify state.
		if ( ! empty( $_GET['orderby'] ) ) {
			$orderby = sanitize_text_field( wp_unslash( $_GET['orderby'] ) );
		}

		// If order is set use this as the order.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Sorting parameters are read-only and do not modify state.
		if ( ! empty( $_GET['order'] ) ) {
			$order = sanitize_text_field( wp_unslash( $_GET['order'] ) );
		}

		// Ensure numeric compare for numeric fields; fallback to string compare.
		$numeric_fields = array( 'id', 'scans' );
		if ( in_array( $orderby, $numeric_fields, true ) ) {
			$a_val  = isset( $a[ $orderby ] ) ? (int) $a[ $orderby ] : 0;
			$b_val  = isset( $b[ $orderby ] ) ? (int) $b[ $orderby ] : 0;
			$result = $a_val <=> $b_val;
		} else {
			$a_val  = isset( $a[ $orderby ] ) ? (string) $a[ $orderby ] : '';
			$b_val  = isset( $b[ $orderby ] ) ? (string) $b[ $orderby ] : '';
			$result = strcmp( $a_val, $b_val );
		}

		if ( 'asc' === $order ) {
			return $result;
		}

		return -$result;
	}

	/**
	 * Remove row actions from the default implementation.
	 *
	 * @since 1.0.0
	 * @param array $item The current item.
	 * @return array Empty array.
	 */
	protected function get_row_actions( $item ) {
		return array();
	}
}
