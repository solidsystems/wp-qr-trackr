<?php
/**
 * Settings page template for QR Code Links.
 *
 * @package WP_QR_TRACKR
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>
<div class="wrap">
	<h1><?php esc_html_e( 'QR Code Settings', 'wp-qr-trackr' ); ?></h1>

	<form method="post" action="options.php">
		<?php
		// Output security fields.
		settings_fields( 'qr_trackr_settings' );

		// Output setting sections and their fields.
		do_settings_sections( 'qr_trackr_settings' );

		// Output save settings button.
		submit_button( __( 'Save Settings', 'wp-qr-trackr' ) );
		?>
	</form>
</div>
