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
	qr_trackr_log_ajax_request( 'qrc_generate_qr_code', $_POST, 'started' );

	// Security check.
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'qrc_generate_qr_code' ) ) {
		qr_trackr_log( 'Security check failed in QR code generation AJAX', 'error', array( 'nonce_provided' => isset( $_POST['nonce'] ) ) );
		qr_trackr_log_ajax_request( 'qrc_generate_qr_code', $_POST, 'security_failed' );
		wp_send_json_error(
			array(
				'message' => __( 'Security check failed. Please refresh the page and try again.', 'wp-qr-trackr' ),
			)
		);
		return;
	}

	// Check user capabilities.
	if ( ! current_user_can( 'edit_posts' ) ) {
		qr_trackr_log(
			'Insufficient permissions for QR code generation',
			'warning',
			array(
				'user_id'    => get_current_user_id(),
				'capability' => 'edit_posts',
			)
		);
		qr_trackr_log_ajax_request( 'qrc_generate_qr_code', $_POST, 'permission_denied' );
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
		qr_trackr_log( 'Invalid post ID in QR code generation', 'error', array( 'post_id' => $post_id ) );
		qr_trackr_log_ajax_request( 'qrc_generate_qr_code', $_POST, 'invalid_post' );
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
		qr_trackr_log( 'Empty destination URL in QR code generation', 'error', array( 'post_id' => $post_id ) );
		qr_trackr_log_ajax_request( 'qrc_generate_qr_code', $_POST, 'invalid_url' );
		wp_send_json_error(
			array(
				'message' => __( 'Destination URL is required and must be valid.', 'wp-qr-trackr' ),
			)
		);
		return;
	}

	// Log form submission.
	$form_data = array(
		'post_id'         => $post_id,
		'destination_url' => $destination_url,
	);
	qr_trackr_log_form_submission( 'generate_qr_code_ajax', $form_data, 'create' );

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

		qr_trackr_log(
			'QR code generation failed',
			'error',
			array(
				'post_id' => $post_id,
				'error'   => $qr_code_url->get_error_message(),
			)
		);
		qr_trackr_log_ajax_request( 'qrc_generate_qr_code', $_POST, 'generation_failed' );

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
		$update_data = array(
			'destination_url' => $destination_url,
			'qr_code_url'     => $qr_code_url,
			'updated_at'      => current_time( 'mysql' ),
		);

		$result = $wpdb->update(
			$table_name,
			$update_data,
			array( 'post_id' => $post_id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);

		qr_trackr_log_db_operation( 'update', $table_name, $update_data, false !== $result );
	} else {
		// Insert new record.
		$insert_data = array(
			'post_id'         => $post_id,
			'destination_url' => $destination_url,
			'qr_code_url'     => $qr_code_url,
			'created_at'      => current_time( 'mysql' ),
			'updated_at'      => current_time( 'mysql' ),
		);

		$result = $wpdb->insert(
			$table_name,
			$insert_data,
			array( '%d', '%s', '%s', '%s', '%s' )
		);

		qr_trackr_log_db_operation( 'insert', $table_name, $insert_data, false !== $result );
	}

	if ( false === $result ) {
		error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentional debug logging.
			sprintf(
				'Database operation failed for post %d: %s',
				$post_id,
				$wpdb->last_error
			)
		);

		qr_trackr_log(
			'Database operation failed for QR code',
			'error',
			array(
				'post_id'  => $post_id,
				'db_error' => $wpdb->last_error,
			)
		);
		qr_trackr_log_ajax_request( 'qrc_generate_qr_code', $_POST, 'database_failed' );

		wp_send_json_error(
			array(
				'message' => __( 'Failed to save QR code data.', 'wp-qr-trackr' ),
			)
		);
		return;
	}

	// Clear the cache.
	wp_cache_delete( 'qr_code_image_' . $post_id );

	qr_trackr_log(
		'QR code generated successfully',
		'info',
		array(
			'post_id'     => $post_id,
			'qr_code_url' => $qr_code_url,
		)
	);
	qr_trackr_log_ajax_request( 'qrc_generate_qr_code', $_POST, 'success' );

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
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'qrc_track_link' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'wp-qr-trackr' ), 403 );
	}

	// Get and validate the link ID.
	$link_id = isset( $_POST['link_id'] ) ? absint( $_POST['link_id'] ) : 0;
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
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentional debug logging.
		error_log(
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
	qr_trackr_log_ajax_request( 'qrc_search_posts', $_POST, 'started' );

	// Debug logging.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only.
		error_log( 'QR Trackr: qrc_search_posts_ajax called with POST data: ' . wp_json_encode( $_POST ) );
	}

	// Security check.
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'qr_trackr_nonce' ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only.
			error_log( 'QR Trackr: Nonce verification failed for qrc_search_posts_ajax' );
		}
		qr_trackr_log( 'Security check failed in search posts AJAX', 'error', array( 'nonce_provided' => isset( $_POST['nonce'] ) ) );
		qr_trackr_log_ajax_request( 'qrc_search_posts', $_POST, 'security_failed' );
		wp_send_json_error(
			array(
				'message' => __( 'Security check failed. Please refresh the page and try again.', 'wp-qr-trackr' ),
			)
		);
		return;
	}

	// Check user capabilities.
	if ( ! current_user_can( 'manage_options' ) ) {
		qr_trackr_log(
			'Insufficient permissions for search posts',
			'warning',
			array(
				'user_id'    => get_current_user_id(),
				'capability' => 'manage_options',
			)
		);
		qr_trackr_log_ajax_request( 'qrc_search_posts', $_POST, 'permission_denied' );
		wp_send_json_error(
			array(
				'message' => __( 'You do not have permission to perform this action.', 'wp-qr-trackr' ),
			)
		);
		return;
	}

	// Get and validate the search term.
	$search_term = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : ( isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '' );

	if ( empty( $search_term ) || strlen( $search_term ) < 2 ) {
		qr_trackr_log(
			'Invalid search term in search posts',
			'warning',
			array(
				'search_term' => $search_term,
				'length'      => strlen( $search_term ),
			)
		);
		qr_trackr_log_ajax_request( 'qrc_search_posts', $_POST, 'invalid_term' );
		wp_send_json_error(
			array(
				'message' => __( 'Search term must be at least 2 characters long.', 'wp-qr-trackr' ),
			)
		);
		return;
	}

	// Log search request.
	qr_trackr_log_form_submission( 'search_posts_ajax', array( 'search_term' => $search_term ), 'search' );

	// Search for posts/pages.
	$posts = get_posts(
		array(
			'post_type'      => array( 'post', 'page' ),
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			's'              => $search_term,
			'orderby'        => 'title',
			'order'          => 'ASC',
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

	// Debug logging.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'QR Trackr: Search results for term "' . $search_term . '": ' . count( $results ) . ' posts found' );
	}

	qr_trackr_log(
		'Search posts completed',
		'info',
		array(
			'search_term'   => $search_term,
			'results_count' => count( $results ),
		)
	);
	qr_trackr_log_ajax_request( 'qrc_search_posts', $_POST, 'success' );

	wp_send_json_success( array( 'posts' => $results ) );
}
add_action( 'wp_ajax_qrc_search_posts', 'qrc_search_posts_ajax' );

