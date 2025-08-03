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
		$code       = '';
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
 * Creates the visual QR code image using the QR Server API and stores it
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
		'size'             => 200,
		'margin'           => 0,
		'error_correction' => 'M',
	);
	$args     = wp_parse_args( $args, $defaults );

	// Generate the URL that the QR code will redirect to.
	$redirect_url = qr_trackr_get_redirect_url( sanitize_text_field( $tracking_code ) );

	// Check cache first.
	$cache_key  = 'qr_trackr_image_' . md5( $tracking_code . wp_json_encode( $args ) );
	$cached_url = wp_cache_get( $cache_key );

	if ( false !== $cached_url ) {
		return $cached_url;
	}

	// Create QR codes directory.
	$qr_dir = qr_trackr_get_qr_code_dir();
	if ( is_wp_error( $qr_dir ) ) {
		return $qr_dir;
	}

	$filename  = sprintf( 'qr-%s.png', sanitize_file_name( $tracking_code ) );
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

	// Generate QR code using QR Server API (modern replacement for deprecated Google Charts API).
	$api_params = array(
		'size'    => absint( $args['size'] ) . 'x' . absint( $args['size'] ),
		'data'    => rawurlencode( $redirect_url ),
		'format'  => 'png',
		'ecc'     => sanitize_text_field( strtoupper( $args['error_correction'] ) ),
		'margin'  => absint( $args['margin'] ),
		'color'   => '000000',
		'bgcolor' => 'ffffff',
	);

	$api_url = add_query_arg( $api_params, 'https://api.qrserver.com/v1/create-qr-code/' );

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

/**
 * Enhanced logging function with Query Monitor integration and environment-specific verbosity.
 *
 * @since 1.0.0
 * @param string $message The message to log.
 * @param string $level The log level (debug, info, warning, error).
 * @param array  $context Optional. Additional context data.
 * @return void
 */
function qr_trackr_log( $message, $level = 'info', $context = array() ) {
	// Determine environment and verbosity level.
	$is_dev     = defined( 'WP_DEBUG' ) && WP_DEBUG;
	$is_nonprod = defined( 'WP_ENVIRONMENT_TYPE' ) && 'staging' === WP_ENVIRONMENT_TYPE;

	// Dev environment (8080) - extra verbose logging.
	if ( $is_dev ) {
		$log_message = sprintf( '[WP QR Trackr] [%s] %s', strtoupper( $level ), $message );

		if ( ! empty( $context ) ) {
			$log_message .= ' Context: ' . wp_json_encode( $context );
		}

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentional debug logging.
		error_log( $log_message );

		// Query Monitor integration for dev environment.
		if ( function_exists( 'do_action' ) ) {
			do_action( 'qm_debug', $log_message );
		}
	}

	// Nonprod environment (8081) - informational logging with verbose capability.
	if ( $is_nonprod ) {
		// Always log errors and warnings.
		if ( in_array( $level, array( 'error', 'warning' ), true ) ) {
			$log_message = sprintf( '[WP QR Trackr] [%s] %s', strtoupper( $level ), $message );

			if ( ! empty( $context ) ) {
				$log_message .= ' Context: ' . wp_json_encode( $context );
			}

			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentional debug logging.
			error_log( $log_message );
		}

		// Log info level if verbose debugging is enabled via control script.
		if ( 'info' === $level && get_option( 'qr_trackr_verbose_logging', false ) ) {
			$log_message = sprintf( '[WP QR Trackr] [INFO] %s', $message );

			if ( ! empty( $context ) ) {
				$log_message .= ' Context: ' . wp_json_encode( $context );
			}

			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentional debug logging.
			error_log( $log_message );
		}
	}
}

/**
 * Log form submission events with detailed context.
 *
 * @since 1.0.0
 * @param string $form_name The name/identifier of the form.
 * @param array  $form_data The form data being submitted.
 * @param string $action The action being performed (create, update, delete).
 * @return void
 */
function qr_trackr_log_form_submission( $form_name, $form_data, $action ) {
	$context = array(
		'form_name' => $form_name,
		'action'    => $action,
		'user_id'   => get_current_user_id(),
		'user_ip'   => qr_trackr_get_user_ip(),
		'timestamp' => current_time( 'mysql' ),
		'data_keys' => array_keys( $form_data ),
	);

	qr_trackr_log( "Form submission: {$form_name} - {$action}", 'info', $context );
}

/**
 * Log element creation events.
 *
 * @since 1.0.0
 * @param string $element_type The type of element being created.
 * @param array  $element_data The element data.
 * @param string $context_location Where the element is being created.
 * @return void
 */
function qr_trackr_log_element_creation( $element_type, $element_data, $context_location ) {
	$context = array(
		'element_type' => $element_type,
		'location'     => $context_location,
		'user_id'      => get_current_user_id(),
		'timestamp'    => current_time( 'mysql' ),
		'data_keys'    => array_keys( $element_data ),
	);

	qr_trackr_log( "Element creation: {$element_type} in {$context_location}", 'info', $context );
}

