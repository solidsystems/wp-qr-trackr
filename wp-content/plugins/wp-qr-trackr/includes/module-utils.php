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
	$link = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}qr_trackr_links WHERE post_id = %d ORDER BY created_at DESC LIMIT 1", $post_id ) );
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
		$links = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}qr_trackr_links WHERE post_id = %d ORDER BY created_at DESC", $post_id ) );
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
	return $wpdb->get_results( "SELECT * FROM `{$wpdb->prefix}qr_trackr_links`" );
}

/**
 * Get a tracking link by its ID.
 *
 * @param int $id The link ID.
 * @return object|null
 */
function qr_trackr_get_link_by_id( $id ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}qr_trackr_links` WHERE id = %d", $id ) );
}

/**
 * Get all scans for a given link ID.
 *
 * @param int $link_id The link ID.
 * @return array
 */
function qr_trackr_get_scans_by_link_id( $link_id ) {
	global $wpdb;
	return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}qr_trackr_scans` WHERE link_id = %d", $link_id ) );
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
	$result = $wpdb->insert(
		$wpdb->prefix . 'qr_trackr_links',
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
	$result = $wpdb->update(
		$wpdb->prefix . 'qr_trackr_links',
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
	$result = $wpdb->delete(
		$wpdb->prefix . 'qr_trackr_links',
		array(
			'id' => $id,
		),
		array(
			'%d',
		)
	);
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
			$wpdb->update( $wpdb->prefix . 'qr_trackr_links', array( 'destination_url' => $dest_url ), array( 'id' => $link_id ) );
		}
	}
);

// Migration/verification for qr_trackr_links table schema.
add_action(
	'init',
	function () {
		global $wpdb;

		// Check if table exists first.
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'qr_trackr_links' ) );
		if ( ! $table_exists ) {
			qr_trackr_debug_log( 'Migration: Table does not exist, creating...' );
			qr_trackr_create_tables();
			return;
		}

		// Get current columns.
		$columns = $wpdb->get_results( "SHOW COLUMNS FROM {$wpdb->prefix}qr_trackr_links", ARRAY_A );
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
				$sql    = "ALTER TABLE {$wpdb->prefix}qr_trackr_links ADD COLUMN {$column} {$definition}";
				$result = $wpdb->query( $sql );
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
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'qr_trackr_scans' ) );
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

if ( function_exists( 'qr_trackr_debug_log' ) ) {
	qr_trackr_debug_log( 'Loaded module-utils.php.' );
}