/**
 * Handle AJAX request to get QR code details for modal.
 *
 * @return void
 */
function qr_trackr_ajax_get_qr_details() {
	qr_trackr_log_ajax_request( 'qr_trackr_get_qr_details', $_POST, 'started' );

	// Verify nonce.
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'qr_trackr_nonce' ) ) {
		qr_trackr_log( 'Security check failed in get QR details AJAX', 'error', array( 'nonce_provided' => isset( $_POST['nonce'] ) ) );
		qr_trackr_log_ajax_request( 'qr_trackr_get_qr_details', $_POST, 'security_failed' );
		wp_send_json_error( esc_html__( 'Security check failed.', 'wp-qr-trackr' ) );
		return;
	}

	// Check user capabilities.
	if ( ! current_user_can( 'manage_options' ) ) {
		qr_trackr_log(
			'Insufficient permissions for get QR details',
			'warning',
			array(
				'user_id'    => get_current_user_id(),
				'capability' => 'manage_options',
			)
		);
		qr_trackr_log_ajax_request( 'qr_trackr_get_qr_details', $_POST, 'permission_denied' );
		wp_send_json_error( esc_html__( 'Insufficient permissions.', 'wp-qr-trackr' ) );
		return;
	}

	// Get and validate QR code ID.
	$qr_id = isset( $_POST['qr_id'] ) ? absint( $_POST['qr_id'] ) : 0;
	if ( 0 === $qr_id ) {
		qr_trackr_log( 'Invalid QR code ID in get QR details', 'error', array( 'qr_id' => $qr_id ) );
		qr_trackr_log_ajax_request( 'qr_trackr_get_qr_details', $_POST, 'invalid_id' );
		wp_send_json_error( esc_html__( 'Invalid QR code ID.', 'wp-qr-trackr' ) );
		return;
	}

	// Log request.
	qr_trackr_log_form_submission( 'get_qr_details_ajax', array( 'qr_id' => $qr_id ), 'read' );

	// Try to get QR code details from cache first.
	$cache_key = 'qr_trackr_details_' . $qr_id;
	$qr_code   = wp_cache_get( $cache_key );

	if ( false === $qr_code ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'qr_trackr_links';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached immediately after query.
		$qr_code = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE id = %d",
				$qr_id
			),
			ARRAY_A
		);

		if ( $qr_code ) {
			wp_cache_set( $cache_key, $qr_code, '', HOUR_IN_SECONDS );
		}
	}

	if ( ! $qr_code ) {
		qr_trackr_log( 'QR code not found in get QR details', 'error', array( 'qr_id' => $qr_id ) );
		qr_trackr_log_ajax_request( 'qr_trackr_get_qr_details', $_POST, 'not_found' );
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
		'destination_url' => esc_url( $qr_code['destination_url'] ),
		'qr_code_url'     => esc_url( $qr_code['qr_code_url'] ?? '' ),
		'post_title'      => esc_html( $post_title ),
		'access_count'    => absint( $qr_code['access_count'] ),
		'recent_scans'    => absint( $recent_scans ?? 0 ),
		'created_at'      => esc_html( $qr_code['created_at'] ),
		'last_accessed'   => esc_html( $qr_code['last_accessed'] ?? __( 'Never', 'wp-qr-trackr' ) ),
		'qr_url'          => esc_url( qr_trackr_get_redirect_url( $qr_code['qr_code'] ) ),
	);

	qr_trackr_log( 'QR code details retrieved successfully', 'info', array( 'qr_id' => $qr_id ) );
	qr_trackr_log_ajax_request( 'qr_trackr_get_qr_details', $_POST, 'success' );

	wp_send_json_success( $response_data );
}
add_action( 'wp_ajax_qr_trackr_get_qr_details', 'qr_trackr_ajax_get_qr_details' );

