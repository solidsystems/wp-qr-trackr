<?php
/**
 * Edit QR Code Page Template
 *
 * @package WP_QR_TRACKR
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Get QR code data from global variable set by the admin function.
$qr_code = $GLOBALS['qr_code_data'] ?? null;

if ( ! $qr_code ) {
	wp_die( esc_html__( 'QR code data not found.', 'wp-qr-trackr' ) );
}

// Get current values.
$common_name             = esc_attr( $qr_code->common_name ?? '' );
$referral_code           = esc_attr( $qr_code->referral_code ?? '' );
$destination_url         = esc_url( $qr_code->destination_url ?? '' );
$qr_code_id              = esc_attr( $qr_code->qr_code ?? '' );
$created_at              = esc_html( $qr_code->created_at ?? '' );
$scans                   = absint( $qr_code->scans ?? $qr_code->access_count ?? 0 );
$previous_referral_codes = array();
if ( ! empty( $qr_code->metadata ) ) {
	$decoded = json_decode( (string) $qr_code->metadata, true );
	if ( is_array( $decoded ) && isset( $decoded['previous_referral_codes'] ) && is_array( $decoded['previous_referral_codes'] ) ) {
		$previous_referral_codes = $decoded['previous_referral_codes'];
	}
}
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Edit QR Code', 'wp-qr-trackr' ); ?></h1>

	<?php if ( isset( $error_message ) ) : ?>
		<div class="notice notice-error is-dismissible">
			<p><?php echo esc_html( $error_message ); ?></p>
		</div>
	<?php endif; ?>

	<div class="qr-edit-container">
		<div class="qr-edit-form">
			<form method="post" action="">
				<?php wp_nonce_field( 'edit_qr_code_' . $qr_code->id ); ?>

				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">
								<label for="common_name"><?php esc_html_e( 'Common Name', 'wp-qr-trackr' ); ?></label>
							</th>
							<td>
								<input type="text" id="common_name" name="common_name"
									value="<?php echo esc_attr( $common_name ); ?>" class="regular-text"
									placeholder="<?php esc_attr_e( 'Enter a friendly name', 'wp-qr-trackr' ); ?>" />
								<p class="description">
									<?php esc_html_e( 'A friendly name to help you identify this QR code.', 'wp-qr-trackr' ); ?>
								</p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label
									for="referral_code"><?php esc_html_e( 'Referral Code', 'wp-qr-trackr' ); ?></label>
							</th>
							<td>
								<input type="text" id="referral_code" name="referral_code"
									value="<?php echo esc_attr( $referral_code ); ?>" class="regular-text"
									placeholder="<?php esc_attr_e( 'Enter a referral code', 'wp-qr-trackr' ); ?>" />
								<p class="description">
									<?php esc_html_e( 'A referral code for tracking and analytics.', 'wp-qr-trackr' ); ?>
								</p>
								<?php if ( ! empty( $previous_referral_codes ) ) : ?>
									<p class="description" style="margin-top:8px;">
										<strong><?php esc_html_e( 'Previous referral codes:', 'wp-qr-trackr' ); ?></strong>
									</p>
									<ul style="margin:4px 0 0 0; padding-left:18px;">
										<?php foreach ( array_reverse( $previous_referral_codes ) as $prev ) : ?>
											<li>
												<code><?php echo esc_html( $prev['code'] ?? '' ); ?></code>
												<?php if ( ! empty( $prev['changed_at'] ) ) : ?>
													<small style="color:#666;">&nbsp;<?php echo esc_html( $prev['changed_at'] ); ?></small>
												<?php endif; ?>
											</li>
										<?php endforeach; ?>
									</ul>
								<?php endif; ?>
							</td>
						</tr>

		<tr>
			<th scope="row"><?php esc_html_e( 'Destination URL', 'wp-qr-trackr' ); ?></th>
			<td>
				<input type="url" name="destination_url" value="<?php echo esc_attr( $destination_url ); ?>" class="regular-text" />
				<p class="description">
					<?php esc_html_e( 'Update the destination URL for this QR code. External URLs are allowed.', 'wp-qr-trackr' ); ?>
				</p>
			</td>
		</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'QR Code', 'wp-qr-trackr' ); ?></th>
							<td>
								<code><?php echo esc_html( $qr_code_id ); ?></code>
								<p class="description">
									<?php esc_html_e( 'The unique QR code identifier.', 'wp-qr-trackr' ); ?>
								</p>
							</td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'Created', 'wp-qr-trackr' ); ?></th>
							<td>
								<?php echo esc_html( $created_at ); ?>
							</td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'Total Scans', 'wp-qr-trackr' ); ?></th>
							<td>
								<?php echo number_format( $scans ); ?>
							</td>
						</tr>
					</tbody>
				</table>

				<div class="qr-edit-actions">
					<?php submit_button( __( 'Update QR Code', 'wp-qr-trackr' ), 'primary', 'submit', false ); ?>

					<a href="<?php echo esc_url( admin_url( 'admin.php?page=qr-code-links' ) ); ?>" class="button">
						<?php esc_html_e( 'Cancel', 'wp-qr-trackr' ); ?>
					</a>
				</div>
			</form>
		</div>

		<div class="qr-edit-preview">
			<h3><?php esc_html_e( 'QR Code Preview', 'wp-qr-trackr' ); ?></h3>

			<?php if ( ! empty( $qr_code->qr_code_url ) ) : ?>
				<div class="qr-code-image">
					<img src="<?php echo esc_url( $qr_code->qr_code_url ); ?>"
						alt="<?php esc_attr_e( 'QR Code', 'wp-qr-trackr' ); ?>" style="max-width: 200px; height: auto;" />
				</div>
			<?php else : ?>
				<div class="qr-code-placeholder">
					<p><?php esc_html_e( 'QR code image not available.', 'wp-qr-trackr' ); ?></p>
				</div>
			<?php endif; ?>

			<div class="qr-code-info">
				<p><strong><?php esc_html_e( 'Tracking URL:', 'wp-qr-trackr' ); ?></strong></p>
				<code><?php echo esc_url( qr_trackr_get_redirect_url( $qr_code_id ) ); ?></code>

				<p><strong><?php esc_html_e( 'Destination URL:', 'wp-qr-trackr' ); ?></strong></p>
				<code><?php echo esc_url( $destination_url ); ?></code>
			</div>
		</div>
	</div>
</div>

<style>
	.qr-edit-container {
		display: flex;
		gap: 30px;
		margin-top: 20px;
	}

	.qr-edit-form {
		flex: 2;
	}

	.qr-edit-preview {
		flex: 1;
		background: #f9f9f9;
		padding: 20px;
		border-radius: 5px;
		border: 1px solid #ddd;
	}

	.qr-edit-preview h3 {
		margin-top: 0;
		margin-bottom: 20px;
	}

	.qr-code-image {
		text-align: center;
		margin-bottom: 20px;
	}

	.qr-code-placeholder {
		text-align: center;
		padding: 40px 20px;
		background: #fff;
		border: 2px dashed #ddd;
		border-radius: 5px;
		margin-bottom: 20px;
	}

	.qr-code-info code {
		display: block;
		word-break: break-all;
		margin-bottom: 15px;
		padding: 8px;
		background: #fff;
		border: 1px solid #ddd;
		border-radius: 3px;
	}

	.qr-edit-actions {
		margin-top: 20px;
		padding-top: 20px;
		border-top: 1px solid #ddd;
	}

	.qr-edit-actions .button {
		margin-right: 10px;
	}

	@media (max-width: 768px) {
		.qr-edit-container {
			flex-direction: column;
		}

		.qr-edit-preview {
			order: -1;
		}
	}
</style>
