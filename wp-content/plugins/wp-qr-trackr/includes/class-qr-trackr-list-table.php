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
				'singular' => 'qr-code',
				'plural'   => 'qr-codes',
				'ajax'     => false,
			)
		);

		// Debug: Log constructor
		error_log( 'QR Trackr Debug: List Table Constructor called' );
	}

	/**
	 * Get column info.
	 *
	 * @return array Column info.
	 */
	protected function get_column_info() {
		// Debug: Log get_column_info call
		error_log( 'QR Trackr Debug: get_column_info() called' );

		$columns = $this->get_columns();
		$hidden = get_hidden_columns( $this->screen );
		$sortable = $this->get_sortable_columns();
		$primary = $this->get_primary_column_name();

		// Debug: Log column info
		error_log( 'QR Trackr Debug: Column info in get_column_info - ' . print_r( array(
			'columns' => $columns,
			'hidden' => $hidden,
			'sortable' => $sortable,
			'primary' => $primary
		), true ) );

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
		// Debug: Log get_columns call
		error_log( 'QR Trackr Debug: get_columns() called' );

		$columns = array(
			'cb'              => '<input type="checkbox" />',
			'title'           => __( 'Title', 'qr-trackr' ),
			'tracking_link'   => __( 'Tracking Link', 'qr-trackr' ),
			'destination_url' => __( 'Destination URL', 'qr-trackr' ),
			'scans'           => __( 'Scans', 'qr-trackr' ),
			'created_at'      => __( 'Created', 'qr-trackr' ),
		);

		// Debug: Log columns
		error_log( 'QR Trackr Debug: Columns defined - ' . print_r( $columns, true ) );

		return $columns;
	}

	/**
	 * Get sortable columns.
	 *
	 * @return array Sortable columns.
	 */
	public function get_sortable_columns() {
		// Debug: Log get_sortable_columns call
		error_log( 'QR Trackr Debug: get_sortable_columns() called' );

		$sortable_columns = array(
			'title'      => array( 'title', true ),
			'scans'      => array( 'scans', false ),
			'created_at' => array( 'created_at', false ),
		);

		// Debug: Log sortable columns
		error_log( 'QR Trackr Debug: Sortable columns defined - ' . print_r( $sortable_columns, true ) );

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
			'<input type="checkbox" name="qr_code[]" value="%s" />',
			$item->id
		);
	}

	/**
	 * Prepare items for display.
	 *
	 * @return void
	 */
	public function prepare_items() {
		global $wpdb;

		// Debug: Log the start of prepare_items
		error_log( 'QR Trackr Debug: Starting prepare_items()' );

		$per_page = 20;
		$current_page = $this->get_pagenum();

		// Debug: Log pagination info
		error_log( 'QR Trackr Debug: Pagination - Page: ' . $current_page . ', Per Page: ' . $per_page );

		// Get filters
		$this->post_type_filter = isset( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : '';
		$this->destination_filter = isset( $_GET['destination'] ) ? sanitize_text_field( wp_unslash( $_GET['destination'] ) ) : '';
		$this->scans_filter = isset( $_GET['scans'] ) ? intval( $_GET['scans'] ) : 0;

		// Debug: Log filter values
		error_log( 'QR Trackr Debug: Filters - Post Type: ' . $this->post_type_filter . ', Destination: ' . $this->destination_filter . ', Min Scans: ' . $this->scans_filter );

		// Build the SQL query
		$sql = "SELECT l.*, p.post_title, p.post_type, 
				(SELECT COUNT(*) FROM {$wpdb->prefix}qr_trackr_scans s WHERE s.link_id = l.id) as scans 
				FROM {$wpdb->prefix}qr_trackr_links l 
				LEFT JOIN {$wpdb->posts} p ON l.post_id = p.ID 
				WHERE 1=1";

		// Add filters
		if ( ! empty( $this->post_type_filter ) ) {
			$sql .= $wpdb->prepare( " AND p.post_type = %s", $this->post_type_filter );
		}
		if ( ! empty( $this->destination_filter ) ) {
			$sql .= $wpdb->prepare( " AND l.destination LIKE %s", '%' . $wpdb->esc_like( $this->destination_filter ) . '%' );
		}
		if ( $this->scans_filter > 0 ) {
			$sql .= $wpdb->prepare( " AND (SELECT COUNT(*) FROM {$wpdb->prefix}qr_trackr_scans s WHERE s.link_id = l.id) >= %d", $this->scans_filter );
		}

		// Debug: Log the SQL query
		error_log( 'QR Trackr Debug: SQL Query - ' . $sql );

		// Get total items
		$total_items = $wpdb->get_var( "SELECT COUNT(*) FROM ({$sql}) as t" );
		
		// Debug: Log total items
		error_log( 'QR Trackr Debug: Total Items - ' . $total_items );

		// Add pagination
		$sql .= " ORDER BY l.created_at DESC LIMIT %d OFFSET %d";
		$sql = $wpdb->prepare( $sql, $per_page, ( $current_page - 1 ) * $per_page );

		// Debug: Log paginated SQL query
		error_log( 'QR Trackr Debug: Paginated SQL Query - ' . $sql );

		// Get items
		$this->items = $wpdb->get_results( $sql );

		// Debug: Log items count and first item
		error_log( 'QR Trackr Debug: Items Count - ' . count( $this->items ) );
		if ( ! empty( $this->items ) ) {
			error_log( 'QR Trackr Debug: First Item - ' . print_r( $this->items[0], true ) );
		}

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page ),
		) );
	}

	/**
	 * Render default column output.
	 *
	 * @param object $item        The current item.
	 * @param string $column_name The column name.
	 * @return string Column output HTML.
	 */
	public function column_default( $item, $column_name ) {
		// Debug: Log column rendering
		error_log( 'QR Trackr Debug: Rendering column ' . $column_name . ' for item ' . $item->id );

		switch ( $column_name ) {
			case 'title':
				return '<strong>' . esc_html( $item->post_title ) . '</strong>';
			case 'tracking_link':
				$link = trailingslashit( home_url() ) . 'qr-trackr/redirect/' . intval( $item->id );
				return '<a href="' . esc_url( $link ) . '" target="_blank">' . esc_html( $link ) . '</a>';
			case 'destination_url':
				return '<a href="' . esc_url( $item->destination_url ) . '" target="_blank">' . esc_html( $item->destination_url ) . '</a>';
			case 'scans':
				error_log( 'QR Trackr Debug: Scans value for item ' . $item->id . ': ' . print_r( $item->scans, true ) );
				return intval( $item->scans );
			case 'created_at':
				return esc_html( gmdate( 'Y-m-d H:i:s', strtotime( $item->created_at ) ) );
			default:
				return print_r( $item, true );
		}
	}

	/**
	 * Render extra table navigation (filters).
	 *
	 * @param string $which Position of the nav ('top' or 'bottom').
	 * @return void
	 */
	public function extra_tablenav( $which ) {
		if ( 'top' === $which ) {
			// Post type filter.
			$selected = isset( $_REQUEST['filter_post_type'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['filter_post_type'] ) ) ) : '';
			echo '<div class="alignleft actions">';
			echo '<select name="filter_post_type">';
			echo '<option value="">All Types</option>';
			foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $type ) {
				echo '<option value="' . esc_attr( $type->name ) . '"' . selected( $selected, $type->name, false ) . '>' . esc_html( $type->labels->singular_name ) . '</option>';
			}
			echo '</select>';
			// Destination link filter.
			$dest = isset( $_REQUEST['filter_destination'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['filter_destination'] ) ) ) : '';
			echo '<input type="text" name="filter_destination" placeholder="Destination Link" value="' . esc_attr( $dest ) . '" style="width:140px;" />';
			// Scans filter.
			$scans = isset( $_REQUEST['filter_scans'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['filter_scans'] ) ) ) : '';
			echo '<input type="number" name="filter_scans" placeholder="Min Scans" value="' . esc_attr( $scans ) . '" style="width:100px;" min="0" />';
			echo '<input type="submit" class="button" value="Filter">';
			echo '</div>';
		}
	}

	/**
	 * Display the rows of records in the table.
	 *
	 * @return void
	 */
	public function display_rows() {
		// Debug: Log start of display_rows
		error_log( 'QR Trackr Debug: Starting display_rows()' );
		error_log( 'QR Trackr Debug: Items in display_rows - ' . print_r( $this->items, true ) );

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
		// Debug: Log single row item
		error_log( 'QR Trackr Debug: Rendering single row for item - ' . print_r( $item, true ) );

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
		// Debug: Log start of display
		error_log( 'QR Trackr Debug: Starting display()' );

		$this->prepare_items();

		// Debug: Log after prepare_items
		error_log( 'QR Trackr Debug: After prepare_items, items count: ' . count( $this->items ) );

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

	public function single_row_columns( $item ) {
		// Debug: Log start of single_row_columns
		error_log( 'QR Trackr Debug: Starting single_row_columns for item ' . $item->id );

		list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

		// Debug: Log column info
		error_log( 'QR Trackr Debug: Column info - ' . print_r( array(
			'columns' => $columns,
			'hidden' => $hidden,
			'sortable' => $sortable,
			'primary' => $primary
		), true ) );

		foreach ( $columns as $column_name => $column_display_name ) {
			// Debug: Log each column being rendered
			error_log( 'QR Trackr Debug: Rendering column ' . $column_name );

			$classes = "$column_name column-$column_name";
			if ( $primary === $column_name ) {
				$classes .= ' has-row-actions column-primary';
			}

			if ( in_array( $column_name, $hidden, true ) ) {
				$classes .= ' hidden';
			}

			// Inline edit data
			$data = '';
			if ( $this->inline_edit && in_array( $column_name, $this->inline_edit_columns, true ) ) {
				$data = " data-colname='$column_display_name'";
			}

			$attributes = "class='$classes'$data";

			if ( 'cb' === $column_name ) {
				echo '<th scope="row" class="check-column">';
				echo $this->column_cb( $item );
				echo '</th>';
			} elseif ( method_exists( $this, '_column_' . $column_name ) ) {
				echo call_user_func(
					array( $this, '_column_' . $column_name ),
					$item,
					$classes,
					$data,
					$primary
				);
			} elseif ( method_exists( $this, 'column_' . $column_name ) ) {
				echo "<td $attributes>";
				echo call_user_func( array( $this, 'column_' . $column_name ), $item );
				echo $this->handle_row_actions( $item, $column_name, $primary );
				echo '</td>';
			} else {
				echo "<td $attributes>";
				echo $this->column_default( $item, $column_name );
				echo $this->handle_row_actions( $item, $column_name, $primary );
				echo '</td>';
			}
		}
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

		// Add view action
		$actions['view'] = sprintf(
			'<a href="%s" target="_blank">%s</a>',
			esc_url( $item->destination_url ),
			esc_html__( 'View', 'qr-trackr' )
		);

		// Add delete action
		$actions['delete'] = sprintf(
			'<a href="%s" class="submitdelete" onclick="return confirm(\'%s\');">%s</a>',
			wp_nonce_url(
				add_query_arg(
					array(
						'action' => 'delete',
						'link'   => $item->id,
					),
					admin_url( 'admin.php?page=qr-trackr' )
				),
				'delete_qr_trackr_link_' . $item->id
			),
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
	 * @return array
	 */
	public function get_bulk_actions() {
		return array(
			'delete' => __( 'Delete', 'qr-trackr' ),
		);
	}
}
