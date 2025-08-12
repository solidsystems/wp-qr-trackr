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
 * Get QR code by ID.
 *
 * @param int $id The QR code ID.
 * @return object|null The QR code object or null if not found.
 */
function qrc_get_link( $id ) {
	global $wpdb;

	$cache_key = 'qrc_link_' . absint( $id );
	$link      = wp_cache_get( $cache_key, 'qrc_links' );

	if ( false === $link ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cache implemented, direct query needed for performance.
		$link = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM `{$wpdb->prefix}qr_trackr_links` WHERE id = %d",
				absint( $id )
			)
		);

		if ( $link ) {
			// Store metadata as JSON instead of serialized data.
			if ( ! empty( $link->metadata ) ) {
				$link->metadata = wp_json_encode( maybe_unserialize( $link->metadata ) );
			}
			wp_cache_set( $cache_key, $link, 'qrc_links', HOUR_IN_SECONDS );

			qr_trackr_log_db_operation( 'select', $wpdb->prefix . 'qr_trackr_links', array( 'id' => $id ), true );
		} else {
			qr_trackr_log_db_operation( 'select', $wpdb->prefix . 'qr_trackr_links', array( 'id' => $id ), false );
		}
	} else {
		qr_trackr_log( 'QR code retrieved from cache', 'info', array( 'id' => $id ) );
	}

	return $link;
}

/**
 * Get all QR codes.
 *
 * @return array Array of QR code objects.
 */
