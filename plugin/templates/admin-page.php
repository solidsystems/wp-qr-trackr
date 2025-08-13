<?php
/**
 * Admin list page template for QR Code Links.
 *
 * @package WP_QR_TRACKR
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Expect $list_table to be prepared by caller (qrc_admin_page()).
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'QR Code Links', 'wp-qr-trackr' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=qr-code-add-new' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'wp-qr-trackr' ); ?></a>
	<hr class="wp-header-end" />

	<form method="get">
		<input type="hidden" name="page" value="qr-code-links" />
		<?php
			$list_table->search_box( __( 'Search QR Codes', 'wp-qr-trackr' ), 'qr-code' );
			$list_table->display();
		?>
	</form>
</div>
