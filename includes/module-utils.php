<?php
/**
 * Utility functions for the QR Trackr plugin.
 *
 * This file contains utility functions for managing QR code directories,
 * URLs, and validation. It handles caching, error handling, and proper
 * sanitization of inputs and outputs.
 *
 * @package WP_QR_TRACKR
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Get the full path to the QR code image directory.
 *
 * Creates the directory if it doesn't exist and caches the path
 * for future use to minimize filesystem operations.
 *
 * @since 1.0.0
 * @return string|WP_Error The path to the QR code directory or WP_Error on failure.
 */
function qr_trackr_get_qr_code_dir() {
	$cache_key = 'qr_trackr_qr_code_dir';
	$qr_code_dir = wp_cache_get( $cache_key );

	if ( false === $qr_code_dir ) {
		$upload_dir = wp_upload_dir();
		
		if ( isset( $upload_dir['error'] ) && ! empty( $upload_dir['error'] ) ) {
			$error_msg = sprintf(
				/* translators: %s: Upload directory error message */
				esc_html__( 'Failed to get upload directory: %s', 'wp-qr-trackr' ),
				esc_html( $upload_dir['error'] )
			);
			qr_trackr_debug_log( $error_msg );
			return new WP_Error( 'upload_dir_error', $error_msg );
		}

		$qr_code_dir = wp_normalize_path( trailingslashit( $upload_dir['basedir'] ) . 'qr-codes' );

		if ( ! file_exists( $qr_code_dir ) ) {
			$created = wp_mkdir_p( $qr_code_dir );
			if ( ! $created ) {
				$error_msg = sprintf(
					/* translators: %s: Directory path */
					esc_html__( 'Failed to create QR code directory: %s', 'wp-qr-trackr' ),
					esc_html( $qr_code_dir )
				);
				qr_trackr_debug_log( $error_msg );
				return new WP_Error( 'dir_creation_failed', $error_msg );
			}
		}

		$cache_set = wp_cache_set( $cache_key, $qr_code_dir, '', HOUR_IN_SECONDS );
		if ( ! $cache_set ) {
			qr_trackr_debug_log( 'Failed to cache QR code directory path.' );
		}
	}

	return $qr_code_dir;
}

/**
 * Get the URL to the QR code image directory.
 *
 * Retrieves and caches the URL to the QR code directory for efficient access.
 * Properly escapes the URL and handles potential upload directory errors.
 *
 * @since 1.0.0
 * @return string|WP_Error The URL to the QR code directory or WP_Error on failure.
 */
function qr_trackr_get_qr_code_url() {
	$cache_key = 'qr_trackr_qr_code_url';
	$qr_code_url = wp_cache_get( $cache_key );

	if ( false === $qr_code_url ) {
		$upload_dir = wp_upload_dir();
		
		if ( isset( $upload_dir['error'] ) && ! empty( $upload_dir['error'] ) ) {
			$error_msg = sprintf(
				/* translators: %s: Upload directory error message */
				esc_html__( 'Failed to get upload directory URL: %s', 'wp-qr-trackr' ),
				esc_html( $upload_dir['error'] )
			);
			qr_trackr_debug_log( $error_msg );
			return new WP_Error( 'upload_dir_error', $error_msg );
		}

		$qr_code_url = esc_url_raw( trailingslashit( $upload_dir['baseurl'] ) . 'qr-codes' );
		$cache_set = wp_cache_set( $cache_key, $qr_code_url, '', HOUR_IN_SECONDS );
		if ( ! $cache_set ) {
			qr_trackr_debug_log( 'Failed to cache QR code directory URL.' );
		}
	}

	return $qr_code_url;
}

/**
 * Validate and sanitize QR code ID.
 *
 * Ensures the provided QR code ID is a valid positive integer.
 * Returns a WP_Error if validation fails.
 *
 * @since 1.0.0
 * @param mixed $qr_id The QR code ID to validate.
 * @return int|WP_Error Sanitized QR code ID or WP_Error on failure.
 */
function qr_trackr_validate_qr_id( $qr_id ) {
	$qr_id = absint( $qr_id );
	
	if ( empty( $qr_id ) ) {
		return new WP_Error(
			'invalid_qr_id',
			esc_html__( 'Invalid QR code ID.', 'wp-qr-trackr' )
		);
	}

	return $qr_id;
}
