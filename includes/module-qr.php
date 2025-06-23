<?php
// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name interpolation is required for dynamic table prefixing in WordPress. All other variables are safely prepared.
$sql = "SELECT * FROM `{$table_name}` WHERE id = %d";
// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name interpolation is required for dynamic table prefixing in WordPress. All other variables are safely prepared.
$link = $wpdb->get_row(
	$wpdb->prepare(
		$sql,
		$link_id
	)
);

// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name interpolation is required for dynamic table prefixing in WordPress. All other variables are safely prepared.
$sql = "SELECT * FROM `{$table}` WHERE post_id = %d";
// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name interpolation is required for dynamic table prefixing in WordPress. All other variables are safely prepared.
$new_link = $wpdb->get_row( $wpdb->prepare( $sql, $post_id ) );

// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name interpolation is required for dynamic table prefixing in WordPress. All other variables are safely prepared.
$sql = "SELECT * FROM `{$table}` WHERE id = %d";
// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name interpolation is required for dynamic table prefixing in WordPress. All other variables are safely prepared.
$link = $wpdb->get_row( $wpdb->prepare( $sql, $link_id ) );