/**
 * Log AJAX request events.
 *
 * @since 1.0.0
 * @param string $action The AJAX action being performed.
 * @param array  $request_data The request data.
 * @param string $response_status The response status (success, error).
 * @return void
 */
function qr_trackr_log_ajax_request( $action, $request_data, $response_status ) {
	$context = array(
		'action'          => $action,
		'response_status' => $response_status,
		'user_id'         => get_current_user_id(),
		'user_ip'         => qr_trackr_get_user_ip(),
		'timestamp'       => current_time( 'mysql' ),
		'data_keys'       => array_keys( $request_data ),
	);

	qr_trackr_log( "AJAX request: {$action} - {$response_status}", 'info', $context );
}

/**
 * Log database operations.
 *
 * @since 1.0.0
 * @param string $operation The database operation (insert, update, delete, select).
 * @param string $table The table being operated on.
 * @param array  $data The data being processed.
 * @param bool   $success Whether the operation was successful.
 * @return void
 */
function qr_trackr_log_db_operation( $operation, $table, $data, $success ) {
	$context = array(
		'operation' => $operation,
		'table'     => $table,
		'success'   => $success,
		'user_id'   => get_current_user_id(),
		'timestamp' => current_time( 'mysql' ),
		'data_keys' => array_keys( $data ),
	);

	$level = $success ? 'info' : 'error';
	qr_trackr_log( "Database operation: {$operation} on {$table} - " . ( $success ? 'SUCCESS' : 'FAILED' ), $level, $context );
}

/**
 * Log page load events.
 *
 * @since 1.0.0
 * @param string $page_name The name of the page being loaded.
 * @param array  $page_data Additional page data.
 * @return void
 */
function qr_trackr_log_page_load( $page_name, $page_data = array() ) {
	$context = array(
		'page_name'  => $page_name,
		'user_id'    => get_current_user_id(),
		'user_ip'    => qr_trackr_get_user_ip(),
		'timestamp'  => current_time( 'mysql' ),
		'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
	);

	if ( ! empty( $page_data ) ) {
		$context['page_data'] = $page_data;
	}

	qr_trackr_log( "Page load: {$page_name}", 'info', $context );
}

/**
 * Get user IP address safely.
 *
 * @since 1.0.0
 * @return string The user's IP address.
 */
function qr_trackr_get_user_ip() {
	$ip_keys = array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' );

			foreach ( $ip_keys as $key ) {
		if ( array_key_exists( $key, $_SERVER ) === true ) {
			foreach ( explode( ',', wp_unslash( $_SERVER[ $key ] ) ) as $ip ) {
				$ip = trim( $ip );
				if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) !== false ) {
					return $ip;
				}
			}
		}
	}

	return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
}

/**
 * Enable verbose logging for nonprod environment.
 *
 * @since 1.0.0
 * @return void
 */
function qr_trackr_enable_verbose_logging() {
	update_option( 'qr_trackr_verbose_logging', true );
	qr_trackr_log( 'Verbose logging enabled via control script', 'info' );
}

/**
 * Disable verbose logging for nonprod environment.
 *
 * @since 1.0.0
 * @return void
 */
function qr_trackr_disable_verbose_logging() {
	update_option( 'qr_trackr_verbose_logging', false );
	qr_trackr_log( 'Verbose logging disabled via control script', 'info' );
}

/**
 * Generate QR code URLs using clean rewrite URLs.
 *
 * Creates URLs in the format: /qr/{code} using WordPress rewrite rules
 * for clean, user-friendly URLs that work with native WordPress redirects.
 *
 * @since 1.0.0
 * @param string $qr_code The QR code tracking identifier.
 * @return string The redirect URL.
 */
function qr_trackr_get_redirect_url( $qr_code ) {
	return home_url( '/qr/' . sanitize_text_field( $qr_code ) . '/' );
}

/**
 * Generate QR code URLs using alternative clean rewrite URLs.
 *
 * Creates URLs in the format: /qrcode/{code} using WordPress rewrite rules
 * for clean, user-friendly URLs that work with native WordPress redirects.
 *
 * @since 1.2.41
 * @param string $qr_code The QR code tracking identifier.
 * @return string The redirect URL.
 */
function qr_trackr_get_redirect_url_alt( $qr_code ) {
	return home_url( '/qrcode/' . sanitize_text_field( $qr_code ) . '/' );
}

/**
 * Get current logging status.
 *
 * @since 1.0.0
 * @return array The current logging configuration.
 */
function qr_trackr_get_logging_status() {
	return array(
		'wp_debug'                => defined( 'WP_DEBUG' ) && WP_DEBUG,
		'environment'             => defined( 'WP_ENVIRONMENT_TYPE' ) ? WP_ENVIRONMENT_TYPE : 'unknown',
		'verbose_logging'         => get_option( 'qr_trackr_verbose_logging', false ),
		'query_monitor_available' => function_exists( 'do_action' ),
	);
}
