<?php
/**
 * Utility module for QR Trackr plugin.
 *
 * Provides database helpers, sanitization, and general utility functions.
 *
 * @package QR_Trackr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( function_exists( 'qr_trackr_debug_log' ) ) {
	qr_trackr_debug_log( 'Loading module-utils.php...' );
}

/**
 * Safely get a value from an array by key.
 *
 * @since 1.0.0
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
 * @since 1.0.0
 * @param string $str The string to sanitize.
 * @return string Sanitized string.
 */
function qr_trackr_sanitize_output( $str ) {
	return esc_html( $str );
}

/**
 * Escape a URL for safe output.
 *
 * @since 1.0.0
 * @param string $url The URL to escape.
 * @return string Escaped URL.
 */
function qr_trackr_escape_url( $url ) {
	return esc_url( $url );
}

/**
 * Get the most recent tracking link for a post.
 *
 * @since 1.0.0
 * @param int $post_id Post ID.
 * @return object|null Most recent tracking link object or null.
 */
function qr_trackr_get_most_recent_tracking_link( $post_id ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';
	$cache_key  = 'qrc_link_recent_' . absint( $post_id );
	$link       = wp_cache_get( $cache_key, 'qrc_links' );

	if ( false === $link ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Caching implemented above, single-row lookup needed for display.
		$link = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}qr_trackr_links WHERE post_id = %d ORDER BY created_at DESC LIMIT 1",
				absint( $post_id )
			)
		);

		if ( $link ) {
			wp_cache_set( $cache_key, $link, 'qrc_links', HOUR_IN_SECONDS );
		}
	}

	return $link;
}

/**
 * Render the HTML for the QR code list for a post.
 *
 * @since 1.0.0
 * @param int $post_id Post ID.
 * @return string HTML output.
 */
