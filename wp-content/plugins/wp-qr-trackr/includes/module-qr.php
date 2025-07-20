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
 * @since 1.0.0
 * @param int $id The QR code ID.
 * @return object|null The QR code object or null if not found.
 */
function qrc_get_link( $id ) {
	global $wpdb;

	$cache_key = 'qrc_link_' . absint( $id );
	$link      = wp_cache_get( $cache_key, 'qrc_links' );

	if ( false === $link ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Caching implemented above, single-row lookup needed for performance.
		$link = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}qr_trackr_links WHERE id = %d",
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
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Caching implemented above, bulk data retrieval.
		$links = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}qr_trackr_links ORDER BY created_at DESC"
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
 * Clean up cached QR code URLs that point to non-existent files.
 *
 * @since 1.0.0
 * @return int Number of cleaned up cache entries.
 */
function qrc_cleanup_invalid_cache() {
	global $wpdb;

	$cleaned_count = 0;
	$transients    = $wpdb->get_results( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE '_transient_qrc_%'" );

	foreach ( $transients as $transient ) {
		$url = $transient->option_value;
		if ( ! empty( $url ) && strpos( $url, '/wp-content/uploads/qr-codes/' ) !== false ) {
			$file_path = str_replace( home_url( '/wp-content/uploads/' ), wp_upload_dir()['basedir'] . '/', $url );
			if ( ! file_exists( $file_path ) ) {
				$cache_key = str_replace( '_transient_', '', $transient->option_name );
				delete_transient( $cache_key );
				++$cleaned_count;
				error_log( 'QR Trackr: Cleaned up invalid cache entry: ' . $cache_key . ' -> ' . $url );
			}
		}
	}

	return $cleaned_count;
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

	// Check if Endroid QR Code library is available.
	$endroid_class_exists = false;

	// Suppress deprecation warnings during class check
	$old_error_reporting = error_reporting();
	error_reporting( $old_error_reporting & ~E_DEPRECATED );

	$endroid_class_exists = class_exists( 'Endroid\QrCode\QrCode' );

	// Restore error reporting
	error_reporting( $old_error_reporting );

	if ( ! $endroid_class_exists ) {
		// Try to load the autoloader manually if it's not already loaded.
		$autoload_path = QR_TRACKR_PLUGIN_DIR . 'vendor/autoload.php';

		// Fallback to root vendor directory if plugin vendor doesn't exist
		if ( ! file_exists( $autoload_path ) ) {
				$autoload_path = dirname( dirname( dirname( QR_TRACKR_PLUGIN_DIR ) ) ) . '/vendor/autoload.php';
		}

		if ( file_exists( $autoload_path ) ) {
			require_once $autoload_path;
		}

				// Check again after attempting to load.
				$endroid_class_exists = class_exists( 'Endroid\QrCode\QrCode' );

		if ( ! $endroid_class_exists ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'QR Trackr: ERROR - Endroid\QrCode\QrCode class not found. Autoloader path: ' . $autoload_path . ', File exists: ' . ( file_exists( $autoload_path ) ? 'YES' : 'NO' ) );
				error_log(
					'QR Trackr: ERROR - Available classes containing "Endroid": ' . implode(
						', ',
						array_filter(
							get_declared_classes(),
							function ( $class ) {
								return strpos( $class, 'Endroid' ) !== false;
							}
						)
					)
				);
			}
			return new WP_Error(
				'qrc_library_missing',
				esc_html__( 'QR code generation library not available. Please run composer install.', 'wp-qr-trackr' )
			);
		}
	}

	$defaults = array(
		'size'             => 200,
		'margin'           => 10,
		'error_correction' => 'M',
		'foreground_color' => '#000000',
		'background_color' => '#ffffff',
		'output_format'    => 'png',
	);
	$args     = wp_parse_args( $args, $defaults );

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

	// Validate color formats.
	$color_regex = '/^#[a-f0-9]{6}$/i';
	foreach ( array( 'foreground_color', 'background_color' ) as $color_key ) {
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
	$qr_url    = get_transient( $cache_key );

	if ( false === $qr_url ) {
		try {
			error_log( 'QR Trackr: Starting QR code generation for data: ' . $data );

			// Create QR code using Endroid library.
			$qr_code = new \Endroid\QrCode\QrCode( $data );

			// Set QR code options.
			$qr_code->setSize( $args['size'] );
			$qr_code->setMargin( $args['margin'] );

			// Set error correction level.
			$error_correction_level = new \Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelMedium();
			switch ( $args['error_correction'] ) {
				case 'L':
					$error_correction_level = new \Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow();
					break;
				case 'Q':
					$error_correction_level = new \Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelQuartile();
					break;
				case 'H':
					$error_correction_level = new \Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh();
					break;
			}
			$qr_code->setErrorCorrectionLevel( $error_correction_level );

			// Set colors.
			$foreground_rgb = sscanf( $args['foreground_color'], '#%02x%02x%02x' );
			$background_rgb = sscanf( $args['background_color'], '#%02x%02x%02x' );
			$qr_code->setForegroundColor( new \Endroid\QrCode\Color\Color( $foreground_rgb[0], $foreground_rgb[1], $foreground_rgb[2] ) );
			$qr_code->setBackgroundColor( new \Endroid\QrCode\Color\Color( $background_rgb[0], $background_rgb[1], $background_rgb[2] ) );

			// Create writer for PNG format.
			$writer = new \Endroid\QrCode\Writer\PngWriter();
			$result = $writer->write( $qr_code );

			// Get the QR code image data.
			$image_data = $result->getString();

			error_log( 'QR Trackr: QR code image data generated, size: ' . strlen( $image_data ) . ' bytes' );

			// Save the QR code image.
			$upload_dir = wp_upload_dir();
			$qr_dir     = $upload_dir['basedir'] . '/qr-codes';

			error_log( 'QR Trackr: Upload directory: ' . $upload_dir['basedir'] );
			error_log( 'QR Trackr: QR directory: ' . $qr_dir );
			error_log( 'QR Trackr: QR directory exists: ' . ( file_exists( $qr_dir ) ? 'YES' : 'NO' ) );

			if ( ! file_exists( $qr_dir ) ) {
				$mkdir_result = wp_mkdir_p( $qr_dir );
				error_log( 'QR Trackr: Created QR directory: ' . ( $mkdir_result ? 'SUCCESS' : 'FAILED' ) );
			}

			$filename  = sanitize_file_name( 'qr-' . md5( $data . wp_json_encode( $args ) ) . '.png' );
			$file_path = $qr_dir . '/' . $filename;
			$qr_url    = $upload_dir['baseurl'] . '/qr-codes/' . $filename;

			error_log( 'QR Trackr: File path: ' . $file_path );
			error_log( 'QR Trackr: QR URL: ' . $qr_url );

			// Save the image using WP_Filesystem.
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
			global $wp_filesystem;

			error_log( 'QR Trackr: About to save file using WP_Filesystem...' );
			if ( ! $wp_filesystem->put_contents( $file_path, $image_data, FS_CHMOD_FILE ) ) {
				error_log( 'QR Trackr: Failed to save QR code image to ' . $file_path );
				error_log( 'QR Trackr: WP_Filesystem error: ' . ( $wp_filesystem->errors ? wp_json_encode( $wp_filesystem->errors ) : 'No errors' ) );
				return new WP_Error(
					'qrc_save_error',
					esc_html__( 'Failed to save QR code image.', 'wp-qr-trackr' )
				);
			}

			error_log( 'QR Trackr: QR code image saved successfully to ' . $file_path );
			error_log( 'QR Trackr: File exists after save: ' . ( file_exists( $file_path ) ? 'YES' : 'NO' ) );

			// Only cache the URL if the file was actually created successfully.
			if ( file_exists( $file_path ) ) {
				set_transient( $cache_key, $qr_url, DAY_IN_SECONDS );
				error_log( 'QR Trackr: QR code generation completed successfully and cached: ' . $qr_url );
			} else {
				error_log( 'QR Trackr: QR code generation failed - file not created, not caching URL' );
				return new WP_Error(
					'qrc_file_missing',
					esc_html__( 'QR code file was not created successfully.', 'wp-qr-trackr' )
				);
			}
		} catch ( Exception $e ) {
			error_log( 'QR Trackr: QR code generation failed with exception: ' . $e->getMessage() );
			return new WP_Error(
				'qrc_generation_error',
				sprintf(
					/* translators: %s: error message */
					esc_html__( 'Failed to generate QR code: %s', 'wp-qr-trackr' ),
					$e->getMessage()
				)
			);
		}
	} else {
		error_log( 'QR Trackr: Using cached QR code URL: ' . $qr_url );
	}

	return $qr_url;
}
