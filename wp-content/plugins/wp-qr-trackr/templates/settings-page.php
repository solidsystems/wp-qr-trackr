<?php
/**
 * Settings page template for QR Code Links.
 *
 * @package WP_QR_TRACKR
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Debug output.
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
	echo '<!-- QR Trackr: Settings page template loaded -->';
}

?>
<div class="wrap">
	<h1><?php esc_html_e( 'QR Code Settings', 'wp-qr-trackr' ); ?></h1>
	
	<?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
	<div class="notice notice-info">
		<p><strong>Debug Info:</strong> Settings page template is loading correctly.</p>
	</div>
	<?php endif; ?>

	<?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
	<div class="notice notice-warning">
		<p><strong>Debug Mode:</strong> Using simplified settings form for troubleshooting.</p>
	</div>
	<?php endif; ?>

	<form method="post" action="options.php">
		<?php
		// Output security fields.
		settings_fields( 'qr_trackr_settings' );
		?>
		
		<table class="form-table">
			<tr>
				<th scope="row">QR Code Size</th>
				<td>
					<select name="qr_trackr_qr_size">
						<option value="small" <?php selected( get_option( 'qr_trackr_qr_size', 'medium' ), 'small' ); ?>>Small</option>
						<option value="medium" <?php selected( get_option( 'qr_trackr_qr_size', 'medium' ), 'medium' ); ?>>Medium</option>
						<option value="large" <?php selected( get_option( 'qr_trackr_qr_size', 'medium' ), 'large' ); ?>>Large</option>
					</select>
					<p class="description">Choose the default size for generated QR codes.</p>
				</td>
			</tr>
			<tr>
				<th scope="row">Tracking Enabled</th>
				<td>
					<label>
						<input type="checkbox" name="qr_trackr_tracking_enabled" value="1" <?php checked( get_option( 'qr_trackr_tracking_enabled', '1' ), '1' ); ?> />
						Enable QR code scan tracking
					</label>
					<p class="description">Track when QR codes are scanned and accessed.</p>
				</td>
			</tr>
		</table>
		
		<?php submit_button( __( 'Save Settings', 'wp-qr-trackr' ) ); ?>
	</form>
</div> 