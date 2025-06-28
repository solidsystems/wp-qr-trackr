<?php
/**
 * AJAX Module
 *
 * Handles all AJAX functionality for the QR Trackr plugin.
 * Includes endpoints for QR code generation, tracking, and data retrieval.
 *
 * @package WP_QR_Trackr
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( function_exists( 'qr_trackr_debug_log' ) ) {
	qr_trackr_debug_log( 'Loading module-ajax.php...' );
}

/**
 * Handle AJAX request to fetch QR scan stats.
 *
 * @return void
 * @throws Exception If database operation fails.
 */
function qr_trackr_ajax_get_stats() {
	check_ajax_referer( 'qr_trackr_stats_nonce', 'security' );

	$post_id = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;

	if ( 0 === $post_id ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Invalid post ID.', 'wp-qr-trackr' ) ) );
	}

	// Try to get stats from cache first.
	$cache_key = 'qr_trackr_stats_' . $post_id;
	$stats     = wp_cache_get( $cache_key, 'qr_trackr' );

	if ( false === $stats ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'qr_trackr_stats';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached immediately after query.
		$stats = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . esc_sql( $table_name ) . ' WHERE post_id = %d ORDER BY scanned_at DESC',
				$post_id
			)
		);

		if ( $stats ) {
			wp_cache_set( $cache_key, $stats, 'qr_trackr', HOUR_IN_SECONDS );
		}
	}

	if ( empty( $stats ) ) {
		wp_send_json_success( array( 'stats' => array() ) );
	}

	$escaped_stats = array();
	foreach ( $stats as $row ) {
		$escaped_stats[] = array(
			'id'         => absint( $row->id ),
			'post_id'    => absint( $row->post_id ),
			'scanned_at' => esc_html( $row->scanned_at ),
			'ip'         => esc_html( $row->ip ),
			'user_agent' => esc_html( $row->user_agent ),
		);
	}

	wp_send_json_success( array( 'stats' => $escaped_stats ) );
}
add_action( 'wp_ajax_qr_trackr_get_stats', 'qr_trackr_ajax_get_stats' );

/**
 * Handle AJAX request to generate a new QR code for a post.
 *
 * @return void
 * @throws Exception If QR code generation fails.
 */
function qr_trackr_ajax_generate_qr() {
	// Verify nonce.
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'qr_trackr_nonce' ) ) {
		wp_send_json_error( esc_html__( 'Invalid nonce.', 'wp-qr-trackr' ) );
		return;
	}

	// Check user capabilities.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( esc_html__( 'Insufficient permissions.', 'wp-qr-trackr' ) );
		return;
	}

	// Get and validate link ID.
	$link_id = isset( $_POST['link_id'] ) ? absint( $_POST['link_id'] ) : 0;
	if ( 0 === $link_id ) {
		wp_send_json_error( esc_html__( 'Invalid link ID.', 'wp-qr-trackr' ) );
		return;
	}

	// Try to get link from cache first.
	$cache_key = 'qr_trackr_link_' . $link_id;
	$link      = wp_cache_get( $cache_key, 'qr_trackr' );

	if ( false === $link ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'qr_trackr_links';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached immediately after query.
		$link = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . esc_sql( $table_name ) . ' WHERE id = %d',
				$link_id
			)
		);

		if ( $link ) {
			wp_cache_set( $cache_key, $link, 'qr_trackr', HOUR_IN_SECONDS );
		}
	}

	if ( ! $link ) {
		wp_send_json_error( esc_html__( 'Link not found.', 'wp-qr-trackr' ) );
		return;
	}

	// Generate QR code.
	$qr_image = qr_trackr_generate_qr_image_for_link( $link_id );
	if ( ! $qr_image ) {
		qr_trackr_debug_log( 'Failed to generate QR code for link ID: ' . $link_id );
		wp_send_json_error( esc_html__( 'Failed to generate QR code.', 'wp-qr-trackr' ) );
		return;
	}

	// Update link with QR code URL (store only PNG).
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cache invalidated after update.
	$result = $wpdb->update(
		$table_name,
		array( 'qr_code_url' => $qr_image['png'] ),
		array( 'id' => $link_id ),
		array( '%s' ),
		array( '%d' )
	);

	if ( false === $result ) {
		qr_trackr_debug_log( 'Failed to update QR code URL in database for link ID: ' . $link_id );
		wp_send_json_error( esc_html__( 'Failed to update QR code URL.', 'wp-qr-trackr' ) );
		return;
	}

	// Clear cache after update.
	wp_cache_delete( $cache_key, 'qr_trackr' );

	wp_send_json_success(
		array(
			'message'         => esc_html__( 'QR code generated successfully.', 'wp-qr-trackr' ),
			'qr_code_url'     => esc_url( $qr_image['png'] ),
			'qr_code_svg_url' => esc_url( $qr_image['svg'] ),
		)
	);
}
add_action( 'wp_ajax_qr_trackr_generate_qr', 'qr_trackr_ajax_generate_qr' );

