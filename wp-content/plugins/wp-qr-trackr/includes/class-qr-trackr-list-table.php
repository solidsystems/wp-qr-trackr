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

		// Set up filters
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
		return array(
			'cb'              => '<input type="checkbox" />',
			'qr_code'         => __( 'QR Code', 'wp-qr-trackr' ),
			'destination_url' => __( 'Destination URL', 'wp-qr-trackr' ),
			'tracking_link'   => __( 'Tracking Link', 'wp-qr-trackr' ),
			'scans'           => __( 'Scans', 'wp-qr-trackr' ),
			'created_at'      => __( 'Created', 'wp-qr-trackr' ),
			'actions'         => __( 'Actions', 'wp-qr-trackr' ),
		);
	}

	/**
	 * Get sortable columns.
	 *
	 * @return array Sortable columns.
	 */
	public function get_sortable_columns() {
		return array(
			'destination_url' => array( 'destination_url', false ),
			'scans'           => array( 'scans', false ),
			'created_at'      => array( 'created_at', true ),
		);
	}

	/**
	 * Render the checkbox column.
	 *
	 * @param object $item The current item.
	 * @return string Checkbox HTML.
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="qr_code[]" value="%s" />',
			esc_attr( $item['id'] )
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

		// Set up pagination
		$per_page     = $this->get_items_per_page( 'qr_trackr_per_page', 20 );
		$current_page = $this->get_pagenum();
		$offset       = ( $current_page - 1 ) * $per_page;

		// Build query
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

		// Build WHERE clause
		$where = '';
		if ( ! empty( $where_clauses ) ) {
			$where = 'WHERE ' . implode( ' AND ', $where_clauses );
		}

		// Get total items for pagination
		$cache_key   = 'qr_trackr_total_items_' . md5( serialize( array( $where, $where_values ) ) );
		$total_items = wp_cache_get( $cache_key, 'qr_trackr' );

		if ( false === $total_items ) {
			$count_query = "SELECT COUNT(*) FROM {$wpdb->prefix}qr_trackr_links $where";
			if ( ! empty( $where_values ) ) {
				$count_query = $wpdb->prepare( $count_query, $where_values );
			}
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached immediately after query.
			$total_items = $wpdb->get_var( $count_query );
			wp_cache_set( $cache_key, $total_items, 'qr_trackr', 300 ); // Cache for 5 minutes
		}

		// Get items
		$orderby = isset( $_REQUEST['orderby'] ) ? sanitize_sql_orderby( $_REQUEST['orderby'] ) : 'created_at';
		$order   = isset( $_REQUEST['order'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) : 'DESC';

		$cache_key   = 'qr_trackr_items_' . md5( serialize( array( $where, $where_values, $orderby, $order, $offset, $per_page ) ) );
		$this->items = wp_cache_get( $cache_key, 'qr_trackr' );

		if ( false === $this->items ) {
			$query      = "SELECT * FROM {$wpdb->prefix}qr_trackr_links $where ORDER BY $orderby $order LIMIT %d OFFSET %d";
			$query_args = array_merge( $where_values, array( $per_page, $offset ) );

			if ( ! empty( $query_args ) ) {
				$query = $wpdb->prepare( $query, $query_args );
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached immediately after query.
			$this->items = $wpdb->get_results( $query, ARRAY_A );
			wp_cache_set( $cache_key, $this->items, 'qr_trackr', 300 ); // Cache for 5 minutes
		}

		// Set up pagination args
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);
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
					// Edit action
					$actions['edit'] = sprintf(
						'<a href="#" class="edit-qr-code" data-id="%d" data-url="%s">%s</a>',
						intval( $item['id'] ),
						esc_attr( $item['destination_url'] ),
						esc_html__( 'Edit', 'wp-qr-trackr' )
					);

					// Delete action with nonce
					$delete_nonce      = wp_create_nonce( 'delete_qr_code_' . $item['id'] );
					$actions['delete'] = sprintf(
						'<a href="#" class="delete-qr-code" data-id="%d" data-nonce="%s">%s</a>',
						intval( $item['id'] ),
						esc_attr( $delete_nonce ),
						esc_html__( 'Delete', 'wp-qr-trackr' )
					);

					// Regenerate action with nonce
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
				return print_r( $item, true ); // Show the whole array for troubleshooting purposes.
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
	 * Render extra table navigation (filters).
	 *
	 * @param string $which Position of the nav ('top' or 'bottom').
	 * @return void
	 */
	public function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}

		echo '<div class="alignleft actions">';

		// Post type filter
		echo '<select name="filter_post_type">';
		echo '<option value="">' . esc_html__( 'All Types', 'wp-qr-trackr' ) . '</option>';
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		foreach ( $post_types as $type ) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $type->name ),
				selected( $this->post_type_filter, $type->name, false ),
				esc_html( $type->labels->singular_name )
			);
		}
		echo '</select>';

		// Destination filter
		printf(
			'<input type="text" name="filter_destination" value="%s" placeholder="%s" />',
			esc_attr( $this->destination_filter ),
			esc_attr__( 'Filter by destination', 'wp-qr-trackr' )
		);

		// Scans filter
		printf(
			'<input type="number" name="filter_scans" value="%s" placeholder="%s" min="0" />',
			esc_attr( $this->scans_filter ),
			esc_attr__( 'Min scans', 'wp-qr-trackr' )
		);

		submit_button( __( 'Filter', 'wp-qr-trackr' ), '', 'filter_action', false );
		echo '</div>';
	}

	/**
	 * Display the rows of records in the table.
	 *
	 * @return void
	 */
	public function display_rows() {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug only, not for production.
			error_log( 'QR Trackr Debug: Starting display_rows().' );
		}
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
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug only, not for production.
			error_log( 'QR Trackr Debug: Rendering single row for item - ' . print_r( $item, true ) . '.' );
		}

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
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug only, not for production.
			error_log( 'QR Trackr Debug: Starting display().' );
		}

		$this->prepare_items();

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug only, not for production.
			error_log( 'QR Trackr Debug: After prepare_items, items count: ' . count( $this->items ) );
		}

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
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug only, not for production.
			error_log( 'QR Trackr Debug: Starting single_row_columns for item ' . esc_html( $item['id'] ) . '.' );
		}

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
	 * Get link by post ID.
	 *
	 * @param int $post_id Post ID.
	 * @return object|null Link object or null if not found.
	 */
	private function get_link_by_post_id( $post_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'qr_trackr_links';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Direct query required for admin utility. Caching is not used to ensure up-to-date data for admin actions.
		$link = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM `{$table_name}` WHERE post_id = %d ORDER BY created_at DESC LIMIT 1",
				$post_id
			)
		);
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
	 * Process bulk actions.
	 *
	 * @return void
	 */
	public function process_bulk_action() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'wp-qr-trackr' ) );
		}

		$action = $this->current_action();
		if ( 'delete' === $action ) {
			if ( empty( $_POST['qr_code'] ) || ! is_array( $_POST['qr_code'] ) ) {
				return;
			}

			if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'bulk-' . $this->_args['plural'] ) ) {
				wp_die( esc_html__( 'Security check failed.', 'wp-qr-trackr' ) );
			}

			$deleted = 0;
			$failed  = 0;

			foreach ( $_POST['qr_code'] as $id ) {
				$result = qr_trackr_delete_qr_code( absint( $id ) );
				if ( is_wp_error( $result ) ) {
					++$failed;
				} else {
					++$deleted;
				}
			}

			if ( $deleted > 0 ) {
				add_settings_error(
					'bulk_action',
					'bulk_action_success',
					sprintf(
						/* translators: %d: number of deleted items */
						_n(
							'%d QR code deleted successfully.',
							'%d QR codes deleted successfully.',
							$deleted,
							'wp-qr-trackr'
						),
						$deleted
					),
					'updated'
				);
			}

			if ( $failed > 0 ) {
				add_settings_error(
					'bulk_action',
					'bulk_action_error',
					sprintf(
						/* translators: %d: number of failed deletions */
						_n(
							'%d QR code could not be deleted.',
							'%d QR codes could not be deleted.',
							$failed,
							'wp-qr-trackr'
						),
						$failed
					),
					'error'
				);
			}
		}
	}

	/**
	 * Get the table classes.
	 *
	 * @return array Array of table classes.
	 */
	protected function get_table_classes() {
		return array( 'widefat', 'fixed', 'striped', 'qr-trackr-list-table' );
	}
}
