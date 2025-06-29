<?php
/**
 * QR code generation and management module.
 *
 * This module handles all QR code related functionality including generation,
 * storage, and retrieval of QR codes and their associated data.
 *
 * @package WP_QR_TRACKR
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Returns an array of available QR code error correction levels.
 *
 * @since 1.0.0
 * @return array Array of error correction levels with their descriptions.
 */
function qrc_get_error_correction_levels() {
	return array(
		'L' => esc_html__( 'Low (7%)', 'wp-qr-trackr' ),
		'M' => esc_html__( 'Medium (15%)', 'wp-qr-trackr' ),
		'Q' => esc_html__( 'Quartile (25%)', 'wp-qr-trackr' ),
		'H' => esc_html__( 'High (30%)', 'wp-qr-trackr' ),
	);
}

/**
 * Returns an array of available QR code dot styles.
 *
 * @since 1.0.0
 * @return array Array of dot styles with their labels.
 */
function qrc_get_dot_styles() {
	return array(
		'square' => esc_html__( 'Square', 'wp-qr-trackr' ),
		'circle' => esc_html__( 'Circle', 'wp-qr-trackr' ),
	);
}

/**
 * Get a specific QR code link from the database.
 *
 * Uses WordPress object cache to improve performance on repeated lookups.
 *
 * @since 1.0.0
 * @param int $id The ID of the link to retrieve.
 * @return object|null The link object, or null if not found.
 */
function qrc_get_link( $id ) {
	global $wpdb;

	$cache_key = 'qrc_link_' . absint( $id );
	$link      = wp_cache_get( $cache_key, 'qrc_links' );

	if ( false === $link ) {
		$table_name = $wpdb->prefix . 'qr_trackr_links';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Caching implemented above, single-row lookup needed for performance.
		$link = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE id = %d",
				absint( $id )
			)
		);

		if ( $link ) {
			// Store metadata as JSON instead of serialized data.
			if ( ! empty( $link->metadata ) ) {
				$link->metadata = wp_json_encode( maybe_unserialize( $link->metadata ) );
			}
			wp_cache_set( $cache_key, $link, 'qrc_links', HOUR_IN_SECONDS );
		}
	}

	return $link;
}

/**
 * Get all QR code links from the database.
 *
 * Uses WordPress transients for caching to improve performance.
 *
 * @since 1.0.0
 * @return array Array of link objects.
 */
function qrc_get_all_links() {
	global $wpdb;

	$cache_key = 'qrc_all_links';
	$links     = get_transient( $cache_key );

	if ( false === $links ) {
		$table_name = $wpdb->prefix . 'qr_trackr_links';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Caching implemented above, bulk data retrieval.
		$links = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} ORDER BY created_at DESC"
			)
		);

		if ( $links ) {
			// Convert metadata to JSON for each link.
			foreach ( $links as $link ) {
				if ( ! empty( $link->metadata ) ) {
					$link->metadata = wp_json_encode( maybe_unserialize( $link->metadata ) );
				}
			}
			set_transient( $cache_key, $links, HOUR_IN_SECONDS );
		}
	}

	return $links;
}

/**
 * Generate a QR code and save it to a file.
 *
 * Uses Google Charts API to generate QR codes with customizable parameters.
 * Implements caching to prevent unnecessary API calls. If the primary API fails,
 * falls back to a secondary QR code generation method.
 *
 * @since 1.1.3
 * @param string $data The data to encode in the QR code.
 * @param array  $args {
 *     Optional. Arguments for QR code generation.
 *
 *     @type int    $size              QR code size in pixels. Default 200.
 *     @type int    $margin            QR code margin in pixels. Default 10.
 *     @type string $error_correction  Error correction level (L|M|Q|H). Default 'M'.
 *     @type string $dot_style         Dot style (square|circle). Default 'square'.
 *     @type string $foreground_color  Foreground color in hex. Default '#000000'.
 *     @type string $background_color  Background color in hex. Default '#ffffff'.
 *     @type bool   $add_logo          Whether to add a logo. Default false.
 *     @type string $logo_path         Path to logo image. Default empty.
 *     @type float  $logo_size_percent Logo size as percentage. Default 0.2.
 *     @type string $eye_shape         Eye shape (square|circle). Default 'square'.
 *     @type string $eye_inner_color   Eye inner color in hex. Default '#000000'.
 *     @type string $eye_outer_color   Eye outer color in hex. Default '#000000'.
 *     @type string $output_format     Output format (png|svg). Default 'png'.
 *     @type bool   $custom_gradient   Whether to use gradient. Default false.
 *     @type string $gradient_start    Gradient start color. Default '#000000'.
 *     @type string $gradient_end      Gradient end color. Default '#000000'.
 *     @type string $gradient_type     Gradient type. Default 'vertical'.
 * }
 * @return string|WP_Error The URL of the generated QR code, or a WP_Error object on failure.
 */
