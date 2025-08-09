<?php
/**
 * Add New QR Code page template.
 *
 * @package WP_QR_TRACKR
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Load Select2 directly in the template.
wp_enqueue_style( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0' );
wp_enqueue_script( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array( 'jquery' ), '4.1.0', true );

// Handle form submission.
if ( isset( $_POST['submit'] ) && isset( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'qr_trackr_add_new' ) ) {
	$destination_url  = isset( $_POST['destination_url'] ) ? esc_url_raw( wp_unslash( $_POST['destination_url'] ) ) : '';
	$custom_url       = isset( $_POST['custom_destination_url'] ) ? esc_url_raw( wp_unslash( $_POST['custom_destination_url'] ) ) : '';
	$selected_post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	$common_name      = isset( $_POST['common_name'] ) ? sanitize_text_field( wp_unslash( $_POST['common_name'] ) ) : '';
	$referral_code    = isset( $_POST['referral_code'] ) ? sanitize_text_field( wp_unslash( $_POST['referral_code'] ) ) : '';

	// Debug: Log form data.
    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only.
	error_log( 'QR Trackr: Form submission - destination_url: ' . $destination_url . ', custom_url: ' . $custom_url . ', post_id: ' . $selected_post_id );

	// Use custom URL if provided, otherwise use dropdown selection.
	if ( ! empty( $custom_url ) ) {
		$destination_url  = $custom_url;
		$selected_post_id = 0; // Clear post ID if custom URL is used.
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only.
		error_log( 'QR Trackr: Using custom URL: ' . $destination_url );
	} elseif ( ! empty( $selected_post_id ) ) {
		// If post ID is provided, get the post URL.
		$selected_post = get_post( $selected_post_id );
		if ( $selected_post ) {
			$destination_url = get_permalink( $selected_post_id );
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only.
			error_log( 'QR Trackr: Using post URL: ' . $destination_url . ' for post ID: ' . $selected_post_id );
		}
	}

	// Validate that we have a destination URL (either from custom URL or post selection).
    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only.
	error_log( 'QR Trackr: Final destination_url: ' . $destination_url . ', validation check: ' . ( ! empty( $destination_url ) ? 'PASS' : 'FAIL' ) );

	if ( ! empty( $destination_url ) ) {
		// Generate unique QR code.
		if ( function_exists( 'qr_trackr_generate_unique_qr_code' ) ) {
			$qr_code = qr_trackr_generate_unique_qr_code();
		} else {
			$qr_code = 'qr_' . wp_generate_password( 8, false );
		}

		error_log( 'QR Trackr: Generated QR code: ' . $qr_code );

		// Generate QR code image URL.
		$qr_code_url = '';

		// Debug: Check if function exists and Endroid library is available.
		error_log( 'QR Trackr: Checking if qrc_generate_qr_code function exists: ' . ( function_exists( 'qrc_generate_qr_code' ) ? 'YES' : 'NO' ) );
		error_log( 'QR Trackr: Checking if Endroid QR Code library is available: ' . ( class_exists( 'Endroid\QrCode\QrCode' ) ? 'YES' : 'NO' ) );
		error_log( 'QR Trackr: About to generate QR code for destination: ' . $destination_url );

		if ( function_exists( 'qrc_generate_qr_code' ) ) {
			// Debug logging for QR code generation.
			error_log( sprintf( 'QR Trackr: Generating QR code for URL: %s', $destination_url ) );

			// Generate QR code with consistent parameters.
			$qr_code_url = qrc_generate_qr_code(
				$destination_url,
				array(
					'size'             => 200,
					'margin'           => 10,
					'error_correction' => 'M',
					'foreground_color' => '#000000',
					'background_color' => '#ffffff',
				)
			);

			error_log( 'QR Trackr: Initial QR generation result: ' . ( is_wp_error( $qr_code_url ) ? 'ERROR: ' . $qr_code_url->get_error_message() : $qr_code_url ) );

			if ( is_wp_error( $qr_code_url ) ) {
				$error_message = $qr_code_url->get_error_message();
				error_log( sprintf( 'QR Trackr: QR code generation failed: %s', $error_message ) );
				// Don't set error message here, just log it and continue with empty URL.
				$qr_code_url = '';
			} else {
				error_log( sprintf( 'QR Trackr: QR code generated successfully: %s', $qr_code_url ) );

				// Debug: Check if the generated URL is accessible.
				$upload_dir = wp_upload_dir();
				$qr_dir     = $upload_dir['basedir'] . '/qr-codes';
				error_log(
					sprintf(
						'QR Trackr: Upload directory: %s, QR directory: %s, QR directory exists: %s, QR directory writable: %s',
						$upload_dir['basedir'],
						$qr_dir,
						file_exists( $qr_dir ) ? 'YES' : 'NO',
						is_writable( $qr_dir ) ? 'YES' : 'NO'
					)
				);
			}
		} else {
			error_log( 'QR Trackr: qrc_generate_qr_code function not found' );
			$qr_code_url = '';
		}

		// Insert into database.
		global $wpdb;
		$table_name = $wpdb->prefix . 'qr_trackr_links';

		// Debug: Log the QR code URL before insertion.
		error_log( 'QR Trackr: QR code URL before insertion: ' . $qr_code_url );

		$result = $wpdb->insert(
			$table_name,
			array(
				'destination_url' => $destination_url,
				'qr_code'         => $qr_code,
				'qr_code_url'     => $qr_code_url,
				'post_id'         => $post_id,
				'common_name'     => $common_name,
				'referral_code'   => $referral_code,
				'created_at'      => current_time( 'mysql' ),
				'updated_at'      => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s' )
		);

		if ( $result ) {
			$qr_id           = $wpdb->insert_id;
			$success_message = __( 'QR code created successfully!', 'wp-qr-trackr' );

			error_log( 'QR Trackr: Database insert successful, ID: ' . $qr_id );

			// Clear relevant caches after successful creation.
			wp_cache_delete( 'qr_trackr_all_links_admin', 'qr_trackr' );
			delete_transient( 'qrc_all_links' );

			// Get the created QR code details for display.
			$created_qr = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}qr_trackr_links WHERE id = %d",
					$qr_id
				),
				ARRAY_A
			);

			// Debug: Log the created QR code details.
			error_log( 'QR Trackr: Created QR code details: ' . wp_json_encode( $created_qr ) );

		} else {
			$error_message = __( 'Failed to create QR code. Please try again.', 'wp-qr-trackr' );
			error_log( 'QR Trackr: Database insert failed: ' . $wpdb->last_error );
		}
	} else {
		$error_message = __( 'Please either select a post/page or enter a custom URL.', 'wp-qr-trackr' );
	}
}
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Add New QR Code', 'wp-qr-trackr' ); ?></h1>

	<?php if ( isset( $success_message ) ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php echo esc_html( $success_message ); ?></p>
		</div>

		<style>
			.qr-success-display {
				display: flex;
				gap: 30px;
				align-items: flex-start;
			}

			.qr-success-image {
				flex: 0 0 200px;
			}

			.qr-success-details {
				flex: 1;
			}

			@media (max-width: 768px) {
				.qr-success-display {
					flex-direction: column;
					gap: 20px;
				}

				.qr-success-image {
					flex: none;
					text-align: center;
				}

				.qr-success-details {
					flex: none;
				}
			}
		</style>

		<?php if ( isset( $created_qr ) && $created_qr ) : ?>
			<div class="card" style="margin-top: 20px;">
				<h2><?php esc_html_e( 'QR Code Created Successfully!', 'wp-qr-trackr' ); ?></h2>

				<div class="qr-success-display">
					<!-- QR Code Image -->
					<div class="qr-success-image">
						<?php
						$display_qr_url = $created_qr['qr_code_url'];

						if ( ! empty( $display_qr_url ) ) :
							?>
							<img src="<?php echo esc_url( $display_qr_url ); ?>"
								alt="<?php esc_attr_e( 'QR Code', 'wp-qr-trackr' ); ?>"
								style="width: 200px; height: 200px; border: 1px solid #ddd; border-radius: 4px;" />
						<?php else : ?>
							<div
								style="width: 200px; height: 200px; border: 1px solid #ddd; border-radius: 4px; display: flex; align-items: center; justify-content: center; background: #f9f9f9;">
								<p style="text-align: center; color: #666;">
									<?php esc_html_e( 'QR Code Image', 'wp-qr-trackr' ); ?><br>
									<small><?php esc_html_e( '(Generated on first scan)', 'wp-qr-trackr' ); ?></small>
								</p>
							</div>
						<?php endif; ?>

						<div style="margin-top: 15px;">
							<?php if ( ! empty( $display_qr_url ) ) : ?>
								<a href="<?php echo esc_url( $display_qr_url ); ?>"
									download="qr-code-<?php echo esc_attr( $created_qr['qr_code'] ); ?>.png"
									class="button button-primary">
									<?php esc_html_e( 'Download QR Code', 'wp-qr-trackr' ); ?>
								</a>
							<?php endif; ?>
						</div>
					</div>

					<!-- QR Code Details -->
					<div class="qr-success-details">
						<table class="form-table" style="margin-top: 0;">
							<tr>
								<th scope="row" style="width: 150px;"><?php esc_html_e( 'QR Code:', 'wp-qr-trackr' ); ?></th>
								<td>
									<code style="font-size: 14px; background: #f9f9f9; padding: 8px; border-radius: 4px;">
																		<?php echo esc_html( $created_qr['qr_code'] ); ?>
																	</code>
								</td>
							</tr>

							<?php if ( ! empty( $created_qr['common_name'] ) ) : ?>
								<tr>
									<th scope="row"><?php esc_html_e( 'Common Name:', 'wp-qr-trackr' ); ?></th>
									<td><?php echo esc_html( $created_qr['common_name'] ); ?></td>
								</tr>
							<?php endif; ?>

							<?php if ( ! empty( $created_qr['referral_code'] ) ) : ?>
								<tr>
									<th scope="row"><?php esc_html_e( 'Referral Code:', 'wp-qr-trackr' ); ?></th>
									<td><code><?php echo esc_html( $created_qr['referral_code'] ); ?></code></td>
								</tr>
							<?php endif; ?>

							<?php if ( ! empty( $created_qr['post_id'] ) ) : ?>
								<?php
								$linked_post = get_post( $created_qr['post_id'] );
								if ( $linked_post ) :
									?>
									<tr>
										<th scope="row"><?php esc_html_e( 'Linked Post:', 'wp-qr-trackr' ); ?></th>
										<td>
											<strong><?php echo esc_html( $linked_post->post_title ); ?></strong>
											<br>
											<small><?php echo esc_html( ucfirst( $linked_post->post_type ) ); ?> •
												<a href="<?php echo esc_url( get_edit_post_link( $linked_post->ID ) ); ?>"
													target="_blank">
													<?php esc_html_e( 'Edit Post', 'wp-qr-trackr' ); ?>
												</a>
											</small>
										</td>
									</tr>
								<?php endif; ?>
							<?php endif; ?>

							<tr>
								<th scope="row"><?php esc_html_e( 'QR URL:', 'wp-qr-trackr' ); ?></th>
								<td>
									<a href="<?php echo esc_url( qr_trackr_get_redirect_url( $created_qr['qr_code'] ) ); ?>"
										target="_blank" style="word-break: break-all;">
										<?php echo esc_url( qr_trackr_get_redirect_url( $created_qr['qr_code'] ) ); ?>
									</a>
								</td>
							</tr>

							<tr>
								<th scope="row"><?php esc_html_e( 'Destination URL:', 'wp-qr-trackr' ); ?></th>
								<td>
									<a href="<?php echo esc_url( $created_qr['destination_url'] ); ?>" target="_blank"
										style="word-break: break-all;">
										<?php echo esc_url( $created_qr['destination_url'] ); ?>
									</a>
								</td>
							</tr>

							<tr>
								<th scope="row"><?php esc_html_e( 'Created:', 'wp-qr-trackr' ); ?></th>
								<td><?php echo esc_html( $created_qr['created_at'] ); ?></td>
							</tr>
						</table>

						<div style="margin-top: 20px;">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=qr-code-links' ) ); ?>" class="button">
								<?php esc_html_e( 'View All QR Codes', 'wp-qr-trackr' ); ?>
							</a>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=qr-code-add-new' ) ); ?>"
								class="button button-primary">
								<?php esc_html_e( 'Create Another QR Code', 'wp-qr-trackr' ); ?>
							</a>
						</div>
					</div>
				</div>
			</div>
		<?php endif; ?>
	<?php endif; ?>

	<?php if ( isset( $error_message ) ) : ?>
		<div class="notice notice-error is-dismissible">
			<p><?php echo esc_html( $error_message ); ?></p>
		</div>
	<?php endif; ?>

	<div class="card">
		<h2><?php esc_html_e( 'Create New QR Code', 'wp-qr-trackr' ); ?></h2>
		<p><?php esc_html_e( 'Create a new QR code that will redirect users to your specified destination URL.', 'wp-qr-trackr' ); ?>
		</p>

		<form method="post" action="">
			<?php wp_nonce_field( 'qr_trackr_add_new' ); ?>

			<!-- Hidden field to store selected post ID -->
			<input type="hidden" id="post_id" name="post_id" value="" />

			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="destination_url"><?php esc_html_e( 'Destination URL', 'wp-qr-trackr' ); ?></label>
					</th>
					<td>
						<select id="destination_url" name="destination_url" class="regular-text">
							<option value=""><?php esc_html_e( 'Select a post or page...', 'wp-qr-trackr' ); ?></option>
						</select>
						<p class="description">
							<?php esc_html_e( 'Search and select a post or page, or enter a custom URL below.', 'wp-qr-trackr' ); ?>
						</p>
						<br>
						<input type="url" id="custom_destination_url" name="custom_destination_url" class="regular-text"
							placeholder="<?php esc_attr_e( 'Or enter a custom URL...', 'wp-qr-trackr' ); ?>" />
						<p class="description">
							<?php esc_html_e( 'Enter a custom URL if you want to link to an external site or specific URL.', 'wp-qr-trackr' ); ?>
						</p>
						<div id="url-validation-error" style="color: #d63638; margin-top: 5px; display: none;">
							<?php esc_html_e( 'Please either select a post/page or enter a custom URL.', 'wp-qr-trackr' ); ?>
						</div>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="common_name"><?php esc_html_e( 'Common Name', 'wp-qr-trackr' ); ?></label>
					</th>
					<td>
						<input type="text" id="common_name" name="common_name" class="regular-text"
							value="<?php echo isset( $_POST['common_name'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_POST['common_name'] ) ) ) : ''; ?>" />
						<p class="description">
							<?php esc_html_e( 'A friendly name to help you identify this QR code (optional).', 'wp-qr-trackr' ); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="referral_code"><?php esc_html_e( 'Referral Code', 'wp-qr-trackr' ); ?></label>
					</th>
					<td>
						<input type="text" id="referral_code" name="referral_code" class="regular-text"
							value="<?php echo isset( $_POST['referral_code'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_POST['referral_code'] ) ) ) : ''; ?>" />
						<p class="description">
							<?php esc_html_e( 'A referral code for tracking and analytics (optional).', 'wp-qr-trackr' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<p class="submit">
				<input type="submit" name="submit" id="submit" class="button button-primary"
					value="<?php esc_attr_e( 'Create QR Code', 'wp-qr-trackr' ); ?>" />
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=qr-code-links' ) ); ?>" class="button">
					<?php esc_html_e( 'Cancel', 'wp-qr-trackr' ); ?>
				</a>
			</p>
		</form>
	</div>

	<div class="card">
		<h2><?php esc_html_e( 'How It Works', 'wp-qr-trackr' ); ?></h2>
		<ol>
			<li><?php esc_html_e( 'Enter the destination URL where you want users to be redirected.', 'wp-qr-trackr' ); ?>
			</li>
			<li><?php esc_html_e( 'Optionally add a common name and referral code for better organization.', 'wp-qr-trackr' ); ?>
			</li>
			<li><?php esc_html_e( 'Click "Create QR Code" to generate a unique QR code.', 'wp-qr-trackr' ); ?></li>
			<li><?php esc_html_e( 'The QR code will be available in your QR Codes list for download and sharing.', 'wp-qr-trackr' ); ?>
			</li>
		</ol>
	</div>
</div>

<script type="text/javascript">
	(function ($) {
		$(function () {
			// Initialize Select2 for the destination URL dropdown.
			$('#destination_url').select2({
				placeholder: '<?php echo esc_js( __( 'Search posts and pages...', 'wp-qr-trackr' ) ); ?>',
				allowClear: true,
				ajax: {
					url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
					type: 'POST',
					dataType: 'json',
					delay: 250,
					data: function (params) {
						return {
							action: 'qrc_search_posts',
							search: params.term,
							nonce: qr_trackr_ajax.nonce
						};
					},
					processResults: function (data) {
						var results = [];
						if (data.success && data.data && data.data.posts) {
							data.data.posts.forEach(function (post) {
								results.push({
									id: post.id, // Use post ID as the value
									text: post.title + ' (' + post.type + ')',
									url: post.url // Store URL as data attribute
								});
							});
						}
						return {
							results: results
						};
					},
					error: function (xhr, status, error) {
						console.error('Select2 AJAX error:', { xhr: xhr, status: status, error: error });
					},
					cache: true
				},
				minimumInputLength: 2
			});

			// Handle post selection from dropdown
			$('#destination_url').on('select2:select', function (e) {
				var data = e.params.data;
				$('#post_id').val(data.id); // Store the post ID
				$('#custom_destination_url').val(''); // Clear custom URL
				$('#url-validation-error').hide(); // Hide validation error
			});

			// Handle clearing the dropdown
			$('#destination_url').on('select2:clear', function (e) {
				$('#post_id').val(''); // Clear post ID
				$('#url-validation-error').hide(); // Hide validation error
			});

			// Handle custom URL input.
			$('#custom_destination_url').on('input', function () {
				if ($(this).val()) {
					$('#destination_url').val('').trigger('change');
					$('#post_id').val(''); // Clear post ID when custom URL is used
					$('#url-validation-error').hide(); // Hide validation error
				}
			});

			// Handle dropdown selection.
			$('#destination_url').on('change', function () {
				if ($(this).val()) {
					$('#custom_destination_url').val('');
					$('#url-validation-error').hide(); // Hide validation error
				}
			});

			// Handle form submission
			$('form').on('submit', function (e) {
				var destinationUrl = $('#destination_url').val();
				var customUrl = $('#custom_destination_url').val();
				var postId = $('#post_id').val();

				if (!destinationUrl && !customUrl) {
					e.preventDefault(); // Prevent form submission
					$('#url-validation-error').show(); // Show validation error
					return false;
				} else {
					$('#url-validation-error').hide(); // Hide validation error
				}
			});
		});
	})(window.jQuery);
</script>
