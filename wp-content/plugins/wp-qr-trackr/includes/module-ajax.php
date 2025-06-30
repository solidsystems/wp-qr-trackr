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
	exit; // Exit if accessed directly.
}

if ( function_exists( 'qr_trackr_debug_log' ) ) {
	qr_trackr_debug_log( 'Loading module-ajax.php...' );
}

/**
 * Handle AJAX request to get QR code stats.
 *
 * @since 1.0.0
 * @return void
 * @throws WP_Error If stats retrieval fails.
 */
function qr_trackr_ajax_get_stats() {
	// Verify nonce.
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'qr_trackr_nonce' ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Invalid nonce.', 'wp-qr-trackr' ) ) );
		return;
	}

	// Get and validate link ID.
	$link_id = isset( $_POST['link_id'] ) ? absint( $_POST['link_id'] ) : 0;
	if ( 0 === $link_id ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Invalid link ID.', 'wp-qr-trackr' ) ) );
		return;
	}

	// Try to get stats from cache first.
	$cache_key = 'qrc_stats_' . $link_id;
	$stats     = wp_cache_get( $cache_key, 'qrc_stats' );

	if ( false === $stats ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'qr_trackr_stats';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Caching implemented above, filtered query needed for display.
		$stats = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}qr_trackr_stats WHERE link_id = %d ORDER BY scan_time DESC LIMIT %d",
				$link_id,
				10
			),
			ARRAY_A
		);

		if ( $stats ) {
			wp_cache_set( $cache_key, $stats, 'qrc_stats', HOUR_IN_SECONDS );
		}
	}

	if ( empty( $stats ) ) {
		wp_send_json_success( array( 'stats' => array() ) );
		return;
	}

	$escaped_stats = array();
	foreach ( $stats as $row ) {
		$escaped_stats[] = array(
			'id'         => absint( $row['id'] ),
			'link_id'    => absint( $row['link_id'] ),
			'scan_time'  => esc_html( $row['scan_time'] ),
			'ip_address' => esc_html( $row['ip_address'] ),
			'user_agent' => esc_html( $row['user_agent'] ),
			'location'   => esc_html( $row['location'] ),
		);
	}

	wp_send_json_success( array( 'stats' => $escaped_stats ) );
}
add_action( 'wp_ajax_qr_trackr_get_stats', 'qr_trackr_ajax_get_stats' );

/**
 * Handle AJAX request to generate a new QR code for a post.
 *
 * @since 1.0.0
 * @return void
 * @throws WP_Error If QR code generation fails.
 */
function qr_trackr_ajax_generate_qr() {
	// Verify nonce.
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'qr_trackr_nonce' ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Invalid nonce.', 'wp-qr-trackr' ) ) );
		return;
	}

	// Check user capabilities.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Insufficient permissions.', 'wp-qr-trackr' ) ) );
		return;
	}

	// Get and validate link ID.
	$link_id = isset( $_POST['link_id'] ) ? absint( $_POST['link_id'] ) : 0;
	if ( 0 === $link_id ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Invalid link ID.', 'wp-qr-trackr' ) ) );
		return;
	}

	// Try to get link from cache first.
	$cache_key = 'qrc_link_' . $link_id;
	$link      = wp_cache_get( $cache_key, 'qrc_links' );

	if ( false === $link ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'qr_trackr_links';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Caching implemented above, single-row lookup needed for display.
		$link = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}qr_trackr_links WHERE id = %d",
				$link_id
			),
			ARRAY_A
		);

		if ( $link ) {
			wp_cache_set( $cache_key, $link, 'qrc_links', HOUR_IN_SECONDS );
		}
	}

	if ( ! $link ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Link not found.', 'wp-qr-trackr' ) ) );
		return;
	}

	// Generate QR code.
	$qr_image = qr_trackr_generate_qr_image_for_link( $link_id );
	if ( ! $qr_image ) {
		qr_trackr_debug_log( sprintf( 'Failed to generate QR code for link ID: %d.', $link_id ) );
		wp_send_json_error( array( 'message' => esc_html__( 'Failed to generate QR code.', 'wp-qr-trackr' ) ) );
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
		qr_trackr_debug_log( sprintf( 'Failed to update QR code URL in database for link ID: %d.', $link_id ) );
		wp_send_json_error( array( 'message' => esc_html__( 'Failed to update QR code URL.', 'wp-qr-trackr' ) ) );
		return;
	}

	// Clear cache after update.
	wp_cache_delete( $cache_key, 'qrc_links' );

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
 * @since 1.0.0
 * @return void
 * @throws WP_Error If QR code creation fails.
 */
