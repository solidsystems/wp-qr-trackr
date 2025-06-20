<?php
/**
 * QR Code generation module for QR Trackr.
 *
 * Handles all QR code image generation and shape logic.
 * All DB helpers are now in module-utility.php.
 *
 * @package QR_Trackr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( getenv( 'CI_DEBUG' ) === 'true' ) {
	echo "[CI_DEBUG] includes/module-qr.php loaded\n";
}

// Use Endroid QR Code library.
// For now, use a simple implementation or placeholder function.
// Pro plugin can add more via filter.
// Use utility to get or create tracking link.
// Allow custom shape generation via filter.

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;

/**
 * Returns available QR shapes (filterable).
 *
 * @return array Filtered array of available QR shapes.
 */
function qr_trackr_get_available_shapes() {
	$shapes = array(
		'square' => __( 'Square', 'wp-qr-trackr' ),
		'circle' => __( 'Circle', 'wp-qr-trackr' ),
		'rounded' => __( 'Rounded', 'wp-qr-trackr' ),
	);
	return apply_filters( 'qr_trackr_available_shapes', $shapes );
}

/**
 * Generate QR code image for a tracking link
 *
 * @param int $link_id The ID of the tracking link
 * @return array|false Array with 'png' and 'svg' URLs, or false on failure
 */
function qr_trackr_generate_qr_image_for_link($link_id) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';
	
	// Get link data
	$link = $wpdb->get_row($wpdb->prepare(
		"SELECT * FROM $table_name WHERE id = %d",
		$link_id
	));
	
	if (!$link) {
		qr_trackr_debug_log('Link not found for QR generation: ' . $link_id);
		return false;
	}
	
	// Get the tracking URL
	$tracking_url = qr_trackr_get_tracking_url($link_id);
	if (!$tracking_url) {
		qr_trackr_debug_log('Failed to generate tracking URL for link: ' . $link_id);
		return false;
	}
	
	// Check if QR code library is available and GD extension is enabled
	if (!qr_trackr_check_library() || !extension_loaded('gd')) {
		qr_trackr_debug_log('QR code library not available or GD extension missing, using fallback service');
		return qr_trackr_generate_qr_fallback($tracking_url);
	}
	
	// Generate unique hash for caching
	$hash = md5($tracking_url);
	$upload_dir = wp_upload_dir();
	$qr_dir = $upload_dir['basedir'] . '/qr-trackr';
	if (!file_exists($qr_dir)) {
		if (!wp_mkdir_p($qr_dir)) {
			qr_trackr_debug_log('Failed to create QR code directory: ' . $qr_dir);
			return qr_trackr_generate_qr_fallback($tracking_url);
		}
	}
	$png_filename = 'qr-' . $link_id . '-' . $hash . '.png';
	$svg_filename = 'qr-' . $link_id . '-' . $hash . '.svg';
	$png_filepath = $qr_dir . '/' . $png_filename;
	$svg_filepath = $qr_dir . '/' . $svg_filename;
	$png_url = $upload_dir['baseurl'] . '/qr-trackr/' . $png_filename;
	$svg_url = $upload_dir['baseurl'] . '/qr-trackr/' . $svg_filename;

	// Only generate if files do not exist (cache)
	if (!file_exists($png_filepath) || !file_exists($svg_filepath)) {
		try {
			$qr_code = \Endroid\QrCode\QrCode::create($tracking_url)
				->setSize(300)
				->setMargin(10)
				->setErrorCorrectionLevel(ErrorCorrectionLevel::High);
			$png_writer = new \Endroid\QrCode\Writer\PngWriter();
			$svg_writer = new SvgWriter();
			$png_result = $png_writer->write($qr_code);
			$svg_result = $svg_writer->write($qr_code);
			file_put_contents($png_filepath, $png_result->getString());
			file_put_contents($svg_filepath, $svg_result->getString());
		} catch (\Exception $e) {
			qr_trackr_debug_log('Failed to generate QR code: ' . $e->getMessage());
			return qr_trackr_generate_qr_fallback($tracking_url);
		}
	}
	// Return both URLs as an array for future extensibility
	return array('png' => $png_url, 'svg' => $svg_url);
}