/**
 * Handle AJAX request to create a new QR code and return HTML.
 *
 * @return void
 */
add_action(
	'wp_ajax_qr_trackr_create_qr_ajax',
	function () {
		$post_id = isset( $_POST['qr_trackr_admin_new_post_id'] ) ? intval( wp_unslash( $_POST['qr_trackr_admin_new_post_id'] ) ) : 0;
		$nonce   = isset( $_POST['qr_trackr_admin_new_qr_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['qr_trackr_admin_new_qr_nonce'] ) ) : '';

		qr_trackr_debug_log( 'AJAX: Create QR called.', array( 'post_id' => $post_id ) );

		// Verify nonce before processing.
		if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'qr_trackr_admin_new_qr' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid nonce.', 'wp-qr-trackr' ) ) );
		}

		if ( 0 === $post_id ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid post ID.', 'wp-qr-trackr' ) ) );
		}

		// Business logic for creating QR code goes here.
		// ...

		wp_send_json_success( array( 'message' => esc_html__( 'QR code created.', 'wp-qr-trackr' ) ) );
	}
);

/**
 * Handle AJAX request to update destination URL.
 *
 * @return void
 */
function qr_trackr_ajax_update_destination() {
	check_ajax_referer( 'qr_trackr_nonce', 'nonce' );
	global $wpdb;
	$links_table = $wpdb->prefix . 'qr_trackr_links';
	$link_id     = isset( $_POST['link_id'] ) ? intval( wp_unslash( $_POST['link_id'] ) ) : 0;
	$destination = isset( $_POST['destination'] ) ? esc_url_raw( wp_unslash( $_POST['destination'] ) ) : '';

	if ( 0 === $link_id || '' === $destination ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Invalid input.', 'wp-qr-trackr' ) ) );
		wp_die();
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safely interpolated for AJAX. Caching is not used to ensure real-time data for AJAX responses.
	$result = $wpdb->update( $links_table, array( 'destination_url' => $destination ), array( 'id' => $link_id ), array( '%s' ), array( '%d' ) );

	if ( false === $result ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Failed to update destination URL.', 'wp-qr-trackr' ) ) );
		wp_die();
	}

	// Clear cache.
	wp_cache_delete( 'qr_trackr_link_' . $link_id );

	wp_send_json_success( array( 'message' => esc_html__( 'Destination URL updated successfully.', 'wp-qr-trackr' ) ) );
	wp_die();
}
add_action( 'wp_ajax_qr_trackr_update_destination', 'qr_trackr_ajax_update_destination' );

/**
 * Handle QR code creation via AJAX.
 *
 * @return void
 */
function qr_trackr_create_qr_code() {
	check_ajax_referer( 'qr_trackr_create', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		qr_trackr_debug_log( 'Permission denied for QR code creation.' );
		if ( function_exists( 'qr_trackr_is_debug_enabled' ) && qr_trackr_is_debug_enabled() ) {
			$error = 'Permission denied.';
			$log   = qr_trackr_get_debug_log();
			$lines = explode( "\n", trim( $log ) );
			$last  = end( $lines );
			wp_send_json_error( esc_html( $error . ( $last ? ' Debug: ' . $last : '' ) ) );
		} else {
			wp_send_json_error( esc_html__( 'Permission denied.', 'wp-qr-trackr' ) );
		}
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';

	$destination_type = isset( $_POST['destination_type'] ) ? sanitize_text_field( wp_unslash( $_POST['destination_type'] ) ) : '';
	qr_trackr_debug_log( 'Destination type: ' . $destination_type );

	if ( 'post' === $destination_type ) {
		$post_id = isset( $_POST['post_id'] ) ? intval( wp_unslash( $_POST['post_id'] ) ) : 0;
		if ( 0 === $post_id || ! get_post( $post_id ) || 'publish' !== get_post_status( $post_id ) ) {
			wp_send_json_error( esc_html__( 'Please select a valid published post or page.', 'wp-qr-trackr' ) );
			return;
		}
		$destination = get_permalink( $post_id );
	} elseif ( 'external' === $destination_type || 'custom' === $destination_type ) {
		$destination = isset( $_POST['destination'] ) ? esc_url_raw( wp_unslash( $_POST['destination'] ) ) : '';
		if ( '' === $destination ) {
			wp_send_json_error( esc_html__( 'Destination URL is required.', 'wp-qr-trackr' ) );
			return;
		}
	} else {
		wp_send_json_error( esc_html__( 'Invalid destination type.', 'wp-qr-trackr' ) );
		return;
	}

	qr_trackr_debug_log( 'Final destination URL: ' . $destination );

	// Create QR code.
	$qr_code = qr_trackr_generate_qr_code( $destination );
	qr_trackr_debug_log( 'qr_trackr_generate_qr_code result:', $qr_code );
	if ( is_wp_error( $qr_code ) ) {
		qr_trackr_debug_log( 'QR code generation error: ' . $qr_code->get_error_message() );
		if ( function_exists( 'qr_trackr_is_debug_enabled' ) && qr_trackr_is_debug_enabled() ) {
			$error = $qr_code->get_error_message();
			$log   = qr_trackr_get_debug_log();
			$lines = explode( "\n", trim( $log ) );
			$last  = end( $lines );
			wp_send_json_error( esc_html( $error . ( $last ? ' Debug: ' . $last : '' ) ) );
		} else {
			wp_send_json_error( esc_html( $qr_code->get_error_message() ) );
		}
	}

	// Save to database.
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safely interpolated for AJAX. Caching is not used to ensure real-time data for AJAX responses.
	$result = $wpdb->insert(
		$table_name,
		array(
			'destination_url' => $destination,
			'created_at'      => current_time( 'mysql' ),
			'access_count'    => 0,
		),
		array( '%s', '%s', '%d' )
	);

	if ( false === $result ) {
		qr_trackr_debug_log( 'Failed to save QR code to database.' );
		if ( function_exists( 'qr_trackr_is_debug_enabled' ) && qr_trackr_is_debug_enabled() ) {
			$error = 'Failed to save QR code.';
			$log   = qr_trackr_get_debug_log();
			$lines = explode( "\n", trim( $log ) );
			$last  = end( $lines );
			wp_send_json_error( esc_html( $error . ( $last ? ' Debug: ' . $last : '' ) ) );
		} else {
			wp_send_json_error( esc_html__( 'Failed to save QR code.', 'wp-qr-trackr' ) );
		}
	}

	wp_send_json_success(
		array(
			'message' => esc_html__( 'QR code created successfully.', 'wp-qr-trackr' ),
			'qr_code' => $qr_code,
		)
	);
}
add_action( 'wp_ajax_qr_trackr_create_qr_code', 'qr_trackr_create_qr_code' );

/**
 * Handle QR code regeneration via AJAX.
 *
 * @return void
 */
function qr_trackr_regenerate_qr_code() {
	check_ajax_referer( 'qr_trackr_regenerate', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( esc_html__( 'Permission denied.', 'wp-qr-trackr' ) );
	}

	$link_id = isset( $_POST['link_id'] ) ? intval( wp_unslash( $_POST['link_id'] ) ) : 0;
	if ( 0 === $link_id ) {
		wp_send_json_error( esc_html__( 'Invalid link ID.', 'wp-qr-trackr' ) );
	}

	// Get current destination.
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';
	$cache_key  = 'qr_trackr_destination_' . $link_id;
	$dest       = wp_cache_get( $cache_key );

	if ( false === $dest ) {
		$table = $wpdb->prefix . 'qr_trackr_links';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached immediately after query.
		$dest = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT destination_url FROM {$wpdb->prefix}qr_trackr_links WHERE id = %d",
				$link_id
			)
		);
		wp_cache_set( $cache_key, $dest, '', 300 ); // Cache for 5 minutes.
	}

	if ( ! $dest ) {
		wp_send_json_error( esc_html__( 'Link not found.', 'wp-qr-trackr' ) );
	}

	// Regenerate QR code.
	$qr_code = qr_trackr_generate_qr_code( $dest );
	if ( is_wp_error( $qr_code ) ) {
		wp_send_json_error( esc_html( $qr_code->get_error_message() ) );
	}

	wp_send_json_success(
		array(
			'message' => esc_html__( 'QR code regenerated successfully.', 'wp-qr-trackr' ),
			'qr_code' => $qr_code,
		)
	);
}
add_action( 'wp_ajax_qr_trackr_regenerate_qr_code', 'qr_trackr_regenerate_qr_code' );

/**
 * Handle QR code deletion via AJAX.
 *
 * @return void
 */
function qr_trackr_delete_qr_code() {
	check_ajax_referer( 'qr_trackr_delete', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( esc_html__( 'Permission denied.', 'wp-qr-trackr' ) );
	}

	$link_id = isset( $_POST['link_id'] ) ? intval( wp_unslash( $_POST['link_id'] ) ) : 0;
	if ( 0 === $link_id ) {
		wp_send_json_error( esc_html__( 'Invalid link ID.', 'wp-qr-trackr' ) );
	}

	// Delete QR code.
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safely interpolated for AJAX. Caching is not used to ensure real-time data for AJAX responses.
	$result = $wpdb->delete( $table_name, array( 'id' => $link_id ), array( '%d' ) );

	if ( false === $result ) {
		wp_send_json_error( esc_html__( 'Failed to delete QR code.', 'wp-qr-trackr' ) );
	}

	wp_send_json_success( esc_html__( 'QR code deleted successfully.', 'wp-qr-trackr' ) );
}
add_action( 'wp_ajax_qr_trackr_delete_qr_code', 'qr_trackr_delete_qr_code' );

/**
 * Handle AJAX request to track QR code scans.
 *
 * @return void
 * @throws Exception If scan tracking fails.
 */
function qr_trackr_track_scan() {
	check_ajax_referer( 'qr_trackr_scan', 'nonce' );

	$qr_id = isset( $_POST['qr_id'] ) ? absint( wp_unslash( $_POST['qr_id'] ) ) : 0;
	if ( 0 === $qr_id ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Invalid QR code ID.', 'wp-qr-trackr' ) ) );
		return;
	}

	// Try to get QR code from cache first.
	$cache_key = 'qr_trackr_code_' . $qr_id;
	$qr_code   = wp_cache_get( $cache_key, 'qr_trackr' );

	if ( false === $qr_code ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'qr_trackr_links';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached immediately after query.
		$qr_code = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . esc_sql( $table_name ) . ' WHERE id = %d',
				$qr_id
			)
		);

		if ( $qr_code ) {
			wp_cache_set( $cache_key, $qr_code, 'qr_trackr', HOUR_IN_SECONDS );
		}
	}

	if ( ! $qr_code ) {
		wp_send_json_error( array( 'message' => esc_html__( 'QR code not found.', 'wp-qr-trackr' ) ) );
		return;
	}

	// Update scan count and last accessed time.
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cache invalidated after update.
	$result = $wpdb->update(
		$table_name,
		array(
			'access_count'  => absint( $qr_code->access_count ) + 1,
			'last_accessed' => current_time( 'mysql' ),
		),
		array( 'id' => $qr_id ),
		array( '%d', '%s' ),
		array( '%d' )
	);

	if ( false === $result ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Failed to update scan count.', 'wp-qr-trackr' ) ) );
		return;
	}

	// Record scan details.
	$stats_table = $wpdb->prefix . 'qr_trackr_stats';

	// Get IP and user agent safely.
	$ip         = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cache invalidated after insert.
	$result = $wpdb->insert(
		$stats_table,
		array(
			'qr_id'      => $qr_id,
			'scan_date'  => current_time( 'mysql' ),
			'ip'         => $ip,
			'user_agent' => $user_agent,
		),
		array( '%d', '%s', '%s', '%s' )
	);

	if ( false === $result ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Failed to record scan details.', 'wp-qr-trackr' ) ) );
		return;
	}

	// Clear caches.
	wp_cache_delete( $cache_key, 'qr_trackr' );
	wp_cache_delete( 'qr_trackr_stats_' . $qr_id, 'qr_trackr' );

	wp_send_json_success(
		array(
			'message'  => esc_html__( 'Scan tracked successfully.', 'wp-qr-trackr' ),
			'qr_code'  => $qr_code,
			'redirect' => esc_url( $qr_code->destination_url ),
		)
	);
}
add_action( 'wp_ajax_nopriv_qr_trackr_track_scan', 'qr_trackr_track_scan' );
add_action( 'wp_ajax_qr_trackr_track_scan', 'qr_trackr_track_scan' );

