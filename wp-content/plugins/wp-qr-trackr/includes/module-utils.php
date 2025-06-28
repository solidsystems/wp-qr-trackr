<?php
/**
 * Utility module for QR Trackr plugin.
 *
 * Provides database helpers, sanitization, and general utility functions.
 *
 * @package QR_Trackr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( function_exists( 'qr_trackr_debug_log' ) ) {
	qr_trackr_debug_log( 'Loading module-utils.php...' );
}

/**
 * Safely get a value from an array by key.
 *
 * @param array  $arr           The array to search.
 * @param string $key           The key to look for.
 * @param mixed  $default_value Default value if key not found.
 * @return mixed Value from array or default.
 */
function qr_trackr_array_get( $arr, $key, $default_value = null ) {
	return isset( $arr[ $key ] ) ? $arr[ $key ] : $default_value;
}

/**
 * Sanitize a string for safe output.
 *
 * @param string $str The string to sanitize.
 * @return string Sanitized string.
 */
function qr_trackr_sanitize_output( $str ) {
	return esc_html( $str );
}

/**
 * Escape a URL for safe output.
 *
 * @param string $url The URL to escape.
 * @return string Escaped URL.
 */
function qr_trackr_escape_url( $url ) {
	return esc_url( $url );
}

/**
 * Get the most recent tracking link for a post.
 *
 * @param int $post_id Post ID.
 * @return object|null Most recent tracking link object or null.
 */
function qr_trackr_get_most_recent_tracking_link( $post_id ) {
	global $wpdb;
	$cache_key = 'qr_trackr_link_' . $post_id;
	$link      = wp_cache_get( $cache_key );

	if ( false !== $link ) {
		return $link;
	}

	$query = $wpdb->prepare(
		sprintf(
			'SELECT * FROM %s WHERE post_id = %%d ORDER BY created_at DESC LIMIT 1',
			$wpdb->prefix . 'qr_trackr_links'
		),
		$post_id
	);
	$link  = $wpdb->get_row( $query );
	wp_cache_set( $cache_key, $link );
	return $link;
}

/**
 * Render the HTML for the QR code list for a post.
 *
 * @param int $post_id Post ID.
 * @return string HTML output.
 */
function qr_trackr_render_qr_list_html( $post_id ) {
	global $wpdb;
	$cache_key = 'qr_trackr_links_list_' . $post_id;
	$links     = wp_cache_get( $cache_key );

	if ( false === $links ) {
		$query = $wpdb->prepare(
			sprintf(
				'SELECT * FROM %s WHERE post_id = %%d ORDER BY created_at DESC',
				$wpdb->prefix . 'qr_trackr_links'
			),
			$post_id
		);
		$links = $wpdb->get_results( $query );
		wp_cache_set( $cache_key, $links, '', 60 ); // Cache for 1 minute.
	}

	if ( empty( $links ) ) {
		return '<div class="qr-trackr-list"><p>' . esc_html__( 'No QR codes found.', 'wp-qr-trackr' ) . '</p></div>';
	}

	$html  = '<div class="qr-trackr-list"><table class="widefat"><thead><tr>';
	$html .= '<th>' . esc_html__( 'ID', 'wp-qr-trackr' ) . '</th><th>' . esc_html__( 'QR Code', 'wp-qr-trackr' ) . '</th><th>' . esc_html__( 'Tracking Link', 'wp-qr-trackr' ) . '</th>';
	$html .= '</tr></thead><tbody>';
	foreach ( $links as $link ) {
		$qr_urls       = qr_trackr_generate_qr_image_for_link( $link->id );
		$tracking_link = trailingslashit( home_url() ) . 'qr-trackr/redirect/' . intval( $link->id );
		$html         .= '<tr>';
		$html         .= '<td>' . intval( $link->id ) . '</td>';
		$html         .= '<td>';
		if ( ! empty( $qr_urls ) && ! empty( $qr_urls['png'] ) ) {
			$html .= '<img src="' . esc_url( $qr_urls['png'] ) . '" style="max-width:60px; display:block; margin-bottom:4px;" alt="QR Code">';
			$html .= '<span style="font-size:12px; color:#555;">';
			$html .= '<a href="' . esc_url( $qr_urls['png'] ) . '" download title="' . esc_attr__( 'Download PNG', 'wp-qr-trackr' ) . '" style="margin-right:8px; text-decoration:none;"><span class="dashicons dashicons-media-default" style="vertical-align:middle;"></span> PNG</a>';
			$html .= '<a href="' . esc_url( $qr_urls['svg'] ) . '" download title="' . esc_attr__( 'Download SVG', 'wp-qr-trackr' ) . '" style="text-decoration:none;"><span class="dashicons dashicons-media-code" style="vertical-align:middle;"></span> SVG</a>';
			$html .= '</span>';
		}
		$html .= '</td>';
		$html .= '<td><a href="' . esc_url( $tracking_link ) . '" target="_blank">' . esc_html( $tracking_link ) . '</a></td>';
		$html .= '</tr>';
	}
	$html .= '</tbody></table></div>';
	return $html;
}

