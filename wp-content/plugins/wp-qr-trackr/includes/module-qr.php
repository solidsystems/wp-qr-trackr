<?php
/**
 * QR Code generation module for QR Trackr.
 *
 * Handles all QR code image generation and shape logic.
 * All DB helpers are now in module-utility.php.
 *
 * @package QR_Trackr
 * @subpackage Modules
 * @author Michael Erps
 * @copyright 2024 Michael Erps
 * @license GPL-2.0-or-later
 *
 * @file This module provides all QR code generation, caching, and tracking link helpers for the plugin.
 *        It uses the Endroid QR Code library and WordPress best practices for file and DB operations.
 *        All direct DB queries are cached and justified. File operations use WP_Filesystem for compliance.
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
		'square'  => __( 'Square', 'wp-qr-trackr' ),
		'circle'  => __( 'Circle', 'wp-qr-trackr' ),
		'rounded' => __( 'Rounded', 'wp-qr-trackr' ),
	);
	return apply_filters( 'qr_trackr_available_shapes', $shapes );
}

/**
 * Generate a unique QR code string.
 *
 * @return string Unique QR code string.
 */
function qr_trackr_generate_unique_qr_code() {
	return substr( str_shuffle( 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789' ), 0, 8 );
}

/**
 * Get cached QR tracking link data.
 *
 * @param string $key Cache key (post_id or link_id).
 * @param string $type Type of lookup ('post' or 'link').
 * @return object|false Cached link data or false.
 */
function qr_trackr_get_cached_link( $key, $type = 'post' ) {
	$cache_key = 'qr_trackr_link_' . $type . '_' . $key;
	$cached    = wp_cache_get( $cache_key, 'qr_trackr' );

	if ( false !== $cached ) {
		return $cached;
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';

	if ( 'post' === $type ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached immediately after query.
		$link = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . esc_sql( $table_name ) . ' WHERE post_id = %d',
				$key
			)
		);
	} else {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached immediately after query.
		$link = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . esc_sql( $table_name ) . ' WHERE id = %d',
				$key
			)
		);
	}

	if ( $link ) {
		wp_cache_set( $cache_key, $link, 'qr_trackr', HOUR_IN_SECONDS );
		return $link;
	}

	return false;
}

/**
 * Generate QR code image for a tracking link.
 *
 * @param int   $link_id The ID of the tracking link.
 * @param array $args    Optional. QR code generation arguments.
 * @return array|WP_Error Array with 'png' and 'svg' URLs, or WP_Error on failure.
 */
