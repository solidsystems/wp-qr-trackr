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
 * @return bool True if debug logging is enabled, false otherwise.
 */
function qr_trackr_is_debug_enabled() {
	static $debug_enabled = null;

	if ( null !== $debug_enabled ) {
		return $debug_enabled;
	}

	// 1. Check environment variable (local or CI/CD).
	$env = getenv( 'QR_TRACKR_DEBUG' );
	if ( false !== $env && in_array( strtolower( $env ), array( '1', 'true', 'on', 'yes' ), true ) ) {
		$debug_enabled = true;
		return $debug_enabled;
	}

	// 2. Check GitHub Actions secret (if running in CI).
	if ( 'true' === getenv( 'GITHUB_ACTIONS' ) && getenv( 'QR_TRACKR_DEBUG' ) ) {
		$debug_enabled = true;
		return $debug_enabled;
	}

	// 3. Check WP_DEBUG constant.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		$debug_enabled = true;
		return $debug_enabled;
	}

	// 4. Fallback to WP option (UI toggle).
	$debug_enabled = ( '1' === get_option( 'qr_trackr_debug_mode', '0' ) );
	return $debug_enabled;
}

/**
 * Get the debug log file path.
 *
 * @return string The path to the debug log file.
 */
function qr_trackr_get_debug_log_path() {
	static $log_path = null;

	if ( null !== $log_path ) {
		return $log_path;
	}

	$upload_dir = wp_upload_dir();
	$log_path   = $upload_dir['basedir'] . '/qr-trackr/debug.log';

	// Create log directory if it doesn't exist.
	$log_dir = dirname( $log_path );
	if ( ! file_exists( $log_dir ) ) {
		wp_mkdir_p( $log_dir );

		// Create .htaccess to protect log files.
		$htaccess = $log_dir . '/.htaccess';
		if ( ! file_exists( $htaccess ) ) {
			file_put_contents( $htaccess, "Order deny,allow\nDeny from all\n" );
		}

		// Create index.php to prevent directory listing.
		$index = $log_dir . '/index.php';
		if ( ! file_exists( $index ) ) {
			file_put_contents( $index, "<?php\n// Silence is golden.\n" );
		}
	}

	return $log_path;
}

/**
 * Log a message to the debug file if debugging is enabled.
 *
 * @param string|array|object $message The message or data to log.
 * @param string              $title   Optional title for the log entry.
 * @param string              $level   Optional log level (debug, info, warning, error).
 * @return bool True if message was logged, false otherwise.
 */
function qr_trackr_debug_log( $message, $title = '', $level = 'debug' ) {
	if ( ! qr_trackr_is_debug_enabled() ) {
		return false;
	}

	try {
		$log_file  = qr_trackr_get_debug_log_path();
		$timestamp = gmdate( 'Y-m-d H:i:s' );
		$user      = function_exists( 'wp_get_current_user' ) ? wp_get_current_user()->user_login : 'system';
		$ip        = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';

		// Format header.
		$header = sprintf(
			"[%s] [%s] [%s] [%s] %s\n",
			$timestamp,
			strtoupper( $level ),
			$user,
			$ip,
			$title ? $title : 'Debug Log Entry'
		);

		// Format message.
		if ( is_array( $message ) || is_object( $message ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r -- Debug only, not for production.
			$formatted = print_r( $message, true );
		} else {
			$formatted = (string) $message;
		}

		// Add stack trace for errors.
		if ( 'error' === $level ) {
			$trace      = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 3 );
			$formatted .= "\nStack Trace:\n";
			foreach ( array_slice( $trace, 1 ) as $i => $t ) {
				$formatted .= sprintf(
					"#%d %s:%d - %s%s%s()\n",
					$i,
					isset( $t['file'] ) ? basename( $t['file'] ) : 'unknown',
					isset( $t['line'] ) ? $t['line'] : 0,
					isset( $t['class'] ) ? $t['class'] : '',
					isset( $t['type'] ) ? $t['type'] : '',
					$t['function']
				);
			}
		}

		// Write to log file with locking.
		$entry = $header . $formatted . "\n\n";
		if ( ! file_put_contents( $log_file, $entry, FILE_APPEND | LOCK_EX ) ) {
			error_log( 'QR Trackr: Failed to write to debug log: ' . $log_file );
			return false;
		}

		// Rotate log if too large.
		qr_trackr_maybe_rotate_log( $log_file );

		return true;

	} catch ( Exception $e ) {
		error_log( 'QR Trackr Debug Error: ' . $e->getMessage() );
		return false;
	}
}

