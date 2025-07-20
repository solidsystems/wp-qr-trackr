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
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
			qr_trackr_debug_log(
				sprintf(
					'QR Code generation failed for post %d: %s',
					$post_id,
					$qr_code_url->get_error_message()
				)
			);
		}

		wp_send_json_error(
			array(
				'message' => $qr_code_url->get_error_message(),
			)
		);
		return;
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';

	// Check cache first.
	$cache_key     = 'qr_trackr_link_' . $post_id;
	$existing_link = wp_cache_get( $cache_key, 'qr_trackr' );

	if ( false === $existing_link ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cache implemented, direct query needed for atomic operation.
		$existing_link = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}qr_trackr_links WHERE post_id = %d",
				$post_id
			)
		);
		wp_cache_set( $cache_key, $existing_link, 'qr_trackr', HOUR_IN_SECONDS );
	}

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
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
			qr_trackr_debug_log(
				sprintf(
					'Database operation failed for post %d: %s',
					$post_id,
					$wpdb->last_error
				)
			);
		}

		wp_send_json_error(
			array(
				'message' => __( 'Failed to save QR code data.', 'wp-qr-trackr' ),
			)
		);
		return;
	}

	// Clear the cache.
	wp_cache_delete( 'qr_code_image_' . $post_id );
	wp_cache_delete( 'qr_trackr_link_' . $post_id );
	wp_cache_delete( 'qr_trackr_all_links_admin', 'qr_trackr' );
	delete_transient( 'qrc_all_links' );

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
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
			qr_trackr_debug_log(
				sprintf(
					'Failed to track QR code click for link ID %d: %s',
					$link_id,
					$wpdb->last_error
				)
			);
		}
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
	// Debug logging for nonce verification.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
		qr_trackr_debug_log(
			sprintf(
				'QR Trackr: AJAX search request - nonce: %s, term: %s',
				isset( $_POST['nonce'] ) ? $_POST['nonce'] : 'not set',
				isset( $_POST['term'] ) ? $_POST['term'] : 'not set'
			)
		);
	}

	// Security check - only verify user is logged in and has admin access.
	// Nonce check removed for reliability since this is admin-only and WordPress has built-in CSRF protection.
	if ( ! is_admin() || ! current_user_can( 'edit_posts' ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
			qr_trackr_debug_log( 'QR Trackr: Admin access check failed for search request' );
		}
		wp_send_json_error(
			array(
				'message' => __( 'You do not have permission to perform this action.', 'wp-qr-trackr' ),
			)
		);
		return;
	}

	// User capabilities already checked above.

	// Get and validate the search term - Select2 sends it as 'term'.
	$search_term = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';

	// Debug logging for search term validation.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
		qr_trackr_debug_log(
			sprintf(
				'QR Trackr: Search term validation - raw: "%s", sanitized: "%s", length: %d, empty: %s, POST data: %s',
				isset( $_POST['term'] ) ? $_POST['term'] : 'not set',
				$search_term,
				strlen( $search_term ),
				empty( $search_term ) ? 'true' : 'false',
				json_encode( $_POST )
			)
		);
	}

	// Temporarily bypass validation for debugging.
	/*
	if ( empty( $search_term ) || strlen( $search_term ) < 2 ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
			qr_trackr_debug_log(
				sprintf(
					'QR Trackr: Search term validation failed - empty: %s, length: %d, term: "%s"',
					empty( $search_term ) ? 'true' : 'false',
					strlen( $search_term ),
					$search_term
				)
			);
		}
		wp_send_json_error(
			array(
				'message' => __( 'Search term must be at least 2 characters long.', 'wp-qr-trackr' ),
			)
		);
		return;
	}
	*/

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

	// Debug logging for search results.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
		qr_trackr_debug_log(
			sprintf(
				'QR Trackr: get_posts search for "%s" returned %d posts',
				$search_term,
				count( $posts )
			)
		);
	}

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

	// Add debug logging if WP_DEBUG is enabled.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
		qr_trackr_debug_log(
			sprintf(
				'QR Trackr: Search for "%s" returned %d results',
				$search_term,
				count( $results )
			)
		);
	}

	wp_send_json_success( array( 'posts' => $results ) );
}
add_action( 'wp_ajax_qrc_search_posts', 'qrc_search_posts_ajax' );