function qrc_generate_qr_code( $data, $args = array() ) {
	// Validate input data.
	if ( empty( $data ) ) {
		return new WP_Error(
			'qrc_empty_data',
			esc_html__( 'QR code data cannot be empty.', 'wp-qr-trackr' )
		);
	}

	$defaults = array(
		'size'              => 200,
		'margin'            => 10,
		'error_correction'  => 'M',
		'dot_style'         => 'square',
		'foreground_color'  => '#000000',
		'background_color'  => '#ffffff',
		'add_logo'          => false,
		'logo_path'         => '',
		'logo_size_percent' => 0.2,
		'eye_shape'         => 'square',
		'eye_inner_color'   => '#000000',
		'eye_outer_color'   => '#000000',
		'output_format'     => 'png',
		'custom_gradient'   => false,
		'gradient_start'    => '#000000',
		'gradient_end'      => '#000000',
		'gradient_type'     => 'vertical',
	);
	$args = wp_parse_args( $args, $defaults );

	// Validate size parameters.
	$args['size'] = absint( $args['size'] );
	if ( $args['size'] < 100 || $args['size'] > 1000 ) {
		return new WP_Error(
			'qrc_invalid_size',
			esc_html__( 'QR code size must be between 100 and 1000 pixels.', 'wp-qr-trackr' )
		);
	}

	$args['margin'] = absint( $args['margin'] );
	if ( $args['margin'] > 50 ) {
		return new WP_Error(
			'qrc_invalid_margin',
			esc_html__( 'QR code margin must not exceed 50 pixels.', 'wp-qr-trackr' )
		);
	}

	// Validate error correction level.
	if ( ! in_array( $args['error_correction'], array( 'L', 'M', 'Q', 'H' ), true ) ) {
		$args['error_correction'] = 'M';
	}

	// Validate dot style.
	if ( ! in_array( $args['dot_style'], array( 'square', 'circle' ), true ) ) {
		$args['dot_style'] = 'square';
	}

	// Validate color formats.
	$color_regex = '/^#[a-f0-9]{6}$/i';
	foreach ( array( 'foreground_color', 'background_color', 'eye_inner_color', 'eye_outer_color' ) as $color_key ) {
		if ( ! preg_match( $color_regex, $args[ $color_key ] ) ) {
			return new WP_Error(
				'qrc_invalid_color',
				sprintf(
					/* translators: %s: color parameter name */
					esc_html__( 'Invalid color format for %s. Must be a valid hex color code.', 'wp-qr-trackr' ),
					$color_key
				)
			);
		}
	}

	// Generate cache key based on parameters.
	$cache_key = 'qrc_' . md5( $data . wp_json_encode( $args ) );
	$qr_url = get_transient( $cache_key );

	if ( false === $qr_url ) {
		// Build the query parameters for the QR code API.
		$api_params = array(
			'cht'  => 'qr',
			'chs'  => $args['size'] . 'x' . $args['size'],
			'chl'  => rawurlencode( wp_kses( $data, array() ) ),
			'choe' => 'UTF-8',
			'chld' => $args['error_correction'] . '|' . $args['margin'],
		);

		$api_url = add_query_arg( $api_params, 'https://chart.googleapis.com/chart' );

		// Verify the API response.
		$response = wp_safe_remote_get( $api_url );
		if ( is_wp_error( $response ) ) {
			// Log the error for debugging.
			error_log(
				sprintf(
					'QR Code API Error: %s (URL: %s).',
					$response->get_error_message(),
					$api_url
				)
			);

			// Try fallback API if primary fails.
			$fallback_url = qrc_generate_fallback_qr( $data, $args );
			if ( ! is_wp_error( $fallback_url ) ) {
				set_transient( $cache_key, $fallback_url, DAY_IN_SECONDS );
				return $fallback_url;
			}

			return new WP_Error(
				'qrc_api_error',
				esc_html__( 'Failed to generate QR code. Please try again later.', 'wp-qr-trackr' )
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $response_code ) {
			error_log(
				sprintf(
					'QR Code API Error: HTTP %d (URL: %s).',
					$response_code,
					$api_url
				)
			);

			// Try fallback API if primary fails.
			$fallback_url = qrc_generate_fallback_qr( $data, $args );
			if ( ! is_wp_error( $fallback_url ) ) {
				set_transient( $cache_key, $fallback_url, DAY_IN_SECONDS );
				return $fallback_url;
			}

			return new WP_Error(
				'qrc_api_error',
				esc_html__( 'Failed to generate QR code. Please try again later.', 'wp-qr-trackr' )
			);
		}

		// Save the QR code image.
		$upload_dir = wp_upload_dir();
		$qr_dir = $upload_dir['basedir'] . '/qr-codes';
		if ( ! file_exists( $qr_dir ) ) {
			wp_mkdir_p( $qr_dir );
		}

		$filename = sanitize_file_name( 'qr-' . md5( $data . wp_json_encode( $args ) ) . '.png' );
		$file_path = $qr_dir . '/' . $filename;
		$qr_url = $upload_dir['baseurl'] . '/qr-codes/' . $filename;

		// Save the image using WP_Filesystem.
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();
		global $wp_filesystem;

		$image_data = wp_remote_retrieve_body( $response );
		if ( ! $wp_filesystem->put_contents( $file_path, $image_data, FS_CHMOD_FILE ) ) {
			error_log( sprintf( 'Failed to save QR code image to %s.', $file_path ) );
			return new WP_Error(
				'qrc_save_error',
				esc_html__( 'Failed to save QR code image.', 'wp-qr-trackr' )
			);
		}

		// Cache the URL.
		set_transient( $cache_key, $qr_url, DAY_IN_SECONDS );
	}

	return $qr_url;
}

/**
 * Generate a QR code using a fallback API.
 *
 * This function is called when the primary QR code generation API fails.
 * It uses an alternative API to ensure QR code generation availability.
 *
 * @since 1.1.3
 * @param string $data The data to encode in the QR code.
 * @param array  $args QR code generation arguments.
 * @return string|WP_Error The URL of the generated QR code, or a WP_Error object on failure.
 */
function qrc_generate_fallback_qr( $data, $args ) {
	// Implementation of fallback QR code generation.
	// This is a placeholder that should be replaced with actual fallback logic.
	return new WP_Error(
		'qrc_no_fallback',
		esc_html__( 'Fallback QR code generation not implemented.', 'wp-qr-trackr' )
	);
}
