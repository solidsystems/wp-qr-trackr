<?php
/**
 * QR Trackr List Table Class
 *
 * Provides a custom WP_List_Table for displaying and managing QR Trackr links in the WordPress admin.
 *
 * @package QR_Trackr
 */

// NOTE: For full WordPress Coding Standards compliance, this file could be renamed to match the class name (e.g., class-qr-trackr-list-table.php).

if ( file_exists( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
if ( ! class_exists( 'WP_List_Table' ) ) {
	/**
	 * Minimal WP_List_Table stub for environments where it is not loaded.
	 */
	class WP_List_Table {
		public function __construct() {}
	}
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
	 * Constructor for QR_Trackr_List_Table.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'qr_trackr_link',
				'plural'   => 'qr_trackr_links',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Get columns for the table.
	 *
	 * @return array Columns for the table.
	 */
	public function get_columns() {
		return array(
			'id'              => 'ID',
			'post_title'      => 'Post/Page',
			'destination_url' => 'Destination Link',
			'scans'           => 'Scans',
			'created_at'      => 'Created',
			'qr_code'         => 'QR Code',
			'tracking_link'   => 'Tracking Link',
			'actions'         => 'Actions',
		);
	}

	/**
	 * Get sortable columns.
	 *
	 * @return array Sortable columns.
	 */
	public function get_sortable_columns() {
		return array(
			'id'              => array( 'id', false ),
			'post_title'      => array( 'post_title', false ),
			'destination_url' => array( 'destination_url', false ),
			'scans'           => array( 'scans', false ),
		);
	}

	/**
	 * Prepare items for display.
	 *
	 * @return void
	 */
	public function prepare_items() {
		global $wpdb;
		$per_page = $this->get_items_per_page( 'qr_trackr_links_per_page', 20 );
		$paged    = $this->get_pagenum();
		$orderby  = ( ! empty( $_REQUEST['orderby'] ) ) ? sanitize_sql_orderby( wp_unslash( $_REQUEST['orderby'] ) ) : 'id';
		$order    = ( ! empty( $_REQUEST['order'] ) ) ? strtoupper( sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) ) : 'DESC';
		$order    = in_array( $order, array( 'ASC', 'DESC' ), true ) ? $order : 'DESC';

		$links_table = $wpdb->prefix . 'qr_trackr_links'; // Safe table name.
		$scans_table = $wpdb->prefix . 'qr_trackr_scans'; // Safe table name.

		$where  = 'WHERE 1=1';
		$join   = '';
		$params = array();

		// Filter: post type.
		if ( ! empty( $_REQUEST['filter_post_type'] ) ) {
			$this->post_type_filter = sanitize_text_field( wp_unslash( $_REQUEST['filter_post_type'] ) );
			$join                  .= " JOIN {$wpdb->posts} p ON p.ID = l.post_id ";
			$where                 .= $wpdb->prepare( ' AND p.post_type = %s', $this->post_type_filter );
		} else {
			$join .= " JOIN {$wpdb->posts} p ON p.ID = l.post_id ";
		}
		// Filter: post title.
		if ( ! empty( $_REQUEST['s'] ) ) {
			$search = '%' . $wpdb->esc_like( sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) ) . '%';
			$where .= $wpdb->prepare( ' AND p.post_title LIKE %s', $search );
		}
		// Filter: destination link.
		if ( ! empty( $_REQUEST['filter_destination'] ) ) {
			$this->destination_filter = sanitize_text_field( wp_unslash( $_REQUEST['filter_destination'] ) );
			$where                   .= $wpdb->prepare( ' AND l.destination_url LIKE %s', '%' . $wpdb->esc_like( $this->destination_filter ) . '%' );
		}
		// Filter: scans (min).
		if ( ! empty( $_REQUEST['filter_scans'] ) ) {
			$this->scans_filter = intval( wp_unslash( $_REQUEST['filter_scans'] ) );
			$where             .= $wpdb->prepare(
				' AND (
                SELECT COUNT(*) FROM `' . $scans_table . '` s WHERE s.post_id = l.post_id AND s.scan_time >= l.created_at
            ) >= %d',
				$this->scans_filter
			);
		}

		// Count total (table names are safe, $where and $join are sanitized above).
		$count_sql   = 'SELECT COUNT(*) FROM `' . $links_table . '` l ' . $join . ' ' . $where;
		$total_items = $wpdb->get_var( $count_sql );

		// Main query (table names are safe, $where, $join, $orderby, $order are sanitized above).
		$sql         = 'SELECT l.*, p.post_title, p.post_type,
			(
				SELECT COUNT(*) FROM `' . $scans_table . '` s WHERE s.post_id = l.post_id AND s.scan_time >= l.created_at
			) as scans
		FROM `' . $links_table . '` l
		' . $join . '
		' . $where . '
		ORDER BY ' . $orderby . ' ' . $order . '
		LIMIT %d OFFSET %d';
		$sql         = $wpdb->prepare( $sql, $per_page, ( $paged - 1 ) * $per_page );
		$this->links = $wpdb->get_results( $sql );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);
	}

	/**
	 * Render default column output.
	 *
	 * @param object $item        The current item.
	 * @param string $column_name The column name.
	 * @return string Column output HTML.
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'id':
				return intval( $item->id );
			case 'post_title':
				$edit_link = get_edit_post_link( $item->post_id );
				return esc_html( $item->post_title ) . ( $edit_link ? ' <br><a href="' . esc_url( $edit_link ) . '" target="_blank">Edit</a>' : '' );
			case 'destination_url':
				return esc_html( $item->destination_url );
			case 'scans':
				return intval( $item->scans );
			case 'created_at':
				return esc_html( $item->created_at );
			case 'qr_code':
				$qr_url = qr_trackr_generate_qr_image_for_link( $item->id );
				return $qr_url ? '<img src="' . esc_url( $qr_url ) . '" style="max-width:60px; display:block; margin-bottom:4px;" alt="QR Code"><a href="' . esc_url( $qr_url ) . '" download class="button">Download</a>' : '';
			case 'tracking_link':
				$tracking_link = trailingslashit( home_url() ) . 'qr-trackr/redirect/' . intval( $item->id );
				return '<a href="' . esc_url( $tracking_link ) . '" target="_blank">' . esc_html( $tracking_link ) . '</a>';
			case 'actions':
				return '<a href="#" class="qr-trackr-update-link" data-link-id="' . esc_attr( $item->id ) . '">Update Destination</a>';
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
}
