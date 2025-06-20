<?php
/**
 * AJAX module for QR Trackr plugin.
 *
 * Handles AJAX endpoints for QR code management and stats.
 *
 * @package QR_Trackr
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
 */
function qr_trackr_ajax_get_stats() {
	check_ajax_referer( 'qr_trackr_stats_nonce', 'security' );
	global $wpdb;
	$table = $wpdb->prefix . 'qr_trackr_stats';

	$post_id = isset( $_POST['post_id'] ) ? intval( wp_unslash( $_POST['post_id'] ) ) : 0;

	if ( 0 === $post_id ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Invalid post ID.', 'wp-qr-trackr' ) ) );
	}

	$stats = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE post_id = %d ORDER BY scanned_at DESC", $post_id ) );

	if ( empty( $stats ) ) {
		wp_send_json_success( array( 'stats' => array() ) );
	}

	$escaped_stats = array();
	foreach ( $stats as $row ) {
		$escaped_stats[] = array(
			'id'         => intval( $row->id ),
			'post_id'    => intval( $row->post_id ),
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
 */
function qr_trackr_ajax_generate_qr() {
	// Verify nonce
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'qr_trackr_nonce' ) ) {
		wp_send_json_error( 'Invalid nonce' );
		return;
	}

	// Check user capabilities
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Insufficient permissions' );
		return;
	}

	// Get and validate link ID
	$link_id = isset( $_POST['link_id'] ) ? intval( $_POST['link_id'] ) : 0;
	if ( $link_id <= 0 ) {
		wp_send_json_error( 'Invalid link ID' );
		return;
	}

	// Get link data
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';
	$link       = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM $table_name WHERE id = %d",
			$link_id
		)
	);

	if ( ! $link ) {
		wp_send_json_error( 'Link not found' );
		return;
	}

	// Generate QR code
	$qr_image = qr_trackr_generate_qr_image_for_link( $link_id );
	if ( ! $qr_image ) {
		qr_trackr_debug_log( 'Failed to generate QR code for link ID: ' . $link_id );
		wp_send_json_error( 'Failed to generate QR code' );
		return;
	}

	// Update link with QR code URL (store only PNG)
	$result = $wpdb->update(
		$table_name,
		array( 'qr_code_url' => $qr_image['png'] ),
		array( 'id' => $link_id ),
		array( '%s' ),
		array( '%d' )
	);

	if ( $result === false ) {
		qr_trackr_debug_log( 'Failed to update QR code URL in database for link ID: ' . $link_id );
		wp_send_json_error( 'Failed to update QR code URL' );
		return;
	}

	wp_send_json_success(
		array(
			'message'         => 'QR code generated successfully',
			'qr_code_url'     => $qr_image['png'],
			'qr_code_svg_url' => $qr_image['svg'],
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

	$result = $wpdb->update(
		$links_table,
		array( 'destination_url' => $destination ),
		array( 'id' => $link_id ),
		array( '%s' ),
		array( '%d' )
	);

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
	check_ajax_referer( 'qr_trackr_nonce', 'nonce' );

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

	// Create QR code.
	$qr_code = qr_trackr_generate_qr_code( $destination );
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
	$table_name  = $wpdb->prefix . 'qr_trackr_links';
	$destination = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT destination_url FROM $table_name WHERE id = %d",
			$link_id
		)
	);

	if ( ! $destination ) {
		wp_send_json_error( esc_html__( 'Link not found.', 'wp-qr-trackr' ) );
	}

	// Regenerate QR code.
	$qr_code = qr_trackr_generate_qr_code( $destination );
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
	$result     = $wpdb->delete(
		$table_name,
		array( 'id' => $link_id ),
		array( '%d' )
	);

	if ( false === $result ) {
		wp_send_json_error( esc_html__( 'Failed to delete QR code.', 'wp-qr-trackr' ) );
	}

	wp_send_json_success( esc_html__( 'QR code deleted successfully.', 'wp-qr-trackr' ) );
}
add_action( 'wp_ajax_qr_trackr_delete_qr_code', 'qr_trackr_delete_qr_code' );

/**
 * Handle QR code scan tracking.
 *
 * @return void
 */
function qr_trackr_track_scan() {
	// Only run on the expected endpoint.
	if ( ! isset( $_GET['id'] ) || ! is_numeric( $_GET['id'] ) ) {
		return; // Do not interfere with unrelated requests.
	}
	$link_id = intval( $_GET['id'] );
	if ( 0 === $link_id ) {
		qr_trackr_debug_log( 'wp_die called: Invalid QR code (empty link_id).' );
		wp_die( esc_html__( 'Invalid QR code.', 'wp-qr-trackr' ) );
	}

	// Update scan count.
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';
	$wpdb->query(
		$wpdb->prepare(
			"UPDATE $table_name SET 
		access_count = access_count + 1,
		last_accessed = %s
		WHERE id = %d",
			current_time( 'mysql' ),
			$link_id
		)
	);

	// Get destination.
	$destination = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT destination_url FROM $table_name WHERE id = %d",
			$link_id
		)
	);

	if ( ! $destination ) {
		wp_die( esc_html__( 'QR code not found.', 'wp-qr-trackr' ) );
	}

	// Redirect to destination.
	wp_redirect( esc_url_raw( $destination ) );
	exit;
}
add_action( 'init', 'qr_trackr_track_scan' );

/**
 * AJAX: Search posts for Select2 dropdown.
 *
 * @return void
 */
add_action( 'wp_ajax_qr_trackr_search_posts', 'qr_trackr_ajax_search_posts' );
function qr_trackr_ajax_search_posts() {
	check_ajax_referer( 'qr_trackr_nonce', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( esc_html__( 'Permission denied.', 'wp-qr-trackr' ) );
	}
	$term    = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';
	$args    = array(
		'post_type'      => array( 'post', 'page' ),
		'post_status'    => 'publish',
		's'              => $term,
		'posts_per_page' => 20,
	);
	$posts   = get_posts( $args );
	$results = array();
	foreach ( $posts as $post ) {
		$results[] = array(
			'ID'        => intval( $post->ID ),
			'title'     => esc_html( $post->post_title ),
			'permalink' => esc_url( get_permalink( $post->ID ) ),
		);
	}
	wp_send_json_success( $results );
}

if ( function_exists( 'qr_trackr_debug_log' ) ) {
	qr_trackr_debug_log( 'Loaded module-ajax.php.' );
}