/**
 * Handle AJAX request to get QR code destination URL.
 *
 * @return void
 * @throws Exception If QR code retrieval fails.
 */
function qr_trackr_get_destination() {
	check_ajax_referer( 'qr_trackr_scan', 'nonce' );

	$qr_id = isset( $_POST['qr_id'] ) ? absint( wp_unslash( $_POST['qr_id'] ) ) : 0;
	if ( 0 === $qr_id ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Invalid QR code ID.', 'wp-qr-trackr' ) ) );
		return;
	}

	// Try to get QR code from cache first.
	$cache_key = 'qr_trackr_code_' . $qr_id;
	$qr_code   = wp_cache_get( $cache_key, 'qr_trackr' );

	if ( false === $qr_code ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'qr_trackr_links';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached immediately after query.
		$qr_code = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . esc_sql( $table_name ) . ' WHERE id = %d',
				$qr_id
			)
		);

		if ( $qr_code ) {
			wp_cache_set( $cache_key, $qr_code, 'qr_trackr', HOUR_IN_SECONDS );
		}
	}

	if ( ! $qr_code ) {
		wp_send_json_error( array( 'message' => esc_html__( 'QR code not found.', 'wp-qr-trackr' ) ) );
		return;
	}

	wp_send_json_success(
		array(
			'destination' => esc_url( $qr_code->destination_url ),
		)
	);
}
add_action( 'wp_ajax_nopriv_qr_trackr_get_destination', 'qr_trackr_get_destination' );
add_action( 'wp_ajax_qr_trackr_get_destination', 'qr_trackr_get_destination' );