/**
 * Get all tracking links.
 *
 * @return array
 */
function qr_trackr_get_all_links() {
	global $wpdb;
	$cache_key = 'qr_trackr_all_links';
	$links     = wp_cache_get( $cache_key );

	if ( false === $links ) {
		$query = sprintf(
			'SELECT * FROM %s',
			$wpdb->prefix . 'qr_trackr_links'
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Direct query is required for admin utility. Caching is implemented.
		$links = $wpdb->get_results( $query );
		wp_cache_set( $cache_key, $links, '', 300 ); // Cache for 5 minutes.
	}

	return $links;
}

/**
 * Get link by ID with caching.
 *
 * @since 1.0.0
 * @param int $id Link ID.
 * @return object|null Link object or null if not found.
 */
function qrc_get_link_by_id( $id ) {
	global $wpdb;

	$cache_key = 'qrc_link_' . absint( $id );
	$link      = wp_cache_get( $cache_key );

	if ( false === $link ) {
		$table = $wpdb->prefix . 'qr_trackr_links';
		$link  = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE id = %d",
				absint( $id )
			)
		);

		if ( $link ) {
			wp_cache_set( $cache_key, $link, '', 300 ); // Cache for 5 minutes.
		}
	}

	return $link;
}

/**
 * Get link by URL with caching.
 *
 * @since 1.0.0
 * @param string $url URL to look up.
 * @return object|null Link object or null if not found.
 */
function qrc_get_link_by_url( $url ) {
	global $wpdb;

	$cache_key = 'qrc_link_url_' . md5( $url );
	$link      = wp_cache_get( $cache_key );

	if ( false === $link ) {
		$table = $wpdb->prefix . 'qr_trackr_links';
		$link  = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE url = %s",
				esc_url_raw( $url )
			)
		);

		if ( $link ) {
			wp_cache_set( $cache_key, $link, '', 300 ); // Cache for 5 minutes.
		}
	}

	return $link;
}

/**
 * Get link statistics.
 *
 * @since 1.0.0
 * @param int $link_id Link ID.
 * @return array Link statistics.
 */
function qrc_get_link_stats( $link_id ) {
	global $wpdb;

	$table = $wpdb->prefix . 'qr_trackr_stats';
	$stats = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT DATE(created_at) as date, COUNT(*) as count 
			FROM {$table} 
			WHERE link_id = %d 
			GROUP BY DATE(created_at) 
			ORDER BY date DESC",
			absint( $link_id )
		)
	);

	return empty( $stats ) ? array() : $stats;
}

/**
 * Create a new link.
 *
 * @since 1.0.0
 * @param array $data Link data.
 * @return int|false The ID of the inserted link, or false on failure.
 */