/**
 * AJAX: Check referral code uniqueness (and basic format) in real-time.
 *
 * @since 1.2.49
 * @return void
 */
function qr_trackr_ajax_check_referral_unique() {
	qr_trackr_log_ajax_request( 'qr_trackr_check_referral_unique', $_POST, 'started' );

	// Verify nonce.
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'qr_trackr_nonce' ) ) {
		qr_trackr_log_ajax_request( 'qr_trackr_check_referral_unique', $_POST, 'security_failed' );
		wp_send_json_error( array( 'message' => __( 'Security check failed.', 'wp-qr-trackr' ) ) );
		return;
	}

	// Cap check.
	if ( ! current_user_can( 'manage_options' ) ) {
		qr_trackr_log_ajax_request( 'qr_trackr_check_referral_unique', $_POST, 'permission_denied' );
		wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'wp-qr-trackr' ) ) );
		return;
	}

	// Get inputs.
	$referral_code = isset( $_POST['referral_code'] ) ? sanitize_text_field( wp_unslash( $_POST['referral_code'] ) ) : '';
	$current_id    = isset( $_POST['qr_id'] ) ? absint( $_POST['qr_id'] ) : 0;

	// Basic format validation first.
	if ( '' !== $referral_code && ! preg_match( '/^[a-zA-Z0-9\-_]+$/', $referral_code ) ) {
		wp_send_json_success(
			array(
				'unique'  => false,
				'message' => __( 'Referral code can only contain letters, numbers, hyphens, and underscores.', 'wp-qr-trackr' ),
				'reason'  => 'format',
			)
		);
		return;
	}

	// Empty referral codes are considered valid/no-conflict.
	if ( '' === $referral_code ) {
		wp_send_json_success( array( 'unique' => true ) );
		return;
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';

	// Cache check by code only; id is used to interpret result.
	$cache_key = 'qr_trackr_referral_owner_' . md5( $referral_code );
	$owner_id  = wp_cache_get( $cache_key, 'qr_trackr' );
	if ( false === $owner_id ) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Small validation query; cached.
		$owner_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table_name} WHERE referral_code = %s",
				$referral_code
			)
		);
		wp_cache_set( $cache_key, $owner_id, 'qr_trackr', 300 );
	}

	if ( null === $owner_id ) {
		wp_send_json_success( array( 'unique' => true ) );
		return;
	}

	// If the only owner is the current record, treat as unique for edit context.
	if ( $current_id > 0 && intval( $owner_id ) === $current_id ) {
		wp_send_json_success( array( 'unique' => true ) );
		return;
	}

	wp_send_json_success(
		array(
			'unique'  => false,
			'message' => __( 'Referral code already exists. Please choose a different one.', 'wp-qr-trackr' ),
			'reason'  => 'duplicate',
		)
	);
}
add_action( 'wp_ajax_qr_trackr_check_referral_unique', 'qr_trackr_ajax_check_referral_unique' );

