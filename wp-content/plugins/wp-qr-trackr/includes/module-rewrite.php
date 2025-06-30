<?php
/**
 * Rewrite module for QR Trackr plugin.
 *
 * Handles custom rewrite rules and redirect logic for QR code tracking links.
 *
 * @package QR_Trackr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( function_exists( 'qr_trackr_debug_log' ) ) {
	qr_trackr_debug_log( 'Loading module-rewrite.php...' );
}

/**
 * Initialize rewrite rules.
 *
 * @return void
 */
function qr_trackr_rewrite_init() {
	// Only add rewrite rules if not in admin or AJAX context.
	if ( ! is_admin() && ! wp_doing_ajax() ) {
		add_filter( 'query_vars', 'qr_trackr_add_query_vars' );
		add_action( 'init', 'qr_trackr_add_rewrite_rules', 20 );
		add_action( 'template_redirect', 'qr_trackr_template_redirect' );
	}
}

/**
 * Add custom query vars for QR Trackr.
 *
 * @param array $vars Query vars.
 * @return array Modified query vars.
 */
function qr_trackr_add_query_vars( $vars ) {
	$vars[] = 'qr_trackr_redirect';
	return $vars;
}

/**
 * Register custom rewrite rules for QR Trackr.
 *
 * @return void
 */
function qr_trackr_add_rewrite_rules() {
	add_rewrite_rule(
		'^qr-trackr/redirect/([0-9]+)/?$',
		'index.php?qr_trackr_redirect=$matches[1]',
		'top'
	);
}

/**
 * Handle template redirect for QR tracking.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 * @return void
 * @throws Exception If database operations fail.
 */
function qr_trackr_template_redirect() {
	global $wpdb;
	$link_id = get_query_var( 'qr_trackr_redirect' );

	if ( empty( $link_id ) || ! is_numeric( $link_id ) ) {
		return;
	}

	$link_id   = absint( $link_id );
	$cache_key = 'qr_trackr_link_' . $link_id;
	$link      = wp_cache_get( $cache_key, 'qr_trackr' );

	if ( false === $link ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached immediately after query.
		$link = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}qr_trackr_links WHERE id = %d",
				$link_id
			)
		);

		if ( $link ) {
			wp_cache_set( $cache_key, $link, 'qr_trackr', 300 ); // Cache for 5 minutes.
		}
	}

	if ( ! $link ) {
		wp_safe_redirect( home_url(), 404 );
		exit;
	}

	// Record scan with proper sanitization.
	$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 255 ) : '';
	$ip_address = qr_trackr_get_client_ip();
	$location   = qr_trackr_get_location_data( $ip_address );

	// Insert scan record.
	$scan_result = $wpdb->insert(
		$wpdb->prefix . 'qr_trackr_scans',
		array(
			'link_id'    => $link_id,
			'user_agent' => $user_agent,
			'ip_address' => $ip_address,
			'location'   => $location,
			'scanned_at' => current_time( 'mysql', true ),
		),
		array(
			'%d',
			'%s',
			'%s',
			'%s',
			'%s',
		)
	);

	// Update scan count if scan was recorded successfully.
	if ( false !== $scan_result ) {
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}qr_trackr_links SET scans = scans + 1 WHERE id = %d",
				$link_id
			)
		);

		// Clear caches.
		wp_cache_delete( $cache_key, 'qr_trackr' );
		wp_cache_delete( 'qr_trackr_stats_' . $link_id, 'qr_trackr' );

		// Log successful scan if debug is enabled.
		if ( function_exists( 'qr_trackr_debug_log' ) ) {
			qr_trackr_debug_log( sprintf( 'QR code scan recorded for link ID %d from IP %s', $link_id, $ip_address ) );
		}
	} else {
		// Log error if debug is enabled.
		if ( function_exists( 'qr_trackr_debug_log' ) ) {
			qr_trackr_debug_log( 'QR code scan recording failed for link ID ' . $link_id );
		}
	}

	// Always redirect to destination, even if scan recording failed.
	wp_safe_redirect( esc_url_raw( $link->destination_url ), 302 );
	exit;
}

/**
 * Get client IP address with proxy support.
 *
 * @return string Sanitized IP address.
 */
function qr_trackr_get_client_ip() {
	$ip = '';

	// Check for proxy addresses.
	$proxy_headers = array(
		'HTTP_CLIENT_IP',
		'HTTP_X_FORWARDED_FOR',
		'HTTP_X_FORWARDED',
		'HTTP_X_CLUSTER_CLIENT_IP',
		'HTTP_FORWARDED_FOR',
		'HTTP_FORWARDED',
		'REMOTE_ADDR',
	);

	foreach ( $proxy_headers as $header ) {
		if ( ! empty( $_SERVER[ $header ] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
			// Validate IP address.
			if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
				return $ip;
			}
		}
	}

	return '0.0.0.0';
}

/**
 * Get location data from IP address.
 *
 * @param string $ip_address IP address to look up.
 * @return string JSON encoded location data or empty string on failure.
 * @throws Exception If API request fails.
 */
function qr_trackr_get_location_data( $ip_address ) {
	if ( ! get_option( 'qr_trackr_track_location', false ) ) {
		return '';
	}

	$cache_key = 'qr_trackr_location_' . md5( $ip_address );
	$location  = wp_cache_get( $cache_key, 'qr_trackr' );

	if ( false === $location ) {
		try {
			$response = wp_safe_remote_get(
				'https://ipapi.co/' . $ip_address . '/json/',
				array(
					'timeout'    => 5,
					'user-agent' => 'WordPress/QR-Trackr',
					'sslverify'  => true,
				)
			);

			if ( is_wp_error( $response ) ) {
				throw new Exception( $response->get_error_message() );
			}

			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );

			if ( empty( $data ) || ! is_array( $data ) ) {
				throw new Exception( 'Invalid location data received.' );
			}

			$location = wp_json_encode(
				array(
					'country' => isset( $data['country_name'] ) ? $data['country_name'] : '',
					'region'  => isset( $data['region'] ) ? $data['region'] : '',
					'city'    => isset( $data['city'] ) ? $data['city'] : '',
				)
			);

			wp_cache_set( $cache_key, $location, 'qr_trackr', DAY_IN_SECONDS );

		} catch ( Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
				qr_trackr_debug_log( 'Location lookup error: ' . $e->getMessage() );
			}
			$location = '';
		}
	}

	return $location;
}

/**
 * Get tracking URL for a QR code.
 *
 * @param int $link_id Link ID.
 * @return string Tracking URL.
 */
function qr_trackr_get_tracking_url( $link_id ) {
	$link_id = absint( $link_id );

	if ( qr_trackr_check_permalinks() ) {
		return home_url( 'qr-trackr/redirect/' . $link_id );
	}

	return add_query_arg( 'qr_trackr_redirect', $link_id, home_url() );
}

/**
 * Get all tracking links for a specific post.
 *
 * @param int $post_id The post ID to get tracking links for.
 * @return array Array of tracking link objects.
 */
function qr_trackr_get_all_tracking_links_for_post( $post_id ) {
	global $wpdb;
	$table = $wpdb->prefix . 'qr_trackr_links';

	// Get QR code links by post ID.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Simple query for post-specific data, results not cached due to infrequent usage.
	$links = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}qr_trackr_links WHERE post_id = %d ORDER BY created_at DESC",
			$post_id
		),
		ARRAY_A
	);

	return ! empty( $links ) ? $links : array();
}

if ( function_exists( 'qr_trackr_debug_log' ) ) {
	qr_trackr_debug_log( 'Loaded module-rewrite.php.' );
}