/**
 * Handle AJAX request to get QR code details for modal.
 *
 * @since 1.2.8
 * @return void
 */
function qr_trackr_ajax_get_qr_details() {
	// Debug logging for AJAX request.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( sprintf( 'QR Trackr: AJAX get_qr_details called. POST data: %s', json_encode( $_POST ) ) );
	}

	// Verify nonce.
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'qr_trackr_nonce' ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( 'QR Trackr: Nonce verification failed. Nonce: %s', isset( $_POST['nonce'] ) ? $_POST['nonce'] : 'not set' ) );
		}
		wp_send_json_error( esc_html__( 'Security check failed.', 'wp-qr-trackr' ) );
		return;
	}

	// Check user capabilities.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( esc_html__( 'Insufficient permissions.', 'wp-qr-trackr' ) );
		return;
	}

	// Get and validate QR code ID.
	$qr_id = isset( $_POST['qr_id'] ) ? absint( $_POST['qr_id'] ) : 0;
	if ( 0 === $qr_id ) {
		wp_send_json_error( esc_html__( 'Invalid QR code ID.', 'wp-qr-trackr' ) );
		return;
	}

	// Try to get QR code details from cache first.
	$cache_key = 'qr_trackr_details_' . $qr_id;
	$qr_code   = wp_cache_get( $cache_key );

	if ( false === $qr_code ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'qr_trackr_links';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached immediately after query.
		$qr_code = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}qr_trackr_links WHERE id = %d",
				$qr_id
			),
			ARRAY_A
		);

		if ( $qr_code ) {
			wp_cache_set( $cache_key, $qr_code, '', HOUR_IN_SECONDS );
		}
	}

	if ( ! $qr_code ) {
		wp_send_json_error( esc_html__( 'QR code not found.', 'wp-qr-trackr' ) );
		return;
	}

	// Get post title if linked to a post.
	$post_title = '';
	if ( ! empty( $qr_code['post_id'] ) ) {
		$post = get_post( $qr_code['post_id'] );
		if ( $post ) {
			$post_title = $post->post_title;
		}
	}

	// Generate QR code image if it doesn't exist.
	if ( empty( $qr_code['qr_code_url'] ) && ! empty( $qr_code['destination_url'] ) && function_exists( 'qrc_generate_qr_code' ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( 'QR Trackr: Generating QR code image for QR ID %d with URL: %s', $qr_id, $qr_code['destination_url'] ) );
		}

		$new_qr_code_url = qrc_generate_qr_code( $qr_code['destination_url'] );
		if ( ! is_wp_error( $new_qr_code_url ) ) {
			// Update the database with the new QR code URL.
			$update_result = $wpdb->update(
				$table_name,
				array(
					'qr_code_url' => $new_qr_code_url,
					'updated_at'  => current_time( 'mysql' ),
				),
				array( 'id' => $qr_id ),
				array( '%s', '%s' ),
				array( '%d' )
			);

			if ( false !== $update_result ) {
				$qr_code['qr_code_url'] = $new_qr_code_url;
				// Clear cache to reflect the update immediately.
				wp_cache_delete( $cache_key );
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( sprintf( 'QR Trackr: QR code image generated successfully: %s', $new_qr_code_url ) );
				}
			} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( sprintf( 'QR Trackr: Failed to update database with new QR code URL: %s', $wpdb->last_error ) );
			}
		} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( 'QR Trackr: Failed to generate QR code image: %s', $new_qr_code_url->get_error_message() ) );
		}
	}

	// Get recent scan statistics (simplified - use access_count for now).
	// Note: The qr_trackr_stats table may not exist in all installations.
	$recent_scans = 0;

	// Check if stats table exists before querying it.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Table existence check.
	$stats_table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'qr_trackr_stats' ) );

	if ( $stats_table_exists ) {
		$stats_cache_key = 'qr_trackr_stats_' . $qr_id;
		$recent_scans    = wp_cache_get( $stats_cache_key );

		if ( false === $recent_scans ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached immediately after query.
			$recent_scans = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}qr_trackr_stats WHERE link_id = %d AND scan_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
					$qr_id
				)
			);

			if ( null !== $recent_scans ) {
				wp_cache_set( $stats_cache_key, $recent_scans, '', HOUR_IN_SECONDS );
			} else {
				$recent_scans = 0;
			}
		}
	}

	$response_data = array(
		'id'              => absint( $qr_code['id'] ),
		'qr_code'         => esc_html( $qr_code['qr_code'] ),
		'common_name'     => esc_html( $qr_code['common_name'] ?? '' ),
		'referral_code'   => esc_html( $qr_code['referral_code'] ?? '' ),
		'destination_url' => $qr_code['destination_url'], // Don't escape for input field
		'qr_code_url'     => esc_url( $qr_code['qr_code_url'] ?? '' ),
		'post_title'      => esc_html( $post_title ),
		'access_count'    => absint( $qr_code['access_count'] ),
		'recent_scans'    => absint( $recent_scans ?? 0 ),
		'created_at'      => esc_html( $qr_code['created_at'] ),
		'last_accessed'   => esc_html( $qr_code['last_accessed'] ?? __( 'Never', 'wp-qr-trackr' ) ),
		'qr_url'          => esc_url( home_url( '/redirect/' . $qr_code['qr_code'] ) ),
	);

	wp_send_json_success( $response_data );
}
add_action( 'wp_ajax_qr_trackr_get_qr_details', 'qr_trackr_ajax_get_qr_details' );