/**
 * Rotate the debug log file if it gets too large.
 *
 * @param string $log_file Path to the log file.
 * @return bool True if rotated, false otherwise.
 */
function qr_trackr_maybe_rotate_log( $log_file ) {
	$max_size = apply_filters( 'qr_trackr_debug_log_max_size', 5 * 1024 * 1024 ); // 5MB default.

	if ( ! file_exists( $log_file ) ) {
		return false;
	}

	$size = filesize( $log_file );
	if ( $size < $max_size ) {
		return false;
	}

	try {
		// Rotate existing backups.
		for ( $i = 3; $i >= 1; $i-- ) {
			$old_file = $log_file . '.' . $i;
			$new_file = $log_file . '.' . ( $i + 1 );
			if ( file_exists( $old_file ) ) {
				rename( $old_file, $new_file );
			}
		}

		// Move current log to .1.
		rename( $log_file, $log_file . '.1' );

		// Create new empty log.
		touch( $log_file );
		chmod( $log_file, 0644 );

		return true;

	} catch ( Exception $e ) {
		error_log( 'QR Trackr Log Rotation Error: ' . $e->getMessage() );
		return false;
	}
}

/**
 * Clear the debug log file.
 *
 * @return bool True if the log was cleared successfully, false otherwise.
 */
function qr_trackr_clear_debug_log() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return false;
	}

	try {
		$log_file = qr_trackr_get_debug_log_path();
		if ( file_exists( $log_file ) ) {
			unlink( $log_file );
			touch( $log_file );
			chmod( $log_file, 0644 );
			qr_trackr_debug_log( 'Debug log cleared by ' . wp_get_current_user()->user_login, 'Log Cleared', 'info' );
			return true;
		}
		return false;

	} catch ( Exception $e ) {
		error_log( 'QR Trackr Clear Log Error: ' . $e->getMessage() );
		return false;
	}
}

/**
 * Get the debug log contents.
 *
 * @param int $lines Optional. Number of lines to return from the end of the log.
 * @return string|false Log content or false on failure.
 */
function qr_trackr_get_debug_log( $lines = 0 ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return false;
	}

	try {
		$log_file = qr_trackr_get_debug_log_path();
		if ( ! file_exists( $log_file ) ) {
			return false;
		}

		if ( $lines > 0 ) {
			// Get only the last N lines.
			$file = new SplFileObject( $log_file, 'r' );
			$file->seek( PHP_INT_MAX );
			$total_lines = $file->key();

			$start_line = max( 0, $total_lines - $lines );
			$file->seek( $start_line );

			$content = '';
			while ( ! $file->eof() ) {
				$content .= $file->fgets();
			}

			return $content;
		}

		// Get entire log.
		return file_get_contents( $log_file );

	} catch ( Exception $e ) {
		error_log( 'QR Trackr Get Log Error: ' . $e->getMessage() );
		return false;
	}
}

/**
 * Add debug settings page to the QR Trackr menu.
 *
 * @return void
 */
