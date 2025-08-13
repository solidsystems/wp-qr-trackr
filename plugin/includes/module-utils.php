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
	$cache_key   = 'qr_trackr_qr_code_dir';
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
	$cache_key   = 'qr_trackr_qr_code_url';
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
		$cache_set   = wp_cache_set( $cache_key, $qr_code_url, '', HOUR_IN_SECONDS );
		if ( ! $cache_set ) {
			qr_trackr_debug_log( 'Failed to cache QR code directory URL.' );
		}
	}

	return $qr_code_url;
}

/**
 * Validate a QR code ID.
 *
 * Ensures the QR code ID is valid and exists in the database.
 * Uses caching to improve performance for repeated validations.
 *
 * @since 1.0.0
 * @param string $qr_id The QR code ID to validate.
 * @return bool|WP_Error True if valid, false if invalid, WP_Error on failure.
 */
function qr_trackr_validate_qr_id( $qr_id ) {
	if ( empty( $qr_id ) ) {
		return false;
	}

	$cache_key     = 'qr_trackr_validate_' . md5( $qr_id );
	$cached_result = wp_cache_get( $cache_key );

	if ( false !== $cached_result ) {
		return $cached_result;
	}

	global $wpdb;
	// Use the correct table name per project standards.
	$table_name = $wpdb->prefix . 'qr_trackr_links';

	$result = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$table_name} WHERE qr_code = %s",
			$qr_id
		)
	);

	$is_valid = ( $result > 0 );
	wp_cache_set( $cache_key, $is_valid, '', 300 ); // Cache for 5 minutes.

	return $is_valid;
}

/**
 * Generate a unique QR code identifier.
 *
 * Creates a random string that can be used as a QR code identifier.
 * Ensures uniqueness by checking against existing codes in the database.
 *
 * @since 1.0.0
 * @param int $length The length of the generated code. Default 8.
 * @return string|WP_Error The generated QR code or WP_Error on failure.
 */
function qr_trackr_generate_unique_qr_code( $length = 8 ) {
	$max_attempts = 10;
	$attempts     = 0;

	do {
		$qr_code = wp_generate_password( $length, false );
		$exists  = qr_trackr_validate_qr_id( $qr_code );
		++$attempts;
	} while ( $exists && $attempts < $max_attempts );

	if ( $attempts >= $max_attempts ) {
		return new WP_Error( 'generation_failed', __( 'Failed to generate unique QR code after maximum attempts.', 'wp-qr-trackr' ) );
	}

	return $qr_code;
}

/**
 * Generate a QR code image.
 *
 * Creates a QR code image using the provided tracking code and options.
 * Supports various QR code formats and styling options.
 *
 * @since 1.0.0
 * @param string $tracking_code The tracking code to encode in the QR code.
 * @param array  $args Optional. Additional arguments for QR code generation.
 * @return string|WP_Error The path to the generated image or WP_Error on failure.
 */
function qr_trackr_generate_qr_image( $tracking_code, $args = array() ) {
	if ( empty( $tracking_code ) ) {
		return new WP_Error( 'invalid_code', __( 'Tracking code cannot be empty.', 'wp-qr-trackr' ) );
	}

	$qr_code_dir = qr_trackr_get_qr_code_dir();
	if ( is_wp_error( $qr_code_dir ) ) {
		return $qr_code_dir;
	}

	$defaults = array(
		'size'   => 200,
		'format' => 'png',
		'ecc'    => 'M',
		'margin' => 2,
		'color'  => '#000000',
	);

	$args     = wp_parse_args( $args, $defaults );
	$filename = sanitize_file_name( $tracking_code . '.' . $args['format'] );
	$filepath = wp_normalize_path( trailingslashit( $qr_code_dir ) . $filename );

	// Return existing file if it already exists.
	if ( file_exists( $filepath ) ) {
		return $filepath;
	}

	// Generate QR code using a library (this is a placeholder - you'd need to implement actual QR generation).
	$qr_data = array(
		'text'   => $tracking_code,
		'size'   => $args['size'],
		'margin' => $args['margin'],
		'format' => $args['format'],
		'ecc'    => $args['ecc'],
		'color'  => $args['color'],
	);

	// For now, create a simple placeholder file using WP_Filesystem API.
	$content = 'QR Code for: ' . $tracking_code;

	// Initialize WP_Filesystem for safe file operations.
	require_once ABSPATH . 'wp-admin/includes/file.php';
	$fs_initialized = WP_Filesystem();

	global $wp_filesystem;
	if ( ! $fs_initialized || ! $wp_filesystem ) {
		return new WP_Error( 'fs_init_failed', __( 'Failed to initialize filesystem API.', 'wp-qr-trackr' ) );
	}

	$written = $wp_filesystem->put_contents( $filepath, $content, FS_CHMOD_FILE );
	if ( ! $written ) {
		return new WP_Error( 'file_creation_failed', __( 'Failed to create QR code image file.', 'wp-qr-trackr' ) );
	}

	return $filepath;
}