function qrc_create_link( $data ) {
	global $wpdb;

	$table  = $wpdb->prefix . 'qr_trackr_links';
	$result = $wpdb->insert(
		$table,
		array(
			'url'        => esc_url_raw( $data['url'] ),
			'title'      => sanitize_text_field( $data['title'] ),
			'created_at' => current_time( 'mysql' ),
			'updated_at' => current_time( 'mysql' ),
		),
		array( '%s', '%s', '%s', '%s' )
	);

	return $result ? $wpdb->insert_id : false;
}

/**
 * Update an existing link.
 *
 * @since 1.0.0
 * @param int   $id Link ID.
 * @param array $data Link data.
 * @return bool True on success, false on failure.
 */
function qrc_update_link( $id, $data ) {
	global $wpdb;

	$table = $wpdb->prefix . 'qr_trackr_links';
	return (bool) $wpdb->update(
		$table,
		array(
			'url'        => esc_url_raw( $data['url'] ),
			'title'      => sanitize_text_field( $data['title'] ),
			'updated_at' => current_time( 'mysql' ),
		),
		array( 'id' => absint( $id ) ),
		array( '%s', '%s', '%s' ),
		array( '%d' )
	);
}

/**
 * Delete a link.
 *
 * @since 1.0.0
 * @param int $id Link ID.
 * @return bool True on success, false on failure.
 */
function qrc_delete_link( $id ) {
	global $wpdb;

	$table = $wpdb->prefix . 'qr_trackr_links';
	return (bool) $wpdb->delete(
		$table,
		array( 'id' => absint( $id ) ),
		array( '%d' )
	);
}

/**
 * Record a link visit.
 *
 * @since 1.0.0
 * @param int $link_id Link ID.
 * @return bool True on success, false on failure.
 */
function qrc_record_visit( $link_id ) {
	global $wpdb;

	$table = $wpdb->prefix . 'qr_trackr_stats';
	return (bool) $wpdb->insert(
		$table,
		array(
			'link_id'    => absint( $link_id ),
			'created_at' => current_time( 'mysql' ),
			'ip'         => sanitize_text_field( qrc_get_client_ip() ),
			'user_agent' => sanitize_text_field( qrc_get_user_agent() ),
		),
		array( '%d', '%s', '%s', '%s' )
	);
}

/**
 * Get all scans for a given link ID.
 *
 * @param int $link_id The link ID.
 * @return array
 */
function qr_trackr_get_scans_by_link_id( $link_id ) {
	global $wpdb;
	$cache_key = 'qr_trackr_scans_' . $link_id;
	$scans     = wp_cache_get( $cache_key );

	if ( false === $scans ) {
		$query = $wpdb->prepare(
			sprintf(
				'SELECT * FROM %s WHERE link_id = %%d',
				$wpdb->prefix . 'qr_trackr_scans'
			),
			$link_id
		);
		$scans = $wpdb->get_results( $query );
		wp_cache_set( $cache_key, $scans, '', 300 ); // Cache for 5 minutes.
	}

	return $scans;
}

/**
 * Create a new tracking link.
 *
 * @param string $url  The destination URL.
 * @param string $name The name of the link.
 * @return object|false The new link object or false on failure.
 */
function qr_trackr_create_link( $url, $name = '' ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';
	$result     = $wpdb->insert(
		$table_name,
		array(
			'destination_url' => $url,
			'name'            => $name,
			'qr_code'         => qr_trackr_generate_unique_qr_code(),
		),
		array(
			'%s',
			'%s',
			'%s',
		)
	);

	if ( ! $result ) {
		return false;
	}

	$link_id = $wpdb->insert_id;
	return qr_trackr_get_link_by_id( $link_id );
}

/**
 * Update an existing tracking link.
 *
 * @param int    $id   The link ID.
 * @param string $url  The new destination URL.
 * @param string $name The new name.
 * @return bool True on success, false on failure.
 */
