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
 * Add rewrite rules for the QR code links.
 *
 * Registers a custom rewrite rule for QR code URLs in the format:
 * /qr/{tracking_code} where {tracking_code} is the alphanumeric tracking code.
 *
 * @since 1.0.0
 * @return void
 * @throws WP_Error If rewrite rules cannot be added.
 */
function qr_trackr_add_rewrite_rules() {
	add_rewrite_rule(
		'qr/([a-zA-Z0-9]+)/?$',
		'index.php?qr_tracking_code=$matches[1]',
		'top'
	);

	// Debug logging to track rule registration.
	if ( defined( 'QR_TRACKR_DEBUG' ) && QR_TRACKR_DEBUG ) {
		error_log( 'QR Trackr: Rewrite rule registered for qr/([a-zA-Z0-9]+)/?$' );
	}
}

/**
 * Initialize rewrite rules and handle deferred flush.
 *
 * @since 1.2.19
 * @return void
 */
function qr_trackr_init_rewrite_rules() {
	// Always register our rewrite rules
	qr_trackr_add_rewrite_rules();

	// Check if we have a pending flush from version update
	if ( get_option( 'qr_trackr_needs_flush' ) ) {
		flush_rewrite_rules();
		delete_option( 'qr_trackr_needs_flush' );

		if ( defined( 'QR_TRACKR_DEBUG' ) && QR_TRACKR_DEBUG ) {
			error_log( 'QR Trackr: Completed deferred rewrite rules flush on init hook' );
		}
	}
}
add_action( 'init', 'qr_trackr_init_rewrite_rules' );

/**
 * Force flush rewrite rules if they appear to be missing.
 *
 * This function can be called from the debug page to ensure rewrite rules
 * are properly registered and flushed.
 *
 * @since 1.2.16
 * @return bool True if rules were flushed, false otherwise.
 */
function qr_trackr_force_flush_rewrite_rules() {
	// Re-register our query vars first.
	global $wp;
	if ( isset( $wp->public_query_vars ) && ! in_array( 'qr_tracking_code', $wp->public_query_vars, true ) ) {
		$wp->add_query_var( 'qr_tracking_code' );
	}

	// Re-register our rewrite rules.
	qr_trackr_add_rewrite_rules();

	// Flush all rewrite rules.
	flush_rewrite_rules();

	// Log the action for debugging.
	if ( defined( 'QR_TRACKR_DEBUG' ) && QR_TRACKR_DEBUG ) {
		error_log( 'QR Trackr: Forced flush of rewrite rules and query vars completed.' );
	}

	return true;
}

/**
 * Check if QR rewrite rules are properly registered.
 *
 * @since 1.2.16
 * @return bool True if QR rules are found, false otherwise.
 */
function qr_trackr_check_rewrite_rules() {
	global $wp_rewrite;

	if ( ! is_object( $wp_rewrite ) ) {
		return false;
	}

	$rules = get_option( 'rewrite_rules' );

	if ( ! is_array( $rules ) ) {
		return false;
	}

	// Look for our specific QR rule pattern.
	$qr_pattern = 'qr/([a-zA-Z0-9]+)/?$';

	foreach ( $rules as $pattern => $rewrite ) {
		if ( false !== strpos( $pattern, 'qr/([a-zA-Z0-9]+)' ) && false !== strpos( $rewrite, 'qr_tracking_code' ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Add the `qr_tracking_code` query var.
 *
 * Registers the qr_tracking_code query variable with WordPress to allow URL parsing.
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
 * Handle the QR code redirect.
 *
 * Processes QR code URLs and redirects to the destination URL.
 * Implements caching and security measures for URL handling.
 *
 * @since 1.0.0
 * @return void
 * @throws WP_Error If redirect fails or database operation fails.
 */
function qr_trackr_template_redirect() {
	global $wp_query;

	if ( ! isset( $wp_query->query_vars['qr_tracking_code'] ) ) {
		return;
	}

	$tracking_code = sanitize_text_field( $wp_query->query_vars['qr_tracking_code'] );

	if ( empty( $tracking_code ) ) {
		qr_trackr_handle_404();
		return;
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';

	// Try to get link data from cache first (store both URL and ID).
	$cache_key       = 'qr_trackr_link_data_' . $tracking_code;
	$link_data       = wp_cache_get( $cache_key );
	$link_id         = null;
	$destination_url = '';

	if ( false === $link_data ) {
		// Get destination URL and ID from database using tracking code.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Caching implemented above, single-row lookup needed for performance.
		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT destination_url, id FROM {$table_name} WHERE qr_code = %s",
				$tracking_code
			)
		);

		if ( $result ) {
			$destination_url = $result->destination_url;
			$link_id         = $result->id;

			// Cache both URL and ID for 5 minutes.
			$link_data = array(
				'destination_url' => $destination_url,
				'link_id'         => $link_id,
			);
			wp_cache_set( $cache_key, $link_data, '', 300 );
		}
	} else {
		// Extract data from cache.
		$destination_url = $link_data['destination_url'];
		$link_id         = $link_data['link_id'];
	}

	if ( empty( $destination_url ) || empty( $link_id ) ) {
		qr_trackr_handle_404();
		return;
	}

	// Validate URL before redirecting.
	if ( ! wp_http_validate_url( $destination_url ) ) {
		error_log( sprintf( 'QR Trackr: Invalid destination URL for tracking code %s.', $tracking_code ) );
		qr_trackr_handle_404();
		return;
	}

	// Update scan count immediately instead of using wp_schedule_single_event.
	// This ensures reliable tracking and avoids issues with wp-cron.
	qr_trackr_update_scan_count_immediate( $link_id );

	// Redirect to the destination URL.
	wp_safe_redirect( esc_url_raw( $destination_url ), 301 );
	exit;
}
add_action( 'template_redirect', 'qr_trackr_template_redirect' );

/**
 * Update the scan count immediately during redirect.
 *
 * This function updates the scan count immediately instead of using wp_schedule_single_event
 * to ensure reliable tracking without dependency on wp-cron.
 *
 * @since 1.2.18
 * @param int $link_id The ID of the QR code link.
 * @return void
 * @throws WP_Error If database operation fails.
 */
function qr_trackr_update_scan_count_immediate( $link_id ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';

	// Update both access_count and scans for compatibility, set last_accessed timestamp.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Write operation, caching not applicable.
	$result = $wpdb->query(
		$wpdb->prepare(
			"UPDATE {$table_name} SET access_count = access_count + 1, scans = scans + 1, last_accessed = %s, updated_at = %s WHERE id = %d",
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
	}
}

/**
 * Update the scan count for a QR code link.
 *
 * This function is called asynchronously via wp_schedule_single_event
 * to avoid blocking the redirect while updating stats.
 *
 * Note: This is kept for backward compatibility but the immediate function above is preferred.
 *
 * @since 1.0.0
 * @param int $link_id The ID of the QR code link.
 * @return void
 * @throws WP_Error If database operation fails.
 */
function qr_trackr_update_scan_count( $link_id ) {
	// Use the immediate function for better reliability.
	qr_trackr_update_scan_count_immediate( $link_id );
}
add_action( 'qr_trackr_update_scan_count', 'qr_trackr_update_scan_count' );

/**
 * Handle 404 errors for QR code links.
 *
 * Sets up proper 404 handling with appropriate headers and template.
 *
 * @since 1.0.0
 * @return void
 * @throws WP_Error If template part cannot be loaded.
 */
function qr_trackr_handle_404() {
	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
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