/**
 * Handle AJAX request to search posts.
 *
 * @return void
 * @throws Exception If search fails.
 */
function qr_trackr_ajax_search_posts() {
	check_ajax_referer( 'qr_trackr_nonce', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Permission denied.', 'wp-qr-trackr' ) ) );
		return;
	}

	$search = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
	if ( '' === $search ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Search term is required.', 'wp-qr-trackr' ) ) );
		return;
	}

	// Try to get search results from cache first.
	$cache_key = 'qr_trackr_search_' . md5( $search );
	$results   = wp_cache_get( $cache_key, 'qr_trackr' );

	if ( false === $results ) {
		$args = array(
			'post_type'      => array( 'post', 'page' ),
			'post_status'    => 'publish',
			's'              => $search,
			'posts_per_page' => 10,
		);

		$query   = new WP_Query( $args );
		$results = array();

		foreach ( $query->posts as $post ) {
			$results[] = array(
				'id'    => absint( $post->ID ),
				'title' => esc_html( $post->post_title ),
				'url'   => esc_url( get_permalink( $post->ID ) ),
			);
		}

		wp_cache_set( $cache_key, $results, 'qr_trackr', HOUR_IN_SECONDS );
	}

	wp_send_json_success( array( 'results' => $results ) );
}
add_action( 'wp_ajax_qr_trackr_search_posts', 'qr_trackr_ajax_search_posts' );

