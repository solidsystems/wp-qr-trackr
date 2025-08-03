<?php
/**
 * Admin page template for QR Trackr plugin.
 *
 * @package WP_QR_Trackr
 * @since 1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

	// Debug logging using Query Monitor.
if ( function_exists( 'do_action' ) ) {
	do_action( 'qm_debug', 'QR Trackr: Admin page template loaded' );
}

// Check user capabilities.
if ( ! current_user_can( 'manage_options' ) ) {
	do_action( 'qm_error', 'QR Trackr: User does not have manage_options capability' );
	wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wp-qr-trackr' ) );
}

// Ensure the list table is available.
if ( ! isset( $list_table ) || ! is_object( $list_table ) ) {
	do_action( 'qm_error', 'QR Trackr: List table not available', array( 'list_table' => $list_table ?? 'not set' ) );
	wp_die( esc_html__( 'QR Trackr: List table not available.', 'wp-qr-trackr' ) );
}

	do_action( 'qm_debug', 'QR Trackr: About to display admin page content' );
?>

<div class="wrap">
	<h1 class="wp-heading-inline">
		<?php esc_html_e( 'QR Code Links', 'wp-qr-trackr' ); ?>
	</h1>

	<a href="<?php echo esc_url( admin_url( 'admin.php?page=qr-code-add-new' ) ); ?>" class="page-title-action">
		<?php esc_html_e( 'Add New', 'wp-qr-trackr' ); ?>
	</a>

	<hr class="wp-header-end">

	<?php
	// Display admin notices.
	if ( function_exists( 'qrc_admin_notices' ) ) {
		do_action( 'qm_debug', 'QR Trackr: Calling qrc_admin_notices' );
		qrc_admin_notices();
	} else {
		do_action( 'qm_warning', 'QR Trackr: qrc_admin_notices function not found' );
	}
	?>

	<div id="qr-trackr-admin-page">
		<form method="post" action="">
			<?php
			// Add nonce for security.
			wp_nonce_field( 'qr_trackr_admin_action', 'qr_trackr_nonce' );

			// Display the list table.
			do_action( 'qm_debug', 'QR Trackr: About to display list table' );
			try {
				$list_table->display();
				do_action( 'qm_debug', 'QR Trackr: List table displayed successfully' );
			} catch ( Exception $e ) {
				do_action( 'qm_error', 'QR Trackr: Error displaying list table', array( 'exception' => $e ) );
				echo '<p>Error displaying QR codes list. Please check the logs.</p>';
			}
			?>
		</form>
	</div>

	<div class="qr-trackr-admin-info">
		<h3><?php esc_html_e( 'QR Code Links Management', 'wp-qr-trackr' ); ?></h3>
		<p>
			<?php esc_html_e( 'Manage your QR code links here. You can view, edit, delete, and track the performance of your QR codes.', 'wp-qr-trackr' ); ?>
		</p>

		<h4><?php esc_html_e( 'Features:', 'wp-qr-trackr' ); ?></h4>
		<ul>
			<li><?php esc_html_e( 'Create QR codes for any URL', 'wp-qr-trackr' ); ?></li>
			<li><?php esc_html_e( 'Track clicks and analytics', 'wp-qr-trackr' ); ?></li>
		</ul>

		<p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=qr-code-settings' ) ); ?>"
				class="button button-secondary">
				<?php esc_html_e( 'Settings', 'wp-qr-trackr' ); ?>
			</a>
		</p>
	</div>
</div>

<?php
do_action( 'qm_debug', 'QR Trackr: Admin page template completed' );
?>

<style>
	.qr-trackr-admin-info {
		margin-top: 20px;
		padding: 15px;
		background: #fff;
		border: 1px solid #ccd0d4;
		border-radius: 4px;
	}

	.qr-trackr-admin-info h3 {
		margin-top: 0;
		color: #23282d;
	}

	.qr-trackr-admin-info h4 {
		margin-bottom: 10px;
		color: #23282d;
	}

	.qr-trackr-admin-info ul {
		margin-left: 20px;
	}

	.qr-trackr-admin-info li {
		margin-bottom: 5px;
	}

	#qr-trackr-admin-page {
		margin-bottom: 20px;
	}
</style>
