<?php
/**
 * Admin module for QR Trackr plugin.
 *
 * Handles admin menu, meta boxes, and admin page rendering for QR code management.
 *
 * @package QR_Trackr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( function_exists( 'qr_trackr_debug_log' ) ) {
	qr_trackr_debug_log( 'Loading module-admin.php...' );
}

// Load the list table class.
require_once QR_TRACKR_PLUGIN_DIR . 'includes/class-qr-trackr-list-table.php';

/**
 * Initialize admin functionality
 */
function qr_trackr_admin_init() {
	add_action( 'admin_menu', 'qr_trackr_add_menu' );
	add_action( 'admin_enqueue_scripts', 'qr_trackr_admin_enqueue_scripts' );
}

/**
 * Add admin menu items
 */
function qr_trackr_add_menu() {
	add_menu_page(
		__( 'QR Trackr', 'wp-qr-trackr' ),
		__( 'QR Trackr', 'wp-qr-trackr' ),
		'manage_options',
		'qr-trackr',
		'qr_trackr_admin_page',
		'dashicons-qrcode',
		30
	);

	add_submenu_page(
		'qr-trackr',
		__( 'Stats', 'wp-qr-trackr' ),
		__( 'Stats', 'wp-qr-trackr' ),
		'manage_options',
		'qr-trackr-stats',
		'qr_trackr_stats_page'
	);

	add_submenu_page(
		'qr-trackr',
		__( 'Settings', 'wp-qr-trackr' ),
		__( 'Settings', 'wp-qr-trackr' ),
		'manage_options',
		'qr-trackr-settings',
		'qr_trackr_settings_page'
	);
}

/**
 * Enqueue admin scripts and styles for QR Trackr.
 *
 * @param string $hook The current admin page hook.
 */
function qr_trackr_admin_enqueue_scripts( $hook ) {
	if ( strpos( $hook, 'qr-trackr' ) === false ) {
		return;
	}

	wp_enqueue_style(
		'qr-trackr-admin',
		QR_TRACKR_PLUGIN_URL . 'assets/css/admin.css',
		array(),
		QR_TRACKR_VERSION
	);

	// Enqueue jQuery UI Autocomplete and its theme.
	wp_enqueue_script( 'jquery-ui-autocomplete' );
	wp_enqueue_style( 'jquery-ui-theme', '//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css', array(), QR_TRACKR_VERSION );
	wp_enqueue_style( 'jquery-ui-autocomplete', '', array(), QR_TRACKR_VERSION );

	// Enqueue Select2 for filterable post select.
	wp_enqueue_style( 'select2', '//cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), QR_TRACKR_VERSION );
	wp_enqueue_script( 'select2', '//cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array( 'jquery' ), QR_TRACKR_VERSION, true );

	wp_enqueue_script(
		'qr-trackr-admin',
		QR_TRACKR_PLUGIN_URL . 'assets/js/admin.js',
		array( 'jquery', 'jquery-ui-autocomplete', 'select2' ),
		QR_TRACKR_VERSION,
		true
	);
	wp_enqueue_style( 'jquery-ui-autocomplete' );

	// Localize admin script for general admin actions.
	wp_localize_script(
		'qr-trackr-admin',
		'qrTrackrAdmin',
		array(
			'nonce'           => wp_create_nonce( 'qr_trackr_nonce' ),
			'editNonce'       => wp_create_nonce( 'qr_trackr_edit' ),
			'deleteNonce'     => wp_create_nonce( 'qr_trackr_delete' ),
			'regenerateNonce' => wp_create_nonce( 'qr_trackr_regenerate' ),
			'ajaxurl'         => admin_url( 'admin-ajax.php' ),
			'i18n'            => array(
				'confirmDelete'     => esc_html__( 'Are you sure you want to delete this QR code?', 'wp-qr-trackr' ),
				'confirmRegenerate' => esc_html__( 'Are you sure you want to regenerate this QR code?', 'wp-qr-trackr' ),
			),
		)
	);

	// Localize Select2 script for AJAX post search.
	wp_localize_script(
		'select2',
		'qrTrackrSelect2',
		array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'qr_trackr_nonce' ),
		)
	);
}

/**
 * Render the main admin page
 */
