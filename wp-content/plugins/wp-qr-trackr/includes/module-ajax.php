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
	qr_trackr_debug_log('Loading module-ajax.php...');
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
		$total = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . $table, array() ) );
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
	// Verify nonce
	if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'qr_trackr_nonce')) {
		wp_send_json_error('Invalid nonce');
		return;
	}

	// Check user capabilities
	if (!current_user_can('manage_options')) {
		wp_send_json_error('Insufficient permissions');
		return;
	}

	// Get and validate link ID
	$link_id = isset($_POST['link_id']) ? intval($_POST['link_id']) : 0;
	if ($link_id <= 0) {
		wp_send_json_error('Invalid link ID');
		return;
	}

	// Get link data
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';
	$link = $wpdb->get_row($wpdb->prepare(
		"SELECT * FROM $table_name WHERE id = %d",
		$link_id
	));

	if (!$link) {
		wp_send_json_error('Link not found');
		return;
	}

	// Generate QR code
	$qr_image = qr_trackr_generate_qr_image_for_link($link_id);
	if (!$qr_image) {
		qr_trackr_debug_log('Failed to generate QR code for link ID: ' . $link_id);
		wp_send_json_error('Failed to generate QR code');
		return;
	}

	// Update link with QR code URL (store only PNG)
	$result = $wpdb->update(
		$table_name,
		array('qr_code_url' => $qr_image['png']),
		array('id' => $link_id),
		array('%s'),
		array('%d')
	);

	if ($result === false) {
		qr_trackr_debug_log('Failed to update QR code URL in database for link ID: ' . $link_id);
		wp_send_json_error('Failed to update QR code URL');
		return;
	}

	wp_send_json_success(array(
		'message' => 'QR code generated successfully',
		'qr_code_url' => $qr_image['png'],
		'qr_code_svg_url' => $qr_image['svg']
	));
}
add_action('wp_ajax_qr_trackr_generate_qr', 'qr_trackr_ajax_generate_qr');