function qr_trackr_create_qr_code() {
	// Verify nonce.
	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'qr_trackr_nonce' ) ) {
		wp_send_json_error( esc_html__( 'Invalid nonce.', 'wp-qr-trackr' ) );
		return;
	}

	// Check user capabilities.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( esc_html__( 'Insufficient permissions.', 'wp-qr-trackr' ) );
		return;
	}

	// Get destination type.
	$destination_type = isset( $_POST['destination_type'] ) ? sanitize_text_field( wp_unslash( $_POST['destination_type'] ) ) : '';
	
	$destination_url = '';
	$post_id = 0;

	if ( 'post' === $destination_type ) {
		// Get and validate post ID.
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		if ( 0 === $post_id ) {
			wp_send_json_error( esc_html__( 'Please select a post or page.', 'wp-qr-trackr' ) );
			return;
		}
		$destination_url = get_permalink( $post_id );
		if ( ! $destination_url ) {
			wp_send_json_error( esc_html__( 'Invalid post ID.', 'wp-qr-trackr' ) );
			return;
		}
	} elseif ( 'external' === $destination_type ) {
		$destination_url = isset( $_POST['destination'] ) ? esc_url_raw( wp_unslash( $_POST['destination'] ) ) : '';
		if ( empty( $destination_url ) ) {
			wp_send_json_error( esc_html__( 'Please enter a destination URL.', 'wp-qr-trackr' ) );
			return;
		}
	} elseif ( 'custom' === $destination_type ) {
		$destination_url = isset( $_POST['destination'] ) ? esc_url_raw( wp_unslash( $_POST['destination'] ) ) : '';
		if ( empty( $destination_url ) ) {
			wp_send_json_error( esc_html__( 'Please enter a destination URL.', 'wp-qr-trackr' ) );
			return;
		}
	} else {
		wp_send_json_error( esc_html__( 'Invalid destination type.', 'wp-qr-trackr' ) );
		return;
	}

	// Generate unique tracking code.
	$tracking_code = qr_trackr_generate_tracking_code();
	
	// Insert into database.
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';
	
	$result = $wpdb->insert(
		$table_name,
		array(
			'post_id'         => $post_id > 0 ? $post_id : null,
			'destination_url' => $destination_url,
			'qr_code'         => $tracking_code,
			'scans'           => 0,
			'created_at'      => current_time( 'mysql', true ),
		),
		array(
			'%d',
			'%s',
			'%s',
			'%d',
			'%s',
		)
	);

	if ( false === $result ) {
		if ( function_exists( 'qr_trackr_debug_log' ) ) {
			qr_trackr_debug_log( 'Failed to insert QR code: ' . $wpdb->last_error );
		}
		wp_send_json_error( esc_html__( 'Failed to create QR code.', 'wp-qr-trackr' ) );
		return;
	}

	$link_id = $wpdb->insert_id;

	// Generate QR code image.
	$qr_image_url = qr_trackr_generate_qr_image( $link_id );
	if ( ! $qr_image_url ) {
		wp_send_json_error( esc_html__( 'Failed to generate QR code image.', 'wp-qr-trackr' ) );
		return;
	}

	// Clear any relevant caches.
	wp_cache_delete( 'qr_trackr_all_codes', 'qr_trackr' );

	wp_send_json_success( esc_html__( 'QR code created successfully!', 'wp-qr-trackr' ) );
}
add_action( 'wp_ajax_qr_trackr_create_qr_code', 'qr_trackr_create_qr_code' );

/**
 * Handle AJAX request to update destination URL.
 *
 * @since 1.0.0
 * @return void
 * @throws WP_Error If URL update fails.
 */
