<?php
/**
 * Requirements Module for QR Trackr plugin.
 *
 * Handles plugin requirements checking and library management.
 *
 * @package QR_Trackr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( function_exists( 'qr_trackr_debug_log' ) ) {
	qr_trackr_debug_log( 'Loading module-requirements.php...' );
}

/**
 * Check if the QR code library is available.
 *
 * @return bool True if the library is available.
 */
function qr_trackr_check_library() {
	if ( class_exists( 'Endroid\QrCode\QrCode' ) ) {
		return true;
	}

	// Check if Composer autoload exists.
	$composer_autoload = plugin_dir_path( __DIR__ ) . 'vendor/autoload.php';
	if ( file_exists( $composer_autoload ) ) {
		require_once $composer_autoload;
		return class_exists( 'Endroid\QrCode\QrCode' );
	}

	return false;
}

/**
 * Check if pretty permalinks are enabled.
 *
 * @return bool True if pretty permalinks are enabled.
 */
function qr_trackr_check_permalinks() {
	static $permalink_status = null;

	// Return cached status if available.
	if ( null !== $permalink_status ) {
		return $permalink_status;
	}

	// Check if we've already logged this status.
	$last_logged  = get_option( 'qr_trackr_permalink_status_logged' );
	$current_time = time();

	// Only log if we haven't logged in the last hour.
	if ( ! $last_logged || ( $current_time - $last_logged ) > 3600 ) {
		$permalink_status = '' !== get_option( 'permalink_structure' );

		if ( false === $permalink_status ) {
			qr_trackr_debug_log( 'Pretty permalinks not enabled.' );
		}

		update_option( 'qr_trackr_permalink_status_logged', $current_time );
	} else {
		$permalink_status = '' !== get_option( 'permalink_structure' );
	}

	return $permalink_status;
}

/**
 * Check if the uploads directory is writable.
 *
 * @return bool True if uploads directory is writable.
 */
function qr_trackr_check_uploads() {
	$upload_dir = wp_upload_dir();
	return wp_is_writable( $upload_dir['basedir'] );
}

/**
 * Get all plugin requirements status.
 *
 * @return array Array of requirements status.
 */
function qr_trackr_check_requirements() {
	$requirements = array(
		'library'    => qr_trackr_check_library(),
		'permalinks' => qr_trackr_check_permalinks(),
		'uploads'    => qr_trackr_check_uploads(),
	);

	// Store requirements status.
	update_option( 'qr_trackr_requirements', $requirements );

	return $requirements;
}

/**
 * Display admin notice for missing requirements.
 *
 * @return void
 */
function qr_trackr_requirements_notice() {
	$requirements = qr_trackr_check_requirements();
	$missing      = array();

	if ( false === $requirements['library'] ) {
		$missing[] = esc_html__( 'QR code library (Endroid QR Code)', 'wp-qr-trackr' );
	}

	if ( false === $requirements['permalinks'] ) {
		$missing[] = sprintf(
			/* translators: 1: Permalink settings URL */
			__( 'Pretty permalinks (<a href="%s" target="_blank">update your permalink settings</a>)', 'wp-qr-trackr' ),
			esc_url( admin_url( 'options-permalink.php' ) )
		);
	}

	if ( false === $requirements['uploads'] ) {
		$missing[] = esc_html__( 'Writable uploads directory', 'wp-qr-trackr' );
	}

	if ( ! empty( $missing ) ) {
		?>
		<div class="notice notice-error">
			<p><strong><?php esc_html_e( 'QR Trackr:', 'wp-qr-trackr' ); ?></strong> <?php esc_html_e( 'The following requirements are missing:', 'wp-qr-trackr' ); ?></p>
			<ul>
				<?php foreach ( $missing as $item ) : ?>
					<li><?php echo wp_kses_post( $item ); ?></li>
					<?php if ( false !== strpos( $item, 'Pretty permalinks' ) ) : ?>
						<li style="margin-top:0; margin-bottom:1em; color:#666; font-size:90%; list-style:none;">
							<?php esc_html_e( '"Post name" is the most commonly used setting.', 'wp-qr-trackr' ); ?>
						</li>
					<?php endif; ?>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}
}
add_action( 'admin_notices', 'qr_trackr_requirements_notice' );

if ( function_exists( 'qr_trackr_debug_log' ) ) {
	qr_trackr_debug_log( 'Loaded module-requirements.php.' );
}
