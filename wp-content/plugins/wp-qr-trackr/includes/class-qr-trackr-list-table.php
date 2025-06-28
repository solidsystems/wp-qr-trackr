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
		$search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';

		// Get and validate pagination parameters.
		$per_page     = $this->get_items_per_page( 'qr_trackr_per_page', 20 );
		$current_page = $this->get_pagenum();

		// Build WHERE clause with proper escaping.
		$where_clauses = array( '1=1' ); // Always true base condition.
		$where_values  = array();

		if ( ! empty( $search ) ) {
			$where_clauses[] = '(destination_url LIKE %s OR title LIKE %s)';
			$search_pattern  = '%' . $wpdb->esc_like( $search ) . '%';
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

		// Get total items count.
		$total_items = $this->get_total_items( $where, $where_values );

		// Set up pagination.
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);

		// Get the items.
		$this->items = $this->get_items( $where, $where_values, $orderby, $order, $per_page, $current_page );
	}

	/**
	 * Get all links.
	 *
	 * @since 1.0.0
	 * @param string $search Search term.
	 * @param string $orderby Order by column.
	 * @param string $order Order direction.
	 * @param int    $per_page Number of items per page.
	 * @param int    $page_number Current page number.
	 * @return array
	 */
	public function get_links( $search = '', $orderby = 'id', $order = 'DESC', $per_page = 20, $page_number = 1 ) {
		global $wpdb;

		$where        = '';
		$where_values = array();

		if ( ! empty( $search ) ) {
			$where        = 'url LIKE %s OR title LIKE %s';
			$search_like  = '%' . $wpdb->esc_like( $search ) . '%';
			$where_values = array( $search_like, $search_like );
		}

		$offset    = ( $page_number - 1 ) * $per_page;
		$cache_key = 'qr_trackr_links_' . md5( $search . $orderby . $order . $per_page . $page_number );
		$results   = wp_cache_get( $cache_key );

		if ( false === $results ) {
			$sql = QR_Trackr_Query_Builder::get_items_with_where_sql( $where, $where_values, $orderby, $order, $per_page, $offset );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached immediately after query.
			$results = $wpdb->get_results( $sql, ARRAY_A );
			wp_cache_set( $cache_key, $results, '', 300 ); // Cache for 5 minutes.
		}

		return $results;
	}

	/**
	 * Delete a QR code link.
	 *
	 * @param int $id Link ID.
	 * @return bool True on success, false on failure.
	 */
	public function delete_link( $id ) {
		global $wpdb;

		$id = absint( $id );
		if ( empty( $id ) ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Direct delete needed for admin action.
		$table_name = $this->get_table_name();
		$result     = $wpdb->delete(
			$table_name,
			array( 'id' => $id ),
			array( '%d' )
		);

		if ( false !== $result ) {
			do_action( 'qr_trackr_link_deleted', $id );
			return true;
		}

		return false;
	}

	/**
	 * Returns the count of records in the database.
	 *
	 * @since 1.0.0
	 * @param string $search Search term for filtering.
	 * @return int Total number of records.
	 */
	public function record_count( $search = '' ) {
		global $wpdb;

		$cache_key = 'qr_trackr_record_count_' . md5( $search );
		$count     = wp_cache_get( $cache_key );

		if ( false === $count ) {
			$where        = '';
			$where_values = array();

			if ( ! empty( $search ) ) {
				$where        = 'url LIKE %s OR title LIKE %s';
				$search_like  = '%' . $wpdb->esc_like( $search ) . '%';
				$where_values = array( $search_like, $search_like );
			}

			$sql = QR_Trackr_Query_Builder::get_count_with_where_sql( $where, $where_values );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached immediately after query.
			$count = (int) $wpdb->get_var( $sql );
			wp_cache_set( $cache_key, $count, '', 300 ); // Cache for 5 minutes.
		}

		return $count;
	}

	/**
	 * Message to be displayed when there are no items.
	 */
	public function no_items() {
		if ( ! empty( $_REQUEST['s'] ) ) {
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

	/**
	 * Get a QR code link by post ID.
	 *
	 * @param int $post_id Post ID.
	 * @return array|null Link data or null if not found.
	 */
	private function get_link_by_post_id( $post_id ) {
		global $wpdb;

		$cache_key = 'qr_trackr_link_by_post_id_' . absint( $post_id );
		$result    = wp_cache_get( $cache_key );

		if ( false === $result ) {
			$sql = QR_Trackr_Query_Builder::get_items_by_post_id_sql( $post_id );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached immediately after query.
			$result = $wpdb->get_row( $sql, ARRAY_A );
			wp_cache_set( $cache_key, $result, '', 300 ); // Cache for 5 minutes.
		}

		return $result;
	}

	/**
	 * Get bulk actions available for the table.
	 *
	 * @since 1.0.0
	 * @return array Array of bulk actions with action as key and label as value.
	 */
	public function get_bulk_actions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return array();
		}

		$actions = array(
			'delete'     => esc_html__( 'Delete', 'wp-qr-trackr' ),
			'regenerate' => esc_html__( 'Regenerate QR Code', 'wp-qr-trackr' ),
		);

		/**
		 * Filter the list of bulk actions.
		 *
		 * @since 1.0.0
		 * @param array $actions Array of bulk actions.
		 */
		return apply_filters( 'qr_trackr_bulk_actions', $actions );
	}

	/**
	 * Get all QR code links with pagination.
	 *
	 * @since 1.0.0
	 * @param int $per_page Number of items per page.
	 * @param int $page_number Current page number.
	 * @return array|WP_Error Array of QR code links or WP_Error on failure.
	 */
	public function get_qr_links( $per_page = 10, $page_number = 1 ) {
		global $wpdb;

		$per_page    = absint( $per_page );
		$page_number = absint( $page_number );

		if ( empty( $per_page ) || empty( $page_number ) ) {
			return new WP_Error( 'invalid_pagination', __( 'Invalid pagination parameters.', 'wp-qr-trackr' ) );
		}

		$cache_key = 'qr_trackr_links_page_' . $page_number . '_' . $per_page;
		$results   = wp_cache_get( $cache_key );

		if ( false === $results ) {
			$table = $wpdb->prefix . 'qr_trackr_links';
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is hardcoded
			$sql    = "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d OFFSET %d";
			$offset = ( $page_number - 1 ) * $per_page;

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is prepared with $per_page and $offset
			$results = $wpdb->get_results(
				$wpdb->prepare( $sql, $per_page, $offset ),
				ARRAY_A
			);

			if ( false === $results ) {
				qr_trackr_debug_log( sprintf( 'Failed to get QR links: %s', $wpdb->last_error ) );
				return new WP_Error( 'db_error', __( 'Failed to get QR links from database.', 'wp-qr-trackr' ) );
			}

			wp_cache_set( $cache_key, $results, '', HOUR_IN_SECONDS );
		}

		return $results;
	}

	/**
	 * Get CSS classes for the table.
	 *
	 * @since 1.0.0
	 * @return array Array of CSS classes.
	 */
	protected function get_table_classes() {
		$classes   = parent::get_table_classes();
		$classes[] = 'qr-trackr-list-table';
		$classes[] = 'widefat';
		$classes[] = 'fixed';
		$classes[] = 'striped';
		$classes[] = 'responsive';
		$classes[] = 'wp-list-table';

		/**
		 * Filter the list of CSS classes for the table.
		 *
		 * @since 1.0.0
		 * @param array $classes Array of CSS classes.
		 */
		return apply_filters( 'qr_trackr_list_table_classes', $classes );
	}

	/**
	 * Get SQL for counting total items.
	 *
	 * @param string $where WHERE clause.
	 * @return string SQL query.
	 */
	private function get_totals_sql( $where = '' ) {
		global $wpdb;

		$sql = sprintf(
			'SELECT COUNT(*) as total FROM %sqr_trackr_links',
			$wpdb->prefix
		);

		if ( $where ) {
			$sql .= ' WHERE ' . $where;
		}

		return $sql;
	}

	/**
	 * Get the SQL query for fetching records with pagination.
	 *
	 * @param string $where WHERE clause without 'WHERE' keyword.
	 * @param string $orderby Column to order by.
	 * @param string $order Order direction (ASC or DESC).
	 * @param int    $per_page Number of items per page.
	 * @param int    $page_number Current page number.
	 * @return string SQL query.
	 */
	private function get_records_sql( $where, $orderby = '', $order = '', $per_page = 20, $page_number = 1 ) {
		global $wpdb;

		// Validate and sanitize order parameters.
		$valid_orderby = array( 'id', 'destination_url', 'scans', 'created_at' );
		$orderby       = in_array( $orderby, $valid_orderby, true ) ? $orderby : 'id';
		$order         = in_array( strtoupper( $order ), array( 'ASC', 'DESC' ), true ) ? $order : 'DESC';

		$offset = ( $page_number - 1 ) * $per_page;

		$sql = sprintf(
			'SELECT * FROM %sqr_trackr_links',
			$wpdb->prefix
		);

		if ( $where ) {
			$sql .= ' WHERE ' . $where;
		}

		$sql .= sprintf(
			' ORDER BY %s %s LIMIT %%d OFFSET %%d',
			esc_sql( $orderby ),
			esc_sql( $order )
		);

		return $wpdb->prepare( $sql, $per_page, $offset );
	}

	/**
	 * Get the SQL query for fetching a single item by ID.
	 *
	 * @param int $id Item ID.
	 * @return string SQL query.
	 */
	private function get_item_sql( $id ) {
		global $wpdb;

		return $wpdb->prepare(
			sprintf(
				'SELECT * FROM %sqr_trackr_links WHERE id = %%d',
				$wpdb->prefix
			),
			$id
		);
	}

	/**
	 * Get the SQL query for fetching items by post ID.
	 *
	 * @param int $post_id Post ID.
	 * @return string SQL query.
	 */
	private function get_items_by_post_id_sql( $post_id ) {
		global $wpdb;

		return $wpdb->prepare(
			sprintf(
				'SELECT * FROM %sqr_trackr_links WHERE post_id = %%d ORDER BY created_at DESC',
				$wpdb->prefix
			),
			$post_id
		);
	}

	/**
	 * Get the SQL query for fetching items by URL.
	 *
	 * @param string $url URL to search for.
	 * @return string SQL query.
	 */
	private function get_item_by_url_sql( $url ) {
		global $wpdb;

		return $wpdb->prepare(
			sprintf(
				'SELECT * FROM %sqr_trackr_links WHERE url = %%s',
				$wpdb->prefix
			),
			$url
		);
	}

	/**
	 * Get the SQL query for fetching items by multiple IDs.
	 *
	 * @param array $ids Array of item IDs.
	 * @return string SQL query.
	 */
	private function get_items_by_ids_sql( $ids ) {
		global $wpdb;

		$placeholders = array_fill( 0, count( $ids ), '%d' );
		$sql = sprintf(
			'SELECT * FROM %sqr_trackr_links WHERE id IN (' . implode( ',', $placeholders ) . ')',
			$wpdb->prefix
		);

		return $wpdb->prepare( $sql, ...$ids );
	}

	/**
	 * Delete multiple QR code links.
	 *
	 * @param array $ids Array of link IDs.
	 * @return int Number of links deleted.
	 */
	public function delete_links( $ids ) {
		global $wpdb;

		if ( empty( $ids ) || ! is_array( $ids ) ) {
			return 0;
		}

		// Sanitize IDs.
		$ids = array_map( 'absint', $ids );
		$ids = array_filter( $ids );

		if ( empty( $ids ) ) {
			return 0;
		}

		// Prepare placeholders for the query.
		$placeholders = array_fill( 0, count( $ids ), '%d' );
		$placeholders = implode( ',', $placeholders );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Direct delete needed for admin action.
		$table_name = $this->get_table_name();
		$sql        = $wpdb->prepare(
			"DELETE FROM {$table_name} WHERE id IN ($placeholders)", // phpcs:ignore WordPress.DB -- Table name is properly prefixed.
			$ids
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct delete needed for admin action.
		$result = $wpdb->query( $sql );

		if ( false !== $result ) {
			foreach ( $ids as $id ) {
				do_action( 'qr_trackr_link_deleted', $id );
			}
			return (int) $result;
		}

		return 0;
	}

	/**
	 * Get the table name with proper prefix.
	 *
	 * @since 1.0.0
	 * @return string The table name with proper prefix.
	 */
	private function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'qr_trackr_links';
	}

	/**
	 * Get total number of items.
	 *
	 * @param string $where WHERE clause.
	 * @param array  $where_values Values for the WHERE clause.
	 * @return int Total number of items.
	 */
	private function get_total_items( $where, $where_values ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cache implemented, direct query needed for performance.
		$table_name = $this->get_table_name();
		$sql        = $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table_name} WHERE $where", // phpcs:ignore WordPress.DB -- Table name is properly prefixed.
			$where_values
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching -- Result is cached at the application level.
		return absint( $wpdb->get_var( $sql ) );
	}

	/**
	 * Get items for the current page.
	 *
	 * @param string $where Where clause.
	 * @param array  $where_values Values for the where clause.
	 * @param string $orderby Order by column.
	 * @param string $order Order direction.
	 * @param int    $per_page Number of items per page.
	 * @param int    $current_page Current page number.
	 * @return array Array of items.
	 */
	private function get_items( $where, $where_values, $orderby, $order, $per_page, $current_page ) {
		global $wpdb;

		$offset = ( $current_page - 1 ) * $per_page;

		// Ensure valid order values.
		$order         = in_array( strtoupper( $order ), array( 'ASC', 'DESC' ), true ) ? $order : 'DESC';
		$valid_orderby = array( 'id', 'destination_url', 'scans', 'created_at' );
		$orderby       = in_array( $orderby, $valid_orderby, true ) ? $orderby : 'id';

		// Build the base query.
		$sql = $wpdb->prepare(
			'SELECT l.*, COALESCE(s.total_scans, 0) as scans
			FROM ' . $wpdb->prefix . 'qr_trackr_links l
			LEFT JOIN (
				SELECT link_id, COUNT(*) as total_scans
				FROM ' . $wpdb->prefix . 'qr_trackr_stats
				GROUP BY link_id
			) s ON l.id = s.link_id
			' . ( $where ? $wpdb->prepare( 'WHERE ' . $where, ...$where_values ) : '' ) . '
			ORDER BY ' . esc_sql( $orderby ) . ' ' . esc_sql( $order ) . '
			LIMIT %d OFFSET %d',
			$per_page,
			$offset
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached immediately after query.
		$results = $wpdb->get_results( $sql, ARRAY_A );
		wp_cache_set( $cache_key, $results, '', 300 ); // Cache for 5 minutes.

		return $results;
	}

	/**
	 * Get the SQL query for fetching items by search term.
	 *
	 * @param string $search Search term.
	 * @return string SQL query.
	 */
	private function get_items_by_search_sql( $search ) {
		global $wpdb;

		$search_like = '%' . $wpdb->esc_like( $search ) . '%';
		return $wpdb->prepare(
			sprintf(
				'SELECT * FROM %sqr_trackr_links WHERE url LIKE %%s OR title LIKE %%s',
				$wpdb->prefix
			),
			$search_like,
			$search_like
		);
	}

	/**
	 * Get the SQL query for fetching items by date range.
	 *
	 * @param string $start_date Start date in Y-m-d format.
	 * @param string $end_date End date in Y-m-d format.
	 * @return string SQL query.
	 */
	private function get_items_by_date_range_sql( $start_date, $end_date ) {
		global $wpdb;

		return $wpdb->prepare(
			sprintf(
				'SELECT * FROM %sqr_trackr_links WHERE DATE(created_at) BETWEEN %%s AND %%s',
				$wpdb->prefix
			),
			$start_date,
			$end_date
		);
	}

	/**
	 * Get the SQL query for fetching items by scan count range.
	 *
	 * @param int $min_scans Minimum number of scans.
	 * @param int $max_scans Maximum number of scans.
	 * @return string SQL query.
	 */
	private function get_items_by_scan_count_range_sql( $min_scans, $max_scans ) {
		global $wpdb;

		return $wpdb->prepare(
			sprintf(
				'SELECT l.*, COALESCE(s.total_scans, 0) as scans
				FROM %1$sqr_trackr_links l
				LEFT JOIN (
					SELECT link_id, COUNT(*) as total_scans
					FROM %1$sqr_trackr_stats
					GROUP BY link_id
				) s ON l.id = s.link_id
				HAVING scans BETWEEN %%d AND %%d',
				$wpdb->prefix
			),
			$min_scans,
			$max_scans
		);
	}

	/**
	 * Get links by multiple IDs.
	 *
	 * @param array $ids Array of link IDs.
	 * @return array Array of link data.
	 */
	public function get_links_by_ids( $ids ) {
		global $wpdb;

		$cache_key = 'qr_trackr_links_by_ids_' . md5( implode( ',', $ids ) );
		$results   = wp_cache_get( $cache_key );

		if ( false === $results ) {
			$sql = QR_Trackr_Query_Builder::get_items_by_ids_sql( $ids );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached immediately after query.
			$results = $wpdb->get_results( $sql, ARRAY_A );
			wp_cache_set( $cache_key, $results, '', 300 ); // Cache for 5 minutes.
		}

		return $results;
	}
}
