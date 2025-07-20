<?php
/**
 * Admin page template for QR Code Links.
 *
 * @package WP_QR_TRACKR
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'QR Code Links', 'wp-qr-trackr' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=qr-code-add-new' ) ); ?>" class="page-title-action">
		<?php esc_html_e( 'Add New', 'wp-qr-trackr' ); ?>
	</a>
	<hr class="wp-header-end">

	<div id="poststuff">
		<div id="post-body" class="metabox-holder">
			<div id="post-body-content">
				<div class="meta-box-sortables ui-sortable">
					<?php
					// Display the list table.
					$list_table->display();
					?>
				</div>
			</div>
		</div>
		<br class="clear">
	</div>
</div>

<!-- AJAX variables for QR Trackr -->
<script type="text/javascript">
var qr_trackr_ajax = {
	ajaxurl: '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>',
	nonce: '<?php echo esc_js( wp_create_nonce( 'qr_trackr_nonce' ) ); ?>'
};
</script>

<!-- QR Trackr Admin JavaScript -->
<script type="text/javascript" src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . '../assets/qrc-admin.js' ); ?>"></script>