function qr_trackr_render_qr_list_html( $post_id ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';
	$cache_key  = 'qrc_links_list_' . absint( $post_id );
	$links      = wp_cache_get( $cache_key, 'qrc_links' );

	if ( false === $links ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Caching implemented above, filtered query needed for display.
		$links = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}qr_trackr_links WHERE post_id = %d ORDER BY created_at DESC",
				absint( $post_id )
			)
		);

		if ( $links ) {
			wp_cache_set( $cache_key, $links, 'qrc_links', HOUR_IN_SECONDS );
		}
	}

	if ( empty( $links ) ) {
		return '<div class="qr-trackr-list"><p>' . esc_html__( 'No QR codes found.', 'wp-qr-trackr' ) . '</p></div>';
	}

	$html  = '<div class="qr-trackr-list"><table class="widefat"><thead><tr>';
	$html .= '<th>' . esc_html__( 'ID', 'wp-qr-trackr' ) . '</th><th>' . esc_html__( 'QR Code', 'wp-qr-trackr' ) . '</th><th>' . esc_html__( 'Tracking Link', 'wp-qr-trackr' ) . '</th>';
	$html .= '</tr></thead><tbody>';

	foreach ( $links as $link ) {
		$qr_urls       = qr_trackr_generate_qr_image_for_link( absint( $link->id ) );
		$tracking_link = trailingslashit( home_url() ) . 'qr-trackr/redirect/' . absint( $link->id );

		$html .= '<tr>';
		$html .= '<td>' . absint( $link->id ) . '</td>';
		$html .= '<td>';

		if ( ! empty( $qr_urls ) && ! empty( $qr_urls['png'] ) ) {
			$html .= '<img src="' . esc_url( $qr_urls['png'] ) . '" style="max-width:60px; display:block; margin-bottom:4px;" alt="' . esc_attr__( 'QR Code', 'wp-qr-trackr' ) . '">';
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
 * @since 1.0.0
 * @return array Array of tracking links.
 */
function qr_trackr_get_all_links() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';
	$cache_key  = 'qrc_all_links';
	$links      = wp_cache_get( $cache_key, 'qrc_links' );

	if ( false === $links ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Caching implemented above, full table lookup needed for display.
		$links = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}qr_trackr_links" );

		if ( $links ) {
			wp_cache_set( $cache_key, $links, 'qrc_links', HOUR_IN_SECONDS );
		}
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
	$table_name = $wpdb->prefix . 'qr_trackr_links';
	$cache_key  = 'qrc_link_' . absint( $id );
	$link       = wp_cache_get( $cache_key, 'qrc_links' );

	if ( false === $link ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Caching implemented above, single-row lookup needed for display.
		$link = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}qr_trackr_links WHERE id = %d",
				absint( $id )
			)
		);

		if ( $link ) {
			wp_cache_set( $cache_key, $link, 'qrc_links', HOUR_IN_SECONDS );
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
	$table_name = $wpdb->prefix . 'qr_trackr_links';
	$cache_key  = 'qrc_link_url_' . md5( $url );
	$link       = wp_cache_get( $cache_key, 'qrc_links' );

	if ( false === $link ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Caching implemented above, single-row lookup needed for display.
		$link = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}qr_trackr_links WHERE url = %s",
				esc_url_raw( $url )
			)
		);

		if ( $link ) {
			wp_cache_set( $cache_key, $link, 'qrc_links', HOUR_IN_SECONDS );
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
	$table_name = $wpdb->prefix . 'qr_trackr_stats';
	$cache_key  = 'qrc_link_stats_' . absint( $link_id );
	$stats      = wp_cache_get( $cache_key, 'qrc_stats' );

	if ( false === $stats ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Caching implemented above, filtered query needed for display.
		$stats = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(created_at) as date, COUNT(*) as count FROM {$wpdb->prefix}qr_trackr_stats WHERE link_id = %d GROUP BY DATE(created_at) ORDER BY date DESC",
				absint( $link_id )
			)
		);

		if ( $stats ) {
			wp_cache_set( $cache_key, $stats, 'qrc_stats', HOUR_IN_SECONDS );
		}
	}

	return $stats;
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
	$table_name = $wpdb->prefix . 'qr_trackr_links';

	// Sanitize input data.
	$sanitized_data = array(
		'url'        => esc_url_raw( $data['url'] ),
		'title'      => sanitize_text_field( $data['title'] ),
		'created_at' => current_time( 'mysql' ),
	);

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Write operation, caching not applicable.
	$result = $wpdb->insert(
		$table_name,
		$sanitized_data,
		array(
			'%s', // url.
			'%s', // title.
			'%s', // created_at.
		)
	);

	if ( false === $result ) {
		qr_trackr_debug_log( sprintf( 'Failed to create link. Error: %s', $wpdb->last_error ) );
		return false;
	}

	$link_id = $wpdb->insert_id;

	// Clear cache.
	wp_cache_delete( 'qrc_all_links', 'qrc_links' );
	wp_cache_delete( 'qrc_link_url_' . md5( $sanitized_data['url'] ), 'qrc_links' );

	return $link_id;
}

/**
 * Update an existing link.
 *
 * @since 1.0.0
 * @param int   $id   Link ID.
 * @param array $data Link data.
 * @return bool True on success, false on failure.
 */
