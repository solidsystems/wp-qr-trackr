<?php
/**
 * QR Trackr Query Builder Class
 *
 * @package QR_Trackr
 * @since   1.0.0
 */

/**
 * Class for building SQL queries for QR Trackr.
 *
 * @since 1.0.0
 */
class QR_Trackr_Query_Builder {

	/**
	 * Get the SQL query for fetching items by post ID.
	 *
	 * @param int $post_id Post ID.
	 * @return string SQL query.
	 */
	public static function get_items_by_post_id_sql( $post_id ) {
		global $wpdb;

		return $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}qr_trackr_links WHERE post_id = %d ORDER BY created_at DESC",
			absint( $post_id )
		);
	}

	/**
	 * Get the SQL query for fetching the most recent item by post ID.
	 *
	 * @param int $post_id Post ID.
	 * @return string SQL query.
	 */
	public static function get_most_recent_item_by_post_id_sql( $post_id ) {
		global $wpdb;

		return $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}qr_trackr_links WHERE post_id = %d ORDER BY created_at DESC LIMIT 1",
			absint( $post_id )
		);
	}

	/**
	 * Get the SQL query for fetching stats by link ID.
	 *
	 * @param int $link_id Link ID.
	 * @return string SQL query.
	 */
	public static function get_stats_by_link_id_sql( $link_id ) {
		global $wpdb;

		return $wpdb->prepare(
			"SELECT DATE(created_at) as date, COUNT(*) as count FROM {$wpdb->prefix}qr_trackr_stats WHERE link_id = %d GROUP BY DATE(created_at) ORDER BY date DESC",
			absint( $link_id )
		);
	}

	/**
	 * Get the SQL query for checking if a table exists.
	 *
	 * @param string $table_name Table name without prefix.
	 * @return string SQL query.
	 */
	public static function get_table_exists_sql( $table_name ) {
		global $wpdb;

		return $wpdb->prepare(
			'SHOW TABLES LIKE %s',
			$wpdb->prefix . $table_name
		);
	}

	/**
	 * Get the SQL query for getting table columns.
	 *
	 * @param string $table_name Table name without prefix.
	 * @return string SQL query.
	 */
	public static function get_table_columns_sql( $table_name ) {
		global $wpdb;

		return $wpdb->prepare(
			'SHOW COLUMNS FROM %s',
			$wpdb->prefix . $table_name
		);
	}

	/**
	 * Get the SQL query for adding a column to a table.
	 *
	 * @param string $table_name Table name without prefix.
	 * @param string $column Column name.
	 * @param string $definition Column definition.
	 * @return string SQL query.
	 */
	public static function get_add_column_sql( $table_name, $column, $definition ) {
		global $wpdb;

		return $wpdb->prepare(
			'ALTER TABLE %s ADD COLUMN %s %s',
			$wpdb->prefix . $table_name,
			$column,
			$definition
		);
	}

	/**
	 * Get the SQL query for fetching items with dynamic WHERE clause and ORDER BY.
	 *
	 * @param string $where WHERE clause without 'WHERE' keyword.
	 * @param array  $where_values Values for the WHERE clause placeholders.
	 * @param string $orderby Column to order by.
	 * @param string $order Order direction (ASC or DESC).
	 * @param int    $per_page Number of items per page.
	 * @param int    $offset Offset for pagination.
	 * @return string SQL query.
	 */
	public static function get_items_with_where_sql( $where = '', $where_values = array(), $orderby = 'id', $order = 'DESC', $per_page = 20, $offset = 0 ) {
		global $wpdb;

		// Validate and sanitize order parameters.
		$valid_orderby = array( 'id', 'destination_url', 'scans', 'created_at' );
		$orderby = in_array( $orderby, $valid_orderby, true ) ? $orderby : 'id';
		$order = in_array( strtoupper( $order ), array( 'ASC', 'DESC' ), true ) ? $order : 'DESC';

		// Build the base query.
		$sql = sprintf(
			'SELECT l.*, COALESCE(s.total_scans, 0) as scans
			FROM %1$sqr_trackr_links l
			LEFT JOIN (
				SELECT link_id, COUNT(*) as total_scans
				FROM %1$sqr_trackr_stats
				GROUP BY link_id
			) s ON l.id = s.link_id',
			$wpdb->prefix
		);

		// Add WHERE clause if provided.
		if ( $where ) {
			$sql .= ' WHERE ' . $where;
		}

		// Add ORDER BY and LIMIT clauses.
		$sql .= sprintf( ' ORDER BY %s %s LIMIT %%d OFFSET %%d', esc_sql( $orderby ), esc_sql( $order ) );

		// Prepare the final query with all values.
		$query_values = array_merge( $where_values, array( $per_page, $offset ) );
		return $wpdb->prepare( $sql, ...$query_values );
	}

	/**
	 * Get the SQL query for counting items with dynamic WHERE clause.
	 *
	 * @param string $where WHERE clause without 'WHERE' keyword.
	 * @param array  $where_values Values for the WHERE clause placeholders.
	 * @return string SQL query.
	 */
	public static function get_count_with_where_sql( $where = '', $where_values = array() ) {
		global $wpdb;

		$sql = sprintf(
			'SELECT COUNT(*) FROM %sqr_trackr_links',
			$wpdb->prefix
		);

		if ( $where ) {
			$sql .= ' WHERE ' . $where;
			return $wpdb->prepare( $sql, ...$where_values );
		}

		return $sql;
	}

	/**
	 * Get the SQL query for fetching items by URL.
	 *
	 * @param string $url URL to search for.
	 * @return string SQL query.
	 */
	public static function get_item_by_url_sql( $url ) {
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
	public static function get_items_by_ids_sql( $ids ) {
		global $wpdb;

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		return $wpdb->prepare(
			sprintf(
				'SELECT * FROM %sqr_trackr_links WHERE id IN (%s)',
				$wpdb->prefix,
				$placeholders
			),
			...$ids
		);
	}
}