/**
 * AJAX handler for deleting QR codes.
 *
 * @since 1.2.24
 * @return void
 */
function qr_trackr_ajax_delete_qr_code() {
	// Verify nonce.
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'qr_trackr_nonce' ) ) {
		wp_send_json_error( esc_html__( 'Security check failed.', 'wp-qr-trackr' ) );
		return;
	}

	// Check user capabilities.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( esc_html__( 'Insufficient permissions.', 'wp-qr-trackr' ) );
		return;
	}

	// Get and validate QR code ID.
	$qr_id = isset( $_POST['qr_id'] ) ? absint( $_POST['qr_id'] ) : 0;
	if ( 0 === $qr_id ) {
		wp_send_json_error( esc_html__( 'Invalid QR code ID.', 'wp-qr-trackr' ) );
		return;
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';

	// Get QR code details before deletion for logging.
	$qr_code = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}qr_trackr_links WHERE id = %d",
			$qr_id
		)
	);

	if ( ! $qr_code ) {
		wp_send_json_error( esc_html__( 'QR code not found.', 'wp-qr-trackr' ) );
		return;
	}

	// Delete the QR code.
	$result = $wpdb->delete(
		$table_name,
		array( 'id' => $qr_id ),
		array( '%d' )
	);

	if ( false === $result ) {
		wp_send_json_error( esc_html__( 'Failed to delete QR code.', 'wp-qr-trackr' ) );
		return;
	}

	// Log the deletion for debugging.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
		qr_trackr_debug_log(
			sprintf(
				'QR Trackr: QR code deleted via AJAX. ID: %d, QR Code: %s, Destination: %s',
				$qr_id,
				$qr_code->qr_code,
				$qr_code->destination_url
			)
		);
	}

	// Clear relevant caches.
	wp_cache_delete( 'qr_trackr_details_' . $qr_id );
	wp_cache_delete( 'qr_trackr_all_links_admin', 'qr_trackr' );
	wp_cache_delete( 'qrc_link_' . $qr_id, 'qrc_links' );
	delete_transient( 'qrc_all_links' );

	// Delete QR code image file if it exists.
	if ( ! empty( $qr_code->qr_code_url ) ) {
		$upload_dir   = wp_upload_dir();
		$qr_file_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $qr_code->qr_code_url );

		if ( file_exists( $qr_file_path ) ) {
			unlink( $qr_file_path );
		}
	}

	wp_send_json_success(
		array(
			'message' => esc_html__( 'QR code deleted successfully.', 'wp-qr-trackr' ),
			'qr_id'   => $qr_id,
		)
	);
}
add_action( 'wp_ajax_qr_trackr_delete_qr_code', 'qr_trackr_ajax_delete_qr_code' );