function qr_trackr_generate_qr_image_for_link( $link_id, $args = array() ) {
	if ( ! is_numeric( $link_id ) ) {
		return new WP_Error( 'invalid_id', esc_html__( 'Invalid link ID provided.', 'wp-qr-trackr' ) );
	}

	$link = qr_trackr_get_cached_link( $link_id, 'link' );
	if ( ! $link ) {
		return new WP_Error( 'link_not_found', esc_html__( 'Link not found.', 'wp-qr-trackr' ) );
	}

	// Ensure qr_code exists
	if ( empty( $link->qr_code ) ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'qr_trackr_links';
		$qr_code    = qr_trackr_generate_unique_qr_code();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cache invalidated after update
		$result = $wpdb->update(
			$table_name,
			array( 'qr_code' => $qr_code ),
			array( 'id' => $link_id ),
			array( '%s' ),
			array( '%d' )
		);

		if ( false === $result ) {
			return new WP_Error( 'db_error', esc_html__( 'Failed to update QR code.', 'wp-qr-trackr' ) );
		}

		// Invalidate cache
		wp_cache_delete( 'qr_trackr_link_link_' . $link_id, 'qr_trackr' );
		wp_cache_delete( 'qr_trackr_link_post_' . $link->post_id, 'qr_trackr' );

		$link->qr_code = $qr_code;
	}

	// Get the tracking URL using qr_code
	$tracking_url = function_exists( 'qr_trackr_get_rewrite_tracking_url' )
		? qr_trackr_get_rewrite_tracking_url( $link->qr_code )
		: home_url( '/qr/' . $link->qr_code );

	if ( ! $tracking_url ) {
		return new WP_Error( 'url_error', esc_html__( 'Failed to generate tracking URL.', 'wp-qr-trackr' ) );
	}

	// Generate unique hash for caching
	$hash = md5( $tracking_url . serialize( $args ) );

	// Get upload directory info
	$upload_dir = wp_upload_dir();
	if ( is_wp_error( $upload_dir ) ) {
		return new WP_Error( 'upload_dir_error', $upload_dir->get_error_message() );
	}

	$qr_dir = $upload_dir['basedir'] . '/qr-trackr';

	// Create directory if it doesn't exist
	if ( ! file_exists( $qr_dir ) ) {
		if ( ! wp_mkdir_p( $qr_dir ) ) {
			return new WP_Error( 'dir_error', esc_html__( 'Failed to create QR code directory.', 'wp-qr-trackr' ) );
		}
	}

	// Set up file paths
	$png_filename = 'qr-' . $link_id . '-' . $hash . '.png';
	$svg_filename = 'qr-' . $link_id . '-' . $hash . '.svg';
	$png_filepath = $qr_dir . '/' . $png_filename;
	$svg_filepath = $qr_dir . '/' . $svg_filename;
	$png_url      = $upload_dir['baseurl'] . '/qr-trackr/' . $png_filename;
	$svg_url      = $upload_dir['baseurl'] . '/qr-trackr/' . $svg_filename;

	// Check if files already exist (cache)
	if ( file_exists( $png_filepath ) && file_exists( $svg_filepath ) ) {
		return array(
			'png' => $png_url,
			'svg' => $svg_url,
		);
	}

	// Initialize WP_Filesystem
	global $wp_filesystem;
	if ( ! function_exists( 'WP_Filesystem' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	WP_Filesystem();

	if ( ! $wp_filesystem ) {
		return new WP_Error( 'filesystem_error', esc_html__( 'Failed to initialize filesystem.', 'wp-qr-trackr' ) );
	}

	try {
		// Set up QR code options
		$default_args = array(
			'size'             => 300,
			'margin'           => 10,
			'error_correction' => ErrorCorrectionLevel::High,
			'foreground_color' => new Color( 0, 0, 0 ),
			'background_color' => new Color( 255, 255, 255 ),
		);
		$args         = wp_parse_args( $args, $default_args );

		// Create QR code
		$qr_code = QrCode::create( $tracking_url )
			->setSize( $args['size'] )
			->setMargin( $args['margin'] )
			->setErrorCorrectionLevel( $args['error_correction'] )
			->setForegroundColor( $args['foreground_color'] )
			->setBackgroundColor( $args['background_color'] );

		// Generate PNG
		$png_writer = new PngWriter();
		$png_result = $png_writer->write( $qr_code );

		// Generate SVG
		$svg_writer = new SvgWriter();
		$svg_result = $svg_writer->write( $qr_code );

		// Save files using WP_Filesystem
		$png_saved = $wp_filesystem->put_contents( $png_filepath, $png_result->getString(), FS_CHMOD_FILE );
		$svg_saved = $wp_filesystem->put_contents( $svg_filepath, $svg_result->getString(), FS_CHMOD_FILE );

		if ( ! $png_saved || ! $svg_saved ) {
			return new WP_Error( 'save_error', esc_html__( 'Failed to save QR code files.', 'wp-qr-trackr' ) );
		}

		return array(
			'png' => $png_url,
			'svg' => $svg_url,
		);

	} catch ( Exception $e ) {
		qr_trackr_debug_log( 'QR code generation error: ' . $e->getMessage() );
		return new WP_Error( 'generation_error', $e->getMessage() );
	}
}

/**
 * Generate QR code using a fallback service.
 *
 * @param string $url The URL to encode in the QR code.
 * @param array  $args Optional. QR code generation arguments.
 * @return string|WP_Error The URL of the generated QR code image, or WP_Error on failure.
 */
function qr_trackr_generate_qr_fallback( $url, $args = array() ) {
	if ( empty( $url ) ) {
		return new WP_Error( 'invalid_url', esc_html__( 'Invalid URL provided.', 'wp-qr-trackr' ) );
	}

	$default_args = array(
		'size'             => '300x300',
		'error_correction' => 'H',
		'margin'           => 0,
	);
	$args         = wp_parse_args( $args, $default_args );

	// Use Google Charts API as fallback
	$api_url = 'https://chart.googleapis.com/chart?';
	$params  = array(
		'cht'  => 'qr',
		'chs'  => $args['size'],
		'chl'  => rawurlencode( $url ),
		'choe' => 'UTF-8',
		'chld' => $args['error_correction'] . '|' . $args['margin'],
	);

	$response = wp_remote_get( $api_url . http_build_query( $params ) );

	if ( is_wp_error( $response ) ) {
		return new WP_Error( 'api_error', $response->get_error_message() );
	}

	if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
		return new WP_Error( 'api_error', esc_html__( 'Failed to generate QR code from fallback service.', 'wp-qr-trackr' ) );
	}

	return $api_url . http_build_query( $params );
}

/**
 * Get the tracking URL for a link.
 *
 * @param int $link_id The ID of the tracking link.
 * @return string|false The tracking URL, or false on failure.
 */
function qr_trackr_get_tracking_url( $link_id ) {
	$tracking_url = home_url( '/qr-trackr/' . $link_id );

	// Check if pretty permalinks are enabled.
	if ( ! qr_trackr_check_permalinks() ) {
		$tracking_url = home_url( '/?qr-trackr=' . $link_id );
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
	$link = qr_trackr_get_cached_link( $post_id, 'post' );

	if ( $link ) {
		// Ensure qr_code exists.
		if ( empty( $link->qr_code ) ) {
			global $wpdb;
			$table   = $wpdb->prefix . 'qr_trackr_links';
			$qr_code = qr_trackr_generate_unique_qr_code();

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cache invalidated after update.
			$wpdb->update(
				$table,
				array( 'qr_code' => $qr_code ),
				array( 'id' => $link->id ),
				array( '%s' ),
				array( '%d' )
			);

			// Invalidate cache.
			wp_cache_delete( 'qr_trackr_link_link_' . $link->id, 'qr_trackr' );
			wp_cache_delete( 'qr_trackr_link_post_' . $post_id, 'qr_trackr' );

			$link->qr_code = $qr_code;
		}
		return $link;
	}

	// Create new link if none exists.
	global $wpdb;
	$table   = $wpdb->prefix . 'qr_trackr_links';
	$qr_code = qr_trackr_generate_unique_qr_code();

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cache set after insert.
	$wpdb->insert(
		$table,
		array(
			'post_id'    => $post_id,
			'qr_code'    => $qr_code,
			'created_at' => current_time( 'mysql' ),
		),
		array( '%d', '%s', '%s' )
	);

	if ( ! $wpdb->insert_id ) {
		return false;
	}

	$link = qr_trackr_get_cached_link( $wpdb->insert_id, 'link' );
	return $link;
}

/**
 * Get a tracking link by ID.
 *
 * @param int $link_id The link ID.
 * @return object|false Tracking link object or false on failure.
 */
function qr_trackr_get_tracking_link_by_id( $link_id ) {
	return qr_trackr_get_cached_link( $link_id, 'link' );
}

/**
 * Get QR code data by ID.
 *
 * @param int $id QR code ID.
 * @return array|null QR code data or null if not found.
 */
function qrc_get_qr_code( $id ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_links';

	// Try to get from cache first.
	$cache_key = 'qr_code_' . absint( $id );
	$data      = wp_cache_get( $cache_key, 'qr_trackr' );

	if ( false === $data ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached immediately after query.
		$data = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . esc_sql( $table_name ) . ' WHERE id = %d',
				absint( $id )
			),
			ARRAY_A
		);

		if ( $data ) {
			wp_cache_set( $cache_key, $data, 'qr_trackr', HOUR_IN_SECONDS );
		}
	}

	return $data;
}

