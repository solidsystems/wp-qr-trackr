<?php
/**
 * QR code redirect handling for the QR Trackr plugin.
 *
 * This module handles QR code redirects using native WordPress redirects
 * instead of custom rewrite rules for better reliability and compatibility.
 *
 * @package WP_QR_TRACKR
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Add rewrite rules for QR code redirects.
 *
 * Registers rewrite rules to handle URLs in the format:
 * /qr/{tracking_code} or /qrcode/{tracking_code} where {tracking_code} is the alphanumeric tracking code.
 *
 * @since 1.0.0
 * @return void
 */
function qr_trackr_add_rewrite_rules() {
	// Primary QR path: /qr/{code}
	add_rewrite_rule(
		'qr/([a-zA-Z0-9]+)/?$',
		'index.php?qr_tracking_code=$matches[1]',
		'top'
	);

	// Alternative QR path: /qrcode/{code}
	add_rewrite_rule(
		'qrcode/([a-zA-Z0-9]+)/?$',
		'index.php?qr_tracking_code=$matches[1]',
		'top'
	);
}

/**
 * Register query variables for QR code tracking.
 *
 * @since 1.0.0
 * @param array $vars The existing query variables.
 * @return array The modified query variables.
 */
function qr_trackr_add_query_vars( $vars ) {
	$vars[] = 'qr_tracking_code';
	return $vars;
}
add_filter( 'query_vars', 'qr_trackr_add_query_vars' );

/**
 * Initialize rewrite rules on WordPress init.
 *
 * @since 1.0.0
 * @return void
 */
function qr_trackr_init_rewrite_rules() {
	qr_trackr_add_rewrite_rules();
}
add_action( 'init', 'qr_trackr_init_rewrite_rules' );

/**
 * Handle QR code redirects for clean URLs.
 *
 * This function handles URLs in the format /qr/{tracking_code} and processes
 * the redirect using WordPress native redirects.
 *
 * @since 1.0.0
 * @return void
 */
function qr_trackr_handle_clean_urls() {
	// Only process frontend requests, not admin or AJAX.
	if ( is_admin() || wp_doing_ajax() ) {
		return;
	}

	// Get the QR code from query vars.
	$qr_code = get_query_var( 'qr_tracking_code' );

	if ( empty( $qr_code ) ) {
		return;
	}

	// Sanitize the QR code.
	$qr_code = sanitize_text_field( $qr_code );

	// Get the database connection.
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';

	// Look up the QR code in the database.
	$result = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$table_name} WHERE qr_code = %s",
			$qr_code
		)
	);

	if ( ! $result ) {
		// QR code not found or inactive, redirect to 404.
		wp_redirect( home_url( '/404/' ) );
		exit;
	}

	// Get the destination URL.
	$destination_url = $result->destination_url;

	// Update the scan count.
	qr_trackr_update_scan_count_immediate( $result->id );

	// Perform the redirect.
	wp_redirect( esc_url_raw( $destination_url ), 302 );
	exit;
}

// Register the template redirect handler.
add_action( 'template_redirect', 'qr_trackr_handle_clean_urls' );

/**
 * Handle QR code redirects using AJAX endpoints for reliability.
 *
 * This provides clean URLs for QR codes but uses AJAX for actual redirects
 * to ensure reliability across all environments.
 *
 * @since 1.0.0
 * @return void
 */