/**
 * Log debug messages.
 *
 * Logs debug messages with proper formatting and context.
 * Only logs if debug mode is enabled.
 *
 * @since 1.0.0
 * @param string $message The message to log.
 * @param array  $context Optional. Additional context data.
 */
function qr_trackr_debug_log( $message, $context = array() ) {
	if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
		return;
	}

	$log_entry = array(
		'timestamp' => current_time( 'mysql' ),
		'message'   => $message,
		'context'   => $context,
	);

	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	error_log( 'QR Trackr Debug: ' . wp_json_encode( $log_entry ) );
}

/**
 * Log messages with different levels.
 *
 * Provides a centralized logging function with different log levels.
 * Supports various output formats and destinations.
 *
 * @since 1.0.0
 * @param string $message The message to log.
 * @param string $level   Optional. The log level. Default 'info'.
 * @param array  $context Optional. Additional context data.
 */
function qr_trackr_log( $message, $level = 'info', $context = array() ) {
	$log_entry = array(
		'timestamp' => current_time( 'mysql' ),
		'level'     => $level,
		'message'   => $message,
		'context'   => $context,
	);

	// Log to WordPress debug log if available.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( 'QR Trackr ' . strtoupper( $level ) . ': ' . wp_json_encode( $log_entry ) );
	}

	// Additional logging can be added here (e.g., to database, external service).
	do_action( 'qr_trackr_log', $log_entry );
}

/**
 * Log form submissions.
 *
 * Logs form submission data for analytics and debugging purposes.
 * Includes user information and form data.
 *
 * @since 1.0.0
 * @param string $form_name The name of the form.
 * @param array  $form_data The form data.
 * @param string $action    The action being performed.
 */
function qr_trackr_log_form_submission( $form_name, $form_data, $action ) {
	$log_data = array(
		'action'    => $action,
		'user_id'   => get_current_user_id(),
		'user_ip'   => qr_trackr_get_user_ip(),
		'form_name' => $form_name,
		'form_data' => $form_data,
	);

	qr_trackr_log( 'Form submission logged', 'info', $log_data );
}

/**
 * Log element creation events.
 *
 * Logs when QR code elements are created for tracking and analytics.
 * Includes element type and context information.
 *
 * @since 1.0.0
 * @param string $element_type      The type of element being created.
 * @param array  $element_data      The element data.
 * @param string $context_location The location where the element was created.
 */
function qr_trackr_log_element_creation( $element_type, $element_data, $context_location ) {
	$log_data = array(
		'action'    => 'element_creation',
		'user_id'   => get_current_user_id(),
		'user_ip'   => qr_trackr_get_user_ip(),
		'timestamp' => current_time( 'mysql' ),
		'data_keys' => array_keys( $element_data ),
		'element'   => sanitize_text_field( $element_type ),
		'location'  => sanitize_text_field( $context_location ),
	);

	qr_trackr_log( 'Element creation logged', 'info', $log_data );
}

/**
 * Log AJAX requests.
 *
 * Logs AJAX request data for debugging and monitoring purposes.
 * Includes request details and response status.
 *
 * @since 1.0.0
 * @param string $action         The AJAX action.
 * @param array  $request_data   The request data.
 * @param string $response_status The response status.
 */
function qr_trackr_log_ajax_request( $action, $request_data, $response_status ) {
	$log_data = array(
		'action'    => $action,
		'user_id'   => get_current_user_id(),
		'user_ip'   => qr_trackr_get_user_ip(),
		'timestamp' => current_time( 'mysql' ),
		'data_keys' => array_keys( $request_data ),
		'status'    => sanitize_text_field( $response_status ),
	);

	qr_trackr_log( 'AJAX request logged', 'info', $log_data );
}

/**
 * Log database operations.
 *
 * Logs database operation details for monitoring and debugging.
 * Includes operation type, table, and success status.
 *
 * @since 1.0.0
 * @param string $operation The database operation.
 * @param string $table     The table name.
 * @param array  $data      The data being operated on.
 * @param bool   $success   Whether the operation was successful.
 */