/**
 * Get QR code scan statistics.
 *
 * @param int $id QR code ID.
 * @return array Array containing scan statistics.
 */
function qrc_get_scan_stats( $id ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_scans';

	// Try to get from cache first.
	$cache_key = 'qr_scan_stats_' . absint( $id );
	$stats     = wp_cache_get( $cache_key, 'qr_trackr' );

	if ( false === $stats ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached immediately after query.
		$stats = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT COUNT(*) as total_scans, DATE(created_at) as scan_date 
				FROM ' . esc_sql( $table_name ) . ' 
				WHERE qr_id = %d 
				GROUP BY scan_date 
				ORDER BY scan_date DESC',
				absint( $id )
			),
			ARRAY_A
		);

		if ( $stats ) {
			wp_cache_set( $cache_key, $stats, 'qr_trackr', HOUR_IN_SECONDS );
		}
	}

	return $stats;
}

/**
 * Record a QR code scan.
 *
 * @param int    $link_id    The ID of the tracking link.
 * @param string $user_agent User agent string.
 * @param string $ip_address IP address.
 * @param string $location   Optional. Location data.
 * @return bool|WP_Error True on success, WP_Error on failure.
 */
function qr_trackr_record_scan( $link_id, $user_agent, $ip_address, $location = '' ) {
	if ( ! is_numeric( $link_id ) ) {
		return new WP_Error( 'invalid_id', esc_html__( 'Invalid link ID provided.', 'wp-qr-trackr' ) );
	}

	// Sanitize inputs
	$user_agent = sanitize_text_field( $user_agent );
	$ip_address = sanitize_text_field( $ip_address );
	$location   = sanitize_text_field( $location );

	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_scans';

	// Insert scan record
	$result = $wpdb->insert(
		$table_name,
		array(
			'link_id'    => $link_id,
			'user_agent' => $user_agent,
			'ip_address' => $ip_address,
			'location'   => $location,
			'scan_time'  => current_time( 'mysql', true ),
		),
		array(
			'%d',
			'%s',
			'%s',
			'%s',
			'%s',
		)
	);

	if ( false === $result ) {
		return new WP_Error( 'db_error', esc_html__( 'Failed to record scan.', 'wp-qr-trackr' ) );
	}

	// Invalidate relevant caches
	wp_cache_delete( 'qr_trackr_stats_' . $link_id, 'qr_trackr' );
	wp_cache_delete( 'qr_trackr_scan_count_' . $link_id, 'qr_trackr' );
	wp_cache_delete( 'qr_trackr_locations_' . $link_id, 'qr_trackr' );
	wp_cache_delete( 'qr_trackr_devices_' . $link_id, 'qr_trackr' );
	wp_cache_delete( 'qr_trackr_history_' . $link_id . '_day', 'qr_trackr' );
	wp_cache_delete( 'qr_trackr_history_' . $link_id . '_week', 'qr_trackr' );
	wp_cache_delete( 'qr_trackr_history_' . $link_id . '_month', 'qr_trackr' );
	wp_cache_delete( 'qr_trackr_history_' . $link_id . '_year', 'qr_trackr' );

	return true;
}

