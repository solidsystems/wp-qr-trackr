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

// Utility functions.
// ... (move qr_trackr_get_most_recent_tracking_link, qr_trackr_render_qr_list_html, etc. here).

/**
 * Safely get a value from an array by key.
 *
 * @param array  $arr The array to search.
 * @param string $key   The key to look for.
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
	$links_table = $wpdb->prefix . 'qr_trackr_links';
	// Table name is safe as it is constructed from $wpdb->prefix and a static string.
	$cache_key = 'qr_trackr_link_' . $post_id;
	$link      = wp_cache_get( $cache_key );
	if ( $link ) {
		return $link;
	}
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query is required for plugin logic and is cached above.
	$link = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %s WHERE post_id = %d ORDER BY created_at DESC LIMIT 1', $links_table, $post_id ) );
	if ( $link ) {
		wp_cache_set( $cache_key, $link );
	}
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
	$links_table = $wpdb->prefix . 'qr_trackr_links';
	// Short-term cache for admin table rendering (1 minute).
	$cache_key = 'qr_trackr_links_list_' . $post_id;
	$links     = wp_cache_get( $cache_key );
	if ( false === $links ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query is required for admin table rendering, short-term cache added.
		$links = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %s WHERE post_id = %d ORDER BY created_at DESC', $links_table, $post_id ) );
		wp_cache_set( $cache_key, $links, '', 60 ); // Cache for 1 minute.
	}
	if ( ! $links ) {
		return '<div class="qr-trackr-list"><p>No QR codes found.</p></div>';
	}
	$html  = '<div class="qr-trackr-list"><table class="widefat"><thead><tr>';
	$html .= '<th>ID</th><th>QR Code</th><th>Tracking Link</th>';
	$html .= '</tr></thead><tbody>';
	foreach ( $links as $link ) {
		$qr_url        = qr_trackr_generate_qr_image_for_link( $link->id );
		$tracking_link = trailingslashit( home_url() ) . 'qr-trackr/redirect/' . intval( $link->id );
		$html         .= '<tr>';
		$html         .= '<td>' . intval( $link->id ) . '</td>';
		$html         .= '<td>';
		if ( $qr_url ) {
			$html .= '<img src="' . esc_url( $qr_url ) . '" style="max-width:60px; display:block; margin-bottom:4px;" alt="QR Code">';
			$html .= '<a href="' . esc_url( $qr_url ) . '" download class="button">Download</a>';
		}
		$html .= '</td>';
		$html .= '<td><a href="' . esc_url( $tracking_link ) . '" target="_blank">' . esc_html( $tracking_link ) . '</a></td>';
		$html .= '</tr>';
	}
	$html .= '</tbody></table></div>';
	return $html;
}

// Save post handler for updating destination URL.
add_action(
	'save_post',
	/**
	 * Handle saving of destination URL for a QR code link.
	 *
	 * @param int $post_id The post ID.
	 * @return void
	 */
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
			$links_table = $wpdb->prefix . 'qr_trackr_links';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Update is required for plugin logic.
			$wpdb->update( $links_table, array( 'destination_url' => $dest_url ), array( 'id' => $link_id ) );
		}
	}
);

// phpcs:disable
// Migration/verification for qr_trackr_links table schema.
add_action(
	'init',
	/**
	 * Verify and migrate qr_trackr_links table schema if needed.
	 *
	 * @return void
	 */
	function () {
		global $wpdb;
		$links_table = $wpdb->prefix . 'qr_trackr_links';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Schema change is required for plugin migration logic; caching is not applicable.
		$columns  = $wpdb->get_results( $wpdb->prepare( 'SHOW COLUMNS FROM %s', $links_table ), ARRAY_A );
		$expected = array( 'id', 'post_id', 'destination_url', 'created_at', 'updated_at' );
		$actual   = array_map(
			function ( $col ) {
				return $col['Field'];
			},
			$columns
		);
		$missing  = array_diff( $expected, $actual );
		if ( $missing ) {
			qr_trackr_debug_log( 'Migration: Missing columns in qr_trackr_links', $missing );
			if ( in_array( 'created_at', $missing, true ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery.NoCaching -- Schema change is required for plugin migration logic; caching is not applicable.
				$wpdb->query( $wpdb->prepare( 'ALTER TABLE %s ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP', $links_table ) );
				qr_trackr_debug_log( 'Migration: Added created_at column.' );
			}
			if ( in_array( 'updated_at', $missing, true ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery.NoCaching -- Schema change is required for plugin migration logic; caching is not applicable.
				$wpdb->query( $wpdb->prepare( 'ALTER TABLE %s ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', $links_table ) );
				qr_trackr_debug_log( 'Migration: Added updated_at column.' );
			}
		} else {
			qr_trackr_debug_log( 'Migration: qr_trackr_links schema OK', $actual );
		}
	}
);
// phpcs:enable
