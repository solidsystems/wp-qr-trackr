<?php
/**
 * AJAX handling for the QR Coder plugin.
 *
 * @package WP_QR_TRACKR
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Get the QR code image tag for a given post.
 *
 * @param int $post_id The post ID.
 * @return string The QR code image tag.
 */
function get_qr_code_image_tag( $post_id ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_code_links';

	// @codingStandardsIgnoreStart
	$link = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE post_id = %d", $post_id ) );
	// @codingStandardsIgnoreEnd

	if ( $link ) {
		return '<img src="' . esc_url( $link->qr_code_url ) . '" alt="QR Code" />';
	}

	return '';
}

/**
 * AJAX handler for generating a QR code.
 */
function qrc_generate_qr_code_ajax() {
	// Security check.
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'qrc_generate_qr_code' ) ) {
		wp_send_json_error( array( 'message' => 'Nonce verification failed.' ) );
		return;
	}

	// Check user capabilities.
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( array( 'message' => 'You do not have permission to perform this action.' ) );
		return;
	}

	// Get the post ID.
	$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	if ( ! $post_id ) {
		wp_send_json_error( array( 'message' => 'Invalid post ID.' ) );
		return;
	}

	// Get the destination URL.
	$destination_url = isset( $_POST['destination_url'] ) ? esc_url_raw( wp_unslash( $_POST['destination_url'] ) ) : '';
	if ( empty( $destination_url ) ) {
		wp_send_json_error( array( 'message' => 'Destination URL is required.' ) );
		return;
	}

	// Generate the QR code.
	$qr_code_url = qrc_generate_qr_code( $destination_url );

	if ( is_wp_error( $qr_code_url ) ) {
		wp_send_json_error(
			array(
				'message' => $qr_code_url->get_error_message(),
			)
		);
		return;
	}

	// Save the QR code URL to the database.
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_code_links';

	$existing_link = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE post_id = %d", $post_id ) );

	if ( null !== $existing_link ) {
		// Update existing record.
		$wpdb->update(
			$table_name,
			array(
				'destination_url' => $destination_url,
				'qr_code_url'     => $qr_code_url,
			),
			array( 'post_id' => $post_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	} else {
		// Insert new record.
		$wpdb->insert(
			$table_name,
			array(
				'post_id'         => $post_id,
				'destination_url' => $destination_url,
				'qr_code_url'     => $qr_code_url,
				'created_at'      => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s' )
		);
	}

	wp_send_json_success(
		array(
			'message'     => 'QR code generated successfully.',
			'qr_code_url' => $qr_code_url,
		)
	);
}
add_action( 'wp_ajax_qrc_generate_qr_code', 'qrc_generate_qr_code_ajax' );

/**
 * AJAX handler for tracking a QR code link click.
 */
function qrc_track_link_click_ajax() {
	// Security check.
	if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'qrc_track_link' ) ) {
		wp_die( 'Invalid nonce.' );
	}

	// Get the link ID.
	$link_id = isset( $_GET['link_id'] ) ? absint( $_GET['link_id'] ) : 0;
	if ( ! $link_id ) {
		wp_die( 'Invalid link ID.' );
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_code_links';

	// Increment the access count.
	$wpdb->query( $wpdb->prepare( "UPDATE {$table_name} SET access_count = access_count + 1 WHERE id = %d", $link_id ) );

	// Get the destination URL.
	$destination_url = $wpdb->get_var( $wpdb->prepare( "SELECT destination_url FROM {$table_name} WHERE id = %d", $link_id ) );

	if ( $destination_url ) {
		wp_redirect( esc_url_raw( $destination_url ) );
		exit;
	} else {
		wp_die( 'Invalid link.' );
	}
}
add_action( 'wp_ajax_qrc_track_link', 'qrc_track_link_click_ajax' );
add_action( 'wp_ajax_nopriv_qrc_track_link', 'qrc_track_link_click_ajax' ); 