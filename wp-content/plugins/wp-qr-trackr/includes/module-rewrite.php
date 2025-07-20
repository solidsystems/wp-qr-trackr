<?php
/**
 * Rewrite rules for the QR Trackr plugin.
 *
 * This module handles URL rewriting and redirection for QR code tracking links.
 * It sets up custom rewrite rules, handles query variables, and manages redirects.
 *
 * @package WP_QR_TRACKR
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Register rewrite rules and query vars for QR code redirects.
 *
 * @since 1.0.0
 * @return void
 */
function qr_trackr_register_rewrite_rules() {
	// Add rewrite rule for QR code redirects using a public redirect path.
	add_rewrite_rule(
		'^redirect/([a-zA-Z0-9]+)/?$',
		'index.php?qr_tracking_code=$matches[1]',
		'top'
	);
}
add_action( 'init', 'qr_trackr_register_rewrite_rules' );

/**
 * Add the qr_tracking_code query var.
 *
 * @since 1.0.0
 * @param array $vars The array of query variables.
 * @return array The modified array of query variables.
 */
function qr_trackr_add_query_vars( $vars ) {
	$vars[] = 'qr_tracking_code';
	return $vars;
}
add_filter( 'query_vars', 'qr_trackr_add_query_vars' );

/**
 * Handle QR code redirects using template_redirect hook.
 *
 * This approach uses proper WordPress redirect handling with
 * appropriate headers to avoid admin detection issues.
 *
 * @since 1.0.0
 * @return void
 */
function qr_trackr_handle_qr_redirect() {
	// Get the QR code from query vars.
	$qr_code = get_query_var( 'qr_tracking_code' );

	// If no QR code, this isn't our request.
	if ( empty( $qr_code ) ) {
		return;
	}

	// Debug: Log that we're processing a QR code.
	error_log( 'QR Trackr: Processing QR code: ' . $qr_code . ' - User logged in: ' . ( is_user_logged_in() ? 'YES' : 'NO' ) );

	// Set proper headers to indicate this is a redirect response.
	status_header( 301 );
	nocache_headers();

	global $wpdb;

	// Get destination URL from database.
	$result = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT destination_url, id FROM {$wpdb->prefix}qr_trackr_links WHERE qr_code = %s",
			$qr_code
		)
	);

	if ( $result ) {
		$destination_url = $result->destination_url;
		$link_id         = $result->id;

		error_log( 'QR Trackr: Found QR code - ID: ' . $link_id . ', Destination: ' . $destination_url );

		// Update scan count.
		qr_trackr_update_scan_count_immediate( $link_id );

		// Set proper redirect headers.
		header( 'Location: ' . esc_url_raw( $destination_url ), true, 301 );
		header( 'X-Redirect-By: WP QR Trackr' );

		// Exit immediately.
		exit;
	} else {
		// QR code not found, show 404.
		qr_trackr_handle_404();
	}
}
add_action( 'template_redirect', 'qr_trackr_handle_qr_redirect', 1 );

/**
 * Update the scan count immediately during redirect.
 *
 * This function updates the scan count immediately instead of using wp_schedule_single_event
 * to ensure reliable tracking without dependency on wp-cron.
 *
 * @since 1.0.0
 * @param int $link_id The ID of the QR code link.
 * @return void
 */
function qr_trackr_update_scan_count_immediate( $link_id ) {
	global $wpdb;

	// Update both access_count and scans for compatibility, set last_accessed timestamp.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Write operation, caching not applicable.
	$result = $wpdb->query(
		$wpdb->prepare(
			"UPDATE {$wpdb->prefix}qr_trackr_links SET access_count = access_count + 1, scans = scans + 1, last_accessed = %s, updated_at = %s WHERE id = %d",
			current_time( 'mysql', true ),
			current_time( 'mysql', true ),
			$link_id
		)
	);

	if ( false === $result ) {
		error_log( sprintf( 'QR Trackr: Failed to update scan count for QR code ID %d: %s.', $link_id, $wpdb->last_error ) );
	} else {
		// Clear relevant caches after successful update.
		wp_cache_delete( 'qr_trackr_details_' . $link_id );
		wp_cache_delete( 'qr_trackr_all_links_admin', 'qr_trackr' );
		wp_cache_delete( 'qrc_link_' . $link_id, 'qrc_links' );
	}
}

/**
 * Handle 404 errors for QR code links.
 *
 * Sets up proper 404 handling with appropriate headers and template.
 *
 * @since 1.0.0
 * @return void
 */
function qr_trackr_handle_404() {
	// For QR code redirects, we want to be silent about the 404.
	// Just exit without showing any error messages.
	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );

	// Check if this is a QR code request by looking at the request URI.
	$request_uri = wp_unslash( $_SERVER['REQUEST_URI'] ?? '' );
	if ( strpos( $request_uri, '/redirect/' ) === 0 ) {
		// This is a QR code request that failed, exit silently.
		exit;
	}

	// For other 404s, use the normal 404 template.
	get_template_part( 404 );
	exit;
}

/**
 * Get destination URL by QR code.
 *
 * @since 1.0.0
 * @param string $qr_code The QR code to look up.
 * @return string|false The destination URL or false if not found.
 */
function qrc_get_destination_url( $qr_code ) {
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cache implemented, single-row lookup needed for performance.
	$result = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT destination_url, id FROM {$wpdb->prefix}qr_trackr_links WHERE qr_code = %s",
			$qr_code
		)
	);

	if ( ! $result ) {
		return false;
	}

	// Update access count.
	qrc_update_access_count( $result->id );

	return $result->destination_url;
}

/**
 * Update access count for a QR code.
 *
 * @since 1.0.0
 * @param int $id The QR code ID.
 * @return void
 */
function qrc_update_access_count( $id ) {
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Simple update, no caching needed.
	$wpdb->query(
		$wpdb->prepare(
			"UPDATE {$wpdb->prefix}qr_trackr_links SET access_count = access_count + 1, scans = scans + 1, last_accessed = %s, updated_at = %s WHERE id = %d",
			current_time( 'mysql', true ),
			current_time( 'mysql', true ),
			$id
		)
	);
}