/**
 * Handle AJAX request to get QR code data.
 *
 * @return void
 * @throws Exception If QR code retrieval fails.
 */
function qr_trackr_ajax_get_qr_code() {
	check_ajax_referer( 'qr_trackr_nonce', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Permission denied.', 'wp-qr-trackr' ) ) );
		return;
	}

	$qr_id = isset( $_POST['qr_id'] ) ? absint( wp_unslash( $_POST['qr_id'] ) ) : 0;
	if ( 0 === $qr_id ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Invalid QR code ID.', 'wp-qr-trackr' ) ) );
		return;
	}

	// Try to get QR code from cache first.
	$cache_key = 'qr_trackr_code_' . $qr_id;
	$qr_code   = wp_cache_get( $cache_key, 'qr_trackr' );

	if ( false === $qr_code ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'qr_trackr_links';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached immediately after query.
		$qr_code = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . esc_sql( $table_name ) . ' WHERE id = %d',
				$qr_id
			)
		);

		if ( $qr_code ) {
			wp_cache_set( $cache_key, $qr_code, 'qr_trackr', HOUR_IN_SECONDS );
		}
	}

	if ( ! $qr_code ) {
		wp_send_json_error( array( 'message' => esc_html__( 'QR code not found.', 'wp-qr-trackr' ) ) );
		return;
	}

	// Get scan stats.
	$stats_cache_key = 'qr_trackr_stats_' . $qr_id;
	$stats           = wp_cache_get( $stats_cache_key, 'qr_trackr' );

	if ( false === $stats ) {
		global $wpdb;
		$stats_table = $wpdb->prefix . 'qr_trackr_stats';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached immediately after query.
		$stats = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . esc_sql( $stats_table ) . ' WHERE qr_id = %d ORDER BY scan_date DESC LIMIT 10',
				$qr_id
			),
			ARRAY_A
		);

		if ( $stats ) {
			wp_cache_set( $stats_cache_key, $stats, 'qr_trackr', HOUR_IN_SECONDS );
		}
	}

	wp_send_json_success(
		array(
			'qr_code' => array(
				'id'            => absint( $qr_code->id ),
				'destination'   => esc_url( $qr_code->destination_url ),
				'qr_code_url'   => esc_url( $qr_code->qr_code_url ),
				'created_at'    => esc_html( $qr_code->created_at ),
				'last_accessed' => esc_html( $qr_code->last_accessed ),
				'access_count'  => absint( $qr_code->access_count ),
			),
			'stats'   => $stats,
		)
	);
}
add_action( 'wp_ajax_qr_trackr_get_qr_code', 'qr_trackr_ajax_get_qr_code' );