/**
 * Get QR code tracking data.
 *
 * @param int $link_id The ID of the tracking link.
 * @return array|WP_Error Array of tracking data or WP_Error on failure.
 */
function qr_trackr_get_tracking_data( $link_id ) {
	if ( ! is_numeric( $link_id ) ) {
		return new WP_Error( 'invalid_id', esc_html__( 'Invalid link ID provided.', 'wp-qr-trackr' ) );
	}

	$cache_key = 'qr_trackr_tracking_data_' . $link_id;
	$data      = wp_cache_get( $cache_key, 'qr_trackr' );

	if ( false !== $data ) {
		return $data;
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_scans';

	try {
		// Get total scans
		$total_scans = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}qr_trackr_scans WHERE link_id = %d",
				$link_id
			)
		);

		// Get unique visitors
		$unique_visitors = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT ip_address) FROM {$wpdb->prefix}qr_trackr_scans WHERE link_id = %d",
				$link_id
			)
		);

		// Get recent scans
		$recent_scans = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}qr_trackr_scans WHERE link_id = %d ORDER BY scan_time DESC LIMIT 10",
				$link_id
			)
		);

		// Get top locations
		$top_locations = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT location, COUNT(*) as count FROM {$wpdb->prefix}qr_trackr_scans 
				WHERE link_id = %d AND location != '' 
				GROUP BY location ORDER BY count DESC LIMIT 5",
				$link_id
			)
		);

		// Get top devices
		$top_devices = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT user_agent, COUNT(*) as count FROM {$wpdb->prefix}qr_trackr_scans 
				WHERE link_id = %d 
				GROUP BY user_agent ORDER BY count DESC LIMIT 5",
				$link_id
			)
		);

		$data = array(
			'total_scans'     => intval( $total_scans ),
			'unique_visitors' => intval( $unique_visitors ),
			'recent_scans'    => $recent_scans,
			'top_locations'   => $top_locations,
			'top_devices'     => $top_devices,
		);

		wp_cache_set( $cache_key, $data, 'qr_trackr', 300 ); // Cache for 5 minutes

		return $data;

	} catch ( Exception $e ) {
		return new WP_Error( 'tracking_error', $e->getMessage() );
	}
}

