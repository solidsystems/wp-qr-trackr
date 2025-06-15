<?php
/**
 * QR code generation module for QR Trackr plugin.
 *
 * Handles QR code image generation and related utilities.
 *
 * @package QR_Trackr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( getenv( 'CI_DEBUG' ) === 'true' ) {
	echo "[CI_DEBUG] includes/module-qr.php loaded\n";
}

/**
 * Generate a QR code image for a given tracking link ID.
 *
 * @param int $link_id The tracking link ID.
 * @return string|false URL to the generated QR code image, or false on failure.
 */
function qr_trackr_generate_qr_image_for_link( $link_id ) {
	qr_trackr_debug_log( 'Generating QR code image for link', $link_id );
	$link = qr_trackr_get_tracking_link_by_id( $link_id );
	if ( false === $link || ! $link ) {
		qr_trackr_debug_log( 'No link found for QR code generation', $link_id );
		return false;
	}
	$tracking_url = trailingslashit( home_url() ) . 'qr-trackr/redirect/' . intval( $link_id );
	$upload_dir   = wp_upload_dir();
	$qr_dir       = trailingslashit( $upload_dir['basedir'] ) . 'qr-trackr/';
	$qr_url       = trailingslashit( $upload_dir['baseurl'] ) . 'qr-trackr/';
	if ( ! file_exists( $qr_dir ) ) {
		wp_mkdir_p( $qr_dir );
		qr_trackr_debug_log( 'Created QR directory', $qr_dir );
	}
	$filename = 'qr-' . $link_id . '.png';
	$filepath = $qr_dir . $filename;
	$fileurl  = $qr_url . $filename;
	if ( ! file_exists( $filepath ) ) {
		// Generate QR code image if it does not exist.
		if ( ! class_exists( 'QRcode' ) ) {
			$qrlib = plugin_dir_path( __FILE__ ) . 'lib/phpqrcode/qrlib.php';
			if ( file_exists( $qrlib ) ) {
				require_once $qrlib;
			}
		}
		QRcode::png( $tracking_url, $filepath, QR_ECLEVEL_L, 6 );
		qr_trackr_debug_log( 'QR code image generated', $filepath );
	}
	return $fileurl;
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
		$link = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %s WHERE post_id = %d', $table, $post_id ) );
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
	$new_link = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %s WHERE id = %d', $table, $link_id ) );
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
		$link = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %s WHERE id = %d', $table, $link_id ) );
		if ( $link ) {
			wp_cache_set( $cache_key, $link, '', 300 ); // Cache for 5 minutes.
		}
	}
	return $link;
}
