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

/**
 * Generate a unique QR code tracking identifier.
 *
 * Creates a unique alphanumeric code for QR code tracking and URL generation.
 * Ensures the generated code doesn't already exist in the database.
 *
 * @since 1.0.0
 * @param int $length Optional. Length of the generated code. Default 8.
 * @return string The unique QR code identifier.
 */
function qr_trackr_generate_unique_qr_code( $length = 8 ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';
	
	do {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$code = '';
		for ( $i = 0; $i < $length; $i++ ) {
			$code .= $characters[ wp_rand( 0, strlen( $characters ) - 1 ) ];
		}
		
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Checking for uniqueness, minimal impact.
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name} WHERE qr_code = %s",
				$code
			)
		);
	} while ( $exists > 0 );
	
	return $code;
}

/**
 * Generate QR code image URL for a given tracking code.
 *
 * Creates the visual QR code image using the Google Charts API and stores it
 * in the WordPress uploads directory. Uses caching to avoid regenerating
 * existing QR codes.
 *
 * @since 1.0.0
 * @param string $tracking_code The QR code tracking identifier.
 * @param array  $args Optional. QR code generation arguments.
 * @return string|WP_Error The QR code image URL or WP_Error on failure.
 */
function qr_trackr_generate_qr_image( $tracking_code, $args = array() ) {
	if ( empty( $tracking_code ) ) {
		return new WP_Error(
			'qr_trackr_empty_code',
			esc_html__( 'QR tracking code cannot be empty.', 'wp-qr-trackr' )
		);
	}

	$defaults = array(
		'size' => 200,
		'margin' => 0,
		'error_correction' => 'M',
	);
	$args = wp_parse_args( $args, $defaults );

	// Generate the URL that the QR code will redirect to.
	$redirect_url = home_url( '/qr/' . sanitize_text_field( $tracking_code ) );

	// Check cache first.
	$cache_key = 'qr_trackr_image_' . md5( $tracking_code . wp_json_encode( $args ) );
	$cached_url = wp_cache_get( $cache_key );

	if ( false !== $cached_url ) {
		return $cached_url;
	}

	// Create QR codes directory.
	$qr_dir = qr_trackr_get_qr_code_dir();
	if ( is_wp_error( $qr_dir ) ) {
		return $qr_dir;
	}

	$filename = sprintf( 'qr-%s.png', sanitize_file_name( $tracking_code ) );
	$file_path = trailingslashit( $qr_dir ) . $filename;

	// Check if file already exists.
	if ( file_exists( $file_path ) ) {
		$qr_url = qr_trackr_get_qr_code_url();
		if ( is_wp_error( $qr_url ) ) {
			return $qr_url;
		}
		$image_url = trailingslashit( $qr_url ) . $filename;
		wp_cache_set( $cache_key, $image_url, '', HOUR_IN_SECONDS );
		return $image_url;
	}

	// Generate QR code using Google Charts API.
	$api_params = array(
		'cht' => 'qr',
		'chs' => absint( $args['size'] ) . 'x' . absint( $args['size'] ),
		'chl' => rawurlencode( $redirect_url ),
		'choe' => 'UTF-8',
		'chld' => sanitize_text_field( $args['error_correction'] ) . '|' . absint( $args['margin'] ),
	);

	$api_url = add_query_arg( $api_params, 'https://chart.googleapis.com/chart' );

	// Get the QR code image.
	$response = wp_safe_remote_get( $api_url, array( 'timeout' => 30 ) );

	if ( is_wp_error( $response ) ) {
		qr_trackr_debug_log( sprintf( 'QR Code API Error: %s', $response->get_error_message() ) );
		return new WP_Error(
			'qr_trackr_api_error',
			esc_html__( 'Failed to generate QR code image.', 'wp-qr-trackr' )
		);
	}

	$response_code = wp_remote_retrieve_response_code( $response );
	if ( 200 !== $response_code ) {
		qr_trackr_debug_log( sprintf( 'QR Code API Error: HTTP %d', $response_code ) );
		return new WP_Error(
			'qr_trackr_api_error',
			esc_html__( 'Failed to generate QR code image.', 'wp-qr-trackr' )
		);
	}

	// Save the image.
	require_once ABSPATH . 'wp-admin/includes/file.php';
	WP_Filesystem();
	global $wp_filesystem;

	$image_data = wp_remote_retrieve_body( $response );
	if ( ! $wp_filesystem->put_contents( $file_path, $image_data, FS_CHMOD_FILE ) ) {
		qr_trackr_debug_log( sprintf( 'Failed to save QR code image to %s', $file_path ) );
		return new WP_Error(
			'qr_trackr_save_error',
			esc_html__( 'Failed to save QR code image.', 'wp-qr-trackr' )
		);
	}

	// Return the image URL.
	$qr_url = qr_trackr_get_qr_code_url();
	if ( is_wp_error( $qr_url ) ) {
		return $qr_url;
	}

	$image_url = trailingslashit( $qr_url ) . $filename;
	wp_cache_set( $cache_key, $image_url, '', HOUR_IN_SECONDS );

	return $image_url;
}

/**
 * Debug logging function.
 *
 * Logs debug messages when WP_DEBUG is enabled. Messages are prefixed
 * with the plugin name for easy identification in logs.
 *
 * @since 1.0.0
 * @param string $message The message to log.
 * @param array  $context Optional. Additional context data.
 * @return void
 */
function qr_trackr_debug_log( $message, $context = array() ) {
	if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
		return;
	}

	$log_message = sprintf( '[WP QR Trackr] %s', $message );
	
	if ( ! empty( $context ) ) {
		$log_message .= ' Context: ' . wp_json_encode( $context );
	}

	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentional debug logging.
	error_log( $log_message );
}