function qr_trackr_log_db_operation( $operation, $table, $data, $success ) {
	$log_data = array(
		'table'   => $table,
		'success' => $success,
		'user_id' => get_current_user_id(),
	);

	qr_trackr_log( 'Database operation logged', 'info', $log_data );
}

/**
 * Log page load events.
 *
 * Logs page load events for analytics and user tracking.
 * Includes page information and user context.
 *
 * @since 1.0.0
 * @param string $page_name The name of the page.
 * @param array  $page_data Optional. Additional page data.
 */
function qr_trackr_log_page_load( $page_name, $page_data = array() ) {
	$log_data = array(
		'page_name' => $page_name,
		'user_id'   => get_current_user_id(),
		'user_ip'   => qr_trackr_get_user_ip(),
		'timestamp' => current_time( 'mysql' ),
		'data_keys' => is_array( $page_data ) ? array_keys( $page_data ) : array(),
	);

	qr_trackr_log( 'Page load logged', 'info', $log_data );
}

/**
 * Get the user's IP address.
 *
 * Retrieves the user's IP address with proper sanitization.
 * Handles various server configurations and proxy setups.
 *
 * @since 1.0.0
 * @return string The user's IP address.
 */
function qr_trackr_get_user_ip() {
	$ip_keys = array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' );

	foreach ( $ip_keys as $key ) {
		if ( array_key_exists( $key, $_SERVER ) === true ) {
			$raw_value = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
			foreach ( explode( ',', $raw_value ) as $ip ) {
				$ip = trim( $ip );
				if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) !== false ) {
					return $ip;
				}
			}
		}
	}

	return '0.0.0.0';
}

/**
 * Enable verbose logging.
 *
 * Enables detailed logging for debugging purposes.
 * Sets appropriate WordPress options and constants.
 *
 * @since 1.0.0
 */
function qr_trackr_enable_verbose_logging() {
	update_option( 'qr_trackr_verbose_logging', true );
	qr_trackr_log( 'Verbose logging enabled', 'info' );
}

/**
 * Disable verbose logging.
 *
 * Disables detailed logging to reduce log file size.
 * Updates WordPress options accordingly.
 *
 * @since 1.0.0
 */
function qr_trackr_disable_verbose_logging() {
	update_option( 'qr_trackr_verbose_logging', false );
	qr_trackr_log( 'Verbose logging disabled', 'info' );
}

/**
 * Get the redirect URL for a QR code.
 *
 * Retrieves the redirect URL associated with a QR code.
 * Uses caching for performance and includes proper error handling.
 *
 * @since 1.0.0
 * @param string $qr_code The QR code identifier.
 * @return string|WP_Error The redirect URL or WP_Error on failure.
 */
function qr_trackr_get_redirect_url( $qr_code ) {
	if ( empty( $qr_code ) ) {
		return new WP_Error( 'invalid_qr_code', __( 'QR code cannot be empty.', 'wp-qr-trackr' ) );
	}

	// Prefer clean rewrite URL for tracking: /qr/{code}/.
	$qr_code   = sanitize_text_field( $qr_code );
	$cache_key = 'qr_trackr_redirect_' . md5( $qr_code );

	$cached_url = wp_cache_get( $cache_key );
	if ( false !== $cached_url ) {
		return $cached_url;
	}

	$url = esc_url_raw( home_url( '/qr/' . rawurlencode( $qr_code ) . '/' ) );
	wp_cache_set( $cache_key, $url, '', 300 );

	return $url;
}

/**
 * Get the redirect URL for a QR code (alternative method).
 *
 * Alternative method for retrieving redirect URLs with different caching strategy.
 * Useful for fallback scenarios or different performance requirements.
 *
 * @since 1.0.0
 * @param string $qr_code The QR code identifier.
 * @return string|WP_Error The redirect URL or WP_Error on failure.
 */
function qr_trackr_get_redirect_url_alt( $qr_code ) {
	// Alt method mirrors primary: construct clean URL directly.
	return qr_trackr_get_redirect_url( $qr_code );
}

/**
 * Get the current logging status.
 *
 * Retrieves the current logging configuration and status.
 * Returns an array with various logging settings and states.
 *
 * @since 1.0.0
 * @return array The logging status information.
 */
function qr_trackr_get_logging_status() {
	return array(
		'wp_debug'        => defined( 'WP_DEBUG' ) ? WP_DEBUG : false,
		'environment'     => defined( 'WP_ENVIRONMENT_TYPE' ) ? WP_ENVIRONMENT_TYPE : 'production',
		'verbose_logging' => get_option( 'qr_trackr_verbose_logging', false ),
	);
}