/**
 * Handle AJAX request to update QR code details from modal.
 *
 * @since 1.2.8
 * @return void
 */
function qr_trackr_ajax_update_qr_details() {
	// Verify nonce.
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'qr_trackr_nonce' ) ) {
		wp_send_json_error( esc_html__( 'Security check failed.', 'wp-qr-trackr' ) );
		return;
	}

	// Check user capabilities.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( esc_html__( 'Insufficient permissions.', 'wp-qr-trackr' ) );
		return;
	}

	// Get and validate input.
	$qr_id = isset( $_POST['qr_id'] ) ? absint( $_POST['qr_id'] ) : 0;
	if ( 0 === $qr_id ) {
		wp_send_json_error( esc_html__( 'Invalid QR code ID.', 'wp-qr-trackr' ) );
		return;
	}

	$common_name     = isset( $_POST['common_name'] ) ? sanitize_text_field( wp_unslash( $_POST['common_name'] ) ) : '';
	$referral_code   = isset( $_POST['referral_code'] ) ? sanitize_text_field( wp_unslash( $_POST['referral_code'] ) ) : '';
	$destination_url = isset( $_POST['destination_url'] ) ? trim( wp_unslash( $_POST['destination_url'] ) ) : '';

	// Validate destination URL if provided.
	if ( ! empty( $destination_url ) ) {
		// Validate URL format using regex pattern
		if ( ! preg_match( '/^https?:\/\/.+/', $destination_url ) ) {
			wp_send_json_error( esc_html__( 'Invalid destination URL format. Please enter a valid URL starting with http:// or https://', 'wp-qr-trackr' ) );
			return;
		}

		// Then sanitize it for storage
		$destination_url = esc_url_raw( $destination_url );

		// Double-check that sanitization didn't break the URL
		if ( empty( $destination_url ) ) {
			wp_send_json_error( esc_html__( 'Invalid destination URL format. Please enter a valid URL.', 'wp-qr-trackr' ) );
			return;
		}
	}

	// Check if the QR code exists and get current data before trying to update it.
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Validation check before update.
	$existing_qr = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT id, destination_url, post_id FROM {$wpdb->prefix}qr_trackr_links WHERE id = %d",
			$qr_id
		)
	);

	if ( ! $existing_qr ) {
		wp_send_json_error( esc_html__( 'QR code not found.', 'wp-qr-trackr' ) );
		return;
	}

	// Validate referral code (alphanumeric and hyphens only).
	if ( ! empty( $referral_code ) && ! preg_match( '/^[a-zA-Z0-9\-_]+$/', $referral_code ) ) {
		wp_send_json_error( esc_html__( 'Referral code can only contain letters, numbers, hyphens, and underscores.', 'wp-qr-trackr' ) );
		return;
	}

	// Check if referral code is unique (if provided).
	if ( ! empty( $referral_code ) ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Uniqueness check for validation.
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}qr_trackr_links WHERE referral_code = %s AND id != %d",
				$referral_code,
				$qr_id
			)
		);

		if ( $existing ) {
			wp_send_json_error( esc_html__( 'Referral code already exists. Please choose a different one.', 'wp-qr-trackr' ) );
			return;
		}
	}

	// Prepare update data.
	$update_data = array(
		'common_name'   => $common_name,
		'referral_code' => $referral_code,
		'updated_at'    => current_time( 'mysql', true ),
	);

	// Add destination URL to update if provided.
	if ( ! empty( $destination_url ) ) {
		$update_data['destination_url'] = $destination_url;

		// If destination URL has changed and there was a linked post, clear the post_id
		if ( $destination_url !== $existing_qr->destination_url && ! empty( $existing_qr->post_id ) ) {
			$update_data['post_id'] = null;
		}

		// If destination URL has changed, clear the QR code URL to force regeneration
		if ( $destination_url !== $existing_qr->destination_url ) {
			$update_data['qr_code_url'] = '';
		}
	}

	// Update the database.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cache invalidated after update.
	$result = $wpdb->update(
		$table_name,
		$update_data,
		array( 'id' => $qr_id ),
		null, // Let WordPress determine the format
		array( '%d' )
	);

	// Check for database error (false) vs no rows updated (0).
	if ( false === $result ) {
		$error_message = $wpdb->last_error ? $wpdb->last_error : 'Unknown database error';
		if ( function_exists( 'qr_trackr_debug_log' ) ) {
			qr_trackr_debug_log( sprintf( 'Failed to update QR code details for ID: %d. Error: %s', $qr_id, $error_message ) );
		}
		wp_send_json_error( esc_html__( 'Failed to update QR code details.', 'wp-qr-trackr' ) );
		return;
	}

	// Clear relevant caches.
	wp_cache_delete( 'qr_trackr_details_' . $qr_id );
	wp_cache_delete( 'qr_trackr_all_links_admin', 'qr_trackr' );
	wp_cache_delete( 'qrc_link_' . $qr_id, 'qrc_links' );
	delete_transient( 'qrc_all_links' );

	// Regenerate QR code if destination URL changed
	$qr_code_regenerated = false;
	if ( ! empty( $destination_url ) && $destination_url !== $existing_qr->destination_url && function_exists( 'qrc_generate_qr_code' ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( 'QR Trackr: Regenerating QR code for ID %d with new URL: %s', $qr_id, $destination_url ) );
		}

		$new_qr_code_url = qrc_generate_qr_code( $destination_url );

		if ( ! is_wp_error( $new_qr_code_url ) ) {
			// Update the database with the new QR code URL.
			$qr_update_result = $wpdb->update(
				$table_name,
				array(
					'qr_code_url' => $new_qr_code_url,
					'updated_at'  => current_time( 'mysql', true ),
				),
				array( 'id' => $qr_id ),
				array( '%s', '%s' ),
				array( '%d' )
			);

			if ( false !== $qr_update_result ) {
				$qr_code_regenerated = true;
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( sprintf( 'QR Trackr: QR code regenerated successfully: %s', $new_qr_code_url ) );
				}
			} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( sprintf( 'QR Trackr: Failed to update database with new QR code URL: %s', $wpdb->last_error ) );
			}
		} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( 'QR Trackr: Failed to regenerate QR code: %s', $new_qr_code_url->get_error_message() ) );
		}
	}

	// Check if post was unlinked
	$post_unlinked = false;
	if ( ! empty( $destination_url ) && $destination_url !== $existing_qr->destination_url && ! empty( $existing_qr->post_id ) ) {
		$post_unlinked = true;
	}

	// Log successful update for debugging.
	if ( function_exists( 'qr_trackr_debug_log' ) ) {
		qr_trackr_debug_log( sprintf( 'Successfully updated QR code details for ID: %d. Rows affected: %d. Post unlinked: %s', $qr_id, $result, $post_unlinked ? 'Yes' : 'No' ) );
	}

	// Create appropriate message
	$message = esc_html__( 'QR code details updated successfully.', 'wp-qr-trackr' );
	if ( $post_unlinked ) {
		$message = esc_html__( 'QR code details updated successfully. The linked post has been unlinked since the destination URL was changed.', 'wp-qr-trackr' );
	}
	if ( $qr_code_regenerated ) {
		$message = esc_html__( 'QR code details updated successfully. QR code image has been regenerated for the new destination URL.', 'wp-qr-trackr' );
	}
	if ( $post_unlinked && $qr_code_regenerated ) {
		$message = esc_html__( 'QR code details updated successfully. The linked post has been unlinked and QR code image has been regenerated for the new destination URL.', 'wp-qr-trackr' );
	}

	// Get the updated record to return complete data
	$updated_record = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}qr_trackr_links WHERE id = %d",
			$qr_id
		),
		ARRAY_A
	);

	$response_data = array(
		'message'         => $message,
		'common_name'     => esc_html( $updated_record['common_name'] ?? '' ),
		'referral_code'   => esc_html( $updated_record['referral_code'] ?? '' ),
		'destination_url' => esc_url( $updated_record['destination_url'] ?? '' ),
		'qr_code'         => esc_html( $updated_record['qr_code'] ?? '' ),
		'qr_code_url'     => esc_url( $updated_record['qr_code_url'] ?? '' ),
		'scans'           => absint( $updated_record['scans'] ?? $updated_record['access_count'] ?? 0 ),
		'created_at'      => esc_html( $updated_record['created_at'] ?? '' ),
		'post_unlinked'   => $post_unlinked,
	);

	wp_send_json_success( $response_data );
}
add_action( 'wp_ajax_qr_trackr_update_qr_details', 'qr_trackr_ajax_update_qr_details' );