function qrc_update_link( $id, $data ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';

	// Sanitize input data.
	$sanitized_data = array(
		'url'   => esc_url_raw( $data['url'] ),
		'title' => sanitize_text_field( $data['title'] ),
	);

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Write operation, caching not applicable.
	$result = $wpdb->update(
		$table_name,
		$sanitized_data,
		array( 'id' => absint( $id ) ),
		array(
			'%s', // url.
			'%s', // title.
		),
		array( '%d' ) // id.
	);

	if ( false === $result ) {
		qr_trackr_debug_log( sprintf( 'Failed to update link ID: %d. Error: %s', $id, $wpdb->last_error ) );
		return false;
	}

	// Clear cache.
	wp_cache_delete( 'qrc_link_' . absint( $id ), 'qrc_links' );
	wp_cache_delete( 'qrc_all_links', 'qrc_links' );
	wp_cache_delete( 'qrc_link_url_' . md5( $sanitized_data['url'] ), 'qrc_links' );

	return true;
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
	$table_name = $wpdb->prefix . 'qr_trackr_links';

	// Get the link first to clear URL-based cache later.
	$link = qrc_get_link_by_id( $id );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Write operation, caching not applicable.
	$result = $wpdb->delete(
		$table_name,
		array( 'id' => absint( $id ) ),
		array( '%d' )
	);

	if ( false === $result ) {
		qr_trackr_debug_log( sprintf( 'Failed to delete link ID: %d. Error: %s', $id, $wpdb->last_error ) );
		return false;
	}

	// Clear cache.
	wp_cache_delete( 'qrc_link_' . absint( $id ), 'qrc_links' );
	wp_cache_delete( 'qrc_all_links', 'qrc_links' );
	if ( $link && ! empty( $link->url ) ) {
		wp_cache_delete( 'qrc_link_url_' . md5( $link->url ), 'qrc_links' );
	}

	return true;
}

/**
 * Record a visit to a link.
 *
 * @since 1.0.0
 * @param int $link_id Link ID.
 * @return bool True on success, false on failure.
 */
function qrc_record_visit( $link_id ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_stats';

	// Get visitor information.
	$ip_address = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Write operation, caching not applicable.
	$result = $wpdb->insert(
		$table_name,
		array(
			'link_id'    => absint( $link_id ),
			'ip_address' => $ip_address,
			'user_agent' => $user_agent,
			'created_at' => current_time( 'mysql' ),
		),
		array(
			'%d', // link_id.
			'%s', // ip_address.
			'%s', // user_agent.
			'%s', // created_at.
		)
	);

	if ( false === $result ) {
		qr_trackr_debug_log( sprintf( 'Failed to record visit for link ID: %d. Error: %s', $link_id, $wpdb->last_error ) );
		return false;
	}

	// Clear stats cache.
	wp_cache_delete( 'qrc_link_stats_' . absint( $link_id ), 'qrc_stats' );

	return true;
}

/**
 * Get scans by link ID.
 *
 * @since 1.0.0
 * @param int $link_id Link ID.
 * @return array|WP_Error Array of scans or error object.
 */
function qr_trackr_get_scans_by_link_id( $link_id ) {
	global $wpdb;

	$cache_key = 'qr_trackr_scans_' . absint( $link_id );
	$scans     = wp_cache_get( $cache_key );

	if ( false !== $scans ) {
		return $scans;
	}

	if ( ! is_numeric( $link_id ) ) {
		return new WP_Error( 'invalid_id', esc_html__( 'Invalid QR code ID provided.', 'wp-qr-trackr' ) );
	}

	$scans = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}qr_trackr_stats WHERE link_id = %d ORDER BY created_at DESC",
			absint( $link_id )
		)
	);

	wp_cache_set( $cache_key, $scans, '', 300 ); // Cache for 5 minutes.

	return $scans;
}

/**
 * Create a new tracking link.
 *
 * @since 1.0.0
 * @param string $url URL to track.
 * @param string $name Optional. Link name.
 * @return int|WP_Error Link ID on success, WP_Error on failure.
 */
function qr_trackr_create_link( $url, $name = '' ) {
	global $wpdb;

	if ( empty( $url ) ) {
		return new WP_Error( 'empty_url', esc_html__( 'URL cannot be empty.', 'wp-qr-trackr' ) );
	}

	$result = $wpdb->insert(
		$wpdb->prefix . 'qr_trackr_links',
		array(
			'url'        => esc_url_raw( $url ),
			'title'      => sanitize_text_field( $name ),
			'created_at' => current_time( 'mysql' ),
			'updated_at' => current_time( 'mysql' ),
		),
		array( '%s', '%s', '%s', '%s' )
	);

	if ( false === $result ) {
		return new WP_Error( 'db_error', esc_html__( 'Failed to create tracking link.', 'wp-qr-trackr' ) );
	}

	wp_cache_delete( 'qr_trackr_all_links' );
	return $wpdb->insert_id;
}