function qr_trackr_ajax_update_destination() {
	// Verify nonce.
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'qr_trackr_nonce' ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Invalid nonce.', 'wp-qr-trackr' ) ) );
		return;
	}

	// Check user capabilities.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Insufficient permissions.', 'wp-qr-trackr' ) ) );
		return;
	}

	// Get and validate input.
	$link_id = isset( $_POST['link_id'] ) ? absint( $_POST['link_id'] ) : 0;
	if ( 0 === $link_id ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Invalid link ID.', 'wp-qr-trackr' ) ) );
		return;
	}

	$destination = isset( $_POST['destination'] ) ? esc_url_raw( wp_unslash( $_POST['destination'] ) ) : '';
	if ( empty( $destination ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Invalid destination URL.', 'wp-qr-trackr' ) ) );
		return;
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cache invalidated after update.
	$result = $wpdb->update(
		$table_name,
		array( 'destination_url' => $destination ),
		array( 'id' => $link_id ),
		array( '%s' ),
		array( '%d' )
	);

	if ( false === $result ) {
		qr_trackr_debug_log( sprintf( 'Failed to update destination URL for link ID: %d.', $link_id ) );
		wp_send_json_error( array( 'message' => esc_html__( 'Failed to update destination URL.', 'wp-qr-trackr' ) ) );
		return;
	}

	// Clear cache.
	wp_cache_delete( 'qrc_link_' . $link_id, 'qrc_links' );

	wp_send_json_success( array( 'message' => esc_html__( 'Destination URL updated successfully.', 'wp-qr-trackr' ) ) );
}
add_action( 'wp_ajax_qr_trackr_update_destination', 'qr_trackr_ajax_update_destination' );

/**
 * Handle AJAX request to track a QR code scan.
 *
 * @return void
 */
function qr_trackr_track_scan() {
	// Verify nonce.
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'qr_trackr_nonce' ) ) {
		wp_send_json_error( esc_html__( 'Invalid nonce.', 'wp-qr-trackr' ) );
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

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached immediately after query.
		$link = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}qr_trackr_links WHERE id = %d",
				$link_id
			),
			ARRAY_A
		);

		if ( $link ) {
			wp_cache_set( $cache_key, $link, 'qr_trackr', HOUR_IN_SECONDS );
		}
	}

	if ( ! $link ) {
		wp_send_json_error( esc_html__( 'Link not found.', 'wp-qr-trackr' ) );
		return;
	}

	// Get scan data.
	$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
	$ip_address = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$location   = isset( $_POST['location'] ) ? sanitize_text_field( wp_unslash( $_POST['location'] ) ) : '';

	// Record scan.
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cache invalidated after insert.
	$result = $wpdb->insert(
		"{$wpdb->prefix}qr_trackr_stats",
		array(
			'link_id'    => $link_id,
			'user_agent' => $user_agent,
			'ip_address' => $ip_address,
			'location'   => $location,
			'scan_time'  => current_time( 'mysql', true ),
		),
		array(
			'%d',
			'%s',
			'%s',
			'%s',
			'%s',
		)
	);

	if ( false === $result ) {
		wp_send_json_error( esc_html__( 'Failed to record scan.', 'wp-qr-trackr' ) );
		return;
	}

	// Update link scan count.
	$result = $wpdb->update(
		"{$wpdb->prefix}qr_trackr_links",
		array(
			'access_count'  => absint( $link['access_count'] ) + 1,
			'last_accessed' => current_time( 'mysql', true ),
		),
		array( 'id' => $link_id ),
		array( '%d', '%s' ),
		array( '%d' )
	);

	if ( false === $result ) {
		wp_send_json_error( esc_html__( 'Failed to update scan count.', 'wp-qr-trackr' ) );
		return;
	}

	// Clear cache after update.
	wp_cache_delete( $cache_key, 'qr_trackr' );
	wp_cache_delete( 'qr_trackr_stats_' . $link_id, 'qr_trackr' );

	wp_send_json_success(
		array(
			'message'    => esc_html__( 'Scan recorded successfully.', 'wp-qr-trackr' ),
			'scan_count' => absint( $link['access_count'] ) + 1,
		)
	);
}
add_action( 'wp_ajax_qr_trackr_track_scan', 'qr_trackr_track_scan' );

/**
 * Handle AJAX request to get QR code destination URL.
 *
 * @return void
 */
