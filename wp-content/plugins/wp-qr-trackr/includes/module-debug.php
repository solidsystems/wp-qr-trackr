<?php
/**
 * Debug module for QR Trackr plugin.
 *
 * Handles debug logging and admin footer debug output.
 *
 * @package QR_Trackr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Determine if debug logging is enabled (env var, CI secret, or WP option).
 *
 * @return bool
 */
function qr_trackr_is_debug_enabled() {
	// 1. Check environment variable (local or CI/CD)
	$env = getenv( 'QR_TRACKR_DEBUG' );
	if ( $env !== false && in_array( strtolower( $env ), array( '1', 'true', 'on', 'yes' ), true ) ) {
		return true;
	}
	// 2. Check GitHub Actions secret (if running in CI)
	if ( getenv( 'GITHUB_ACTIONS' ) === 'true' && getenv( 'QR_TRACKR_DEBUG' ) ) {
		return true;
	}
	// 3. Fallback to WP option (UI toggle)
	return ( '1' === get_option( 'qr_trackr_debug_mode', '0' ) );
}

/**
 * Log debug messages for QR Trackr if debug mode is enabled.
 *
 * @param string $msg  The debug message.
 * @param mixed  $data Optional. Additional data to log.
 * @return void
 */
function qr_trackr_debug_log( $msg, $data = null ) {
	if ( ! qr_trackr_is_debug_enabled() ) {
		return;
	}
	$out = '[QR Trackr Debug] ' . gmdate( 'Y-m-d H:i:s' ) . ' ' . $msg;
	if ( null !== $data ) {
		$out .= ' ' . ( is_string( $data ) ? $data : wp_json_encode( $data ) );
	}
	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	error_log( $out );
}

if ( function_exists( 'add_action' ) ) {
	add_action(
		'admin_footer',
		/**
		 * Output debug information in the admin footer (for development only).
		 *
		 * @return void
		 */
		function () {
			global $post, $pagenow;
			$now      = gmdate( 'Y-m-d H:i:s' );
			$debug    = array(
				'timestamp' => $now,
				'pagenow'   => $pagenow,
				'post_id'   => isset( $post->ID ) ? $post->ID : null,
				'user'      => wp_get_current_user()->user_login,
			);
			$qr_links = array();
			if ( isset( $post->ID ) ) {
				$links = qr_trackr_get_all_tracking_links_for_post( $post->ID );
				foreach ( $links as $link ) {
					$qr_links[] = array(
						'id'              => $link->id,
						'tracking_link'   => trailingslashit( home_url() ) . 'qr-trackr/redirect/' . intval( $link->id ),
						'destination_url' => $link->destination_url,
						'created_at'      => $link->created_at,
					);
				}
			}
			$debug['qr_trackr_links'] = $qr_links;
			// No debug <script> output here.
		}
	);
}

// Debug admin footer output.
// ... (move admin_footer debug output here).