/**
 * Handle AJAX request to update scan count.
 *
 * @return void
 * @throws Exception If scan count update fails.
 */
function qr_trackr_ajax_update_scan_count() {
	check_ajax_referer( 'qr_trackr_nonce', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Permission denied.', 'wp-qr-trackr' ) ) );
		return;
	}

	$qr_id = isset( $_POST['qr_id'] ) ? absint( $_POST['qr_id'] ) : 0;
	if ( 0 === $qr_id ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Invalid QR code ID.', 'wp-qr-trackr' ) ) );
		return;
	}

	// Try to get QR code from cache first.
	$cache_key = 'qr_trackr_code_' . $qr_id;
	$qr_code   = wp_cache_get( $cache_key, 'qr_trackr' );

	if ( false === $qr_code ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'qr_trackr_links';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached immediately after query.
		$qr_code = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . esc_sql( $table_name ) . ' WHERE id = %d',
				$qr_id
			)
		);

		if ( $qr_code ) {
			wp_cache_set( $cache_key, $qr_code, 'qr_trackr', HOUR_IN_SECONDS );
		}
	}

	if ( ! $qr_code ) {
		wp_send_json_error( array( 'message' => esc_html__( 'QR code not found.', 'wp-qr-trackr' ) ) );
		return;
	}

	// Update scan count.
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cache invalidated after update.
	$result = $wpdb->update(
		$table_name,
		array(
			'access_count'  => absint( $qr_code->access_count ) + 1,
			'last_accessed' => current_time( 'mysql' ),
		),
		array( 'id' => $qr_id ),
		array( '%d', '%s' ),
		array( '%d' )
	);

	if ( false === $result ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Failed to update scan count.', 'wp-qr-trackr' ) ) );
		return;
	}

	// Clear cache after update.
	wp_cache_delete( $cache_key, 'qr_trackr' );
	wp_cache_delete( 'qr_trackr_stats_' . $qr_id, 'qr_trackr' );

	wp_send_json_success(
		array(
			'message'    => esc_html__( 'Scan count updated successfully.', 'wp-qr-trackr' ),
			'scan_count' => absint( $qr_code->access_count ) + 1,
		)
	);
}
add_action( 'wp_ajax_qr_trackr_update_scan_count', 'qr_trackr_ajax_update_scan_count' );

if ( function_exists( 'qr_trackr_debug_log' ) ) {
	qr_trackr_debug_log( 'Loaded module-ajax.php.' );
}