function qr_trackr_get_destination() {
	// Verify nonce.
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'qr_trackr_nonce' ) ) {
		wp_send_json_error( esc_html__( 'Invalid nonce.', 'wp-qr-trackr' ) );
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

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached immediately after query.
		$link = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}qr_trackr_links WHERE id = %d",
				$link_id
			),
			ARRAY_A
		);

		if ( $link ) {
			wp_cache_set( $cache_key, $link, 'qr_trackr', HOUR_IN_SECONDS );
		}
	}

	if ( ! $link ) {
		wp_send_json_error( esc_html__( 'Link not found.', 'wp-qr-trackr' ) );
		return;
	}

	wp_send_json_success( array( 'destination' => esc_url( $link['destination_url'] ) ) );
}
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

	// Accept both 'search' and 'term' parameters for compatibility.
	$search = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
	if ( empty( $search ) ) {
		$search = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';
	}

	// If still empty, return recent posts.
	if ( empty( $search ) ) {
		$args = array(
			'post_type'      => array( 'post', 'page' ),
			'post_status'    => 'publish',
			'posts_per_page' => 10,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);
	} else {
		// Try to get search results from cache first.
		$cache_key = 'qr_trackr_search_' . md5( $search );
		$results   = wp_cache_get( $cache_key, 'qr_trackr' );

		if ( false !== $results ) {
			wp_send_json_success( $results );
			return;
		}

		$args = array(
			'post_type'      => array( 'post', 'page' ),
			'post_status'    => 'publish',
			's'              => $search,
			'posts_per_page' => 10,
		);
	}

	$query   = new WP_Query( $args );
	$results = array();

	foreach ( $query->posts as $post ) {
		$results[] = array(
			'ID'    => absint( $post->ID ),
			'title' => esc_html( $post->post_title ),
			'url'   => esc_url( get_permalink( $post->ID ) ),
		);
	}

	// Cache search results.
	if ( ! empty( $search ) ) {
		wp_cache_set( $cache_key, $results, 'qr_trackr', HOUR_IN_SECONDS );
	}

	wp_send_json_success( $results );
}
add_action( 'wp_ajax_qr_trackr_search_posts', 'qr_trackr_ajax_search_posts' );

/**
 * Handle AJAX request to get all posts for dropdown.
 *
 * @return void
 */
function qr_trackr_ajax_get_posts() {
	check_ajax_referer( 'qr_trackr_nonce', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Permission denied.', 'wp-qr-trackr' ) ) );
		return;
	}

	// Try to get posts from cache first.
	$cache_key = 'qr_trackr_all_posts';
	$results   = wp_cache_get( $cache_key, 'qr_trackr' );

	if ( false === $results ) {
		$args = array(
			'post_type'      => array( 'post', 'page' ),
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		$query   = new WP_Query( $args );
		$results = array();

		foreach ( $query->posts as $post ) {
			$results[] = array(
				'ID'          => absint( $post->ID ),
				'post_title'  => esc_html( $post->post_title ),
				'post_type'   => esc_html( $post->post_type ),
				'permalink'   => esc_url( get_permalink( $post->ID ) ),
			);
		}

		wp_cache_set( $cache_key, $results, 'qr_trackr', HOUR_IN_SECONDS );
	}

	wp_send_json_success( $results );
}
add_action( 'wp_ajax_qr_trackr_get_posts', 'qr_trackr_ajax_get_posts' );

/**
 * Handle AJAX request to get QR code link.
 *
 * @return void
 */
function qr_trackr_ajax_get_link() {
	// Verify nonce.
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'qr_trackr_nonce' ) ) {
		wp_send_json_error( esc_html__( 'Invalid nonce.', 'wp-qr-trackr' ) );
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

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached immediately after query.
		$link = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}qr_trackr_links WHERE id = %d",
				$link_id
			),
			ARRAY_A
		);

		if ( $link ) {
			wp_cache_set( $cache_key, $link, 'qr_trackr', HOUR_IN_SECONDS );
		}
	}

	if ( ! $link ) {
		wp_send_json_error( esc_html__( 'Link not found.', 'wp-qr-trackr' ) );
		return;
	}

	wp_send_json_success( array( 'link' => $link ) );
}
add_action( 'wp_ajax_qr_trackr_get_link', 'qr_trackr_ajax_get_link' );

/**
 * Handle AJAX request to get QR code link by ID.
 *
 * @return void
 */