// AJAX: QR code creation.
add_action(
	'wp_ajax_qr_trackr_create_qr_ajax',
	/**
	 * Handle AJAX request to create a new QR code and return HTML.
	 *
	 * @return void
	 */
	function () {
		$post_id = isset( $_POST['qr_trackr_admin_new_post_id'] ) ? intval( wp_unslash( $_POST['qr_trackr_admin_new_post_id'] ) ) : 0;
		$nonce   = isset( $_POST['qr_trackr_admin_new_qr_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['qr_trackr_admin_new_qr_nonce'] ) ) : '';
		
		qr_trackr_debug_log( 'AJAX: Create QR called', array( 'post_id' => $post_id ) );

		// Verify nonce before processing
		if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'qr_trackr_admin_new_qr' ) ) {
			qr_trackr_debug_log( 'AJAX: Invalid nonce', $nonce );
			wp_send_json_error( array( 'message' => 'Invalid nonce.' ) );
			wp_die();
		}

		// Validate post exists and is published
		if ( 0 === $post_id || ! get_post( $post_id ) || get_post_status( $post_id ) !== 'publish' ) {
			qr_trackr_debug_log( 'AJAX: Invalid post', array( 'post_id' => $post_id ) );
			wp_send_json_error( array( 'message' => 'Invalid or unpublished post ID.' ) );
			wp_die();
		}

		// Check if user can edit posts and has access to this specific post
		if ( ! current_user_can( 'edit_posts' ) || ! current_user_can( 'edit_post', $post_id ) ) {
			qr_trackr_debug_log( 'AJAX: Permission denied', array( 'post_id' => $post_id, 'user' => wp_get_current_user()->user_login ) );
			wp_send_json_error( array( 'message' => 'You do not have permission to edit this post.' ) );
			wp_die();
		}

		global $wpdb;
		$links_table     = $wpdb->prefix . 'qr_trackr_links';
		$destination_type = isset($_POST['destination_type']) ? sanitize_text_field(wp_unslash($_POST['destination_type'])) : 'post';
		$post_id = isset($_POST['post_id']) ? intval(wp_unslash($_POST['post_id'])) : 0;
		$external_url = isset($_POST['external_url']) ? esc_url_raw(wp_unslash($_POST['external_url'])) : '';
		$custom_url = isset($_POST['custom_url']) ? esc_url_raw(wp_unslash($_POST['custom_url'])) : '';

		if ($destination_type === 'post') {
			if (!$post_id || !get_post($post_id) || get_post_status($post_id) !== 'publish') {
				qr_trackr_debug_log('AJAX: Invalid or missing post ID');
				wp_send_json_error(array('message' => 'Please select a valid published post or page.'));
				wp_die();
			}
			$destination_url = get_permalink($post_id);
		} elseif ($destination_type === 'external') {
			if (empty($external_url) || !filter_var($external_url, FILTER_VALIDATE_URL)) {
				qr_trackr_debug_log('AJAX: Invalid external URL');
				wp_send_json_error(array('message' => 'Please enter a valid external URL.'));
				wp_die();
			}
			$destination_url = $external_url;
		} elseif ($destination_type === 'custom') {
			if (empty($custom_url) || !filter_var($custom_url, FILTER_VALIDATE_URL)) {
				qr_trackr_debug_log('AJAX: Invalid custom URL');
				wp_send_json_error(array('message' => 'Please enter a valid custom URL.'));
				wp_die();
			}
			$destination_url = $custom_url;
		} else {
			$destination_url = '';
		}

		if (empty($destination_url)) {
			qr_trackr_debug_log('AJAX: Destination URL is empty');
			wp_send_json_error(array('message' => 'Destination URL is required.'));
			wp_die();
		}

		// Check if a tracking link already exists for this post
		$existing_link = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$links_table} WHERE post_id = %d LIMIT 1",
				$post_id
			)
		);

		if ( $existing_link ) {
			$link_id = $existing_link->id;
			qr_trackr_debug_log( 'AJAX: Using existing link', array( 'link_id' => $link_id ) );
		} else {
			$result = $wpdb->insert(
				$links_table,
				array(
					'post_id'         => $post_id,
					'destination_url' => $destination_url,
				),
				array('%d', '%s')
			);
			if (false === $result) {
				qr_trackr_debug_log('AJAX: Insert failed', $wpdb->last_error);
				wp_send_json_error(array('message' => 'Insert failed: ' . $wpdb->last_error));
				wp_die();
			}
			$link_id = $wpdb->insert_id;
			qr_trackr_debug_log('AJAX: Created new link', array('link_id' => $link_id));
		}

		// Generate QR code image and get URLs
		$qr_url        = qr_trackr_generate_qr_image_for_link( $link_id );
		$tracking_link = trailingslashit( home_url() ) . 'qr-trackr/redirect/' . intval( $link_id );
		$html          = qr_trackr_render_qr_list_html( $post_id );
		$qr_image_html = $qr_url ? '<div class="qr-trackr-ajax-qr"><img src="' . esc_url($qr_url['png']) . '" class="qr-trackr-qr-image" alt="QR Code"><br><a href="' . esc_url($qr_url['png']) . '" download class="button">Download PNG</a> <a href="' . esc_url($qr_url['svg']) . '" download class="button">Download SVG</a><br><strong>Tracking Link:</strong> <a href="' . esc_url($tracking_link) . '" target="_blank">' . esc_html($tracking_link) . '</a></div>' : '';

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

/**
 * Handle AJAX request to update destination URL.
 *
 * @return void
 */
