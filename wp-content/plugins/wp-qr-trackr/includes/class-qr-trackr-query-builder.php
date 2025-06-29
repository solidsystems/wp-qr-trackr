<?php
/**
 * QR Trackr Query Builder Class
 *
 * Handles database queries for QR code tracking.
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
	 * Get the stats table name with proper prefix.
	 *
	 * @since 1.0.0
	 * @return string The stats table name with proper prefix.
	 */
	private function get_stats_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'qr_trackr_stats';
	}

	/**
	 * Build a SELECT query with conditions.
	 *
	 * @param array  $conditions Array of conditions.
	 * @param string $orderby    Column to order by.
	 * @param string $order      Order direction (ASC/DESC).
	 * @param int    $limit      Number of rows to return.
	 * @param int    $offset     Number of rows to skip.
	 * @return array {
	 *     Query data.
	 *
	 *     @type string $query  The prepared query string.
	 *     @type array  $values The values for the prepared query.
	 * }
	 */
	private function build_select_query( $conditions, $orderby, $order, $limit = null, $offset = null ) {
		global $wpdb;

		$where  = array();
		$values = array();

		foreach ( $conditions as $field => $value ) {
			if ( is_array( $value ) ) {
				$placeholders = implode( ',', array_fill( 0, count( $value ), '%d' ) );
				$where[]      = "$field IN ($placeholders)";
				$values       = array_merge( $values, $value );
			} else {
				$where[]  = "$field = %s";
				$values[] = $value;
			}
		}

		$where_clause = ! empty( $where ) ? ' WHERE ' . implode( ' AND ', $where ) : '';
		$order        = in_array( strtoupper( $order ), array( 'ASC', 'DESC' ), true ) ? $order : 'DESC';
		$order_clause = sprintf( ' ORDER BY %s %s', esc_sql( $orderby ), esc_sql( $order ) );

		$query = "SELECT * FROM {$wpdb->prefix}qr_trackr_links" . $where_clause . $order_clause;

		if ( ! is_null( $limit ) ) {
			$query   .= ' LIMIT %d';
			$values[] = $limit;

			if ( ! is_null( $offset ) ) {
				$query   .= ' OFFSET %d';
				$values[] = $offset;
			}
		}

		return array(
			'query'  => $query,
			'values' => $values,
		);
	}

	/**
	 * Execute a SELECT query and return results.
	 *
	 * @param array  $conditions Array of conditions.
	 * @param string $orderby    Column to order by.
	 * @param string $order      Order direction (ASC/DESC).
	 * @param int    $limit      Number of rows to return.
	 * @param int    $offset     Number of rows to skip.
	 * @return array|null Array of results or null on error.
	 */
	public function get_results( $conditions = array(), $orderby = 'id', $order = 'DESC', $limit = null, $offset = null ) {
		global $wpdb;

		$query_data = $this->build_select_query( $conditions, $orderby, $order, $limit, $offset );
		$cache_key  = 'qr_trackr_query_' . md5( wp_json_encode( $query_data ) );
		$results    = wp_cache_get( $cache_key );

		if ( false === $results ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared -- Query is built with proper placeholders and prepared below.
			$results = $wpdb->get_results(
				// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.NotPrepared -- Dynamic query built with validated placeholders.
				$wpdb->prepare( $query_data['query'], ...$query_data['values'] ),
				ARRAY_A
			);

			if ( ! is_null( $results ) ) {
				wp_cache_set( $cache_key, $results, '', 300 ); // Cache for 5 minutes.
			}
		}

		return $results;
	}

	/**
	 * Get a single row by ID.
	 *
	 * @param int $id The row ID.
	 * @return array|null The row data or null if not found.
	 */
	public function get_by_id( $id ) {
		global $wpdb;

		$cache_key = 'qr_trackr_item_' . $id;
		$result    = wp_cache_get( $cache_key );

		if ( false === $result ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Result is cached.
			$result = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}qr_trackr_links WHERE id = %d",
					$id
				),
				ARRAY_A
			);

			if ( ! is_null( $result ) ) {
				wp_cache_set( $cache_key, $result, '', 300 ); // Cache for 5 minutes.
			}
		}

		return $result;
	}

	/**
	 * Insert a new row.
	 *
	 * @param array $data The data to insert.
	 * @return int|false The inserted ID or false on failure.
	 */
	public function insert( $data ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Insert operation needs to be immediate.
		$result = $wpdb->insert(
			"{$wpdb->prefix}qr_trackr_links",
			$data
		);

		if ( false !== $result ) {
			return $wpdb->insert_id;
		}

		return false;
	}

	/**
	 * Update a row by ID.
	 *
	 * @param int   $id   The row ID.
	 * @param array $data The data to update.
	 * @return bool True on success, false on failure.
	 */
	public function update( $id, $data ) {
		global $wpdb;
		$table_name = $this->get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Update operation needs to be immediate.
		$result = $wpdb->update(
			$table_name,
			$data,
			array( 'id' => $id )
		);

		if ( false !== $result ) {
			$cache_key = 'qr_trackr_item_' . $id;
			wp_cache_delete( $cache_key );
			return true;
		}

		return false;
	}

	/**
	 * Delete a row by ID.
	 *
	 * @param int $id The row ID.
	 * @return bool True on success, false on failure.
	 */
	public function delete( $id ) {
		global $wpdb;
		$table_name = $this->get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Delete operation needs to be immediate.
		$result = $wpdb->delete(
			$table_name,
			array( 'id' => $id ),
			array( '%d' )
		);

		if ( false !== $result ) {
			$cache_key = 'qr_trackr_item_' . $id;
			wp_cache_delete( $cache_key );
			return true;
		}

		return false;
	}

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
		$orderby       = in_array( $orderby, $valid_orderby, true ) ? $orderby : 'id';
		$order         = in_array( strtoupper( $order ), array( 'ASC', 'DESC' ), true ) ? $order : 'DESC';

		// Build the base query.
		$sql = "SELECT l.*, COALESCE(s.total_scans, 0) as scans FROM {$wpdb->prefix}qr_trackr_links l LEFT JOIN (SELECT link_id, COUNT(*) as total_scans FROM {$wpdb->prefix}qr_trackr_stats GROUP BY link_id) s ON l.id = s.link_id";

		// Add WHERE clause if provided.
		if ( $where ) {
			$sql .= ' WHERE ' . $where;
		}

		// Add ORDER BY and LIMIT clauses.
		$sql .= sprintf( ' ORDER BY %s %s LIMIT %%d OFFSET %%d', esc_sql( $orderby ), esc_sql( $order ) );

		// Prepare the final query with all values.
		$query_values = array_merge( $where_values, array( $per_page, $offset ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SQL is built with validated parameters and prepared with placeholders.
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

		$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}qr_trackr_links";

		if ( $where ) {
			$sql .= ' WHERE ' . $where;
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- WHERE clause is provided by calling code with proper placeholders.
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
			"SELECT * FROM {$wpdb->prefix}qr_trackr_links WHERE url = %s",
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
		$placeholders = array_fill( 0, count( $ids ), '%d' );
		$sql          = "SELECT * FROM {$wpdb->prefix}qr_trackr_links WHERE id IN (" . implode( ',', $placeholders ) . ')';
		// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.NotPrepared -- Dynamic array of IDs prepared with generated placeholders.
		return $wpdb->prepare( $sql, ...$ids );
	}
}
