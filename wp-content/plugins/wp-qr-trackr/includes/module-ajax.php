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

/**
 * Handle AJAX request to fetch QR scan stats.
 *
 * @return void
 */
function qr_trackr_ajax_get_stats() {
	check_ajax_referer( 'qr_trackr_stats_nonce', 'security' );
	global $wpdb;
	$table = $wpdb->prefix . 'qr_trackr_scans'; // Safe table name.
	
	// Get total scans with caching.
	$cache_key = 'qr_trackr_total_scans';
	$total     = wp_cache_get( $cache_key );
	if ( false === $total ) {
		$total = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM `%s`', $table ) );
		wp_cache_set( $cache_key, $total, '', 300 ); // Cache for 5 minutes.
	}
	wp_send_json_success( array( 'total_scans' => intval( $total ) ) );
}
add_action( 'wp_ajax_qr_trackr_get_stats', 'qr_trackr_ajax_get_stats' );

/**
 * Handle AJAX request to generate a new QR code for a post.
 *
 * @return void
 */
function qr_trackr_ajax_generate_qr() {
	check_ajax_referer( 'qr_trackr_generate_qr_nonce', 'security' );
	$post_id = isset( $_POST['post_id'] ) ? intval( wp_unslash( $_POST['post_id'] ) ) : 0;
	if ( 0 === $post_id || ! get_post( $post_id ) ) {
		wp_send_json_error( array( 'message' => 'Invalid post ID.' ) );
	}
	$link   = qr_trackr_get_or_create_tracking_link( $post_id );
	$qr_url = qr_trackr_generate_qr_image_for_link( $link->id );
	if ( false !== $qr_url && $qr_url ) {
		wp_send_json_success( array( 'qr_url' => esc_url( $qr_url ) ) );
	}
	wp_send_json_error( array( 'message' => 'Failed to generate QR code.' ) );
}
add_action( 'wp_ajax_qr_trackr_generate_qr', 'qr_trackr_ajax_generate_qr' );

// AJAX: QR code creation.
add_action(
	'wp_ajax_qr_trackr_create_qr_ajax',
	/**
	 * Handle AJAX request to create a new QR code and return HTML.
	 *
	 * @return void
	 */
	function () {
		$post_id = intval( wp_unslash( $_POST['post_id'] ?? 0 ) );
		$nonce   = isset( $_POST['qr_trackr_new_qr_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['qr_trackr_new_qr_nonce'] ) ) : '';
		qr_trackr_debug_log( 'AJAX: Create QR called', array( 'post_id' => $post_id ) );
		// Verify nonce before processing.
		if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'qr_trackr_admin_new_qr' ) ) {
			qr_trackr_debug_log( 'AJAX: Invalid nonce', $nonce );
			wp_send_json_error( array( 'message' => 'Invalid nonce.' ) );
			wp_die();
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			qr_trackr_debug_log( 'AJAX: Permission denied', $post_id );
			wp_send_json_error( array( 'message' => 'You do not have permission to edit this post.' ) );
		}
		global $wpdb;
		$links_table     = $wpdb->prefix . 'qr_trackr_links';
		$destination_url = get_permalink( $post_id );
		if ( false === $destination_url ) {
			qr_trackr_debug_log( 'AJAX: Could not determine permalink', $post_id );
			wp_send_json_error( array( 'message' => 'Could not determine permalink for this post.' ) );
		}
		$result = $wpdb->insert(
			$links_table,
			array(
				'post_id'         => $post_id,
				'destination_url' => esc_url_raw( $destination_url ),
			)
		);
		if ( false === $result ) {
			qr_trackr_debug_log( 'AJAX: Insert failed', $wpdb->last_error );
			wp_send_json_error( array( 'message' => 'Insert failed: ' . $wpdb->last_error ) );
		}
		$link_id = $wpdb->insert_id;
		// Generate QR code image and get URLs.
		$qr_url        = qr_trackr_generate_qr_image_for_link( $link_id );
		$tracking_link = trailingslashit( home_url() ) . 'qr-trackr/redirect/' . intval( $link_id );
		$html          = qr_trackr_render_qr_list_html( $post_id );
		$qr_image_html = $qr_url ? '<div class="qr-trackr-ajax-qr"><img src="' . esc_url( $qr_url ) . '" class="qr-trackr-qr-image" alt="QR Code"><br><a href="' . esc_url( $qr_url ) . '" download class="button">Download QR Code</a><br><strong>Tracking Link:</strong> <a href="' . esc_url( $tracking_link ) . '" target="_blank">' . esc_html( $tracking_link ) . '</a></div>' : '';
		qr_trackr_debug_log(
			'AJAX: QR code created',
			array(
				'link_id' => $link_id,
				'qr_url'  => $qr_url,
			)
		);
		wp_send_json_success(
			array(
				'html'          => $html,
				'qr_image_html' => $qr_image_html,
			)
		);
	}
);
