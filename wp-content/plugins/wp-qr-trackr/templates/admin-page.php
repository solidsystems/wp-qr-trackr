<?php
/**
 * Admin page template for QR Trackr plugin.
 *
 * @package WP_QR_TRACKR
 * @since 1.0.0
 */

// Prevent direct access.
if (!defined('ABSPATH')) {
	exit;
}

// Check user capabilities.
if (!current_user_can('manage_options')) {
	wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'wp-qr-trackr'));
}

// Get the list table instance.
global $list_table;

// Handle bulk actions.
$action = $list_table->current_action();
if ($action) {
	$nonce = sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'] ?? ''));
	if (!wp_verify_nonce($nonce, 'bulk-' . $list_table->_args['plural'])) {
		wp_die(esc_html__('Security check failed.', 'wp-qr-trackr'));
	}

	$ids = array();
	if (isset($_REQUEST['qr_code_ids']) && is_array($_REQUEST['qr_code_ids'])) {
		$ids = array_map('absint', $_REQUEST['qr_code_ids']);
	}

	switch ($action) {
		case 'delete':
			if (!empty($ids)) {
				foreach ($ids as $id) {
					// Delete QR code logic here.
					do_action('qr_trackr_delete_qr_code', $id);
				}
				$message = sprintf(
					/* translators: %d: number of QR codes deleted */
					_n('%d QR code deleted successfully.', '%d QR codes deleted successfully.', count($ids), 'wp-qr-trackr'),
					count($ids)
				);
			}
			break;
	}
}
?>

<div class="wrap">
	<h1 class="wp-heading-inline">
		<?php esc_html_e('QR Code Links', 'wp-qr-trackr'); ?>
	</h1>
	<a href="<?php echo esc_url(admin_url('admin.php?page=qr-code-add-new')); ?>" class="page-title-action">
		<?php esc_html_e('Add New', 'wp-qr-trackr'); ?>
	</a>
	<hr class="wp-header-end">

	<?php if (!empty($message)): ?>
		<div class="notice notice-success is-dismissible">
			<p><?php echo esc_html($message); ?></p>
		</div>
	<?php endif; ?>

	<form method="post">
		<?php
		// Add nonce field for security.
		wp_nonce_field('bulk-' . $list_table->_args['plural']);

		// Display the list table.
		$list_table->display();
		?>
	</form>
</div>

<script type="text/javascript">
	jQuery(document).ready(function ($) {
		// Handle QR code image clicks to show full size.
		$('.qr-code-image').on('click', function () {
			var imgSrc = $(this).attr('src');
			if (imgSrc) {
				var newWindow = window.open(imgSrc, 'QRCode', 'width=400,height=400,scrollbars=yes,resizable=yes');
				if (newWindow) {
					newWindow.focus();
				}
			}
		});

		// Handle delete confirmations.
		$('.delete-qr-code').on('click', function (e) {
			if (!confirm('<?php echo esc_js(__('Are you sure you want to delete this QR code?', 'wp-qr-trackr')); ?>')) {
				e.preventDefault();
				return false;
			}
		});

		// Handle bulk delete confirmations.
		$('#doaction, #doaction2').on('click', function (e) {
			var action = $(this).prev('select').val();
			if (action === 'delete') {
				if (!confirm('<?php echo esc_js(__('Are you sure you want to delete the selected QR codes?', 'wp-qr-trackr')); ?>')) {
					e.preventDefault();
					return false;
				}
			}
		});
	});
</script>