function qr_trackr_ajax_get_link_by_id() {
	// Verify nonce.
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'qr_trackr_nonce' ) ) {
		wp_send_json_error( esc_html__( 'Invalid nonce.', 'wp-qr-trackr' ) );
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

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached immediately after query.
		$link = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}qr_trackr_links WHERE id = %d",
				$link_id
			),
			ARRAY_A
		);

		if ( $link ) {
			wp_cache_set( $cache_key, $link, 'qr_trackr', HOUR_IN_SECONDS );
		}
	}

	if ( ! $link ) {
		wp_send_json_error( esc_html__( 'Link not found.', 'wp-qr-trackr' ) );
		return;
	}

	wp_send_json_success( array( 'link' => $link ) );
}
add_action( 'wp_ajax_qr_trackr_get_link_by_id', 'qr_trackr_ajax_get_link_by_id' );

/**
 * Handle AJAX request to update scan count.
 *
 * @return void
 */
function qr_trackr_ajax_update_scan_count() {
	// Verify nonce.
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'qr_trackr_nonce' ) ) {
		wp_send_json_error( esc_html__( 'Invalid nonce.', 'wp-qr-trackr' ) );
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

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached immediately after query.
		$link = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}qr_trackr_links WHERE id = %d",
				$link_id
			),
			ARRAY_A
		);

		if ( $link ) {
			wp_cache_set( $cache_key, $link, 'qr_trackr', HOUR_IN_SECONDS );
		}
	}

	if ( ! $link ) {
		wp_send_json_error( esc_html__( 'Link not found.', 'wp-qr-trackr' ) );
		return;
	}

	// Update scan count.
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cache invalidated after update.
	$result = $wpdb->update(
		"{$wpdb->prefix}qr_trackr_links",
		array(
			'access_count'  => absint( $link['access_count'] ) + 1,
			'last_accessed' => current_time( 'mysql' ),
		),
		array( 'id' => $link_id ),
		array( '%d', '%s' ),
		array( '%d' )
	);

	if ( false === $result ) {
		wp_send_json_error( esc_html__( 'Failed to update scan count.', 'wp-qr-trackr' ) );
		return;
	}

	// Clear cache after update.
	wp_cache_delete( $cache_key, 'qr_trackr' );
	wp_cache_delete( 'qr_trackr_stats_' . $link_id, 'qr_trackr' );

	wp_send_json_success(
		array(
			'message'    => esc_html__( 'Scan count updated successfully.', 'wp-qr-trackr' ),
			'scan_count' => absint( $link['access_count'] ) + 1,
		)
	);
}
add_action( 'wp_ajax_qr_trackr_update_scan_count', 'qr_trackr_ajax_update_scan_count' );

/**
 * Handle AJAX request to get QR code data.
 *
 * @return void
 */
function qr_trackr_ajax_get_qr_code() {
	// Verify nonce.
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'qr_trackr_nonce' ) ) {
		wp_send_json_error( esc_html__( 'Invalid nonce.', 'wp-qr-trackr' ) );
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

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached immediately after query.
		$link = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}qr_trackr_links WHERE id = %d",
				$link_id
			),
			ARRAY_A
		);

		if ( $link ) {
			wp_cache_set( $cache_key, $link, 'qr_trackr', HOUR_IN_SECONDS );
		}
	}

	if ( ! $link ) {
		wp_send_json_error( esc_html__( 'Link not found.', 'wp-qr-trackr' ) );
		return;
	}

	// Get scan stats.
	$stats_cache_key = 'qr_trackr_stats_' . $link_id;
	$stats           = wp_cache_get( $stats_cache_key, 'qr_trackr' );

	if ( false === $stats ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached immediately after query.
		$stats = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}qr_trackr_stats WHERE link_id = %d ORDER BY scan_time DESC LIMIT 10",
				$link_id
			),
			ARRAY_A
		);

		if ( $stats ) {
			wp_cache_set( $stats_cache_key, $stats, 'qr_trackr', HOUR_IN_SECONDS );
		}
	}

	wp_send_json_success(
		array(
			'link'  => array(
				'id'            => absint( $link['id'] ),
				'destination'   => esc_url( $link['destination_url'] ),
				'qr_code_url'   => esc_url( $link['qr_code_url'] ),
				'created_at'    => esc_html( $link['created_at'] ),
				'last_accessed' => esc_html( $link['last_accessed'] ),
				'access_count'  => absint( $link['access_count'] ),
			),
			'stats' => $stats,
		)
	);
}
add_action( 'wp_ajax_qr_trackr_get_qr_code', 'qr_trackr_ajax_get_qr_code' );

if ( function_exists( 'qr_trackr_debug_log' ) ) {
	qr_trackr_debug_log( 'Loaded module-ajax.php.' );
}