function qr_trackr_update_link( $id, $url, $name = '' ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';
	$result     = $wpdb->update(
		$table_name,
		array(
			'destination_url' => $url,
			'name'            => $name,
		),
		array(
			'id' => $id,
		),
		array(
			'%s',
			'%s',
		),
		array(
			'%d',
		)
	);

	if ( $result ) {
		wp_cache_delete( 'qr_trackr_link_' . $id );
		wp_cache_delete( 'qr_trackr_all_links' );
	}

	return (bool) $result;
}

/**
 * Delete a tracking link.
 *
 * @param int $id The link ID.
 * @return bool True on success, false on failure.
 */
function qr_trackr_delete_link( $id ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';
	$result     = $wpdb->delete(
		$table_name,
		array(
			'id' => $id,
		),
		array(
			'%d',
		)
	);

	if ( $result ) {
		wp_cache_delete( 'qr_trackr_link_' . $id );
		wp_cache_delete( 'qr_trackr_all_links' );
	}

	return (bool) $result;
}

// Save post handler for updating destination URL.
add_action(
	'save_post',
	function ( $post_id ) {
		if ( isset( $_POST['qr_trackr_dest_nonce'], $_POST['qr_trackr_dest_url'], $_POST['qr_trackr_link_id'] ) ) {
			$nonce    = isset( $_POST['qr_trackr_dest_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['qr_trackr_dest_nonce'] ) ) : '';
			$link_id  = isset( $_POST['qr_trackr_link_id'] ) ? intval( wp_unslash( $_POST['qr_trackr_link_id'] ) ) : 0;
			$dest_url = isset( $_POST['qr_trackr_dest_url'] ) ? esc_url_raw( wp_unslash( $_POST['qr_trackr_dest_url'] ) ) : '';

			if ( ! wp_verify_nonce( $nonce, 'qr_trackr_update_dest_' . $post_id ) ) {
				return;
			}

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			global $wpdb;
			$table_name = $wpdb->prefix . 'qr_trackr_links';
			$result     = $wpdb->update(
				$table_name,
				array( 'destination_url' => $dest_url ),
				array( 'id' => $link_id ),
				array( '%s' ),
				array( '%d' )
			);

			if ( $result ) {
				wp_cache_delete( 'qr_trackr_link_' . $link_id );
				wp_cache_delete( 'qr_trackr_all_links' );
			}
		}
	}
);

// Migration/verification for qr_trackr_links table schema.
add_action(
	'init',
	function () {
		global $wpdb;

		// Check if table exists first.
		$table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$wpdb->prefix . 'qr_trackr_links'
			)
		);
		if ( ! $table_exists ) {
			qr_trackr_debug_log( 'Migration: Table does not exist, creating...' );
			qr_trackr_create_tables();
			return;
		}

		// Get current columns.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Schema check query, not user data.
		$columns = $wpdb->get_results(
			$wpdb->prepare(
				'SHOW COLUMNS FROM %s',
				$wpdb->prefix . 'qr_trackr_links'
			),
			ARRAY_A
		);
		if ( ! $columns ) {
			qr_trackr_debug_log( 'Migration: Failed to get columns.', $wpdb->last_error );
			return;
		}

		$expected = array(
			'id'              => 'bigint(20) NOT NULL AUTO_INCREMENT',
			'post_id'         => 'bigint(20) NOT NULL',
			'destination_url' => 'varchar(255) NOT NULL',
			'created_at'      => 'datetime NOT NULL DEFAULT CURRENT_TIMESTAMP',
			'updated_at'      => 'datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
			'access_count'    => 'bigint(20) NOT NULL DEFAULT 0',
			'last_accessed'   => 'datetime DEFAULT NULL',
		);

		$actual = array();
		foreach ( $columns as $col ) {
			$actual[ $col['Field'] ] = $col['Type'] . ( 'NO' === $col['Null'] ? ' NOT NULL' : '' ) .
				( $col['Default'] ? ' DEFAULT ' . $col['Default'] : '' ) .
				( $col['Extra'] ? ' ' . $col['Extra'] : '' );
		}

		$missing = array_diff_key( $expected, $actual );
		if ( ! empty( $missing ) ) {
			qr_trackr_debug_log( 'Migration: Missing columns in qr_trackr_links.', array_keys( $missing ) );

			// Add missing columns.
			foreach ( $missing as $column => $definition ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Schema migration query, not user data.
				$query  = $wpdb->prepare(
					'ALTER TABLE %s ADD COLUMN %s %s',
					$wpdb->prefix . 'qr_trackr_links',
					$wpdb->_escape( $column ),
					$wpdb->_escape( $definition )
				);
				$result = $wpdb->query( $query );
				if ( false === $result ) {
					qr_trackr_debug_log( 'Migration: Failed to add column ' . $column . '.', $wpdb->last_error );
				} else {
					qr_trackr_debug_log( 'Migration: Added column ' . $column . '.' );
				}
			}
		} else {
			qr_trackr_debug_log( 'Migration: qr_trackr_links schema OK.' );
		}

		// Verify and migrate scans table.
		$table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$wpdb->prefix . 'qr_trackr_scans'
			)
		);
		if ( ! $table_exists ) {
			qr_trackr_debug_log( 'Migration: Scans table does not exist, creating...' );
			qr_trackr_create_tables();
			return;
		}

		// Get scan history with proper interval handling.
		if ( ! isset( $link_id ) || ! isset( $interval ) ) {
			return array();
		}

		$link_id       = absint( $link_id );
		$interval_days = absint( $interval );
		$cache_key     = 'qr_trackr_scan_history_' . $link_id . '_' . $interval_days;
		$scan_data     = wp_cache_get( $cache_key );

		if ( false === $scan_data ) {
			$query = $wpdb->prepare(
				"SELECT DATE(scan_time) as date, COUNT(*) as count 
				FROM {$wpdb->prefix}qr_trackr_scans 
				WHERE link_id = %d 
				AND scan_time >= DATE_SUB(NOW(), INTERVAL %d DAY)
				GROUP BY DATE(scan_time) 
				ORDER BY date DESC",
				$link_id,
				$interval_days
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached immediately after query.
			$scan_data = $wpdb->get_results( $query, ARRAY_A );
			wp_cache_set( $cache_key, $scan_data, '', 300 ); // Cache for 5 minutes.
		}

		return $scan_data;
	}
);

