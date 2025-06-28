<?php
/**
 * Rewrite rules for the QR Coder plugin.
 *
 * @package WP_QR_TRACKR
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Add rewrite rules for the QR code links.
 */
function qrc_add_rewrite_rules() {
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
 */
function qrc_template_redirect() {
	global $wp_query;

	if ( isset( $wp_query->query_vars['qrc_id'] ) ) {
		$link_id = absint( $wp_query->query_vars['qrc_id'] );

		if ( $link_id > 0 ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'qr_code_links';

			// Try to get destination URL from cache first.
			$cache_key       = 'qrc_destination_' . $link_id;
			$destination_url = wp_cache_get( $cache_key );

			if ( false === $destination_url ) {
				// Get destination URL from database.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Caching implemented above.
				$destination_url = $wpdb->get_var( $wpdb->prepare( "SELECT destination_url FROM {$table_name} WHERE id = %d", $link_id ) );

				if ( ! empty( $destination_url ) ) {
					// Cache for 5 minutes.
					wp_cache_set( $cache_key, $destination_url, '', 300 );
				}
			}

			// Increment access count (no caching needed for write operation).
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Write operation, caching not applicable.
			$wpdb->query( $wpdb->prepare( "UPDATE {$table_name} SET access_count = access_count + 1 WHERE id = %d", $link_id ) );

			if ( ! empty( $destination_url ) ) {
				wp_safe_redirect( esc_url_raw( $destination_url ), 301 );
				exit;
			} else {
				// Handle not found case.
				$wp_query->set_404();
				status_header( 404 );
				get_template_part( 404 );
				exit;
			}
		}
	}
}
add_action( 'template_redirect', 'qrc_template_redirect' );