function qr_trackr_admin_page() {
	// Get all QR codes.
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$qr_codes = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i ORDER BY created_at DESC', $table_name ) );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'QR Trackr', 'wp-qr-trackr' ); ?></h1>
		<p style="font-size:1.1em; max-width:600px; margin-bottom:24px; background:#f8f8ff; border-left:4px solid #6c63ff; padding:12px 18px; border-radius:6px;">
			<strong>Welcome!</strong> <?php esc_html_e( 'QR Trackr makes it easy to create, print, and track QR codes for your website. No tech skills needed—just pick a page or link, and you\'re ready to go!', 'wp-qr-trackr' ); ?>
		</p>
		<div id="qr-trackr-message"></div>
		<div class="qr-trackr-create-form">
			<h2 style="margin-top:0;"><?php esc_html_e( 'Create a New QR Code', 'wp-qr-trackr' ); ?></h2>
			<p style="margin-bottom:10px; color:#444;"><?php esc_html_e( 'Choose what you want your QR code to point to. You can always change it later!', 'wp-qr-trackr' ); ?></p>
			<form id="qr-trackr-create-form" method="post">
				<?php wp_nonce_field( 'qr_trackr_nonce', 'qr_trackr_nonce' ); ?>
				<div class="form-field">
					<label for="destination_type"><strong><?php esc_html_e( 'Where should your QR code go?', 'wp-qr-trackr' ); ?></strong></label>
					<select name="destination_type" id="destination_type" required>
						<option value="post"><?php esc_html_e( 'A page or post on my site', 'wp-qr-trackr' ); ?></option>
						<option value="external"><?php esc_html_e( 'An external website', 'wp-qr-trackr' ); ?></option>
						<option value="custom"><?php esc_html_e( 'A custom link', 'wp-qr-trackr' ); ?></option>
					</select>
				</div>
				<div class="form-field post-select" style="display: none;">
					<label for="post_id"><?php esc_html_e( 'Pick a page or post', 'wp-qr-trackr' ); ?></label>
					<select name="post_id" id="post_id" style="width:100%">
						<option value=""><?php esc_html_e( 'Start typing to search…', 'wp-qr-trackr' ); ?></option>
					</select>
					<div id="post_search_results" class="qr-trackr-search-results" style="position:relative;"></div>
				</div>
				<div class="form-field external-url" style="display: none;">
					<label for="external_url"><?php esc_html_e( 'Website address (URL)', 'wp-qr-trackr' ); ?></label>
					<input type="url" name="external_url" id="external_url" placeholder="https://">
					<small style="color:#888;">Example: https://yourbusiness.com/menu</small>
				</div>
				<div class="form-field custom-url" style="display: none;">
					<label for="custom_url"><?php esc_html_e( 'Custom link', 'wp-qr-trackr' ); ?></label>
					<input type="url" name="custom_url" id="custom_url" placeholder="https://">
					<small style="color:#888;">Any valid web address</small>
				</div>
				<div class="form-field">
					<button type="submit" class="button button-primary" style="font-size:1.1em; padding:8px 24px; background:#6c63ff; border:none; border-radius:4px;">🎉 <?php esc_html_e( 'Create QR Code', 'wp-qr-trackr' ); ?></button>
				</div>
			</form>
		</div>
		<div class="qr-trackr-list">
			<h2 style="margin-top:32px;"><?php esc_html_e( 'Your QR Codes', 'wp-qr-trackr' ); ?></h2>
			<p style="color:#444; margin-bottom:10px;"><?php esc_html_e( 'Download, print, or update your QR codes anytime. Click "Regenerate" if you change the destination.', 'wp-qr-trackr' ); ?></p>
			<?php if ( empty( $qr_codes ) ) : ?>
				<p style="color:#888; font-style:italic;"><?php esc_html_e( 'No QR codes yet. Create your first one above!', 'wp-qr-trackr' ); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th style="width:40px; padding: 4px 8px; font-family:monospace; text-align:right;">ID</th>
							<th><?php esc_html_e( 'QR Code', 'wp-qr-trackr' ); ?></th>
							<th><?php esc_html_e( 'Destination', 'wp-qr-trackr' ); ?></th>
							<th><?php esc_html_e( 'Created', 'wp-qr-trackr' ); ?></th>
							<th><?php esc_html_e( 'Scans', 'wp-qr-trackr' ); ?></th>
							<th><?php esc_html_e( 'Last Scan', 'wp-qr-trackr' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'wp-qr-trackr' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $qr_codes as $qr_code ) : ?>
							<tr>
								<td style="width:40px; padding: 4px 8px; font-family:monospace; text-align:right;"><?php echo esc_html( $qr_code->id ); ?></td>
								<td>
									<?php
									$qr_urls = qr_trackr_generate_qr_image_for_link( $qr_code->id );
									if ( ! empty( $qr_urls['png'] ) ) :
										?>
										<img src="<?php echo esc_url( $qr_urls['png'] ); ?>" alt="QR Code" width="100" style="display:block; margin-bottom:4px; border-radius:8px; box-shadow:0 2px 8px #eee;">
										<span style="font-size:12px; color:#555;">
											<a href="<?php echo esc_url( $qr_urls['png'] ); ?>" download title="Download PNG" style="margin-right:8px; text-decoration:none;">
												<span class="dashicons dashicons-media-default" style="vertical-align:middle;"></span> PNG
											</a>
											<a href="<?php echo esc_url( $qr_urls['svg'] ); ?>" download title="Download SVG" style="text-decoration:none;">
												<span class="dashicons dashicons-media-code" style="vertical-align:middle;"></span> SVG
											</a>
										</span>
									<?php else : ?>
										<span class="qr-trackr-error" style="color:#c00;">😕 <?php esc_html_e( 'QR image not available', 'wp-qr-trackr' ); ?></span>
									<?php endif; ?>
								</td>
								<td>
									<a href="<?php echo esc_url( $qr_code->destination_url ); ?>" target="_blank">
										<?php echo esc_html( $qr_code->destination_url ); ?>
									</a>
								</td>
								<td><?php echo esc_html( $qr_code->created_at ); ?></td>
								<td><?php echo esc_html( $qr_code->access_count ); ?></td>
								<td><?php echo $qr_code->last_accessed ? esc_html( $qr_code->last_accessed ) : esc_html__( 'Never', 'wp-qr-trackr' ); ?></td>
								<td>
									<button class="button edit-qr" data-id="<?php echo esc_attr( $qr_code->id ); ?>">
										<?php esc_html_e( 'Edit', 'wp-qr-trackr' ); ?>
									</button>
									<button class="button regenerate-qr" data-id="<?php echo esc_attr( $qr_code->id ); ?>">
										<?php esc_html_e( 'Regenerate', 'wp-qr-trackr' ); ?>
									</button>
									<button class="button delete-qr" data-id="<?php echo esc_attr( $qr_code->id ); ?>">
										<?php esc_html_e( 'Delete', 'wp-qr-trackr' ); ?>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
	</div>
	<?php
	echo '<script>
    jQuery(document).ready(function($) {
        function toggleFields() {
            var type = $("#destination_type").val();
            $(".post-select").hide();
            $(".external-url").hide();
            $(".custom-url").hide();
            if (type === "post") {
                $(".post-select").show();
            } else if (type === "external") {
                $(".external-url").show();
            } else if (type === "custom") {
                $(".custom-url").show();
            }
        }

        // Initial state
        toggleFields();

        // On change
        $("#destination_type").on("change", toggleFields);

        // AJAX search for posts
        $("#post_id").select2({
            ajax: {
                url: qrTrackrSelect2.ajaxurl,
                dataType: "json",
                delay: 250,
                data: function(params) {
                    return {
                        action: "qr_trackr_search_posts",
                        s: params.term,
                        nonce: qrTrackrSelect2.nonce,
                    };
                },
                processResults: function(data) {
                    return {
                        results: data.data,
                    };
                },
                cache: true,
            },
            minimumInputLength: 2,
        });
    });
    </script>';
}