/**
 * Check if the current user has administrator capabilities.
 *
 * @return bool True if the user is an admin, false otherwise.
 */
function qr_trackr_is_admin_user() {
	return current_user_can( 'manage_options' );
}

/**
 * Get QR code data by ID.
 *
 * @param int $qr_id QR code ID.
 * @return array|WP_Error QR code data or error object.
 */
function get_qr_data( $qr_id ) {
	global $wpdb;
	$cache_key = 'qr_trackr_data_' . $qr_id;
	$qr_data   = wp_cache_get( $cache_key );

	if ( false !== $qr_data ) {
		return $qr_data;
	}

	if ( ! is_numeric( $qr_id ) ) {
		return new WP_Error( 'invalid_id', esc_html__( 'Invalid QR code ID provided.', 'wp-qr-trackr' ) );
	}

	try {
		$query = $wpdb->prepare(
			sprintf(
				'SELECT * FROM %s WHERE id = %%d',
				$wpdb->prefix . 'qr_trackr_links'
			),
			intval( $qr_id )
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Direct query needed for QR data, caching implemented.
		$qr_data = $wpdb->get_row( $query, ARRAY_A );

		if ( ! $qr_data ) {
			return new WP_Error( 'not_found', esc_html__( 'QR code not found.', 'wp-qr-trackr' ) );
		}

		wp_cache_set( $cache_key, $qr_data, '', 300 ); // Cache for 5 minutes.

		return $qr_data;

	} catch ( Exception $e ) {
		return new WP_Error( 'data_error', $e->getMessage() );
	}
}

/**
 * Get scan count for a QR code.
 *
 * @param int $qr_id QR code ID.
 * @return int|WP_Error Scan count or error object.
 */
function get_scan_count( $qr_id ) {
	global $wpdb;
	$cache_key = 'qr_trackr_scan_count_' . $qr_id;
	$count     = wp_cache_get( $cache_key );

	if ( false !== $count ) {
		return intval( $count );
	}

	if ( ! is_numeric( $qr_id ) ) {
		return new WP_Error( 'invalid_id', esc_html__( 'Invalid QR code ID provided.', 'wp-qr-trackr' ) );
	}

	try {
		$query = $wpdb->prepare(
			sprintf(
				'SELECT COUNT(*) FROM %s WHERE link_id = %%d',
				$wpdb->prefix . 'qr_trackr_scans'
			),
			intval( $qr_id )
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Direct query needed for scan count, caching implemented.
		$count = $wpdb->get_var( $query );

		wp_cache_set( $cache_key, $count, '', 300 ); // Cache for 5 minutes.

		return intval( $count );

	} catch ( Exception $e ) {
		return new WP_Error( 'count_error', $e->getMessage() );
	}
}

/**
 * Get scan history for a QR code.
 *
 * @param int    $id     QR code ID.
 * @param string $period Time period ('day', 'week', 'month', 'year').
 * @return array|WP_Error Scan history data or error object.
 */
function get_scan_history( $id, $period = 'month' ) {
	global $wpdb;
	$cache_key = 'qr_trackr_history_' . $id . '_' . $period;
	$history   = wp_cache_get( $cache_key );

	if ( false !== $history ) {
		return $history;
	}

	if ( ! is_numeric( $id ) ) {
		return new WP_Error( 'invalid_id', esc_html__( 'Invalid QR code ID provided.', 'wp-qr-trackr' ) );
	}

	$valid_periods = array( 'day', 'week', 'month', 'year' );
	if ( ! in_array( $period, $valid_periods, true ) ) {
		return new WP_Error( 'invalid_period', esc_html__( 'Invalid time period specified.', 'wp-qr-trackr' ) );
	}

	try {
		$interval = '';
		$format   = '';

		switch ( $period ) {
			case 'day':
				$interval = '30 DAY';
				$format   = '%Y-%m-%d';
				break;
			case 'week':
				$interval = '12 WEEK';
				$format   = '%x-W%v';
				break;
			case 'month':
				$interval = '12 MONTH';
				$format   = '%Y-%m';
				break;
			case 'year':
				$interval = '5 YEAR';
				$format   = '%Y';
				break;
		}

		$query = $wpdb->prepare(
			sprintf(
				'SELECT DATE_FORMAT(scan_time, %%s) as date, COUNT(*) as count ' .
				'FROM %s ' .
				'WHERE link_id = %%d ' .
				'AND scan_time >= DATE_SUB(NOW(), INTERVAL %%s) ' .
				'GROUP BY date ' .
				'ORDER BY date ASC',
				$wpdb->prefix . 'qr_trackr_scans'
			),
			$format,
			$id,
			$interval
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Direct query needed for scan history, caching implemented.
		$results = $wpdb->get_results( $query );

		wp_cache_set( $cache_key, $results, '', 300 ); // Cache for 5 minutes.

		return $results;

	} catch ( Exception $e ) {
		return new WP_Error( 'history_error', $e->getMessage() );
	}
}

/**
 * Get QR code statistics.
 *
 * @param int $id QR code ID.
 * @return array|WP_Error Statistics data or error object.
 */
function qrc_get_statistics( $id ) {
	global $wpdb;

	$cache_key = 'qr_trackr_stats_' . $id;
	$stats     = wp_cache_get( $cache_key );

	if ( false !== $stats ) {
		return $stats;
	}

	if ( ! is_numeric( $id ) ) {
		return new WP_Error( 'invalid_id', esc_html__( 'Invalid QR code ID provided.', 'wp-qr-trackr' ) );
	}

	$id = intval( $id );

	try {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Direct query needed for stats, caching implemented!
		$total_scans = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}qr_trackr_scans WHERE link_id = %d",
				$id
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Direct query needed for stats, caching implemented!
		$unique_visitors = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT ip_address) FROM {$wpdb->prefix}qr_trackr_scans WHERE link_id = %d",
				$id
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Direct query needed for stats, caching implemented!
		$last_scan = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}qr_trackr_scans WHERE link_id = %d ORDER BY scan_time DESC LIMIT 1",
				$id
			)
		);

		$stats = array(
			'total_scans'     => intval( $total_scans ),
			'unique_visitors' => intval( $unique_visitors ),
			'last_scan'       => $last_scan ? array(
				'time'     => $last_scan->scan_time,
				'location' => $last_scan->location,
				'device'   => $last_scan->user_agent,
			) : null,
			'scan_history'    => get_scan_history( $id ),
			'scan_locations'  => qrc_get_scan_locations( $id ),
			'scan_devices'    => qrc_get_scan_devices( $id ),
		);

		wp_cache_set( $cache_key, $stats, '', 300 ); // Cache for 5 minutes!

		return $stats;

	} catch ( Exception $e ) {
		return new WP_Error( 'stats_error', $e->getMessage() );
	}
}