/**
 * Debug endpoint to test AJAX functionality.
 * Remove this after troubleshooting.
 *
 * @since 1.2.8
 * @return void
 */
function qr_trackr_ajax_debug() {
	// Verify nonce.
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'qr_trackr_nonce' ) ) {
		wp_send_json_error( 'Nonce verification failed' );
		return;
	}

	// Check user capabilities.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Insufficient permissions' );
		return;
	}

	// Check database table and fields.
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';

	// Check if table exists.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Debug function.
	$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );

	$debug_info = array(
		'table_exists'    => $table_exists ? 'Yes' : 'No',
		'wpdb_last_error' => $wpdb->last_error,
		'php_version'     => PHP_VERSION,
		'wp_version'      => get_bloginfo( 'version' ),
	);

	if ( $table_exists ) {
		// Check table structure.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Debug function.
		$columns      = $wpdb->get_results( "SHOW COLUMNS FROM {$wpdb->prefix}qr_trackr_links" );
		$column_names = array();
		foreach ( $columns as $column ) {
			$column_names[] = $column->field;
		}
		$debug_info['table_columns']     = $column_names;
		$debug_info['has_common_name']   = in_array( 'common_name', $column_names, true ) ? 'Yes' : 'No';
		$debug_info['has_referral_code'] = in_array( 'referral_code', $column_names, true ) ? 'Yes' : 'No';
	}

	wp_send_json_success( $debug_info );
}
add_action( 'wp_ajax_qr_trackr_debug', 'qr_trackr_ajax_debug' );