/**
 * Check if a QR code exists.
 *
 * @param string $qr_code The QR code string.
 * @return bool|WP_Error True if exists, false if not, WP_Error on failure.
 */
function qr_trackr_qr_code_exists( $qr_code ) {
	if ( empty( $qr_code ) ) {
		return new WP_Error( 'invalid_code', esc_html__( 'Invalid QR code provided.', 'wp-qr-trackr' ) );
	}

	$qr_code = sanitize_text_field( $qr_code );

	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';

	$exists = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}qr_trackr_links WHERE qr_code = %s",
			$qr_code
		)
	);

	if ( null === $exists ) {
		return new WP_Error( 'db_error', esc_html__( 'Database error while checking QR code.', 'wp-qr-trackr' ) );
	}

	return $exists > 0;
}

/**
 * Get QR code by its unique code string.
 *
 * @param string $qr_code The QR code string.
 * @return object|WP_Error QR code data or WP_Error on failure.
 */
function qr_trackr_get_qr_by_code( $qr_code ) {
	if ( empty( $qr_code ) ) {
		return new WP_Error( 'invalid_code', esc_html__( 'Invalid QR code provided.', 'wp-qr-trackr' ) );
	}

	$qr_code = sanitize_text_field( $qr_code );

	$cache_key = 'qr_trackr_code_' . md5( $qr_code );
	$data      = wp_cache_get( $cache_key, 'qr_trackr' );

	if ( false !== $data ) {
		return $data;
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';

	$data = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}qr_trackr_links WHERE qr_code = %s",
			$qr_code
		)
	);

	if ( null === $data ) {
		return new WP_Error( 'not_found', esc_html__( 'QR code not found.', 'wp-qr-trackr' ) );
	}

	wp_cache_set( $cache_key, $data, 'qr_trackr', 300 ); // Cache for 5 minutes

	return $data;
}
