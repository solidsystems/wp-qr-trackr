<?php
/**
 * Utility functions for the QR Coder plugin.
 *
 * @package WP_QR_TRACKR
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Get the full path to the QR code image directory.
 *
 * @return string The path to the QR code directory.
 */
function qrc_get_qr_code_dir() {
	$upload_dir = wp_upload_dir();
	$qr_code_dir = $upload_dir['basedir'] . '/qr-codes';

	if ( ! file_exists( $qr_code_dir ) ) {
		wp_mkdir_p( $qr_code_dir );
	}

	return $qr_code_dir;
}

/**
 * Get the URL to the QR code image directory.
 *
 * @return string The URL to the QR code directory.
 */
function qrc_get_qr_code_url() {
	$upload_dir = wp_upload_dir();
	return $upload_dir['baseurl'] . '/qr-codes';
} 