function qr_trackr_ajax_update_destination() {
	check_ajax_referer('qr_trackr_edit', 'nonce');
	
	$link_id = isset($_POST['link_id']) ? intval($_POST['link_id']) : 0;
	$destination = isset($_POST['destination']) ? esc_url_raw($_POST['destination']) : '';
	
	if (!$link_id || !$destination) {
		wp_send_json_error(array('message' => 'Invalid link ID or destination URL.'));
		wp_die();
	}
	
	global $wpdb;
	$links_table = $wpdb->prefix . 'qr_trackr_links';
	
	// Get the link to check permissions
	$link = $wpdb->get_row($wpdb->prepare(
		"SELECT * FROM {$links_table} WHERE id = %d",
		$link_id
	));
	
	if (!$link) {
		wp_send_json_error(array('message' => 'Link not found.'));
		wp_die();
	}
	
	// Check if user can edit the post
	if (!current_user_can('edit_post', $link->post_id)) {
		wp_send_json_error(array('message' => 'You do not have permission to edit this link.'));
		wp_die();
	}
	
	// Update the destination URL
	$result = $wpdb->update(
		$links_table,
		array('destination_url' => $destination),
		array('id' => $link_id),
		array('%s'),
		array('%d')
	);
	
	if ($result === false) {
		wp_send_json_error(array('message' => 'Failed to update destination URL.'));
		wp_die();
	}
	
	// Clear cache
	wp_cache_delete('qr_trackr_link_' . $link_id);
	
	wp_send_json_success(array('message' => 'Destination URL updated successfully.'));
	wp_die();
}
add_action('wp_ajax_qr_trackr_update_destination', 'qr_trackr_ajax_update_destination');

/**
 * Handle QR code creation
 */