/**
 * Update a tracking link.
 *
 * @since 1.0.0
 * @param int    $id Link ID.
 * @param string $url New URL.
 * @param string $name Optional. New name.
 * @return bool|WP_Error True on success, WP_Error on failure.
 */
function qr_trackr_update_link( $id, $url, $name = '' ) {
	global $wpdb;

	if ( empty( $url ) ) {
		return new WP_Error( 'empty_url', esc_html__( 'URL cannot be empty.', 'wp-qr-trackr' ) );
	}

	$result = $wpdb->update(
		$wpdb->prefix . 'qr_trackr_links',
		array(
			'url'        => esc_url_raw( $url ),
			'title'      => sanitize_text_field( $name ),
			'updated_at' => current_time( 'mysql' ),
		),
		array( 'id' => absint( $id ) ),
		array( '%s', '%s', '%s' ),
		array( '%d' )
	);

	if ( false === $result ) {
		return new WP_Error( 'db_error', esc_html__( 'Failed to update tracking link.', 'wp-qr-trackr' ) );
	}

	wp_cache_delete( 'qrc_link_' . absint( $id ) );
	wp_cache_delete( 'qr_trackr_all_links' );
	return true;
}

/**
 * Delete a tracking link.
 *
 * @since 1.0.0
 * @param int $id Link ID.
 * @return bool|WP_Error True on success, WP_Error on failure.
 */
function qr_trackr_delete_link( $id ) {
	global $wpdb;

	$result = $wpdb->delete(
		$wpdb->prefix . 'qr_trackr_links',
		array( 'id' => absint( $id ) ),
		array( '%d' )
	);

	if ( false === $result ) {
		return new WP_Error( 'db_error', esc_html__( 'Failed to delete tracking link.', 'wp-qr-trackr' ) );
	}

	wp_cache_delete( 'qrc_link_' . absint( $id ) );
	wp_cache_delete( 'qr_trackr_all_links' );
	return true;
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
		$table = $wpdb->prefix . 'qr_trackr_links';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- One-time migration check, no caching needed
		$table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$table
			)
		);

		if ( ! $table_exists ) {
			qr_trackr_debug_log( 'Migration: Table does not exist, creating...' );
			qr_trackr_create_tables();
			return;
		}

		// Get current columns.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- One-time migration check, no caching needed
		$columns = $wpdb->get_results(
			$wpdb->prepare(
				"SHOW COLUMNS FROM {$wpdb->prefix}qr_trackr_links"
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
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange -- Schema changes are required during migration
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- One-time migration operation, no caching needed
				$result = $wpdb->query(
					$wpdb->prepare(
						'ALTER TABLE %i ADD COLUMN %i %s',
						$table,
						$column,
						$definition
					)
				);
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
		$table = $wpdb->prefix . 'qr_trackr_scans';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- One-time migration check, no caching needed
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- No variables used in query
		$table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$table
			)
		);

		if ( ! $table_exists ) {
			qr_trackr_debug_log( 'Migration: Scans table does not exist, creating...' );
			qr_trackr_create_tables();
			return;
		}
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
 * Get QR code data.
 *
 * @since 1.0.0
 * @param int $qr_id QR code ID.
 * @return array|WP_Error QR code data or error object.
 */
function get_qr_data( $qr_id ) {
	global $wpdb;

	$cache_key = 'qr_trackr_data_' . absint( $qr_id );
	$data      = wp_cache_get( $cache_key );

	if ( false === $data ) {
		$data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}qr_trackr_links WHERE id = %d",
				absint( $qr_id )
			)
		);

		if ( $data ) {
			wp_cache_set( $cache_key, $data, '', 300 ); // Cache for 5 minutes.
		}
	}

	return $data;
}

/**
 * Get scan count for a QR code.
 *
 * @since 1.0.0
 * @param int $qr_id QR code ID.
 * @return int|WP_Error Scan count or error object.
 */
