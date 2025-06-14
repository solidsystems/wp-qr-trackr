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

/**
 * Register custom rewrite rules for QR Trackr.
 *
 * @return void
 */
function qr_trackr_add_rewrite_rules() {
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
 * Handle QR Trackr redirect requests.
 *
 * @return void
 */
function qr_trackr_template_redirect() {
	$redirect_id = get_query_var( 'qr_trackr_redirect_id' );
	if ( '' !== $redirect_id && $redirect_id ) {
		$redirect_id = intval( wp_unslash( $redirect_id ) );
		global $wpdb;
		$table = $wpdb->prefix . 'qr_trackr_links';
		$link  = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %s WHERE id = %d', $table, $redirect_id ) );
		if ( false !== $link && $link ) {
			qr_trackr_record_scan( $link->id );
			$post_url = get_permalink( $link->post_id );
			wp_safe_redirect( $post_url );
			exit;
		}
		wp_safe_redirect( home_url() );
		exit;
	}
}
add_action( 'template_redirect', 'qr_trackr_template_redirect' );

/**
 * Record a scan for a given tracking link ID.
 *
 * @param int $link_id The tracking link ID.
 * @return void
 */
function qr_trackr_record_scan( $link_id ) {
	global $wpdb;
	$table = $wpdb->prefix . 'qr_trackr_scans';
	$wpdb->insert(
		$table,
		array(
			'link_id'    => $link_id,
			'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
			'ip_address' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
			'scanned_at' => current_time( 'mysql' ),
		),
		array( '%d', '%s', '%s', '%s' )
	);
}
