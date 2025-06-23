<?php
/**
 * QR code generation and management.
 *
 * @package WP_QR_TRACKR
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Returns an array of available QR code error correction levels.
 *
 * @return array
 */
function qrc_get_error_correction_levels() {
	return array(
		'L' => 'Low (7%)',
		'M' => 'Medium (15%)',
		'Q' => 'Quartile (25%)',
		'H' => 'High (30%)',
	);
}

/**
 * Returns an array of available QR code dot styles.
 *
 * @return array
 */
function qrc_get_dot_styles() {
	return array(
		'square' => 'Square',
		'circle' => 'Circle',
	);
}

/**
 * Get a specific QR code link from the database.
 *
 * @param int $id The ID of the link to retrieve.
 * @return object|null The link object, or null if not found.
 */
function qrc_get_link( $id ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_code_links';
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
	return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id ) );
}

/**
 * Get all QR code links from the database.
 *
 * @return array An array of link objects.
 */
function qrc_get_all_links() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_code_links';
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
	return $wpdb->get_results( "SELECT * FROM $table_name ORDER BY created_at DESC" );
}

/**
 * Generate a QR code and save it to a file.
 *
 * @param string $data The data to encode in the QR code.
 * @param array  $args Optional. Arguments for QR code generation.
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
	$args     = wp_parse_args( $args, $defaults );

	// Build the query parameters for the QR code API.
	$api_params = array(
		'cht'  => 'qr',
		'chs'  => $args['size'] . 'x' . $args['size'],
		'chl'  => rawurlencode( $data ),
		'choe' => 'UTF-8',
		'chld' => $args['error_correction'] . '|' . $args['margin'],
	);

	$api_url = 'https://chart.googleapis.com/chart?' . http_build_query( $api_params );

	// For simplicity in this example, we are just returning the URL.
	// In a real-world scenario, you would use wp_remote_get() to fetch the image
	// and save it to the media library.
	return $api_url;
}