function get_scan_count( $qr_id ) {
	global $wpdb;

	$cache_key = 'qr_trackr_scan_count_' . absint( $qr_id );
	$count     = wp_cache_get( $cache_key );

	if ( false === $count ) {
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}qr_trackr_stats WHERE link_id = %d",
				absint( $qr_id )
			)
		);

		wp_cache_set( $cache_key, $count, '', 300 ); // Cache for 5 minutes.
	}

	return absint( $count );
}

/**
 * Get scan history for a QR code.
 *
 * @since 1.0.0
 * @param int    $id QR code ID.
 * @param string $period Period to get history for (day, week, month, year).
 * @return array|WP_Error Scan history or error object.
 */
function get_scan_history( $id, $period = 'month' ) {
	global $wpdb;

	$cache_key = 'qr_trackr_scan_history_' . absint( $id ) . '_' . sanitize_key( $period );
	$history   = wp_cache_get( $cache_key );

	if ( false === $history ) {
		$history = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(created_at) as date, COUNT(*) as count 
				FROM {$wpdb->prefix}qr_trackr_stats 
				WHERE link_id = %d 
				GROUP BY DATE(created_at) 
				ORDER BY date DESC",
				absint( $id )
			)
		);

		wp_cache_set( $cache_key, $history, '', 300 ); // Cache for 5 minutes.
	}

	return $history;
}

/**
 * Get statistics for a QR code.
 *
 * @since 1.0.0
 * @param int $id QR code ID.
 * @return array|WP_Error Statistics or error object.
 */
function qrc_get_statistics( $id ) {
	global $wpdb;

	$cache_key = 'qr_trackr_statistics_' . absint( $id );
	$stats     = wp_cache_get( $cache_key );

	if ( false === $stats ) {
		$stats = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(created_at) as date, COUNT(*) as count 
				FROM {$wpdb->prefix}qr_trackr_stats 
				WHERE link_id = %d 
				GROUP BY DATE(created_at) 
				ORDER BY date DESC",
				absint( $id )
			)
		);

		wp_cache_set( $cache_key, $stats, '', 300 ); // Cache for 5 minutes.
	}

	return $stats;
}

/**
 * Get scan locations for a QR code.
 *
 * @since 1.0.0
 * @param int $id QR code ID.
 * @return array|WP_Error Array of locations or error object.
 */
function qrc_get_scan_locations( $id ) {
	global $wpdb;

	$cache_key = 'qr_trackr_scan_locations_' . absint( $id );
	$locations = wp_cache_get( $cache_key );

	if ( false === $locations ) {
		$locations = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT location, COUNT(*) as count 
				FROM {$wpdb->prefix}qr_trackr_stats 
				WHERE link_id = %d 
				GROUP BY location 
				ORDER BY count DESC",
				absint( $id )
			)
		);

		wp_cache_set( $cache_key, $locations, '', 300 ); // Cache for 5 minutes.
	}

	return $locations;
}

/**
 * Get scan devices for a QR code.
 *
 * @since 1.0.0
 * @param int $id QR code ID.
 * @return array|WP_Error Array of devices or error object.
 */
function qrc_get_scan_devices( $id ) {
	global $wpdb;

	$cache_key = 'qr_trackr_scan_devices_' . absint( $id );
	$devices   = wp_cache_get( $cache_key );

	if ( false === $devices ) {
		$devices = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT user_agent, COUNT(*) as count 
				FROM {$wpdb->prefix}qr_trackr_stats 
				WHERE link_id = %d 
				GROUP BY user_agent 
				ORDER BY count DESC",
				absint( $id )
			)
		);

		wp_cache_set( $cache_key, $devices, '', 300 ); // Cache for 5 minutes.
	}

	return $devices;
}

if ( function_exists( 'qr_trackr_debug_log' ) ) {
	qr_trackr_debug_log( 'Loaded module-utils.php.' );
}