function qr_trackr_debug_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wp-qr-trackr' ) );
	}

	// Handle debug mode toggle.
	if ( isset( $_POST['toggle_debug'] ) && check_admin_referer( 'qr_trackr_toggle_debug' ) ) {
		$current_mode = get_option( 'qr_trackr_debug_mode', '0' );
		update_option( 'qr_trackr_debug_mode', '1' === $current_mode ? '0' : '1' );
		qr_trackr_debug_log( 'Debug mode ' . ( '1' === $current_mode ? 'disabled' : 'enabled' ), 'Debug Toggle', 'info' );
		add_settings_error(
			'qr_trackr_messages',
			'qr_trackr_message',
			esc_html__( 'Debug mode updated.', 'wp-qr-trackr' ),
			'updated'
		);
	}

	// Clear log if requested.
	if ( isset( $_POST['clear_log'] ) && check_admin_referer( 'qr_trackr_clear_log' ) ) {
		if ( qr_trackr_clear_debug_log() ) {
			add_settings_error(
				'qr_trackr_messages',
				'qr_trackr_message',
				esc_html__( 'Debug log cleared.', 'wp-qr-trackr' ),
				'updated'
			);
		} else {
			add_settings_error(
				'qr_trackr_messages',
				'qr_trackr_message',
				esc_html__( 'Failed to clear debug log.', 'wp-qr-trackr' ),
				'error'
			);
		}
	}

	// Get log contents (last 1000 lines).
	$log_contents = qr_trackr_get_debug_log( 1000 );
	$debug_mode   = get_option( 'qr_trackr_debug_mode', '0' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'QR Trackr Debug Settings', 'wp-qr-trackr' ); ?></h1>

		<?php settings_errors( 'qr_trackr_messages' ); ?>

		<div class="qr-trackr-debug-info">
			<h2><?php esc_html_e( 'Debug Information', 'wp-qr-trackr' ); ?></h2>
			<table class="widefat">
				<tbody>
					<tr>
						<td><strong><?php esc_html_e( 'Debug Mode:', 'wp-qr-trackr' ); ?></strong></td>
						<td><?php echo qr_trackr_is_debug_enabled() ? esc_html__( 'Enabled', 'wp-qr-trackr' ) : esc_html__( 'Disabled', 'wp-qr-trackr' ); ?></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Log File:', 'wp-qr-trackr' ); ?></strong></td>
						<td><?php echo esc_html( qr_trackr_get_debug_log_path() ); ?></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Log Size:', 'wp-qr-trackr' ); ?></strong></td>
						<td><?php echo esc_html( size_format( file_exists( qr_trackr_get_debug_log_path() ) ? filesize( qr_trackr_get_debug_log_path() ) : 0 ) ); ?></td>
					</tr>
				</tbody>
			</table>
		</div>

		<form method="post" class="qr-trackr-debug-form">
			<?php wp_nonce_field( 'qr_trackr_toggle_debug' ); ?>
			<p>
				<label>
					<input type="checkbox" name="debug_mode" value="1" <?php checked( '1', $debug_mode ); ?> disabled>
					<?php esc_html_e( 'Debug Mode', 'wp-qr-trackr' ); ?>
				</label>
				<input type="submit" name="toggle_debug" class="button" value="<?php esc_attr_e( 'Toggle Debug Mode', 'wp-qr-trackr' ); ?>">
			</p>
		</form>

		<form method="post" class="qr-trackr-debug-form">
			<?php wp_nonce_field( 'qr_trackr_clear_log' ); ?>
			<p>
				<input type="submit" name="clear_log" class="button" value="<?php esc_attr_e( 'Clear Log', 'wp-qr-trackr' ); ?>">
			</p>
		</form>

		<div class="debug-log-container" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); margin-top: 20px;">
			<h2><?php esc_html_e( 'Debug Log', 'wp-qr-trackr' ); ?></h2>
			<?php if ( false === $log_contents ) : ?>
				<p><?php esc_html_e( 'No log entries found.', 'wp-qr-trackr' ); ?></p>
			<?php else : ?>
				<pre style="margin: 0; white-space: pre-wrap; max-height: 500px; overflow-y: auto;"><?php echo esc_html( $log_contents ); ?></pre>
			<?php endif; ?>
		</div>
	</div>
	<?php
}

// Debug menu is now registered in module-admin.php to ensure proper load order.

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
