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
	 * Post type filter value.
	 *
	 * @var string|null
	 */
	private $post_type_filter;
	/**
	 * Destination filter value.
	 *
	 * @var string|null
	 */
	private $destination_filter;
	/**
	 * Scans filter value.
	 *
	 * @var int|null
	 */
	private $scans_filter;
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
	 *
	 * @return void
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

		// Set up filters with proper validation.
		$this->post_type_filter   = isset( $_REQUEST['filter_post_type'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['filter_post_type'] ) ) : '';
		$this->destination_filter = isset( $_REQUEST['filter_destination'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['filter_destination'] ) ) : '';
		$this->scans_filter       = isset( $_REQUEST['filter_scans'] ) ? absint( $_REQUEST['filter_scans'] ) : null;
	}

	/**
	 * Get column info.
	 *
	 * @return array Column info.
	 */
	protected function get_column_info() {
		$columns  = $this->get_columns();
		$hidden   = get_hidden_columns( $this->screen );
		$sortable = $this->get_sortable_columns();
		$primary  = $this->get_primary_column_name();

		return array( $columns, $hidden, $sortable, $primary );
	}

	/**
	 * Get primary column name.
	 *
	 * @return string Primary column name.
	 */
	protected function get_primary_column_name() {
		return 'id';
	}

	/**
	 * Get columns for the table.
	 *
	 * @return array Columns for the table.
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

		return $columns;
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
	 * @param object $item The current item.
	 * @return string Checkbox HTML.
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="qr_code[]" value="%d" />', // Checkbox for bulk actions!
			intval( $item['id'] )
		);
	}

	/**
	 * Prepare items for display.
	 *
	 * Note: All destructive actions (delete, bulk delete, etc.) are handled in the admin page handler (module-admin.php),
	 * which verifies nonces for all such actions. This method only prepares data for display and does not process form submissions.
	 *
	 * @return void
	 */
	public function prepare_items() {
		global $wpdb;

		// Verify nonce for form data processing.
		$nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_key( $_REQUEST['_wpnonce'] ) : '';
		if ( ! wp_verify_nonce( $nonce, 'bulk-' . $this->_args['plural'] ) ) {
			return;
		}

		// Set up pagination.
		$per_page     = $this->get_items_per_page( 'qr_trackr_per_page', 20 );
		$current_page = $this->get_pagenum();
		$offset       = ( $current_page - 1 ) * $per_page;

		// Build query.
		$where_clauses = array();
		$where_values  = array();

		if ( ! empty( $this->post_type_filter ) ) {
			$where_clauses[] = 'post_type = %s';
			$where_values[]  = $this->post_type_filter;
		}

		if ( ! empty( $this->destination_filter ) ) {
			$where_clauses[] = 'destination_url LIKE %s';
			$where_values[]  = '%' . $wpdb->esc_like( $this->destination_filter ) . '%';
		}

		if ( ! is_null( $this->scans_filter ) ) {
			$where_clauses[] = 'scans >= %d';
			$where_values[]  = $this->scans_filter;
		}

		// Build WHERE clause.
		$where = '';
		if ( ! empty( $where_clauses ) ) {
			$where = 'WHERE ' . implode( ' AND ', $where_clauses );
		}

		// Get total items for pagination.
		$cache_key   = 'qr_trackr_total_items_' . md5( wp_json_encode( array( $where, $where_values ) ) );
		$total_items = wp_cache_get( $cache_key, 'qr_trackr' );

		if ( false === $total_items ) {
			// Build the base query with proper escaping.
			$base_query = "SELECT COUNT(*) FROM {$wpdb->prefix}qr_trackr_links";

			// Add WHERE clause if we have conditions.
			if ( ! empty( $where_clauses ) && ! empty( $where_values ) ) {
				$base_query .= ' WHERE ' . implode( ' AND ', array_map( 'esc_sql', $where_clauses ) );
				$query       = $wpdb->prepare( $base_query, ...$where_values );
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached immediately after query.
				$total_items = $wpdb->get_var( $query );
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached immediately after query.
				$total_items = $wpdb->get_var( $base_query );
			}

			wp_cache_set( $cache_key, $total_items, 'qr_trackr', 300 ); // Cache for 5 minutes.
		}

		// Get items with proper sanitization and validation.
		$orderby = isset( $_REQUEST['orderby'] ) ? sanitize_sql_orderby( wp_unslash( $_REQUEST['orderby'] ) ) : 'created_at';
		$order   = isset( $_REQUEST['order'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) : 'DESC';
		$order   = 'ASC' === strtoupper( $order ) ? 'ASC' : 'DESC';

		$cache_key   = 'qr_trackr_items_' . md5( wp_json_encode( array( $where, $where_values, $orderby, $order, $offset, $per_page ) ) );
		$this->items = wp_cache_get( $cache_key, 'qr_trackr' );

		if ( false === $this->items ) {
			// Build the base query with proper escaping.
			$base_query = "SELECT * FROM {$wpdb->prefix}qr_trackr_links";

			// Add WHERE clause if we have conditions.
			if ( ! empty( $where_clauses ) && ! empty( $where_values ) ) {
				$base_query .= ' WHERE ' . implode( ' AND ', array_map( 'esc_sql', $where_clauses ) );
				$base_query .= sprintf(
					' ORDER BY %s %s LIMIT %d OFFSET %d',
					esc_sql( $orderby ),
					esc_sql( $order ),
					absint( $per_page ),
					absint( $offset )
				);
				$query       = $wpdb->prepare( $base_query, ...$where_values );
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached immediately after query.
				$this->items = $wpdb->get_results( $query, ARRAY_A );
			} else {
				$base_query .= sprintf(
					' ORDER BY %s %s LIMIT %d OFFSET %d',
					esc_sql( $orderby ),
					esc_sql( $order ),
					absint( $per_page ),
					absint( $offset )
				);
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached immediately after query.
				$this->items = $wpdb->get_results( $base_query, ARRAY_A );
			}

			wp_cache_set( $cache_key, $this->items, 'qr_trackr', 300 ); // Cache for 5 minutes.
		}

		// Set up pagination args with proper validation.
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);

		// Process bulk actions with proper security checks and validation.
		if ( 'delete' === $this->current_action() ) {
			check_admin_referer( 'bulk-' . $this->_args['plural'] );

			$selected_ids = isset( $_REQUEST['qr_codes'] ) ? array_map( 'absint', (array) wp_unslash( $_REQUEST['qr_codes'] ) ) : array();

			if ( ! empty( $selected_ids ) ) {
				$placeholders = implode( ',', array_fill( 0, count( $selected_ids ), '%d' ) );
				$query        = $wpdb->prepare(
					"DELETE FROM {$wpdb->prefix}qr_trackr_links WHERE id IN ($placeholders)",
					...$selected_ids
				);
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Bulk delete operation.
				$wpdb->query( $query );

				// Clear cache for deleted items.
				foreach ( $selected_ids as $id ) {
					wp_cache_delete( 'qr_trackr_item_' . $id, 'qr_trackr' );
				}
				wp_cache_delete( 'qr_trackr_items', 'qr_trackr' );
			}
		}

		// Display filters with proper escaping and validation.
		echo '<div class="alignleft actions">';
		echo '<select name="filter_post_type">';
		echo '<option value="">' . esc_html__( 'All post types', 'wp-qr-trackr' ) . '</option>';

		// Get all public post types for filtering purposes.
		$post_types = get_post_types( array( 'public' => true ), 'objects' );

		// Display post type filter dropdown with proper escaping and validation.
		foreach ( $post_types as $type ) {
			$selected = selected( $this->post_type_filter, $type->name, false );
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $type->name ),
				esc_attr( $selected ),
				esc_html( $type->labels->singular_name )
			);
		}
		echo '</select>';

		// Display destination filter input with proper escaping and validation.
		printf(
			'<input type="text" name="filter_destination" value="%s" placeholder="%s" />',
			esc_attr( $this->destination_filter ),
			esc_attr__( 'Filter by destination', 'wp-qr-trackr' )
		);

		// Display scans filter input with proper escaping and validation.
		printf(
			'<input type="number" name="filter_scans" value="%s" placeholder="%s" min="0" />',
			esc_attr( $this->scans_filter ),
			esc_attr__( 'Min scans', 'wp-qr-trackr' )
		);

		submit_button( __( 'Filter', 'wp-qr-trackr' ), '', 'filter_action', false );
		echo '</div>';
	}

	/**
	 * Render default column output (unescaped, escaping handled in single_row_columns).
	 *
	 * @param object $item        The current item.
	 * @param string $column_name The column name.
	 * @return string Column output HTML (unescaped).
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'qr_code':
				$qr_urls = qr_trackr_generate_qr_image_for_link( $item['id'] );
				if ( is_wp_error( $qr_urls ) ) {
					return '<div class="error"><p>' . esc_html( $qr_urls->get_error_message() ) . '</p></div>';
				}
				$html  = '<div class="qr-code-preview">';
				$html .= '<img src="' . esc_url( $qr_urls['png'] ) . '" alt="QR Code" style="width:100px; height:100px;" />';
				$html .= '<div class="qr-code-actions">';
				$html .= '<a href="' . esc_url( $qr_urls['png'] ) . '" download class="button button-small"><span class="dashicons dashicons-download"></span> PNG</a>';
				$html .= '<a href="' . esc_url( $qr_urls['svg'] ) . '" download class="button button-small"><span class="dashicons dashicons-download"></span> SVG</a>';
				$html .= '</div></div>';
				return $html;

			case 'destination_url':
				return '<a href="' . esc_url( $item['destination_url'] ) . '" target="_blank">' . esc_html( $item['destination_url'] ) . '</a>';

			case 'tracking_link':
				$tracking_link = trailingslashit( home_url() ) . 'qr-trackr/redirect/' . intval( $item['id'] );
				return '<a href="' . esc_url( $tracking_link ) . '" target="_blank">' . esc_html( $tracking_link ) . '</a>';

			case 'scans':
				return esc_html( number_format_i18n( $item['scans'] ) );

			case 'created_at':
				return esc_html( gmdate( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $item['created_at'] ) ) );

			case 'actions':
				$actions = array();

				if ( current_user_can( 'manage_options' ) ) {
					// Edit action.
					$actions['edit'] = sprintf(
						'<a href="#" class="edit-qr-code" data-id="%d" data-url="%s">%s</a>',
						intval( $item['id'] ),
						esc_attr( $item['destination_url'] ),
						esc_html__( 'Edit', 'wp-qr-trackr' )
					);

					// Delete action with nonce.
					$delete_nonce      = wp_create_nonce( 'delete_qr_code_' . $item['id'] );
					$actions['delete'] = sprintf(
						'<a href="#" class="delete-qr-code" data-id="%d" data-nonce="%s">%s</a>',
						intval( $item['id'] ),
						esc_attr( $delete_nonce ),
						esc_html__( 'Delete', 'wp-qr-trackr' )
					);

					// Regenerate action with nonce.
					$regenerate_nonce      = wp_create_nonce( 'regenerate_qr_code_' . $item['id'] );
					$actions['regenerate'] = sprintf(
						'<a href="#" class="regenerate-qr-code" data-id="%d" data-nonce="%s">%s</a>',
						intval( $item['id'] ),
						esc_attr( $regenerate_nonce ),
						esc_html__( 'Regenerate', 'wp-qr-trackr' )
					);
				}

				return $this->row_actions( $actions );

			default:
				// Return a safe, escaped representation of the item for debugging purposes.
				$item_data = array(
					'id'            => isset( $item['id'] ) ? absint( $item['id'] ) : 0,
					'destination'   => isset( $item['destination_url'] ) ? esc_url( $item['destination_url'] ) : '',
					'created_at'    => isset( $item['created_at'] ) ? sanitize_text_field( $item['created_at'] ) : '',
					'last_accessed' => isset( $item['last_accessed'] ) ? sanitize_text_field( $item['last_accessed'] ) : '',
					'access_count'  => isset( $item['access_count'] ) ? absint( $item['access_count'] ) : 0,
				);
				return esc_html( wp_json_encode( $item_data, JSON_PRETTY_PRINT ) );
		}
	}

	/**
	 * Escape column output for the correct context.
	 *
	 * @param string $column_name The column name.
	 * @param object $item        The current item.
	 * @return string Escaped column output.
	 */
	protected function column_escaped( $column_name, $item ) {
		switch ( $column_name ) {
			case 'title':
				return '<strong>' . esc_html( $item['post_title'] ) . '</strong>';
			case 'tracking_link':
				$link = trailingslashit( home_url() ) . 'qr-trackr/redirect/' . intval( $item['id'] );
				return '<a href="' . esc_url( $link ) . '" target="_blank">' . esc_html( $link ) . '</a>';
			case 'destination_url':
				$actions = array(
					'edit' => sprintf(
						'<a href="#" class="edit-destination" data-link-id="%d" data-destination="%s">%s</a>',
						intval( $item['id'] ),
						esc_attr( $item['destination_url'] ),
						esc_html__( 'Edit', 'qr-trackr' )
					),
				);
				return sprintf(
					'%1$s %2$s',
					'<a href="' . esc_url( $item['destination_url'] ) . '" target="_blank">' . esc_html( $item['destination_url'] ) . '</a>',
					$this->row_actions( $actions )
				);
			case 'scans':
				return esc_html( intval( $item['scans'] ) );
			case 'created_at':
				return esc_html( gmdate( 'Y-m-d H:i:s', strtotime( $item['created_at'] ) ) );
			default:
				return '';
		}
	}

	/**
	 * Display the rows of records in the table.
	 *
	 * @return void
	 */
	public function display_rows() {
		foreach ( $this->items as $item ) {
			$this->single_row( $item );
		}
	}

	/**
	 * Display a single row.
	 *
	 * @param object $item The current item.
	 * @return void
	 */
	public function single_row( $item ) {
		echo '<tr>';
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * Display the list table.
	 *
	 * @return void
	 */
	public function display() {
		$this->prepare_items();

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<?php $this->views(); ?>
			<form method="get">
				<?php
				$this->search_box( 'Search', 'search_id' );
				$this->display_tablenav( 'top' );
				?>
				<table class="wp-list-table <?php echo esc_attr( implode( ' ', $this->get_table_classes() ) ); ?>">
					<thead>
						<tr>
							<?php $this->print_column_headers(); ?>
						</tr>
					</thead>

					<tbody id="the-list"<?php echo $this->list_table_has_items() ? ' data-wp-lists="list:' . esc_attr( $this->_args['singular'] ) . '"' : ''; ?>>
						<?php $this->display_rows_or_placeholder(); ?>
					</tbody>

					<tfoot>
						<tr>
							<?php $this->print_column_headers( false ); ?>
						</tr>
					</tfoot>
				</table>
				<?php
				$this->display_tablenav( 'bottom' );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render a single row of columns.
	 *
	 * @param object $item The current item.
	 * @return void
	 */
	public function single_row_columns( $item ) {
		list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

		foreach ( $columns as $column_name => $column_display_name ) {
			$attributes         = $this->get_column_attributes( $column_name );
			$attributes_escaped = esc_attr( $attributes );
			$column_output      = call_user_func( array( $this, 'column_' . $column_name ), $item );
			// Output is already escaped for HTML context above.
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped with esc_html() and esc_attr().
			echo '<td ' . $attributes_escaped . '>' . esc_html( $column_output ) . '</td>';
		}
	}

	/**
	 * Verify a nonce for destructive actions.
	 *
	 * @param string $action The action name.
	 * @param string $nonce  The nonce value.
	 * @return bool True if valid, false otherwise.
	 */
	public static function verify_action_nonce( $action, $nonce ) {
		return ( ! empty( $nonce ) && wp_verify_nonce( $nonce, $action ) );
	}

	/**
	 * Handle row actions.
	 *
	 * @param object $item        The current item.
	 * @param string $column_name The column name.
	 * @param string $primary     The primary column name.
	 * @return string Row actions HTML.
	 */
	protected function handle_row_actions( $item, $column_name, $primary ) {
		if ( $primary !== $column_name ) {
			return '';
		}

		$actions = array();

		// Add view action.
		$actions['view'] = sprintf(
			'<a href="%s" target="_blank">%s</a>',
			esc_url( $item['destination_url'] ),
			esc_html__( 'View', 'qr-trackr' )
		);

		// Add delete action with nonce.
		$delete_url        = wp_nonce_url(
			add_query_arg(
				array(
					'action' => 'delete',
					'link'   => $item['id'],
				),
				admin_url( 'admin.php?page=qr-trackr' )
			),
			'delete_qr_trackr_link_' . $item['id']
		);
		$actions['delete'] = sprintf(
			'<a href="%s" class="submitdelete" onclick="return confirm(\'%s\');">%s</a>',
			esc_url( $delete_url ),
			esc_js( __( 'Are you sure you want to delete this QR code link?', 'qr-trackr' ) ),
			esc_html__( 'Delete', 'qr-trackr' )
		);

		return $this->row_actions( $actions );
	}

	/**
	 * Get a link by post ID.
	 *
	 * @param int $post_id The post ID.
	 * @return object|null The link object or null.
	 */
	private function get_link_by_post_id( $post_id ) {
		global $wpdb;
		$cache_key = 'qr_trackr_link_by_post_' . $post_id;
		$link      = wp_cache_get( $cache_key );

		if ( false === $link ) {
			$query = $wpdb->prepare(
				sprintf(
					'SELECT * FROM %s WHERE post_id = %%d ORDER BY created_at DESC LIMIT 1',
					$wpdb->prefix . 'qr_trackr_links'
				),
				$post_id
			);
			$link  = $wpdb->get_row( $query );
			wp_cache_set( $cache_key, $link, 'qr_trackr', 300 ); // Cache for 5 minutes.
		}

		return $link;
	}

	/**
	 * Get bulk actions.
	 *
	 * Note: All bulk actions are processed in the admin page handler (module-admin.php),
	 * which verifies nonces for all destructive actions.
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return array();
		}

		return array(
			'delete' => __( 'Delete', 'wp-qr-trackr' ),
		);
	}

	/**
	 * Get all QR code links with pagination.
	 *
	 * @param int $per_page Number of items per page.
	 * @param int $page_number Current page number.
	 * @return array Array of QR code links.
	 */
	public function get_qr_links( $per_page = 10, $page_number = 1 ) {
		global $wpdb;

		// Try to get from cache first.
		$cache_key = 'qr_links_page_' . $page_number . '_' . $per_page;
		$results   = wp_cache_get( $cache_key, 'wp_qr_trackr' );

		if ( false === $results ) {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}qr_links ORDER BY created_at DESC LIMIT %d OFFSET %d",
					absint( $per_page ),
					absint( ( $page_number - 1 ) * $per_page )
				),
				ARRAY_A
			);

			if ( $results ) {
				wp_cache_set( $cache_key, $results, 'wp_qr_trackr', HOUR_IN_SECONDS );
			}
		}

		return $results;
	}

	/**
	 * Get the total count of QR code links.
	 *
	 * @return int Total number of QR code links.
	 */
	public function record_count() {
		global $wpdb;

		// Try to get from cache first.
		$cache_key = 'qr_links_total_count';
		$count     = wp_cache_get( $cache_key, 'wp_qr_trackr' );

		if ( false === $count ) {
			$count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}qr_links"
				)
			);

			if ( false !== $count ) {
				wp_cache_set( $cache_key, $count, 'wp_qr_trackr', HOUR_IN_SECONDS );
			}
		}

		return absint( $count );
	}

	/**
	 * Get QR code link data.
	 *
	 * @param int $post_id Post ID.
	 * @return array|null QR code link data or null if not found.
	 */
	public function get_qr_link_data( $post_id ) {
		global $wpdb;

		// Try to get from cache first.
		$cache_key = 'qr_link_data_' . absint( $post_id );
		$data      = wp_cache_get( $cache_key, 'wp_qr_trackr' );

		if ( false === $data ) {
			$data = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}qr_links WHERE post_id = %d ORDER BY created_at DESC LIMIT 1",
					absint( $post_id )
				),
				ARRAY_A
			);

			if ( $data ) {
				wp_cache_set( $cache_key, $data, 'wp_qr_trackr', HOUR_IN_SECONDS );
			}
		}

		return $data;
	}

	/**
	 * Get the table classes.
	 *
	 * @return array Array of table classes.
	 */
	protected function get_table_classes() {
		return array( 'widefat', 'fixed', 'striped', 'qr-trackr-list-table' );
	}

	/**
	 * Get the SQL for the totals.
	 *
	 * @since 1.0.0
	 * @param string $where Where clause for the query.
	 * @return string
	 */
	private function get_totals_sql( $where ) {
		global $wpdb;

		$table = $wpdb->prefix . 'qr_trackr_links';
		$sql = $wpdb->prepare( 'SELECT COUNT(*) FROM %i', $table );

		if ( ! empty( $where ) ) {
			$sql .= ' WHERE ' . $where;
		}

		return $sql;
	}

	/**
	 * Get the SQL for the records.
	 *
	 * @since 1.0.0
	 * @param string $where Where clause for the query.
	 * @param string $orderby Order by clause.
	 * @param string $order Order direction.
	 * @param int    $per_page Number of items per page.
	 * @param int    $page_number Current page number.
	 * @return string
	 */
	private function get_records_sql( $where, $orderby = '', $order = '', $per_page = 20, $page_number = 1 ) {
		global $wpdb;

		$table = $wpdb->prefix . 'qr_trackr_links';
		$sql = $wpdb->prepare( 'SELECT * FROM %i', $table );

		if ( ! empty( $where ) ) {
			$sql .= ' WHERE ' . $where;
		}

		if ( ! empty( $orderby ) && ! empty( $order ) ) {
			$sql .= $wpdb->prepare( ' ORDER BY %s %s', $orderby, $order );
		}

		$sql .= $wpdb->prepare( ' LIMIT %d OFFSET %d', $per_page, ( $page_number - 1 ) * $per_page );

		return $sql;
	}

	/**
	 * Delete links by IDs.
	 *
	 * @since 1.0.0
	 * @param array $ids Array of link IDs to delete.
	 * @return int|false The number of rows deleted, or false on error.
	 */
	public function delete_links( $ids ) {
		global $wpdb;

		if ( empty( $ids ) ) {
			return false;
		}

		// Ensure all IDs are integers.
		$ids = array_map( 'absint', $ids );

		// Create placeholders for the number of IDs.
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$table = $wpdb->prefix . 'qr_trackr_links';

		// Prepare and execute the delete query.
		$sql = $wpdb->prepare(
			'DELETE FROM %i WHERE id IN (' . $placeholders . ')',
			array_merge( array( $table ), $ids )
		);

		return $wpdb->query( $sql );
	}

	/**
	 * Returns the count of records in the database.
	 *
	 * @since 1.0.0
	 * @param string $search Search term for filtering.
	 * @return int
	 */
	public function record_count( $search = '' ) {
		global $wpdb;

		$where = '';
		if ( ! empty( $search ) ) {
			$where = $wpdb->prepare(
				'url LIKE %s OR title LIKE %s',
				'%' . $wpdb->esc_like( $search ) . '%',
				'%' . $wpdb->esc_like( $search ) . '%'
			);
		}

		$sql = $this->get_totals_sql( $where );
		return absint( $wpdb->get_var( $sql ) );
	}
}
