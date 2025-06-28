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
}
