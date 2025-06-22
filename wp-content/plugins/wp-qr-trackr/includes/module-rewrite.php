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
	add_filter( 'query_vars', 'qr_trackr_add_query_vars' );
	add_action( 'init', 'qr_trackr_add_rewrite_rules', 20 );
	add_action( 'template_redirect', 'qr_trackr_template_redirect' );
}

/**
 * Add custom query vars for QR Trackr.
 *
 * @param array $vars Query vars.
 * @return array Modified query vars.
 */
function qr_trackr_add_query_vars( $vars ) {
	$vars[] = 'qr_trackr_scan';
	return $vars;
}

/**
 * Register custom rewrite rules for QR Trackr.
 *
 * @return void
 */
function qr_trackr_add_rewrite_rules() {
	add_rewrite_rule( '^qr-trackr/scan/([0-9]+)/?$', 'index.php?qr_trackr_scan=$matches[1]', 'top' );
}

/**
 * Handle template redirect for QR tracking.
 *
 * @return void
 */
function qr_trackr_template_redirect() {
	global $wpdb;
	$scan_id = get_query_var( 'qr_trackr_scan' );

	if ( empty( $scan_id ) ) {
		return;
	}

	$link = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}qr_trackr_links WHERE id = %d",
			$scan_id
		)
	);

	if ( ! $link ) {
		wp_safe_redirect( home_url() );
		exit;
	}

	$wpdb->insert(
		"{$wpdb->prefix}qr_trackr_scans",
		array(
			'link_id'    => $link->id,
			'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
			'ip_address' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
			'scanned_at' => current_time( 'mysql' ),
		)
	);

	$wpdb->update(
		"{$wpdb->prefix}qr_trackr_links",
		array( 'access_count' => $link->access_count + 1, 'last_accessed' => current_time( 'mysql' ) ),
		array( 'id' => $link->id )
	);

	wp_safe_redirect( esc_url_raw( $link->destination_url ) );
	exit;
}

/**
 * Flush rewrite rules on plugin activation.
 *
 * @return void
 */
if ( ! function_exists( 'qr_trackr_flush_rewrite_rules' ) ) {
	function qr_trackr_flush_rewrite_rules() {
		qr_trackr_add_rewrite_rules();
		flush_rewrite_rules();
	}
}

/**
 * Get tracking URL for a link.
 *
 * @param string $qr_code The QR code to generate URL for.
 * @return string The tracking URL.
 */
function qr_trackr_get_rewrite_tracking_url( $qr_code ) {
	if ( qr_trackr_check_permalinks() ) {
		return home_url( 'qr/' . $qr_code );
	}

	return add_query_arg( 'qr_trackr', $qr_code, home_url() );
}

/**
 * Get client IP address.
 *
 * @return string Client IP address.
 */
function qr_trackr_get_client_ip() {
	$ip = '';

	if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) ) {
		$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
	} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
	} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
		$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
	}

	return $ip;
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

	// Get links with caching.
	$cache_key = 'qr_trackr_links_post_' . $post_id;
	$links     = wp_cache_get( $cache_key );

	if ( false === $links ) {
		$links = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$wpdb->prefix}qr_trackr_links` WHERE post_id = %d ORDER BY created_at DESC",
				$post_id
			)
		);
		if ( false !== $links ) {
			wp_cache_set( $cache_key, $links, '', 300 ); // Cache for 5 minutes.
		}
	}

	return ! empty( $links ) ? $links : array();
}

if ( function_exists( 'qr_trackr_debug_log' ) ) {
	qr_trackr_debug_log( 'Loaded module-rewrite.php.' );
}