function qr_trackr_create_qr_code() {
	check_ajax_referer('qr_trackr_nonce', 'nonce');

	if (!current_user_can('manage_options')) {
		qr_trackr_debug_log('Permission denied for QR code creation.');
		if (function_exists('qr_trackr_is_debug_enabled') && qr_trackr_is_debug_enabled()) {
			$error = 'Permission denied.';
			$log = qr_trackr_get_debug_log();
			$lines = explode("\n", trim($log));
			$last = end($lines);
			wp_send_json_error($error . ($last ? ' Debug: ' . $last : ''));
		} else {
			wp_send_json_error('Permission denied');
		}
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';

	$destination = isset($_POST['destination']) ? esc_url_raw($_POST['destination']) : '';
	if (empty($destination)) {
		qr_trackr_debug_log('Destination URL is required for QR code creation.');
		if (function_exists('qr_trackr_is_debug_enabled') && qr_trackr_is_debug_enabled()) {
			$error = 'Destination URL is required.';
			$log = qr_trackr_get_debug_log();
			$lines = explode("\n", trim($log));
			$last = end($lines);
			wp_send_json_error($error . ($last ? ' Debug: ' . $last : ''));
		} else {
			wp_send_json_error('Destination URL is required');
		}
	}

	// Create QR code
	$qr_code = qr_trackr_generate_qr_code($destination);
	if (is_wp_error($qr_code)) {
		qr_trackr_debug_log('QR code generation error: ' . $qr_code->get_error_message());
		if (function_exists('qr_trackr_is_debug_enabled') && qr_trackr_is_debug_enabled()) {
			$error = $qr_code->get_error_message();
			$log = qr_trackr_get_debug_log();
			$lines = explode("\n", trim($log));
			$last = end($lines);
			wp_send_json_error($error . ($last ? ' Debug: ' . $last : ''));
		} else {
			wp_send_json_error($qr_code->get_error_message());
		}
	}

	// Save to database
	$result = $wpdb->insert(
		$table_name,
		array(
			'destination_url' => $destination,
			'created_at' => current_time('mysql'),
			'access_count' => 0
		),
		array('%s', '%s', '%d')
	);

	if ($result === false) {
		qr_trackr_debug_log('Failed to save QR code to database.');
		if (function_exists('qr_trackr_is_debug_enabled') && qr_trackr_is_debug_enabled()) {
			$error = 'Failed to save QR code.';
			$log = qr_trackr_get_debug_log();
			$lines = explode("\n", trim($log));
			$last = end($lines);
			wp_send_json_error($error . ($last ? ' Debug: ' . $last : ''));
		} else {
			wp_send_json_error('Failed to save QR code');
		}
	}

	wp_send_json_success(array(
		'message' => 'QR code created successfully',
		'qr_code' => $qr_code
	));
}
add_action('wp_ajax_qr_trackr_create_qr_code', 'qr_trackr_create_qr_code');

/**
 * Handle QR code regeneration
 */
function qr_trackr_regenerate_qr_code() {
	check_ajax_referer('qr_trackr_regenerate', 'nonce');
	
	if (!current_user_can('manage_options')) {
		wp_send_json_error('Permission denied');
	}
	
	$link_id = isset($_POST['link_id']) ? intval($_POST['link_id']) : 0;
	if (empty($link_id)) {
		wp_send_json_error('Invalid link ID');
	}
	
	// Get current destination
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';
	$destination = $wpdb->get_var($wpdb->prepare(
		"SELECT destination_url FROM $table_name WHERE id = %d",
		$link_id
	));
	
	if (!$destination) {
		wp_send_json_error('Link not found');
	}
	
	// Regenerate QR code
	$qr_code = qr_trackr_generate_qr_code($destination);
	if (is_wp_error($qr_code)) {
		wp_send_json_error($qr_code->get_error_message());
	}
	
	wp_send_json_success(array(
		'message' => 'QR code regenerated successfully',
		'qr_code' => $qr_code
	));
}
add_action('wp_ajax_qr_trackr_regenerate_qr_code', 'qr_trackr_regenerate_qr_code');

/**
 * Handle QR code deletion
 */
function qr_trackr_delete_qr_code() {
	check_ajax_referer('qr_trackr_delete', 'nonce');
	
	if (!current_user_can('manage_options')) {
		wp_send_json_error('Permission denied');
	}
	
	$link_id = isset($_POST['link_id']) ? intval($_POST['link_id']) : 0;
	if (empty($link_id)) {
		wp_send_json_error('Invalid link ID');
	}
	
	// Delete QR code
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';
	$result = $wpdb->delete(
		$table_name,
		array('id' => $link_id),
		array('%d')
	);
	
	if ($result === false) {
		wp_send_json_error('Failed to delete QR code');
	}
	
	wp_send_json_success('QR code deleted successfully');
}
add_action('wp_ajax_qr_trackr_delete_qr_code', 'qr_trackr_delete_qr_code');

/**
 * Handle QR code scan tracking
 */
function qr_trackr_track_scan() {
	// Only run on the expected endpoint
	if ( ! isset( $_GET['id'] ) || ! is_numeric( $_GET['id'] ) ) {
		return; // Do not interfere with unrelated requests
	}
	$link_id = intval( $_GET['id'] );
	if ( empty( $link_id ) ) {
		qr_trackr_debug_log('wp_die called: Invalid QR code (empty link_id)');
		wp_die('Invalid QR code');
	}
	
	// Update scan count
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';
	$wpdb->query($wpdb->prepare(
		"UPDATE $table_name SET 
		access_count = access_count + 1,
		last_accessed = %s
		WHERE id = %d",
		current_time('mysql'),
		$link_id
	));
	
	// Get destination
	$destination = $wpdb->get_var($wpdb->prepare(
		"SELECT destination_url FROM $table_name WHERE id = %d",
		$link_id
	));
	
	if (!$destination) {
		wp_die('QR code not found');
	}
	
	// Redirect to destination
	wp_redirect($destination);
	exit;
}
add_action('init', 'qr_trackr_track_scan');

if ( function_exists( 'qr_trackr_debug_log' ) ) {
	qr_trackr_debug_log('Loaded module-ajax.php.');
}
