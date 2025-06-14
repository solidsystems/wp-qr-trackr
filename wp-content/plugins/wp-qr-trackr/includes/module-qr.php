<?php
/**
 * QR code generation module for QR Trackr plugin.
 *
 * Handles QR code image generation and related utilities.
 *
 * @package QR_Trackr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generate a QR code image for a given tracking link ID.
 *
 * @param int $link_id The tracking link ID.
 * @return string|false URL to the generated QR code image, or false on failure.
 */
function qr_trackr_generate_qr_image_for_link( $link_id ) {
	$link = qr_trackr_get_tracking_link_by_id( $link_id );
	if ( false === $link || ! $link ) {
		return false;
	}
	$tracking_url = trailingslashit( home_url() ) . 'qr-trackr/redirect/' . intval( $link_id );
	$upload_dir   = wp_upload_dir();
	$qr_dir       = trailingslashit( $upload_dir['basedir'] ) . 'qr-trackr/';
	$qr_url       = trailingslashit( $upload_dir['baseurl'] ) . 'qr-trackr/';
	if ( ! file_exists( $qr_dir ) ) {
		wp_mkdir_p( $qr_dir );
	}
	$filename = 'qr-' . $link_id . '.png';
	$filepath = $qr_dir . $filename;
	$fileurl  = $qr_url . $filename;
	if ( ! file_exists( $filepath ) ) {
		// Generate QR code image if it does not exist.
		if ( ! class_exists( 'QRcode' ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'lib/phpqrcode/qrlib.php';
		}
		QRcode::png( $tracking_url, $filepath, QR_ECLEVEL_L, 6 );
	}
	return $fileurl;
}

/**
 * Get or create a tracking link for a given post ID.
 *
 * @param int $post_id The post ID.
 * @return object|false Tracking link object or false on failure.
 */
function qr_trackr_get_or_create_tracking_link( $post_id ) {
	global $wpdb;
	$table = $wpdb->prefix . 'qr_trackr_links';
	$link  = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %s WHERE post_id = %d', $table, $post_id ) );
	if ( $link ) {
		return $link;
	}
	$wpdb->insert(
		$table,
		array( 'post_id' => $post_id ),
		array( '%d' )
	);
	$link_id = $wpdb->insert_id;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %s WHERE id = %d', $table, $link_id ) );
}

/**
 * Get a tracking link by its ID.
 *
 * @param int $link_id The tracking link ID.
 * @return object|false Tracking link object or false on failure.
 */
function qr_trackr_get_tracking_link_by_id( $link_id ) {
	global $wpdb;
	$table = $wpdb->prefix . 'qr_trackr_links';
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %s WHERE id = %d', $table, $link_id ) );
}
// ... existing code ...
