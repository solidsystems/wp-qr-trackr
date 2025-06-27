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
	$link = wp_cache_get( $cache_key, 'qrc_links' );

	if ( false === $link ) {
		$table_name = $wpdb->prefix . 'qr_code_links';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching -- Caching is implemented via wp_cache_get/set
		$link = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM %i WHERE id = %d", $table_name, absint( $id ) ) );

		if ( $link ) {
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
	$links = get_transient( $cache_key );

	if ( false === $links ) {
		$table_name = $wpdb->prefix . 'qr_code_links';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching -- Caching is implemented via transients
		$links = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM %i ORDER BY created_at DESC", $table_name ) );

		if ( $links ) {
			set_transient( $cache_key, $links, HOUR_IN_SECONDS );
		}
	}

	return $links;
}

/**
 * Generate a QR code and save it to a file.
 *
 * Uses Google Charts API to generate QR codes with customizable parameters.
 * Implements caching to prevent unnecessary API calls.
 *
 * @since 1.0.0
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
	$defaults = array(
		'size'                => 200,
		'margin'              => 10,
		'error_correction'    => 'M',
		'dot_style'           => 'square',
		'foreground_color'    => '#000000',
		'background_color'    => '#ffffff',
		'add_logo'            => false,
		'logo_path'           => '',
		'logo_size_percent'   => 0.2,
		'eye_shape'           => 'square',
		'eye_inner_color'     => '#000000',
		'eye_outer_color'     => '#000000',
		'output_format'       => 'png',
		'custom_gradient'     => false,
		'gradient_start'      => '#000000',
		'gradient_end'        => '#000000',
		'gradient_type'       => 'vertical',
	);
	$args = wp_parse_args( $args, $defaults );

	// Sanitize and validate input parameters.
	$args['size'] = absint( $args['size'] );
	$args['margin'] = absint( $args['margin'] );
	$args['error_correction'] = in_array( $args['error_correction'], array( 'L', 'M', 'Q', 'H' ), true ) ? $args['error_correction'] : 'M';
	$args['dot_style'] = in_array( $args['dot_style'], array( 'square', 'circle' ), true ) ? $args['dot_style'] : 'square';

	// Generate cache key based on parameters.
	$cache_key = 'qrc_' . md5( $data . serialize( $args ) );
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

		// Cache the generated URL.
		set_transient( $cache_key, $api_url, DAY_IN_SECONDS );
		$qr_url = $api_url;
	}

	return $qr_url;
}
