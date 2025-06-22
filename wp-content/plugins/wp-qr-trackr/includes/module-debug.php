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
	if ( false !== $env && in_array( strtolower( $env ), array( '1', 'true', 'on', 'yes' ), true ) ) {
		return true;
	}
	// 2. Check GitHub Actions secret (if running in CI)
	if ( 'true' === getenv( 'GITHUB_ACTIONS' ) && getenv( 'QR_TRACKR_DEBUG' ) ) {
		return true;
	}
	// 3. Fallback to WP option (UI toggle)
	return ( '1' === get_option( 'qr_trackr_debug_mode', '0' ) );
}

/**
 * Log a message to the debug file if debugging is enabled.
 *
 * @param string|array|object $message The message or data to log.
 * @param string              $title   Optional title for the log entry.
 * @return void
 */
function qr_trackr_debug_log( $message, $title = '' ) {
	if ( ! qr_trackr_is_debug_enabled() ) {
		return;
	}

	$log_file  = qr_trackr_get_debug_log_path();
	$timestamp = gmdate( 'Y-m-d H:i:s' );
	$header    = $title ? "--- [ $timestamp ] $title ---\n" : "--- [ $timestamp ] ---\n";

	$entry = $header;
	if ( is_array( $message ) || is_object( $message ) ) {
		$entry .= print_r( $message, true );
	} else {
		$entry .= $message;
	}
	$entry .= "\n\n";

	// Use file_put_contents with FILE_APPEND to handle file locking.
	file_put_contents( $log_file, $entry, FILE_APPEND | LOCK_EX );
}

/**
 * Get the debug log file path.
 *
 * @return string The path to the debug log file.
 */
function qr_trackr_get_debug_log_path() {
	return QR_TRACKR_PLUGIN_DIR . 'debug.log';
}

/**
 * Clear the debug log file.
 *
 * @return bool True if the log was cleared successfully, false otherwise.
 */
function qr_trackr_clear_debug_log() {
	$log_file = qr_trackr_get_debug_log_path();
	if ( file_exists( $log_file ) ) {
		return wp_delete_file( $log_file );
	}
	return false;
}

/**
 * Get the debug log contents.
 *
 * @return string|false Log content or false on failure.
 */
function qr_trackr_get_debug_log() {
	$log_file = qr_trackr_get_debug_log_path();
	if ( ! file_exists( $log_file ) ) {
		return false;
	}
	$response = wp_remote_get( $log_file );
	if ( is_wp_error( $response ) ) {
		return false;
	}
	return wp_remote_retrieve_body( $response );
}

/**
 * Add debug settings page to the QR Trackr menu.
 */
function qr_trackr_debug_settings_page() {
	// Handle debug mode toggle.
	if ( isset( $_POST['toggle_debug'] ) && check_admin_referer( 'qr_trackr_toggle_debug' ) ) {
		$current_mode = get_option( 'qr_trackr_debug_mode', '0' );
		update_option( 'qr_trackr_debug_mode', '1' === $current_mode ? '0' : '1' );
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Debug mode updated.', 'qr-trackr' ) . '</p></div>';
	}

	// Clear log if requested.
	if ( isset( $_POST['clear_log'] ) && check_admin_referer( 'qr_trackr_clear_log' ) ) {
		qr_trackr_clear_debug_log();
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Debug log cleared.', 'qr-trackr' ) . '</p></div>';
	}

	// Get log contents.
	$log_contents = qr_trackr_get_debug_log();
	$debug_mode   = get_option( 'qr_trackr_debug_mode', '0' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'QR Trackr Debug Settings', 'qr-trackr' ); ?></h1>
		
		<form method="post" class="qr-trackr-debug-form">
			<?php wp_nonce_field( 'qr_trackr_toggle_debug' ); ?>
			<p>
				<label>
					<input type="checkbox" name="debug_mode" value="1" <?php checked( '1', $debug_mode ); ?> disabled>
					<?php esc_html_e( 'Debug Mode', 'qr-trackr' ); ?>
				</label>
				<input type="submit" name="toggle_debug" class="button" value="<?php esc_attr_e( 'Toggle Debug Mode', 'qr-trackr' ); ?>">
			</p>
		</form>

		<form method="post" class="qr-trackr-debug-form">
			<?php wp_nonce_field( 'qr_trackr_clear_log' ); ?>
			<p>
				<input type="submit" name="clear_log" class="button" value="<?php esc_attr_e( 'Clear Log', 'qr-trackr' ); ?>">
			</p>
		</form>

		<div class="debug-log-container" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
			<pre style="margin: 0; white-space: pre-wrap;"><?php echo esc_html( $log_contents ); ?></pre>
		</div>
	</div>
	<?php
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