/**
 * Handle AJAX request to update QR code details from modal.
 *
 * @return void
 */
function qr_trackr_ajax_update_qr_details() {
	qr_trackr_log_ajax_request( 'qr_trackr_update_qr_details', $_POST, 'started' );

	// Verify nonce.
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'qr_trackr_nonce' ) ) {
		qr_trackr_log( 'Security check failed in update QR details AJAX', 'error', array( 'nonce_provided' => isset( $_POST['nonce'] ) ) );
		qr_trackr_log_ajax_request( 'qr_trackr_update_qr_details', $_POST, 'security_failed' );
		wp_send_json_error( esc_html__( 'Security check failed.', 'wp-qr-trackr' ) );
		return;
	}

	// Check user capabilities.
	if ( ! current_user_can( 'manage_options' ) ) {
		qr_trackr_log(
			'Insufficient permissions for update QR details',
			'warning',
			array(
				'user_id'    => get_current_user_id(),
				'capability' => 'manage_options',
			)
		);
		qr_trackr_log_ajax_request( 'qr_trackr_update_qr_details', $_POST, 'permission_denied' );
		wp_send_json_error( esc_html__( 'Insufficient permissions.', 'wp-qr-trackr' ) );
		return;
	}

	// Get and validate input.
	$qr_id = isset( $_POST['qr_id'] ) ? absint( $_POST['qr_id'] ) : 0;
	if ( 0 === $qr_id ) {
		qr_trackr_log( 'Invalid QR code ID in update QR details', 'error', array( 'qr_id' => $qr_id ) );
		qr_trackr_log_ajax_request( 'qr_trackr_update_qr_details', $_POST, 'invalid_id' );
		wp_send_json_error( esc_html__( 'Invalid QR code ID.', 'wp-qr-trackr' ) );
		return;
	}

	$common_name     = isset( $_POST['common_name'] ) ? sanitize_text_field( wp_unslash( $_POST['common_name'] ) ) : '';
	$referral_code   = isset( $_POST['referral_code'] ) ? sanitize_text_field( wp_unslash( $_POST['referral_code'] ) ) : '';
	$destination_url = isset( $_POST['destination_url'] ) ? esc_url_raw( wp_unslash( $_POST['destination_url'] ) ) : '';

	// Log form submission.
	$form_data = array(
		'qr_id'           => $qr_id,
		'common_name'     => $common_name,
		'referral_code'   => $referral_code,
		'destination_url' => $destination_url,
	);
	qr_trackr_log_form_submission( 'update_qr_details_ajax', $form_data, 'update' );

	// Check if the QR code exists before trying to update it.
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Validation check before update.
	$existing_qr = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT id FROM {$table_name} WHERE id = %d",
			$qr_id
		)
	);

	if ( ! $existing_qr ) {
		qr_trackr_log( 'QR code not found for update', 'error', array( 'qr_id' => $qr_id ) );
		qr_trackr_log_ajax_request( 'qr_trackr_update_qr_details', $_POST, 'not_found' );
		wp_send_json_error( esc_html__( 'QR code not found.', 'wp-qr-trackr' ) );
		return;
	}

	// Validate destination URL if provided.
	if ( ! empty( $destination_url ) && ! wp_http_validate_url( $destination_url ) ) {
		qr_trackr_log(
			'Invalid destination URL format',
			'error',
			array(
				'qr_id'           => $qr_id,
				'destination_url' => $destination_url,
			)
		);
		qr_trackr_log_ajax_request( 'qr_trackr_update_qr_details', $_POST, 'invalid_destination_url' );
		wp_send_json_error( esc_html__( 'Please enter a valid destination URL starting with http:// or https://', 'wp-qr-trackr' ) );
		return;
	}

	// Validate referral code (alphanumeric and hyphens only).
	if ( ! empty( $referral_code ) && ! preg_match( '/^[a-zA-Z0-9\-_]+$/', $referral_code ) ) {
		qr_trackr_log(
			'Invalid referral code format',
			'error',
			array(
				'qr_id'         => $qr_id,
				'referral_code' => $referral_code,
			)
		);
		qr_trackr_log_ajax_request( 'qr_trackr_update_qr_details', $_POST, 'invalid_referral_code' );
		wp_send_json_error( esc_html__( 'Referral code can only contain letters, numbers, hyphens, and underscores.', 'wp-qr-trackr' ) );
		return;
	}

	// Check if referral code is unique (if provided).
	if ( ! empty( $referral_code ) ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Uniqueness check for validation.
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table_name} WHERE referral_code = %s AND id != %d",
				$referral_code,
				$qr_id
			)
		);

		if ( $existing ) {
			qr_trackr_log(
				'Referral code already exists',
				'error',
				array(
					'qr_id'         => $qr_id,
					'referral_code' => $referral_code,
				)
			);
			qr_trackr_log_ajax_request( 'qr_trackr_update_qr_details', $_POST, 'duplicate_referral_code' );
			wp_send_json_error( esc_html__( 'Referral code already exists. Please choose a different one.', 'wp-qr-trackr' ) );
			return;
		}
	}

	// Update the database.
	$update_data = array(
		'common_name'   => $common_name,
		'referral_code' => $referral_code,
		'updated_at'    => current_time( 'mysql', true ),
	);

	// Only update destination URL if provided.
	if ( ! empty( $destination_url ) ) {
		$update_data['destination_url'] = $destination_url;
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cache invalidated after update.
	$result = $wpdb->update(
		$table_name,
		$update_data,
		array( 'id' => $qr_id ),
		array( '%s', '%s', '%s' ),
		array( '%d' )
	);

	qr_trackr_log_db_operation( 'update', $table_name, $update_data, false !== $result );

	// Check for database error (false) vs no rows updated (0).
	if ( false === $result ) {
		$error_message = $wpdb->last_error ? $wpdb->last_error : 'Unknown database error';
		if ( function_exists( 'qr_trackr_debug_log' ) ) {
			qr_trackr_debug_log( sprintf( 'Failed to update QR code details for ID: %d. Error: %s', $qr_id, $error_message ) );
		}
		qr_trackr_log(
			'Failed to update QR code details',
			'error',
			array(
				'qr_id'    => $qr_id,
				'db_error' => $error_message,
			)
		);
		qr_trackr_log_ajax_request( 'qr_trackr_update_qr_details', $_POST, 'database_failed' );
		wp_send_json_error( esc_html__( 'Failed to update QR code details.', 'wp-qr-trackr' ) );
		return;
	}

	// Clear relevant caches.
	wp_cache_delete( 'qr_trackr_details_' . $qr_id );
	wp_cache_delete( 'qr_trackr_all_links_admin', 'qr_trackr' );
	wp_cache_delete( 'qrc_link_' . $qr_id, 'qrc_links' );
	delete_transient( 'qrc_all_links' );

	// Log successful update for debugging.
	if ( function_exists( 'qr_trackr_debug_log' ) ) {
		qr_trackr_debug_log( sprintf( 'Successfully updated QR code details for ID: %d. Rows affected: %d', $qr_id, $result ) );
	}

	qr_trackr_log(
		'QR code details updated successfully',
		'info',
		array(
			'qr_id'         => $qr_id,
			'rows_affected' => $result,
		)
	);
	qr_trackr_log_ajax_request( 'qr_trackr_update_qr_details', $_POST, 'success' );

	wp_send_json_success(
		array(
			'message'         => esc_html__( 'QR code details updated successfully.', 'wp-qr-trackr' ),
			'common_name'     => esc_html( $common_name ),
			'referral_code'   => esc_html( $referral_code ),
			'destination_url' => esc_url( $destination_url ),
		)
	);
}
add_action( 'wp_ajax_qr_trackr_update_qr_details', 'qr_trackr_ajax_update_qr_details' );

/**
 * Debug endpoint to test AJAX functionality.
 * Remove this after troubleshooting.
 *
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
		$columns      = $wpdb->get_results( "SHOW COLUMNS FROM {$table_name}" );
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

if ( function_exists( 'qr_trackr_debug_log' ) ) {
	qr_trackr_debug_log( 'Loaded module-ajax.php.' );
}