/**
 * Render settings page.
 */
function qr_trackr_settings_page() {
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'QR Trackr Settings', 'wp-qr-trackr' ); ?></h1>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'qr_trackr_settings_group' );
			do_settings_sections( 'qr-trackr-settings' );
			submit_button();
			?>
		</form>
	</div>
	<?php
}

/**
 * Adds QR Code column to post lists
 *
 * @param array $columns Existing columns.
 * @return array Modified columns.
 */
function qr_trackr_add_column( $columns ) {
	$columns['qr_code'] = __( 'QR Code', 'wp-qr-trackr' );
	return $columns;
}

/**
 * Content for the QR Code column
 *
 * @param string $column  The column name.
 * @param int    $post_id The post ID.
 */
function qr_trackr_column_content( $column, $post_id ) {
	if ( 'qr_code' === $column ) {
		global $wpdb;
		$table = $wpdb->prefix . 'qr_trackr_links';
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$link = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE post_id = %d', $table, $post_id ) );

		if ( $link ) {
			$qr_urls = qr_trackr_generate_qr_image_for_link( $link->id );
			if ( ! empty( $qr_urls['png'] ) ) :
				?>
				<img src="<?php echo esc_url( $qr_urls['png'] ); ?>" alt="QR Code" width="100" style="display:block; margin-bottom:4px; border-radius:8px; box-shadow:0 2px 8px #eee;">
				<span style="font-size:12px; color:#555;">
					<a href="<?php echo esc_url( $qr_urls['png'] ); ?>" download title="Download PNG" style="margin-right:8px; text-decoration:none;">
						<span class="dashicons dashicons-media-default" style="vertical-align:middle;"></span> PNG
					</a>
					<a href="<?php echo esc_url( $qr_urls['svg'] ); ?>" download title="Download SVG" style="text-decoration:none;">
						<span class="dashicons dashicons-media-code" style="vertical-align:middle;"></span> SVG
					</a>
				</span>
				<?php
			else :
				?>
				<span class="qr-trackr-error" style="color:#c00;">😕 <?php esc_html_e( 'QR image not available', 'wp-qr-trackr' ); ?></span>
				<?php
			endif;
		} else {
			echo esc_html__( 'No QR code', 'wp-qr-trackr' );
		}
	}
}

