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
	// Strict check for admin or AJAX - return immediately.
	if ( is_admin() || wp_doing_ajax() || defined( 'DOING_AJAX' ) ) {
		return;
	}

	// Add query var.
	add_filter(
		'query_vars',
		function ( $vars ) {
			$vars[] = 'qr_trackr';
			return $vars;
		}
	);

	// Handle template redirect only on frontend.
	if ( ! is_admin() && ! wp_doing_ajax() && ! defined( 'DOING_AJAX' ) ) {
		add_action( 'template_redirect', 'qr_trackr_template_redirect' );
	}
}

/**
 * Register rewrite rules.
 *
 * @return void
 */
function qr_trackr_register_rewrite_rules() {
	// Strict check for admin or AJAX - return immediately.
	if ( is_admin() || wp_doing_ajax() || defined( 'DOING_AJAX' ) ) {
		return;
	}

	// Check if pretty permalinks are enabled.
	if ( ! qr_trackr_check_permalinks() ) {
		// Use query string fallback.
		add_filter(
			'query_vars',
			function ( $vars ) {
				$vars[] = 'qr_trackr';
				return $vars;
			}
		);
		return;
	}

	// Add pretty permalink rule.
	add_rewrite_rule(
		'qr/([a-zA-Z0-9]+)/?$',
		'index.php?qr_trackr=$matches[1]',
		'top'
	);
}
add_action( 'init', 'qr_trackr_register_rewrite_rules', 20 ); // Run after default rules.

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
 * Handle template redirect for QR tracking.
 *
 * @return void
 */
function qr_trackr_template_redirect() {
	// Strict check for admin or AJAX - return immediately.
	if ( is_admin() || wp_doing_ajax() || defined( 'DOING_AJAX' ) ) {
		return;
	}

	// Get QR code from query var.
	$qr_code = get_query_var( 'qr_trackr' );
	if ( '' === $qr_code ) {
		return;
	}

	// Get link from database.
	global $wpdb;
	$link = $wpdb->get_row(
		$wpdb->prepare(
			'SELECT * FROM ' . $wpdb->prefix . 'qr_trackr_links WHERE qr_code = %s',
			$qr_code
		)
	);

	// If link not found, redirect to home.
	if ( ! $link ) {
		wp_safe_redirect( home_url() );
		exit;
	}

	// Record scan.
	$wpdb->insert(
		$wpdb->prefix . 'qr_trackr_scans',
		array(
			'link_id'    => $link->id,
			'ip_address' => qr_trackr_get_client_ip(),
			'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
			'scan_date'  => current_time( 'mysql' ),
			'referer'    => isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '',
		),
		array( '%d', '%s', '%s', '%s', '%s' )
	);

	// Update scan count.
	$wpdb->update(
		$wpdb->prefix . 'qr_trackr_links',
		array( 'scan_count' => $link->scan_count + 1 ),
		array( 'id' => $link->id ),
		array( '%d' ),
		array( '%d' )
	);

	// Redirect to destination.
	wp_safe_redirect( esc_url_raw( $link->destination_url ) );
	exit;
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
 * Flush rewrite rules on plugin activation.
 *
 * @return void
 */
function qr_trackr_flush_rewrite_rules() {
	// Only add rules if not in admin.
	if ( ! is_admin() ) {
		add_rewrite_rule(
			'qr/([a-zA-Z0-9]+)/?$',
			'index.php?qr_trackr=$matches[1]',
			'top'
		);
	}
	flush_rewrite_rules();
}

/**
 * Register custom rewrite rules for QR Trackr.
 *
 * @return void
 */
function qr_trackr_add_rewrite_rules() {
	qr_trackr_debug_log( 'Registering rewrite rules.' );
	add_rewrite_rule( '^qr-trackr/scan/([0-9]+)/?$', 'index.php?qr_trackr_scan=$matches[1]', 'top' );
	add_rewrite_rule( '^qr-trackr/redirect/([0-9]+)/?$', 'index.php?qr_trackr_redirect_id=$matches[1]', 'top' );
}
add_action( 'init', 'qr_trackr_add_rewrite_rules' );

/**
 * Add custom query vars for QR Trackr.
 *
 * @param array $vars Query vars.
 * @return array Modified query vars.
 */
function qr_trackr_add_query_vars( $vars ) {
	$vars[] = 'qr_trackr_scan';
	$vars[] = 'qr_trackr_redirect_id';
	return $vars;
}
add_filter( 'query_vars', 'qr_trackr_add_query_vars' );

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
				'SELECT * FROM `' . $table . '` WHERE post_id = %d ORDER BY created_at DESC',
				$post_id
			)
		);
		if ( false !== $links ) {
			wp_cache_set( $cache_key, $links, '', 300 ); // Cache for 5 minutes.
		}
	}

	return $links ?: array();
}

if ( function_exists( 'qr_trackr_debug_log' ) ) {
	qr_trackr_debug_log( 'Loaded module-rewrite.php.' );
}