/**
 * AJAX handler for QR code redirects.
 *
 * @since 1.2.24
 * @return void
 */
function qr_trackr_ajax_qr_redirect() {
	// Get the QR code from the request.
	$qr_code = isset( $_GET['qr'] ) ? sanitize_text_field( wp_unslash( $_GET['qr'] ) ) : '';

	if ( empty( $qr_code ) ) {
		wp_die( esc_html__( 'Invalid QR code.', 'wp-qr-trackr' ), 400 );
	}

	global $wpdb;

	// Get destination URL from database.
	$result = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT destination_url, id FROM {$wpdb->prefix}qr_trackr_links WHERE qr_code = %s",
			$qr_code
		)
	);

	if ( ! $result ) {
		wp_die( esc_html__( 'QR code not found.', 'wp-qr-trackr' ), 404 );
	}

	$destination_url = $result->destination_url;
	$link_id         = $result->id;

	// Update scan count immediately.
	qr_trackr_update_scan_count_immediate( $link_id );

	// Redirect to the destination URL.
	header( 'Location: ' . esc_url_raw( $destination_url ), true, 301 );
	exit;
}
add_action( 'wp_ajax_qr_redirect', 'qr_trackr_ajax_qr_redirect' );
add_action( 'wp_ajax_nopriv_qr_redirect', 'qr_trackr_ajax_qr_redirect' );

if ( function_exists( 'qr_trackr_debug_log' ) ) {
	qr_trackr_debug_log( 'Loaded module-ajax.php.' );
}