function qr_trackr_ajax_redirect() {
	// Get QR code from AJAX request.
	$qr_code = isset( $_GET['qr'] ) ? sanitize_text_field( wp_unslash( $_GET['qr'] ) ) : '';

	if ( empty( $qr_code ) ) {
		wp_die( esc_html__( 'QR code not provided.', 'wp-qr-trackr' ) );
	}

	qr_trackr_log_page_load(
		'qr_redirect',
		array(
			'qr_code' => $qr_code,
			'user_ip' => qr_trackr_get_user_ip(),
		)
	);

	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';

	// Try to get link data from cache first.
	$cache_key       = 'qr_trackr_link_data_' . $qr_code;
	$link_data       = wp_cache_get( $cache_key );
	$link_id         = null;
	$destination_url = '';

	if ( false === $link_data ) {
		qr_trackr_log( 'QR code not found in cache, querying database', 'info', array( 'qr_code' => $qr_code ) );

		// Get destination URL and ID from database using tracking code.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Caching implemented above, single-row lookup needed for performance.
		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT destination_url, id FROM {$table_name} WHERE qr_code = %s",
				$qr_code
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

			qr_trackr_log_db_operation(
				'select',
				$table_name,
				array(
					'qr_code' => $qr_code,
					'link_id' => $link_id,
				),
				true
			);
		} else {
			qr_trackr_log_db_operation( 'select', $table_name, array( 'qr_code' => $qr_code ), false );
		}
	} else {
		// Extract data from cache.
		$destination_url = $link_data['destination_url'];
		$link_id         = $link_data['link_id'];

		qr_trackr_log(
			'QR code data retrieved from cache',
			'info',
			array(
				'qr_code' => $qr_code,
				'link_id' => $link_id,
			)
		);
	}

	if ( empty( $destination_url ) || empty( $link_id ) ) {
		qr_trackr_log( 'QR code not found, showing 404', 'error', array( 'qr_code' => $qr_code ) );
		qr_trackr_handle_404();
		return;
	}

	// Validate URL before redirecting.
	if ( ! wp_http_validate_url( $destination_url ) ) {
		qr_trackr_log(
			'Invalid destination URL for QR code',
			'error',
			array(
				'qr_code'         => $qr_code,
				'destination_url' => $destination_url,
			)
		);

		error_log( sprintf( 'QR Trackr: Invalid destination URL for tracking code %s.', $qr_code ) );
		qr_trackr_handle_404();
		return;
	}

	// Update scan count immediately.
	qr_trackr_update_scan_count_immediate( $link_id );

	// Log successful redirect.
	qr_trackr_log(
		'QR code redirect successful',
		'info',
		array(
			'qr_code'         => $qr_code,
			'link_id'         => $link_id,
			'destination_url' => $destination_url,
		)
	);

	// Debug logging.
	error_log( 'QR Trackr: About to redirect to: ' . $destination_url );

	// Use JavaScript redirect for AJAX context.
	echo '<script>window.location.href = "' . esc_url( $destination_url ) . '";</script>';
	exit;
}

// Register AJAX actions for both logged-in and non-logged-in users.
add_action( 'wp_ajax_qr_trackr_redirect', 'qr_trackr_ajax_redirect' );
add_action( 'wp_ajax_nopriv_qr_trackr_redirect', 'qr_trackr_ajax_redirect' );



/**
 * Update the scan count immediately during redirect.
 *
 * This function updates the scan count immediately instead of using wp_schedule_single_event
 * to ensure reliable tracking without dependency on wp-cron.
 *
 * @since 1.2.18
 * @param int $link_id The ID of the QR code link.
 * @return void
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

	$update_data = array(
		'link_id'                => $link_id,
		'access_count_increment' => 1,
		'scans_increment'        => 1,
	);

	qr_trackr_log_db_operation( 'update_scan_count', $table_name, $update_data, false !== $result );

	if ( false === $result ) {
		qr_trackr_log(
			'Failed to update scan count for QR code',
			'error',
			array(
				'link_id'  => $link_id,
				'db_error' => $wpdb->last_error,
			)
		);
		error_log( sprintf( 'QR Trackr: Failed to update scan count for QR code ID %d: %s.', $link_id, $wpdb->last_error ) );
	} else {
		// Clear relevant caches after successful update.
		wp_cache_delete( 'qr_trackr_details_' . $link_id );
		wp_cache_delete( 'qr_trackr_all_links_admin', 'qr_trackr' );
		wp_cache_delete( 'qrc_link_' . $link_id, 'qrc_links' );

		qr_trackr_log(
			'Scan count updated successfully',
			'info',
			array(
				'link_id'       => $link_id,
				'rows_affected' => $result,
			)
		);
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
 */
function qr_trackr_update_scan_count( $link_id ) {
	// Use the immediate function for better reliability.
	qr_trackr_update_scan_count_immediate( $link_id );
}
add_action( 'qr_trackr_update_scan_count', 'qr_trackr_update_scan_count' );

/**
 * Handle 404 errors for QR code redirects.
 *
 * @since 1.0.0
 * @return void
 */
function qr_trackr_handle_404() {
	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	get_template_part( 404 );
	exit;
}

/**
 * Check if QR code redirects are working properly.
 *
 * @since 1.2.16
 * @return bool True if QR redirects are working, false otherwise.
 */
function qr_trackr_check_redirect_functionality() {
	// Check if the template_redirect hook is properly set up.
	return has_action( 'template_redirect', 'qr_trackr_template_redirect' ) > 0;
}