/**
 * Get scan locations for a QR code.
 *
 * @param int $id QR code ID.
 * @return array|WP_Error Array of locations or error object.
 */
function qrc_get_scan_locations( $id ) {
	global $wpdb;

	$cache_key = 'qr_trackr_locations_' . $id;
	$locations = wp_cache_get( $cache_key );

	if ( false !== $locations ) {
		return $locations;
	}

	if ( ! is_numeric( $id ) ) {
		return new WP_Error( 'invalid_id', esc_html__( 'Invalid QR code ID provided.', 'wp-qr-trackr' ) );
	}

	try {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Direct query needed for locations, caching implemented!
		$locations = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT location, COUNT(*) as count FROM {$wpdb->prefix}qr_trackr_scans WHERE link_id = %d AND location IS NOT NULL GROUP BY location ORDER BY count DESC",
				$id
			)
		);

		wp_cache_set( $cache_key, $locations, '', 300 ); // Cache for 5 minutes!

		return $locations;

	} catch ( Exception $e ) {
		return new WP_Error( 'location_error', $e->getMessage() );
	}
}

/**
 * Get scan devices for a QR code.
 *
 * @param int $id QR code ID.
 * @return array|WP_Error Array of devices or error object.
 */
function qrc_get_scan_devices( $id ) {
	global $wpdb;

	$cache_key = 'qr_trackr_devices_' . $id;
	$devices   = wp_cache_get( $cache_key );

	if ( false !== $devices ) {
		return $devices;
	}

	if ( ! is_numeric( $id ) ) {
		return new WP_Error( 'invalid_id', esc_html__( 'Invalid QR code ID provided.', 'wp-qr-trackr' ) );
	}

	try {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Direct query needed for devices, caching implemented!
		$devices = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT user_agent, COUNT(*) as count FROM {$wpdb->prefix}qr_trackr_scans WHERE link_id = %d GROUP BY user_agent ORDER BY count DESC",
				$id
			)
		);

		wp_cache_set( $cache_key, $devices, '', 300 ); // Cache for 5 minutes!

		return $devices;

	} catch ( Exception $e ) {
		return new WP_Error( 'device_error', $e->getMessage() );
	}
}

if ( function_exists( 'qr_trackr_debug_log' ) ) {
	qr_trackr_debug_log( 'Loaded module-utils.php.' );
}
