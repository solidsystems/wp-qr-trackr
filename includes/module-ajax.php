<?php
/**
 * AJAX handling for the QR Coder plugin.
 *
 * @package WP_QR_TRACKR
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Get the QR code image tag for a given post.
 *
 * @since 1.0.0
 * @param int $post_id The post ID to get the QR code for.
 * @return string The QR code image tag or empty string if not found.
 */
function get_qr_code_image_tag( $post_id ) {
	global $wpdb;

	// Try to get from cache first.
	$cache_key    = 'qr_code_image_' . $post_id;
	$cached_image = wp_cache_get( $cache_key );

	if ( false !== $cached_image ) {
		return $cached_image;
	}

	$table_name = $wpdb->prefix . 'qr_trackr_links';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cache implemented, direct query needed for performance.
	$link = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM $table_name WHERE post_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safe, validated by WordPress.
			$post_id
		)
	);

	$image_tag = '';
	if ( $link ) {
		$image_tag = '<img src="' . esc_url( $link->qr_code_url ) . '" alt="QR Code" />';
		// Cache for 5 minutes.
		wp_cache_set( $cache_key, $image_tag, '', 300 );
	}

	return $image_tag;
}

/**
 * AJAX handler for generating a QR code.
 *
 * @since 1.0.0
 * @return void
 */
function qrc_generate_qr_code_ajax() {
	// Security check.
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'qrc_generate_qr_code' ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Security check failed. Please refresh the page and try again.', 'wp-qr-trackr' ),
			)
		);
		return;
	}

	// Check user capabilities.
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'You do not have permission to perform this action.', 'wp-qr-trackr' ),
			)
		);
		return;
	}

	// Get and validate the post ID.
	$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	if ( ! $post_id || ! get_post( $post_id ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Invalid or non-existent post ID.', 'wp-qr-trackr' ),
			)
		);
		return;
	}

	// Get and validate the destination URL.
	$destination_url = isset( $_POST['destination_url'] ) ? esc_url_raw( wp_unslash( $_POST['destination_url'] ) ) : '';
	if ( empty( $destination_url ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Destination URL is required and must be valid.', 'wp-qr-trackr' ),
			)
		);
		return;
	}

	// Generate the QR code.
	$qr_code_url = qrc_generate_qr_code( $destination_url );

	if ( is_wp_error( $qr_code_url ) ) {
		// Log the error for debugging.
		error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentional debug logging.
			sprintf(
				'QR Code generation failed for post %d: %s',
				$post_id,
				$qr_code_url->get_error_message()
			)
		);

		wp_send_json_error(
			array(
				'message' => $qr_code_url->get_error_message(),
			)
		);
		return;
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cache implemented below, direct query needed for atomic operation.
	$existing_link = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM $table_name WHERE post_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safe, validated by WordPress.
			$post_id
		)
	);

	if ( null !== $existing_link ) {
		// Update existing record.
		$result = $wpdb->update(
			$table_name,
			array(
				'destination_url' => $destination_url,
				'qr_code_url'     => $qr_code_url,
				'updated_at'      => current_time( 'mysql' ),
			),
			array( 'post_id' => $post_id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);
	} else {
		// Insert new record.
		$result = $wpdb->insert(
			$table_name,
			array(
				'post_id'         => $post_id,
				'destination_url' => $destination_url,
				'qr_code_url'     => $qr_code_url,
				'created_at'      => current_time( 'mysql' ),
				'updated_at'      => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%s' )
		);
	}

	if ( false === $result ) {
		error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentional debug logging.
			sprintf(
				'Database operation failed for post %d: %s',
				$post_id,
				$wpdb->last_error
			)
		);

		wp_send_json_error(
			array(
				'message' => __( 'Failed to save QR code data.', 'wp-qr-trackr' ),
			)
		);
		return;
	}

	// Clear the cache.
	wp_cache_delete( 'qr_code_image_' . $post_id );

	wp_send_json_success(
		array(
			'message'     => __( 'QR code generated successfully.', 'wp-qr-trackr' ),
			'qr_code_url' => $qr_code_url,
		)
	);
}
add_action( 'wp_ajax_qrc_generate_qr_code', 'qrc_generate_qr_code_ajax' );

/**
 * AJAX handler for tracking a QR code link click.
 *
 * @since 1.0.0
 * @return void
 */
function qrc_track_link_click_ajax() {
	// Security check.
	if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'qrc_track_link' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'wp-qr-trackr' ), 403 );
	}

	// Get and validate the link ID.
	$link_id = isset( $_GET['link_id'] ) ? absint( $_GET['link_id'] ) : 0;
	if ( ! $link_id ) {
		wp_die( esc_html__( 'Invalid link ID.', 'wp-qr-trackr' ), 400 );
	}

	global $wpdb;

	// Increment the access count and get destination URL in one query for efficiency.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Atomic operation required, caching not applicable.
	$result = $wpdb->query(
		$wpdb->prepare(
			"UPDATE {$wpdb->prefix}qr_trackr_links SET access_count = access_count + 1, last_accessed = %s WHERE id = %d RETURNING destination_url AS url",
			current_time( 'mysql' ),
			$link_id
		)
	);

	if ( false === $result ) {
		error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentional debug logging.
			sprintf(
				'Failed to track QR code click for link ID %d: %s',
				$link_id,
				$wpdb->last_error
			)
		);
		wp_die( esc_html__( 'Failed to process link.', 'wp-qr-trackr' ), 500 );
	}

	// Get the destination URL from the RETURNING clause.
	$destination_url = $wpdb->get_var( 'SELECT url FROM (' . $wpdb->last_query . ') AS t' );

	if ( $destination_url ) {
		wp_safe_redirect( esc_url_raw( $destination_url ), 302 );
		exit;
	}

	wp_die( esc_html__( 'Invalid or expired link.', 'wp-qr-trackr' ), 404 );
}
add_action( 'wp_ajax_qrc_track_link', 'qrc_track_link_click_ajax' );
add_action( 'wp_ajax_nopriv_qrc_track_link', 'qrc_track_link_click_ajax' );

/**
 * AJAX handler for searching posts/pages.
 *
 * @since 1.2.8
 * @return void
 */
function qrc_search_posts_ajax() {
	// Security check.
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'qrc_admin_nonce' ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Security check failed. Please refresh the page and try again.', 'wp-qr-trackr' ),
			)
		);
		return;
	}

	// Check user capabilities.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'You do not have permission to perform this action.', 'wp-qr-trackr' ),
			)
		);
		return;
	}

	// Get and validate the search term.
	$search_term = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';

	if ( empty( $search_term ) || strlen( $search_term ) < 2 ) {
		wp_send_json_error(
			array(
				'message' => __( 'Search term must be at least 2 characters long.', 'wp-qr-trackr' ),
			)
		);
		return;
	}

	// Search for posts/pages.
	$posts = get_posts(
		array(
			'post_type'      => array( 'post', 'page' ),
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			's'              => $search_term,
			'orderby'        => 'relevance',
		)
	);

	$results = array();
	if ( $posts ) {
		foreach ( $posts as $post ) {
			$results[] = array(
				'id'    => $post->ID,
				'title' => $post->post_title,
				'type'  => $post->post_type,
				'url'   => get_permalink( $post->ID ),
			);
		}
	}

	wp_send_json_success( array( 'posts' => $results ) );
}
add_action( 'wp_ajax_qrc_search_posts', 'qrc_search_posts_ajax' );