/**
 * Generate QR code using a fallback service
 *
 * @param string $url The URL to encode in the QR code
 * @return string|false The URL of the generated QR code image, or false on failure
 */
function qr_trackr_generate_qr_fallback($url) {
	// Use Google Charts API as fallback
	$api_url = 'https://chart.googleapis.com/chart?';
	$params = array(
		'cht' => 'qr',
		'chs' => '300x300',
		'chl' => urlencode($url),
		'choe' => 'UTF-8',
		'chld' => 'H|0' // High error correction, no margin
	);
	
	return $api_url . http_build_query($params);
}

/**
 * Get the tracking URL for a link
 *
 * @param int $link_id The ID of the tracking link
 * @return string|false The tracking URL, or false on failure
 */
function qr_trackr_get_tracking_url($link_id) {
	$tracking_url = home_url('/qr-trackr/' . $link_id);
	
	// Check if pretty permalinks are enabled
	if (!qr_trackr_check_permalinks()) {
		$tracking_url = home_url('/?qr-trackr=' . $link_id);
	}
	
	return $tracking_url;
}

/**
 * Generates a QR code image for a tracking link (legacy function).
 *
 * @param int $link_id The ID of the tracking link.
 * @return string The URL of the generated QR code image.
 */
function qr_trackr_generate_qr_image( $link_id ) {
	return qr_trackr_generate_qr_image_for_link( $link_id );
}

/**
 * Generates a QR code image for a tracking link (legacy function).
 *
 * @param int $link_id The ID of the tracking link.
 * @return string The URL of the generated QR code image.
 */
function qr_trackr_generate_qr_code( $link_id ) {
	return qr_trackr_generate_qr_image_for_link( $link_id );
}

/**
 * Get or create a tracking link for a given post ID.
 *
 * @param int $post_id The post ID.
 * @return object|false Tracking link object or false on failure.
 */
function qr_trackr_get_or_create_tracking_link( $post_id ) {
	global $wpdb;
	$table = $wpdb->prefix . 'qr_trackr_links';

	// Get link with caching.
	$cache_key = 'qr_trackr_link_' . $post_id;
	$link      = wp_cache_get( $cache_key );
	if ( false === $link ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query is required for plugin logic and is cached above.
		$link = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $table . ' WHERE post_id = %d', $post_id ) );
		if ( $link ) {
			wp_cache_set( $cache_key, $link, '', 300 ); // Cache for 5 minutes.
		}
	}

	if ( $link ) {
		qr_trackr_debug_log(
			'Found existing tracking link',
			array(
				'post_id' => $post_id,
				'link_id' => $link->id,
			)
		);
		return $link;
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Insert is required for plugin logic.
	$wpdb->insert(
		$table,
		array( 'post_id' => $post_id ),
		array( '%d' )
	);
	$link_id = $wpdb->insert_id;
	qr_trackr_debug_log(
		'Created new tracking link',
		array(
			'post_id' => $post_id,
			'link_id' => $link_id,
		)
	);

	// Get and cache the new link.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query is required for plugin logic and is cached above.
	$new_link = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $table . ' WHERE id = %d', $link_id ) );
	if ( $new_link ) {
		wp_cache_set( $cache_key, $new_link, '', 300 ); // Cache for 5 minutes.
	}
	return $new_link;
}

/**
 * Get a tracking link by its ID.
 *
 * @param int $link_id The tracking link ID.
 * @return object|false Tracking link object or false on failure.
 */
function qr_trackr_get_tracking_link_by_id( $link_id ) {
	global $wpdb;
	$table = $wpdb->prefix . 'qr_trackr_links';

	// Get link with caching.
	$cache_key = 'qr_trackr_link_by_id_' . $link_id;
	$link      = wp_cache_get( $cache_key );
	if ( false === $link ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query is required for plugin logic and is cached above.
		$link = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $table . ' WHERE id = %d', $link_id ) );
		if ( $link ) {
			wp_cache_set( $cache_key, $link, '', 300 ); // Cache for 5 minutes.
		}
	}
	return $link;
}