/**
 * Handle permalink changes to update QR codes
 *
 * @param string $old_value Old permalink.
 * @param string $new_value New permalink.
 */
function qr_trackr_handle_permalink_change( $old_value, $new_value ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';
	$wpdb->update(
		$table_name,
		array( 'destination_url' => $new_value ),
		array( 'destination_url' => $old_value ),
		array( '%s' ),
		array( '%s' )
	);
}

/**
 * Register meta box for QR codes
 */
function qr_trackr_add_meta_box() {
	add_meta_box(
		'qr_trackr_meta_box',
		__( 'QR Code', 'wp-qr-trackr' ),
		'qr_trackr_render_meta_box',
		array( 'post', 'page' ),
		'side'
	);
}

/**
 * Render the QR code meta box
 *
 * @param WP_Post $post The post object.
 */
function qr_trackr_render_meta_box( $post ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';
	$link       = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE post_id = %d", $post->ID ) );

	if ( $link ) {
		$qr_urls = qr_trackr_generate_qr_image_for_link( $link->id );
		if ( ! empty( $qr_urls['png'] ) ) {
			echo '<img src="' . esc_url( $qr_urls['png'] ) . '" alt="QR Code" width="150" style="display:block; margin: 0 auto 8px; border-radius:8px; box-shadow:0 2px 8px #eee;">';
			echo '<p style="text-align:center;"><a href="' . esc_url( $qr_urls['png'] ) . '" download class="button" style="margin-right:8px;">Download PNG</a> <a href="' . esc_url( $qr_urls['svg'] ) . '" download class="button">Download SVG</a></p>';
		} else {
			echo '<p>' . esc_html__( 'QR code could not be generated.', 'wp-qr-trackr' ) . '</p>';
		}
	} else {
		echo '<p>' . esc_html__( 'Save this post to generate a QR code.', 'wp-qr-trackr' ) . '</p>';
	}
}

/**
 * Register settings.
 */
function qr_trackr_register_settings() {
	register_setting( 'qr_trackr_settings_group', 'qr_trackr_options' );
}

/**
 * Render stats page.
 */
function qr_trackr_stats_page() {
	global $wpdb;
	$table = $wpdb->prefix . 'qr_trackr_links';
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$links = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i ORDER BY created_at DESC', $table ) );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'QR Code Stats', 'wp-qr-trackr' ); ?></h1>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'ID', 'wp-qr-trackr' ); ?></th>
					<th><?php esc_html_e( 'Destination URL', 'wp-qr-trackr' ); ?></th>
					<th><?php esc_html_e( 'Created At', 'wp-qr-trackr' ); ?></th>
					<th><?php esc_html_e( 'Access Count', 'wp-qr-trackr' ); ?></th>
					<th><?php esc_html_e( 'Last Accessed', 'wp-qr-trackr' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $links as $link ) :
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					$scans = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i WHERE link_id = %d ORDER BY scanned_at DESC', $table_name, $link->id ) );
					?>
					<tr>
						<td><?php echo esc_html( $link->id ); ?></td>
						<td>
							<a href="<?php echo esc_url( $link->destination_url ); ?>" target="_blank">
								<?php echo esc_html( $link->destination_url ); ?>
							</a>
						</td>
						<td><?php echo esc_html( $link->created_at ); ?></td>
						<td><?php echo esc_html( $link->access_count ); ?></td>
						<td><?php echo $link->last_accessed ? esc_html( $link->last_accessed ) : esc_html__( 'Never', 'wp-qr-trackr' ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}

/**
 * Get QR code data by ID.
 *
 * @param int $link_id The link ID.
 * @return object|null The link object or null if not found.
 */
function qr_trackr_get_link_by_id( $link_id ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return null;
	}

	global $wpdb;
	$table = $wpdb->prefix . 'qr_trackr_links';
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$link = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', $table, $link_id ) );

	if ( ! $link ) {
		return null;
	}

	return $link;
}

if ( function_exists( 'qr_trackr_debug_log' ) ) {
	qr_trackr_debug_log( 'Loaded module-admin.php.' );
}
