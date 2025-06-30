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
	// Check if we're in admin or doing AJAX to prevent interference.
	if ( is_admin() || wp_doing_ajax() ) {
		return;
	}

	add_rewrite_rule(
		'qr/([a-zA-Z0-9]+)/?$',
		'index.php?qr_tracking_code=$matches[1]',
		'top'
	);
}
add_action( 'init', 'qr_trackr_add_rewrite_rules' );

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

	// Try to get destination URL from cache first.
	$cache_key = 'qr_trackr_destination_' . $tracking_code;
	$destination_url = wp_cache_get( $cache_key );

	if ( false === $destination_url ) {
		// Get destination URL from database using tracking code.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Caching implemented above, single-row lookup needed for performance.
		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT destination_url, id FROM {$table_name} WHERE qr_code = %s",
				$tracking_code
			)
		);

		if ( $result ) {
			$destination_url = $result->destination_url;
			$link_id = $result->id;
			
			// Cache for 5 minutes.
			wp_cache_set( $cache_key, $destination_url, '', 300 );
		}
	}

	if ( empty( $destination_url ) ) {
		qr_trackr_handle_404();
		return;
	}

	// Validate URL before redirecting.
	if ( ! wp_http_validate_url( $destination_url ) ) {
		error_log( sprintf( 'QR Trackr: Invalid destination URL for tracking code %s.', $tracking_code ) );
		qr_trackr_handle_404();
		return;
	}

	// Update scan count asynchronously to avoid blocking the redirect.
	if ( isset( $link_id ) ) {
		wp_schedule_single_event( time(), 'qr_trackr_update_scan_count', array( $link_id ) );
	}

	// Redirect to the destination URL.
	wp_safe_redirect( esc_url_raw( $destination_url ), 301 );
	exit;
}
add_action( 'template_redirect', 'qr_trackr_template_redirect' );

/**
 * Update the scan count for a QR code link.
 *
 * This function is called asynchronously via wp_schedule_single_event
 * to avoid blocking the redirect while updating stats.
 *
 * @since 1.0.0
 * @param int $link_id The ID of the QR code link.
 * @return void
 * @throws WP_Error If database operation fails.
 */
function qr_trackr_update_scan_count( $link_id ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Write operation, caching not applicable.
	$result = $wpdb->query(
		$wpdb->prepare(
			"UPDATE {$table_name} SET scans = scans + 1, updated_at = %s WHERE id = %d",
			current_time( 'mysql', true ),
			$link_id
		)
	);

	if ( false === $result ) {
		error_log( sprintf( 'QR Trackr: Failed to update scan count for QR code ID %d: %s.', $link_id, $wpdb->last_error ) );
	}
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
