<?php
/**
 * QR Trackr List Table Class
 *
 * Provides a custom WP_List_Table for displaying and managing QR Trackr links in the WordPress admin.
 *
 * @package QR_Trackr
 */

// phpcs:disable WordPress.Files.FileName.InvalidClassFileName -- This file is correctly named for the custom QR_Trackr_List_Table class, not WP_List_Table.

if ( file_exists( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
if ( ! class_exists( 'WP_List_Table' ) ) {
	// phpcs:ignore Squiz.Classes.OneObjectStructurePerFile -- This fallback is required for plugin portability/testing.
	require_once __DIR__ . '/class-wp-list-table.php';
}

/**
 * QR_Trackr_List_Table class for displaying QR Trackr links in the admin.
 */
class QR_Trackr_List_Table extends WP_List_Table {
	/**
	 * List of links for the table.
	 *
	 * @var array
	 */
	private $links;
	/**
	 * The current search term.
	 *
	 * @var string
	 */
	private $search_term = '';
	/**
	 * Current post type filter.
	 *
	 * @var string
	 */
	private $post_type_filter = '';
	/**
	 * Current destination filter.
	 *
	 * @var string
	 */
	private $destination_filter = '';
	/**
	 * Current scans filter.
	 *
	 * @var int|null
	 */
	private $scans_filter = null;
	/**
	 * Inline edit property.
	 *
	 * @var bool
	 */
	protected $inline_edit = false;

	/**
	 * Constructor for QR_Trackr_List_Table.
	 *
	 * Initializes the custom list table for QR Trackr links.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'qr_code',
				'plural'   => 'qr_codes',
				'ajax'     => false,
			)
		);

		// Verify nonce for filter form processing.
		$nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_key( $_REQUEST['_wpnonce'] ) : '';
		if ( ! wp_verify_nonce( $nonce, 'qr_trackr_filter_nonce' ) ) {
			return;
		}

		// Verify user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wp-qr-trackr' ) );
		}

		// Set up filters with proper validation.
		$this->post_type_filter   = isset( $_REQUEST['filter_post_type'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['filter_post_type'] ) ) : '';
		$this->destination_filter = isset( $_REQUEST['filter_destination'] ) ? esc_url_raw( wp_unslash( $_REQUEST['filter_destination'] ) ) : '';
		$this->scans_filter       = isset( $_REQUEST['filter_scans'] ) ? absint( $_REQUEST['filter_scans'] ) : null;

		// Initialize inline edit property.
		$this->inline_edit = false;
	}

	/**
	 * Get column info.
	 *
	 * @since 1.0.0
	 * @return array Array of column information including columns, hidden columns, sortable columns, and primary column.
	 */
	protected function get_column_info() {
		$columns  = $this->get_columns();
		$hidden   = get_hidden_columns( $this->screen );
		$sortable = $this->get_sortable_columns();
		$primary  = $this->get_primary_column_name();

		/**
		 * Filter the column information.
		 *
		 * @since 1.0.0
		 * @param array  $columns  Array of columns.
		 * @param array  $hidden   Array of hidden columns.
		 * @param array  $sortable Array of sortable columns.
		 * @param string $primary  Primary column name.
		 */
		return apply_filters(
			'qr_trackr_column_info',
			array( $columns, $hidden, $sortable, $primary )
		);
	}

	/**
	 * Get primary column name.
	 *
	 * @since 1.0.0
	 * @return string Primary column name.
	 */
	protected function get_primary_column_name() {
		/**
		 * Filter the primary column name.
		 *
		 * @since 1.0.0
		 * @param string $primary_column Primary column name.
		 */
		return apply_filters( 'qr_trackr_primary_column', 'id' );
	}

	/**
	 * Get columns for the table.
	 *
	 * @since 1.0.0
	 * @return array Array of column information with column ID as key and label as value.
	 */
	public function get_columns() {
		$columns = array(
			'cb'              => '<input type="checkbox" />',
			'qr_code'         => esc_html__( 'QR Code', 'wp-qr-trackr' ),
			'destination_url' => esc_html__( 'Destination URL', 'wp-qr-trackr' ),
			'tracking_link'   => esc_html__( 'Tracking Link', 'wp-qr-trackr' ),
			'scans'           => esc_html__( 'Scans', 'wp-qr-trackr' ),
			'created_at'      => esc_html__( 'Created', 'wp-qr-trackr' ),
			'actions'         => esc_html__( 'Actions', 'wp-qr-trackr' ),
		);

		/**
		 * Filter the list of columns.
		 *
		 * @since 1.0.0
		 * @param array $columns Array of columns.
		 */
		return apply_filters( 'qr_trackr_list_table_columns', $columns );
	}

	/**
	 * Get sortable columns.
	 *
	 * @return array Sortable columns.
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'destination_url' => array( 'destination_url', false ),
			'scans'           => array( 'scans', true ),
			'created_at'      => array( 'created_at', true ),
		);

		return $sortable_columns;
	}

	/**
	 * Render the checkbox column.
	 *
	 * @param array $item Item data.
	 * @return string Checkbox HTML.
	 */
	public function column_cb( $item ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return '';
		}

		return sprintf(
			'<input type="checkbox" name="qr_code[]" value="%d" />',
			absint( $item['id'] )
		);
	}

	/**
	 * Prepare items for the table.
	 *
	 * @since 1.0.0
	 */
	public function prepare_items() {
		global $wpdb;

		$this->_column_headers = $this->get_column_info();

		// Verify nonce for search and filter operations.
		$nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_key( $_REQUEST['_wpnonce'] ) : '';
		if ( ! wp_verify_nonce( $nonce, 'qr_trackr_list_nonce' ) ) {
			wp_die( esc_html__( 'Invalid nonce verification', 'wp-qr-trackr' ) );
		}

		// Get and sanitize search term.
		$this->search_term = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';

		// Get and validate pagination parameters.
		$per_page     = $this->get_items_per_page( 'qr_trackr_per_page', 20 );
		$current_page = $this->get_pagenum();

		// Build WHERE clause with proper escaping.
		$where_clauses = array( '1=1' ); // Always true base condition.
		$where_values  = array();

		if ( ! empty( $this->search_term ) ) {
			$where_clauses[] = '(destination_url LIKE %s OR title LIKE %s)';
			$search_pattern  = '%' . $wpdb->esc_like( $this->search_term ) . '%';
			$where_values[]  = $search_pattern;
			$where_values[]  = $search_pattern;
		}

		if ( ! empty( $this->post_type_filter ) ) {
			$where_clauses[] = 'post_type = %s';
			$where_values[]  = $this->post_type_filter;
		}

		if ( ! empty( $this->destination_filter ) ) {
			$where_clauses[] = 'destination_url LIKE %s';
			$where_values[]  = '%' . $wpdb->esc_like( $this->destination_filter ) . '%';
		}

		if ( ! empty( $this->scans_filter ) ) {
			$where_clauses[] = 'scans >= %d';
			$where_values[]  = absint( $this->scans_filter );
		}

		// Build the complete WHERE clause.
		$where = implode( ' AND ', $where_clauses );

		// Get order parameters and sanitize them.
		$orderby = isset( $_REQUEST['orderby'] ) ? sanitize_sql_orderby( wp_unslash( $_REQUEST['orderby'] ) ) : 'id';
		$order   = isset( $_REQUEST['order'] ) ? sanitize_sql_orderby( wp_unslash( $_REQUEST['order'] ) ) : 'DESC';

		// Ensure valid order values.
		$order         = in_array( strtoupper( $order ), array( 'ASC', 'DESC' ), true ) ? $order : 'DESC';
		$valid_orderby = array( 'id', 'destination_url', 'scans', 'created_at' );
		$orderby       = in_array( $orderby, $valid_orderby, true ) ? $orderby : 'id';

		// Get total items for pagination.
		$total_items = $this->get_total_items( $where, $where_values );

		// Set up pagination arguments.
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);

		// Get the items for the current page.
		$this->items = $this->get_items( $where, $where_values, $orderby, $order, $per_page, $current_page );
	}

	/**
	 * Get total number of items.
	 *
	 * @param string $where        WHERE clause without WHERE keyword.
	 * @param array  $where_values Values for WHERE clause placeholders.
	 * @return int Total number of items.
	 */
	private function get_total_items( $where, $where_values ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'qr_trackr_links';

		// Try to get from cache first.
		$cache_key = 'qrc_total_' . md5( $where . wp_json_encode( $where_values ) );
		$total     = wp_cache_get( $cache_key );

		if ( false === $total ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Caching implemented above, count query needed for pagination.
			if ( ! empty( $where_values ) && ! empty( $where ) ) {
				$query = "SELECT COUNT(*) FROM {$wpdb->prefix}qr_trackr_links WHERE {$where}";
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query built with validated WHERE clause and prepared values.
				$total = $wpdb->get_var( $wpdb->prepare( $query, ...$where_values ) );
			} else {
				$total = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}qr_trackr_links" );
			}

			wp_cache_set( $cache_key, $total, '', HOUR_IN_SECONDS );
		}

		return absint( $total );
	}

	/**
	 * Get items for the current page.
	 *
	 * @param string $where        WHERE clause without WHERE keyword.
	 * @param array  $where_values Values for WHERE clause placeholders.
	 * @param string $orderby      ORDER BY column.
	 * @param string $order        Order direction (ASC/DESC).
	 * @param int    $per_page     Items per page.
	 * @param int    $current_page Current page number.
	 * @return array Array of items.
	 */
	private function get_items( $where, $where_values, $orderby, $order, $per_page, $current_page ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'qr_trackr_links';

		// Calculate offset.
		$offset = ( $current_page - 1 ) * $per_page;

		// Try to get from cache first.
		$cache_key = 'qrc_items_' . md5( $where . wp_json_encode( $where_values ) . $orderby . $order . $per_page . $offset );
		$items     = wp_cache_get( $cache_key );

		if ( false === $items ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Caching implemented above, paginated query needed for display.
			if ( ! empty( $where ) ) {
				$base_query     = "SELECT * FROM {$wpdb->prefix}qr_trackr_links WHERE {$where} ORDER BY " . esc_sql( $orderby ) . ' ' . esc_sql( $order ) . ' LIMIT %d OFFSET %d'; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- WHERE clause is validated and sanitized before use.
				$prepare_values = array_merge( $where_values, array( $per_page, $offset ) );
				$items          = $wpdb->get_results(
					$wpdb->prepare( $base_query, $prepare_values ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is prepared above with placeholders.
					ARRAY_A
				);
			} else {
				$items = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT * FROM {$wpdb->prefix}qr_trackr_links ORDER BY " . esc_sql( $orderby ) . ' ' . esc_sql( $order ) . ' LIMIT %d OFFSET %d',
						$per_page,
						$offset
					),
					ARRAY_A
				);
			}

			if ( $items ) {
				// Convert metadata to JSON for each item.
				foreach ( $items as &$item ) {
					if ( ! empty( $item['metadata'] ) ) {
						$item['metadata'] = wp_json_encode( maybe_unserialize( $item['metadata'] ) );
					}
				}
				wp_cache_set( $cache_key, $items, '', HOUR_IN_SECONDS );
			}
		}

		return $items;
	}

	/**
	 * Delete a link.
	 *
	 * @param int $id Link ID.
	 * @return bool True on success, false on failure.
	 */
	public function delete_link( $id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'qr_trackr_links';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Write operation, caching not applicable.
		$result = $wpdb->delete(
			$table_name,
			array( 'id' => absint( $id ) ),
			array( '%d' )
		);

		if ( false !== $result ) {
			// Clear caches.
			wp_cache_delete( 'qrc_link_' . absint( $id ), 'qrc_links' );
			wp_cache_delete( 'qrc_all_links' );
			return true;
		}

		return false;
	}

	/**
	 * Delete multiple links.
	 *
	 * @param array $ids Array of link IDs.
	 * @return bool True on success, false on failure.
	 */
	private function delete_links( $ids ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'qr_trackr_links';

		// Sanitize IDs.
		$ids = array_map( 'absint', $ids );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Write operation, caching not applicable.
		$result = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}qr_trackr_links WHERE id IN (" . implode( ',', array_fill( 0, count( $ids ), '%d' ) ) . ')', $ids ) );

		if ( false !== $result ) {
			// Clear caches.
			foreach ( $ids as $id ) {
				wp_cache_delete( 'qrc_link_' . $id, 'qrc_links' );
			}
			wp_cache_delete( 'qrc_all_links' );
			return true;
		}

		return false;
	}

	/**
	 * Get a link by its URL.
	 *
	 * @param string $url The URL to look up.
	 * @return array|null The link data or null if not found.
	 */
	private function get_item_by_url( $url ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'qr_trackr_links';

		// Try to get from cache first.
		$cache_key = 'qrc_link_url_' . md5( $url );
		$item      = wp_cache_get( $cache_key );

		if ( false === $item ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Caching implemented above, single-row lookup needed for display.
			$item = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}qr_trackr_links WHERE destination_url = %s",
					esc_url_raw( $url )
				),
				ARRAY_A
			);

			if ( $item ) {
				// Convert metadata to JSON.
				if ( ! empty( $item['metadata'] ) ) {
					$item['metadata'] = wp_json_encode( maybe_unserialize( $item['metadata'] ) );
				}
				wp_cache_set( $cache_key, $item, '', HOUR_IN_SECONDS );
			}
		}

		return $item;
	}

	/**
	 * Get links by post ID.
	 *
	 * @param int $post_id The post ID.
	 * @return array Array of links.
	 */
	private function get_link_by_post_id( $post_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'qr_trackr_links';

		// Try to get from cache first.
		$cache_key = 'qrc_links_post_' . absint( $post_id );
		$links     = wp_cache_get( $cache_key );

		if ( false === $links ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Caching implemented above, filtered query needed for display.
			$links = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}qr_trackr_links WHERE post_id = %d",
					absint( $post_id )
				),
				ARRAY_A
			);

			if ( $links ) {
				// Convert metadata to JSON for each link.
				foreach ( $links as &$link ) {
					if ( ! empty( $link['metadata'] ) ) {
						$link['metadata'] = wp_json_encode( maybe_unserialize( $link['metadata'] ) );
					}
				}
				wp_cache_set( $cache_key, $links, '', HOUR_IN_SECONDS );
			}
		}

		return $links;
	}

	/**
	 * Get links by IDs.
	 *
	 * @param array $ids Array of link IDs.
	 * @return array Array of links.
	 */
	public function get_links_by_ids( $ids ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'qr_trackr_links';

		// Sanitize IDs.
		$ids = array_map( 'absint', $ids );

		// Try to get from cache first.
		$cache_key = 'qrc_links_ids_' . md5( wp_json_encode( $ids ) );
		$links     = wp_cache_get( $cache_key );

		if ( false === $links ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Caching implemented above, filtered query needed for display.
			$links = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}qr_trackr_links WHERE id IN (" . implode( ',', array_fill( 0, count( $ids ), '%d' ) ) . ')',
					$ids
				),
				ARRAY_A
			);

			if ( $links ) {
				// Convert metadata to JSON for each link.
				foreach ( $links as &$link ) {
					if ( ! empty( $link['metadata'] ) ) {
						$link['metadata'] = wp_json_encode( maybe_unserialize( $link['metadata'] ) );
					}
				}
				wp_cache_set( $cache_key, $links, '', HOUR_IN_SECONDS );
			}
		}

		return $links;
	}

	/**
	 * Message to be displayed when there are no items.
	 */
	public function no_items() {
		if ( ! empty( $this->search_term ) ) {
			esc_html_e( 'No QR codes found matching your search criteria.', 'wp-qr-trackr' );
		} elseif ( ! empty( $this->post_type_filter ) || ! empty( $this->destination_filter ) || ! empty( $this->scans_filter ) ) {
			esc_html_e( 'No QR codes found matching your filter criteria.', 'wp-qr-trackr' );
		} else {
			esc_html_e( 'No QR codes have been created yet.', 'wp-qr-trackr' );
		}

		if ( current_user_can( 'manage_options' ) ) {
			printf(
				' <a href="%s">%s</a>',
				esc_url( admin_url( 'admin.php?page=qr-trackr&action=new' ) ),
				esc_html__( 'Create your first QR code', 'wp-qr-trackr' )
			);
		}
	}

	/**
	 * Default column rendering.
	 *
	 * @param array  $item Item data.
	 * @param string $column_name Column name.
	 * @return string Column content.
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'destination_url':
				return sprintf(
					'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
					esc_url( $item['destination_url'] ),
					esc_html( $item['destination_url'] )
				);

			case 'tracking_link':
				$tracking_url = home_url( '/qr/' . $item['tracking_code'] );
				return sprintf(
					'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
					esc_url( $tracking_url ),
					esc_html( $tracking_url )
				);

			case 'scans':
				return sprintf(
					'<span class="qr-scans">%d</span>',
					absint( $item['scans'] )
				);

			case 'created_at':
				return sprintf(
					'<span class="qr-created">%s</span>',
					esc_html( gmdate( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $item['created_at'] ) ) )
				);

			default:
				return print_r( $item, true ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r -- Debug output is escaped.
		}
	}

	/**
	 * Get escaped column content.
	 *
	 * @param string $column_name Column name.
	 * @param array  $item Item data.
	 * @return string Escaped column content.
	 */
	protected function column_escaped( $column_name, $item ) {
		switch ( $column_name ) {
			case 'qr_code':
				$qr_urls = qr_trackr_generate_qr_image_for_link( absint( $item['id'] ) );
				if ( is_wp_error( $qr_urls ) ) {
					qr_trackr_debug_log( sprintf( 'Failed to generate QR code for link %d: %s', $item['id'], $qr_urls->get_error_message() ) );
					return '<div class="error"><p>' . esc_html( $qr_urls->get_error_message() ) . '</p></div>';
				}

				if ( ! isset( $qr_urls['png'], $qr_urls['svg'] ) ) {
					qr_trackr_debug_log( sprintf( 'Invalid QR code URLs for link %d', $item['id'] ) );
					return '<div class="error"><p>' . esc_html__( 'Failed to generate QR code URLs.', 'wp-qr-trackr' ) . '</p></div>';
				}

				$html  = '<div class="qr-code-preview">';
				$html .= sprintf(
					'<img src="%s" alt="%s" style="width:100px; height:100px;" />',
					esc_url( $qr_urls['png'] ),
					sprintf(
						/* translators: %d: QR code ID */
						esc_attr__( 'QR Code #%d', 'wp-qr-trackr' ),
						absint( $item['id'] )
					)
				);
				$html .= '<div class="qr-code-actions">';
				$html .= sprintf(
					'<a href="%s" download class="button button-small"><span class="dashicons dashicons-download"></span> %s</a>',
					esc_url( $qr_urls['png'] ),
					esc_html__( 'PNG', 'wp-qr-trackr' )
				);
				$html .= sprintf(
					'<a href="%s" download class="button button-small"><span class="dashicons dashicons-download"></span> %s</a>',
					esc_url( $qr_urls['svg'] ),
					esc_html__( 'SVG', 'wp-qr-trackr' )
				);
				$html .= '</div></div>';
				return $html;

			case 'actions':
				if ( ! current_user_can( 'manage_options' ) ) {
					return '';
				}

				$actions = array();

				// Edit action.
				$actions['edit'] = sprintf(
					'<a href="#" class="edit-qr-code" data-id="%d" data-url="%s">%s</a>',
					absint( $item['id'] ),
					esc_attr( $item['destination_url'] ),
					esc_html__( 'Edit', 'wp-qr-trackr' )
				);

				// Delete action with nonce.
				$delete_nonce      = wp_create_nonce( 'delete_qr_code_' . absint( $item['id'] ) );
				$actions['delete'] = sprintf(
					'<a href="#" class="delete-qr-code" data-id="%d" data-nonce="%s">%s</a>',
					absint( $item['id'] ),
					esc_attr( $delete_nonce ),
					esc_html__( 'Delete', 'wp-qr-trackr' )
				);

				// Regenerate action with nonce.
				$regenerate_nonce      = wp_create_nonce( 'regenerate_qr_code_' . absint( $item['id'] ) );
				$actions['regenerate'] = sprintf(
					'<a href="#" class="regenerate-qr-code" data-id="%d" data-nonce="%s">%s</a>',
					absint( $item['id'] ),
					esc_attr( $regenerate_nonce ),
					esc_html__( 'Regenerate', 'wp-qr-trackr' )
				);

				return $this->row_actions( $actions );

			default:
				return esc_html( $this->column_default( $item, $column_name ) );
		}
	}

	/**
	 * Display rows or placeholder if no items exist.
	 */
	public function display_rows() {
		if ( ! $this->has_items() ) {
			echo '<tr class="no-items"><td class="colspanchange" colspan="' . esc_attr( $this->get_column_count() ) . '">';
			$this->no_items();
			echo '</td></tr>';
			return;
		}

		foreach ( $this->items as $item ) {
			$this->single_row( $item );
		}
	}

	/**
	 * Display a single row.
	 *
	 * @param array $item Item data.
	 */
	public function single_row( $item ) {
		$row_class = '';

		// Add row class for inactive items.
		if ( isset( $item['is_active'] ) && ! $item['is_active'] ) {
			$row_class = ' class="inactive"';
		}

		// Add row class for items with high scan counts.
		if ( isset( $item['scans'] ) && $item['scans'] > 100 ) {
			$row_class = ' class="high-traffic"';
		}

		echo '<tr' . $row_class . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Class is properly escaped above.
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * Display the table.
	 *
	 * @since 1.0.0
	 */
	public function display() {
		// Verify user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wp-qr-trackr' ) );
		}

		// Add screen option for items per page.
		add_screen_option(
			'per_page',
			array(
				'label'   => esc_html__( 'QR Codes per page', 'wp-qr-trackr' ),
				'default' => 20,
				'option'  => 'qr_trackr_per_page',
			)
		);

		// Add filters form.
		$this->display_filters_form();

		// Add search box.
		$this->search_box( esc_html__( 'Search QR Codes', 'wp-qr-trackr' ), 'qr-trackr-search' );

		// Display the table.
		parent::display();

		// Add inline edit form.
		$this->display_inline_edit_form();

		// Add inline scripts.
		$this->add_inline_scripts();
	}

	/**
	 * Display the filters form.
	 */
	private function display_filters_form() {
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		?>
		<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
			<input type="hidden" name="page" value="qr-trackr" />
			<?php wp_nonce_field( 'qr_trackr_filter_nonce', '_wpnonce', false ); ?>

			<div class="tablenav top">
				<div class="alignleft actions">
					<select name="filter_post_type">
						<option value=""><?php esc_html_e( 'All post types', 'wp-qr-trackr' ); ?></option>
						<?php foreach ( $post_types as $post_type ) : ?>
							<option value="<?php echo esc_attr( $post_type->name ); ?>" <?php selected( $this->post_type_filter, $post_type->name ); ?>>
								<?php echo esc_html( $post_type->labels->singular_name ); ?>
							</option>
						<?php endforeach; ?>
					</select>

					<select name="filter_scans">
						<option value=""><?php esc_html_e( 'All scan counts', 'wp-qr-trackr' ); ?></option>
						<option value="10" <?php selected( $this->scans_filter, 10 ); ?>><?php esc_html_e( '10+ scans', 'wp-qr-trackr' ); ?></option>
						<option value="50" <?php selected( $this->scans_filter, 50 ); ?>><?php esc_html_e( '50+ scans', 'wp-qr-trackr' ); ?></option>
						<option value="100" <?php selected( $this->scans_filter, 100 ); ?>><?php esc_html_e( '100+ scans', 'wp-qr-trackr' ); ?></option>
					</select>

					<input type="text" name="filter_destination" value="<?php echo esc_attr( $this->destination_filter ); ?>" placeholder="<?php esc_attr_e( 'Filter by destination URL', 'wp-qr-trackr' ); ?>" />

					<?php submit_button( esc_html__( 'Filter', 'wp-qr-trackr' ), 'button', 'filter_action', false ); ?>
				</div>
			</div>
		</form>
		<?php
	}

	/**
	 * Display the inline edit form.
	 */
	private function display_inline_edit_form() {
		?>
		<div id="qr-trackr-inline-edit" style="display:none;">
			<form method="post" action="">
				<?php wp_nonce_field( 'qr_trackr_inline_edit', 'qr_trackr_inline_edit_nonce' ); ?>
				<input type="hidden" name="link_id" value="" />
				<fieldset>
					<div class="inline-edit-col">
						<label>
							<span class="title"><?php esc_html_e( 'Destination URL', 'wp-qr-trackr' ); ?></span>
							<span class="input-text-wrap">
								<input type="url" name="destination_url" value="" class="regular-text" required />
							</span>
						</label>
					</div>
				</fieldset>
				<p class="submit inline-edit-save">
					<button type="button" class="button cancel alignleft"><?php esc_html_e( 'Cancel', 'wp-qr-trackr' ); ?></button>
					<button type="submit" class="button button-primary save alignright"><?php esc_html_e( 'Update', 'wp-qr-trackr' ); ?></button>
					<span class="spinner"></span>
					<br class="clear" />
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Add inline scripts for table functionality.
	 *
	 * @since 1.0.0
	 */
	private function add_inline_scripts() {
		// Verify user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Localize script data.
		$localized_data = array(
			'ajaxurl'     => admin_url( 'admin-ajax.php' ),
			'nonce'       => wp_create_nonce( 'qr_trackr_nonce' ),
			'deleteText'  => __( 'Are you sure you want to delete this QR code?', 'wp-qr-trackr' ),
			'errorText'   => __( 'An error occurred. Please try again.', 'wp-qr-trackr' ),
			'successText' => __( 'QR code deleted successfully.', 'wp-qr-trackr' ),
			'loadingText' => __( 'Loading...', 'wp-qr-trackr' ),
		);

		// Add inline script.
		wp_add_inline_script(
			'qr-trackr-admin',
			sprintf(
				'var QRTrackr = %s;',
				wp_json_encode( $localized_data )
			)
		);

		// Add inline styles.
		wp_add_inline_style(
			'qr-trackr-admin',
			'
			.qr-code-preview { text-align: center; margin-bottom: 10px; }
			.qr-code-actions { margin-top: 5px; }
			.qr-code-actions .button { margin: 0 2px; }
			.qr-scans { font-weight: bold; }
			.qr-created { color: #666; }
			'
		);
	}

	/**
	 * Render a single row of columns.
	 *
	 * @since 1.0.0
	 * @param array $item The current item.
	 * @return void
	 */
	public function single_row_columns( $item ) {
		if ( ! is_array( $item ) ) {
			return;
		}

		list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

		foreach ( $columns as $column_name => $column_display_name ) {
			$classes = array( $column_name );

			if ( $primary === $column_name ) {
				$classes[] = 'column-primary';
			}

			if ( in_array( $column_name, $hidden, true ) ) {
				$classes[] = 'hidden';
			}

			// Check if this column is sortable.
			if ( isset( $sortable[ $column_name ] ) ) {
				$classes[] = 'sortable';
				$classes[] = $sortable[ $column_name ][1] ? 'asc' : 'desc';
			}

			$attributes = 'class="' . esc_attr( implode( ' ', $classes ) ) . '"';

			if ( 'cb' === $column_name ) {
				// Special handling for the checkbox column.
				echo '<th scope="row" class="check-column">';
				echo $this->column_cb( $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped in column_cb().
				echo '</th>';
			} else {
				$column_method = 'column_' . $column_name;

				echo '<td ' . $attributes . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Attributes are escaped above.

				if ( method_exists( $this, $column_method ) ) {
					echo call_user_func( array( $this, $column_method ), $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped in column methods.
				} else {
					echo $this->column_escaped( $column_name, $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped in column_escaped().
				}

				if ( $primary === $column_name ) {
					echo $this->handle_row_actions( $item, $column_name, $primary ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped in handle_row_actions().
				}

				echo '</td>';
			}
		}
	}

	/**
	 * Verify nonce for an action.
	 *
	 * @param string $action Action name.
	 * @param string $nonce Nonce value.
	 * @return bool True if nonce is valid, false otherwise.
	 */
	public static function verify_action_nonce( $action, $nonce ) {
		if ( empty( $action ) || empty( $nonce ) ) {
			return false;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		$action = sanitize_key( $action );
		$nonce  = sanitize_key( $nonce );

		return wp_verify_nonce( $nonce, $action );
	}

	/**
	 * Handle row actions.
	 *
	 * @param array  $item Item data.
	 * @param string $column_name Column name.
	 * @param string $primary Primary column name.
	 * @return string Row actions HTML or empty string.
	 */
	protected function handle_row_actions( $item, $column_name, $primary ) {
		if ( $primary !== $column_name ) {
			return '';
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return '';
		}

		$actions = array();

		// Edit action.
		$actions['edit'] = sprintf(
			'<a href="#" class="edit-qr-code" data-id="%d" data-url="%s">%s</a>',
			absint( $item['id'] ),
			esc_attr( $item['destination_url'] ),
			esc_html__( 'Edit', 'wp-qr-trackr' )
		);

		// Delete action with nonce.
		$delete_nonce      = wp_create_nonce( 'delete_qr_code_' . absint( $item['id'] ) );
		$actions['delete'] = sprintf(
			'<a href="#" class="delete-qr-code" data-id="%d" data-nonce="%s">%s</a>',
			absint( $item['id'] ),
			esc_attr( $delete_nonce ),
			esc_html__( 'Delete', 'wp-qr-trackr' )
		);

		// Regenerate action with nonce.
		$regenerate_nonce      = wp_create_nonce( 'regenerate_qr_code_' . absint( $item['id'] ) );
		$actions['regenerate'] = sprintf(
			'<a href="#" class="regenerate-qr-code" data-id="%d" data-nonce="%s">%s</a>',
			absint( $item['id'] ),
			esc_attr( $regenerate_nonce ),
			esc_html__( 'Regenerate', 'wp-qr-trackr' )
		);

		/**
		 * Filter the list of row actions.
		 *
		 * @since 1.0.0
		 * @param array $actions Array of row actions.
		 * @param array $item Item data.
		 */
		$actions = apply_filters( 'qr_trackr_row_actions', $actions, $item );

		return $this->row_actions( $actions );
	}
}