function qrc_get_all_links() {
	global $wpdb;

	$cache_key = 'qrc_all_links';
	$links     = get_transient( $cache_key );

	if ( false === $links ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cache implemented, direct query needed for performance.
		$links = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$wpdb->prefix}qr_trackr_links` ORDER BY created_at DESC"
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

			qr_trackr_log_db_operation( 'select_all', $wpdb->prefix . 'qr_trackr_links', array( 'count' => count( $links ) ), true );
		} else {
			qr_trackr_log_db_operation( 'select_all', $wpdb->prefix . 'qr_trackr_links', array(), false );
		}
	} else {
		qr_trackr_log( 'All QR codes retrieved from cache', 'info', array( 'count' => count( $links ) ) );
	}

	return $links;
}

/**
 * Generate a QR code and save it to a file.
 *
 * Generates QR codes locally using the Endroid QR Code library (no third parties).
 * Images are written to uploads/qr-codes and URLs are returned. Results are cached.
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
	// Log QR code generation attempt.
	qr_trackr_log_element_creation(
		'qr_code',
		array(
			'data_length' => strlen( $data ),
			'args'        => $args,
		),
		'qr_generation'
	);

	// Validate input data.
	if ( empty( $data ) ) {
		qr_trackr_log( 'QR code generation failed - empty data', 'error', array( 'data' => $data ) );
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
	$args     = wp_parse_args( $args, $defaults );

	// Validate size parameters.
	$args['size'] = absint( $args['size'] );
	if ( $args['size'] < 100 || $args['size'] > 1000 ) {
		qr_trackr_log( 'QR code generation failed - invalid size', 'error', array( 'size' => $args['size'] ) );
		return new WP_Error(
			'qrc_invalid_size',
			esc_html__( 'QR code size must be between 100 and 1000 pixels.', 'wp-qr-trackr' )
		);
	}

	$args['margin'] = absint( $args['margin'] );
	if ( $args['margin'] > 50 ) {
		qr_trackr_log( 'QR code generation failed - invalid margin', 'error', array( 'margin' => $args['margin'] ) );
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
			qr_trackr_log(
				'QR code generation failed - invalid color format',
				'error',
				array(
					'color_key'   => $color_key,
					'color_value' => $args[ $color_key ],
				)
			);
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
	$qr_url    = get_transient( $cache_key );

	if ( false !== $qr_url ) {
		qr_trackr_log( 'QR code retrieved from cache', 'info', array( 'qr_url' => $qr_url ) );
		return $qr_url;
	}

	// Pure local generation through Endroid.
	$upload_dir = wp_upload_dir();
	$qr_dir     = trailingslashit( $upload_dir['basedir'] ) . 'qr-codes';
	if ( ! file_exists( $qr_dir ) ) {
		wp_mkdir_p( $qr_dir );
	}

	$filename  = sanitize_file_name( 'qr-' . md5( $data . wp_json_encode( $args ) ) . '.png' );
	$file_path = trailingslashit( $qr_dir ) . $filename;
	$qr_url    = trailingslashit( $upload_dir['baseurl'] ) . 'qr-codes/' . $filename;

	$to_rgb = static function ( $hex ) {
		$hex = ltrim( $hex, '#' );
		if ( 6 !== strlen( $hex ) ) {
			$hex = '000000';
		}
		return array(
			hexdec( substr( $hex, 0, 2 ) ),
			hexdec( substr( $hex, 2, 2 ) ),
			hexdec( substr( $hex, 4, 2 ) ),
		);
	};

	$ec_map   = array(
		'L' => '\\Endroid\\QrCode\\ErrorCorrectionLevel\\ErrorCorrectionLevelLow',
		'M' => '\\Endroid\\QrCode\\ErrorCorrectionLevel\\ErrorCorrectionLevelMedium',
		'Q' => '\\Endroid\\QrCode\\ErrorCorrectionLevel\\ErrorCorrectionLevelQuartile',
		'H' => '\\Endroid\\QrCode\\ErrorCorrectionLevel\\ErrorCorrectionLevelHigh',
	);
	$ec_class = isset( $ec_map[ $args['error_correction'] ] ) ? $ec_map[ $args['error_correction'] ] : $ec_map['M'];

	try {
		$qr_code = \Endroid\QrCode\QrCode::create( $data )
			->setEncoding( new \Endroid\QrCode\Encoding\Encoding( 'UTF-8' ) )
			->setErrorCorrectionLevel( new $ec_class() )
			->setSize( (int) $args['size'] )
			->setMargin( (int) $args['margin'] );

		list( $fr, $fg, $fb ) = $to_rgb( $args['foreground_color'] );
		list( $br, $bg, $bb ) = $to_rgb( $args['background_color'] );

		$qr_code = $qr_code
			->setForegroundColor( new \Endroid\QrCode\Color\Color( $fr, $fg, $fb ) )
			->setBackgroundColor( new \Endroid\QrCode\Color\Color( $br, $bg, $bb ) );

		$writer = new \Endroid\QrCode\Writer\PngWriter();
		$result = $writer->write( $qr_code );
		$result->saveToFile( $file_path );

		set_transient( $cache_key, $qr_url, DAY_IN_SECONDS );

		qr_trackr_log(
			'QR code generated and saved successfully (local Endroid)',
			'info',
			array(
				'file_path' => $file_path,
				'qr_url'    => $qr_url,
			)
		);

		return $qr_url;
	} catch ( \Throwable $e ) {
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only.
		error_log( 'QR local generation failed: ' . $e->getMessage() );
		return new WP_Error( 'qrc_generation_error', esc_html__( 'Failed to generate QR code.', 'wp-qr-trackr' ) );
	}
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
	// Build uploads target paths.
	$upload_dir = wp_upload_dir();
	$qr_dir     = $upload_dir['basedir'] . '/qr-codes';
	if ( ! file_exists( $qr_dir ) ) {
		wp_mkdir_p( $qr_dir );
	}

	$filename  = sanitize_file_name( 'qr-' . md5( $data . wp_json_encode( $args ) ) . '.png' );
	$file_path = $qr_dir . '/' . $filename;
	$qr_url    = $upload_dir['baseurl'] . '/qr-codes/' . $filename;

	// Convert hex color (e.g. #aabbcc) to RGB triplet using Endroid\QrCode\Color\Color.
	$to_rgb = static function ( $hex ) {
		$hex = ltrim( $hex, '#' );
		if ( 6 !== strlen( $hex ) ) {
			$hex = '000000';
		}
		return array(
			hexdec( substr( $hex, 0, 2 ) ),
			hexdec( substr( $hex, 2, 2 ) ),
			hexdec( substr( $hex, 4, 2 ) ),
		);
	};

	// Map error correction.
	$ec_map   = array(
		'L' => '\\Endroid\\QrCode\\ErrorCorrectionLevel\\ErrorCorrectionLevelLow',
		'M' => '\\Endroid\\QrCode\\ErrorCorrectionLevel\\ErrorCorrectionLevelMedium',
		'Q' => '\\Endroid\\QrCode\\ErrorCorrectionLevel\\ErrorCorrectionLevelQuartile',
		'H' => '\\Endroid\\QrCode\\ErrorCorrectionLevel\\ErrorCorrectionLevelHigh',
	);
	$ec_class = isset( $ec_map[ $args['error_correction'] ] ) ? $ec_map[ $args['error_correction'] ] : $ec_map['M'];

	try {
		$qr_code = \Endroid\QrCode\QrCode::create( $data )
			->setEncoding( new \Endroid\QrCode\Encoding\Encoding( 'UTF-8' ) )
			->setErrorCorrectionLevel( new $ec_class() )
			->setSize( (int) $args['size'] )
			->setMargin( (int) $args['margin'] );

		list( $fr, $fg, $fb ) = $to_rgb( $args['foreground_color'] );
		list( $br, $bg, $bb ) = $to_rgb( $args['background_color'] );

		$qr_code = $qr_code
			->setForegroundColor( new \Endroid\QrCode\Color\Color( $fr, $fg, $fb ) )
			->setBackgroundColor( new \Endroid\QrCode\Color\Color( $br, $bg, $bb ) );

		$writer = new \Endroid\QrCode\Writer\PngWriter();
		$result = $writer->write( $qr_code );
		$result->saveToFile( $file_path );

		return $qr_url;
	} catch ( \Throwable $e ) {
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only.
		error_log( 'QR fallback generation failed: ' . $e->getMessage() );
		return new WP_Error( 'qrc_fallback_error', esc_html__( 'Failed to generate QR code (local).', 'wp-qr-trackr' ) );
	}
}
