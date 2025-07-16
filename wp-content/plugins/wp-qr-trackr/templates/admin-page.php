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