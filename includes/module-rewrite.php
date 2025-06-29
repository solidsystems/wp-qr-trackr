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
 * /qr-code/{id}/ where {id} is the numeric identifier of the QR code.
 *
 * @since 1.0.0
 * @return void
 * @throws WP_Error If rewrite rules cannot be added.
 */
function qrc_add_rewrite_rules() {
	// Check if we're in admin or doing AJAX to prevent interference.
	if ( is_admin() || wp_doing_ajax() ) {
		return;
	}

	add_rewrite_rule(
		'qr-code/([0-9]+)/?$',
		'index.php?qrc_id=$matches[1]',
		'top'
	);
}
add_action( 'init', 'qrc_add_rewrite_rules' );

/**
 * Add the `qrc_id` query var.
 *
 * Registers the qrc_id query variable with WordPress to allow URL parsing.
 *
 * @since 1.0.0
 * @param array $vars The array of query variables.
 * @return array The modified array of query variables.
 */
function qrc_add_query_vars( $vars ) {
	$vars[] = 'qrc_id';
	return $vars;
}
add_filter( 'query_vars', 'qrc_add_query_vars' );

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
function qrc_template_redirect() {
	global $wp_query;

	if ( ! isset( $wp_query->query_vars['qrc_id'] ) ) {
		return;
	}

	$link_id = absint( $wp_query->query_vars['qrc_id'] );

	if ( $link_id <= 0 ) {
		qrc_handle_404();
		return;
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';

	// Try to get destination URL from cache first.
	$cache_key = 'qrc_destination_' . $link_id;
	$destination_url = wp_cache_get( $cache_key );

	if ( false === $destination_url ) {
		// Get destination URL from database.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Caching implemented above, single-row lookup needed for performance.
		$destination_url = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT destination_url FROM {$table_name} WHERE id = %d AND is_active = 1",
				$link_id
			)
		);

		if ( ! empty( $destination_url ) ) {
			// Cache for 5 minutes.
			wp_cache_set( $cache_key, $destination_url, '', 300 );
		}
	}

	if ( empty( $destination_url ) ) {
		qrc_handle_404();
		return;
	}

	// Validate URL before redirecting.
	if ( ! wp_http_validate_url( $destination_url ) ) {
		error_log( sprintf( 'QR Trackr: Invalid destination URL for QR code ID %d.', $link_id ) );
		qrc_handle_404();
		return;
	}

	// Update access count asynchronously to avoid blocking the redirect.
	wp_schedule_single_event( time(), 'qrc_update_access_count', array( $link_id ) );

	// Redirect to the destination URL.
	wp_safe_redirect( esc_url_raw( $destination_url ), 301 );
	exit;
}
add_action( 'template_redirect', 'qrc_template_redirect' );

/**
 * Update the access count for a QR code link.
 *
 * This function is called asynchronously via wp_schedule_single_event
 * to avoid blocking the redirect while updating stats.
 *
 * @since 1.0.0
 * @param int $link_id The ID of the QR code link.
 * @return void
 * @throws WP_Error If database operation fails.
 */
function qrc_update_access_count( $link_id ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Write operation, caching not applicable.
	$result = $wpdb->query(
		$wpdb->prepare(
			"UPDATE {$table_name} SET access_count = access_count + 1, last_access = %s WHERE id = %d",
			current_time( 'mysql', true ),
			$link_id
		)
	);

	if ( false === $result ) {
		error_log( sprintf( 'QR Trackr: Failed to update access count for QR code ID %d: %s.', $link_id, $wpdb->last_error ) );
	}
}
add_action( 'qrc_update_access_count', 'qrc_update_access_count' );

/**
 * Handle 404 errors for QR code links.
 *
 * Sets up proper 404 handling with appropriate headers and template.
 *
 * @since 1.0.0
 * @return void
 * @throws WP_Error If template part cannot be loaded.
 */
function qrc_handle_404() {
	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	get_template_part( 404 );
	exit;
}
