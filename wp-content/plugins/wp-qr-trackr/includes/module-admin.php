<?php
/**
 * Admin Module
 *
 * Handles all admin-related functionality including settings pages,
 * admin menus, and AJAX callbacks for the admin interface.
 *
 * @package WP_QR_Trackr
 * @since 1.0.0
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
	if ( false === strpos( $hook, 'qr-trackr' ) ) {
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
	// Handle delete action with nonce verification.
	if ( isset( $_GET['action'], $_GET['link'], $_GET['_wpnonce'] ) && 'delete' === $_GET['action'] ) {
		$link_id = absint( $_GET['link'] );
		$nonce   = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) );
		$action  = 'delete_qr_trackr_link_' . $link_id;

		if ( QR_Trackr_List_Table::verify_action_nonce( $action, $nonce ) ) {
			try {
				if ( qr_trackr_delete_qr_code( $link_id ) ) {
					add_action(
						'admin_notices',
						function () {
							printf(
								'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
								esc_html__( 'QR code deleted successfully.', 'wp-qr-trackr' )
							);
						}
					);
				}
			} catch ( Exception $e ) {
				add_action(
					'admin_notices',
					function () use ( $e ) {
						printf(
							'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
							esc_html( $e->getMessage() )
						);
					}
				);
			}
		} else {
			add_action(
				'admin_notices',
				function () {
					printf(
						'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
						esc_html__( 'Invalid or expired nonce. Please try again.', 'wp-qr-trackr' )
					);
				}
			);
		}
	}

	/**
	 * Handle Create QR form submission with nonce verification.
	 */
	if ( isset( $_POST['qr_trackr_nonce'] ) && isset( $_POST['destination_type'] ) ) {
		$nonce = sanitize_text_field( wp_unslash( $_POST['qr_trackr_nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'qr_trackr_nonce' ) ) {
			add_action(
				'admin_notices',
				function () {
					printf( '<div class="notice notice-error is-dismissible"><p>%s</p></div>', esc_html__( 'Invalid or expired nonce. Please try again.', 'wp-qr-trackr' ) );
				}
			);
			return;
		}

		// Process form data here.
		$destination_type = sanitize_text_field( wp_unslash( $_POST['destination_type'] ) );
		// Additional form processing logic will be added here.
	}

	// Get all QR codes with caching.
	$cache_key = 'qr_trackr_all_codes';
	$qr_codes  = wp_cache_get( $cache_key, 'qr_trackr' );

	if ( false === $qr_codes ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'qr_trackr_links';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached immediately after query.
		$qr_codes = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . esc_sql( $table_name ) . ' ORDER BY created_at DESC'
			)
		);

		if ( $qr_codes ) {
			wp_cache_set( $cache_key, $qr_codes, 'qr_trackr', HOUR_IN_SECONDS );
		}
	}
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
 * Add QR code column to posts list.
 *
 * @param array $columns Array of column names.
 * @return array Modified array of column names.
 */
function qr_trackr_add_column( $columns ) {
	$columns['qr_code'] = __( 'QR Code', 'wp-qr-trackr' );
	return $columns;
}

/**
 * Display QR code column content.
 *
 * @param string $column  Column name.
 * @param int    $post_id Post ID.
 */
function qr_trackr_column_content( $column, $post_id ) {
	if ( 'qr_code' !== $column ) {
		return;
	}

	$qr_code = qr_trackr_get_cached_qr_code( $post_id );
	if ( ! $qr_code ) {
		echo '<em>' . esc_html__( 'No QR code generated.', 'wp-qr-trackr' ) . '</em>';
		return;
	}

	printf(
		'<a href="%s" target="_blank">%s</a>',
		esc_url( $qr_code->url ),
		esc_html__( 'View QR Code', 'wp-qr-trackr' )
	);
}

/**
 * Handle permalink structure changes.
 *
 * @param string $old_value Old permalink structure.
 * @param string $new_value New permalink structure.
 */
function qr_trackr_handle_permalink_change( $old_value, $new_value ) {
	if ( $old_value === $new_value ) {
		return;
	}

	// Clear all QR code caches.
	wp_cache_delete( 'qr_trackr_all_codes', 'qr_trackr' );
}

/**
 * Add QR code meta box to posts.
 */
function qr_trackr_add_meta_box() {
	add_meta_box(
		'qr_trackr_meta_box',
		__( 'QR Code', 'wp-qr-trackr' ),
		'qr_trackr_render_meta_box',
		'post',
		'side',
		'high'
	);
}

/**
 * Render QR code meta box content.
 *
 * @param WP_Post $post Post object.
 */
function qr_trackr_render_meta_box( $post ) {
	$qr_code = qr_trackr_get_cached_qr_code( $post->ID );
	if ( ! $qr_code ) {
		printf(
			'<p><em>%s</em></p>',
			esc_html__( 'No QR code generated yet.', 'wp-qr-trackr' )
		);
		return;
	}

	printf(
		'<p><a href="%s" target="_blank">%s</a></p>',
		esc_url( $qr_code->url ),
		esc_html__( 'View QR Code', 'wp-qr-trackr' )
	);
}

/**
 * Register plugin settings.
 */
function qr_trackr_register_settings() {
	register_setting(
		'qr_trackr_settings',
		'qr_trackr_options',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'qr_trackr_sanitize_settings',
		)
	);
}

/**
 * Render the stats page.
 */
function qr_trackr_stats_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wp-qr-trackr' ) );
	}

	$stats = qr_trackr_get_cached_stats( get_current_blog_id() );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'QR Code Statistics', 'wp-qr-trackr' ); ?></h1>
		<div class="qr-trackr-stats">
			<div class="qr-trackr-stat-card">
				<h3><?php esc_html_e( 'Total Scans', 'wp-qr-trackr' ); ?></h3>
				<div class="qr-trackr-stat-value"><?php echo esc_html( number_format_i18n( $stats['total_scans'] ) ); ?></div>
			</div>
			<div class="qr-trackr-stat-card">
				<h3><?php esc_html_e( 'Active QR Codes', 'wp-qr-trackr' ); ?></h3>
				<div class="qr-trackr-stat-value"><?php echo esc_html( number_format_i18n( $stats['active_codes'] ) ); ?></div>
			</div>
			<div class="qr-trackr-stat-card">
				<h3><?php esc_html_e( 'Most Popular QR Code', 'wp-qr-trackr' ); ?></h3>
				<?php if ( ! empty( $stats['most_popular'] ) ) : ?>
					<div class="qr-trackr-stat-value">
						<a href="<?php echo esc_url( $stats['most_popular']['url'] ); ?>">
							<?php echo esc_html( $stats['most_popular']['title'] ); ?>
						</a>
						<?php
						/* translators: %s: Number of scans formatted with number_format_i18n() */
						printf(
							'<small>%s</small>',
							esc_html(
								sprintf(
									/* translators: %s: Number of scans */
									__( '%s scans', 'wp-qr-trackr' ),
									number_format_i18n( $stats['most_popular']['scans'] )
								)
							)
						);
						?>
					</div>
				<?php else : ?>
					<div class="qr-trackr-stat-value">
						<em><?php esc_html_e( 'No data available.', 'wp-qr-trackr' ); ?></em>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Get cached QR code data.
 *
 * @param int $id QR code ID.
 * @return array|WP_Error QR code data or error object.
 */
function qr_trackr_get_cached_qr_code( $id ) {
	if ( ! is_numeric( $id ) ) {
		return new WP_Error( 'invalid_id', esc_html__( 'Invalid QR code ID provided.', 'wp-qr-trackr' ) );
	}

	$cache_key = 'qr_trackr_code_' . intval( $id );
	$data      = wp_cache_get( $cache_key, 'qr_trackr' );

	if ( false !== $data ) {
		return $data;
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';

	try {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached immediately after query.
		$data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}qr_trackr_links WHERE id = %d",
				intval( $id )
			),
			ARRAY_A
		);

		if ( ! $data ) {
			return new WP_Error( 'not_found', esc_html__( 'QR code not found.', 'wp-qr-trackr' ) );
		}

		wp_cache_set( $cache_key, $data, 'qr_trackr', 300 ); // Cache for 5 minutes
		return $data;

	} catch ( Exception $e ) {
		return new WP_Error( 'db_error', $e->getMessage() );
	}
}

/**
 * Get cached statistics for a QR code.
 *
 * @param int $id QR code ID.
 * @return array|WP_Error Statistics data or error object.
 */
function qr_trackr_get_cached_stats( $id ) {
	if ( ! is_numeric( $id ) ) {
		return new WP_Error( 'invalid_id', esc_html__( 'Invalid QR code ID provided.', 'wp-qr-trackr' ) );
	}

	$cache_key = 'qr_trackr_stats_' . intval( $id );
	$stats     = wp_cache_get( $cache_key, 'qr_trackr' );

	if ( false !== $stats ) {
		return $stats;
	}

	try {
		$stats = qr_trackr_get_tracking_data( $id );
		if ( is_wp_error( $stats ) ) {
			return $stats;
		}

		wp_cache_set( $cache_key, $stats, 'qr_trackr', 300 ); // Cache for 5 minutes
		return $stats;

	} catch ( Exception $e ) {
		return new WP_Error( 'stats_error', $e->getMessage() );
	}
}

/**
 * Delete a QR code and its associated data.
 *
 * @param int $id QR code ID.
 * @return bool|WP_Error True on success, WP_Error on failure.
 */
function qr_trackr_delete_qr_code( $id ) {
	if ( ! is_numeric( $id ) ) {
		return new WP_Error( 'invalid_id', esc_html__( 'Invalid QR code ID provided.', 'wp-qr-trackr' ) );
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return new WP_Error( 'permission_denied', esc_html__( 'You do not have permission to delete QR codes.', 'wp-qr-trackr' ) );
	}

	global $wpdb;
	$id = intval( $id );

	try {
		// Start transaction
		$wpdb->query( 'START TRANSACTION' );

		// Delete QR code
		$result = $wpdb->delete(
			$wpdb->prefix . 'qr_trackr_links',
			array( 'id' => $id ),
			array( '%d' )
		);

		if ( false === $result ) {
			throw new Exception( esc_html__( 'Failed to delete QR code.', 'wp-qr-trackr' ) );
		}

		// Delete associated scans
		$wpdb->delete(
			$wpdb->prefix . 'qr_trackr_scans',
			array( 'link_id' => $id ),
			array( '%d' )
		);

		// Delete QR code files
		$upload_dir = wp_upload_dir();
		$qr_dir     = $upload_dir['basedir'] . '/qr-trackr';
		$files      = glob( $qr_dir . '/qr-' . $id . '-*.{png,svg}', GLOB_BRACE );

		if ( $files ) {
			foreach ( $files as $file ) {
				if ( is_file( $file ) ) {
					wp_delete_file( $file );
				}
			}
		}

		// Commit transaction
		$wpdb->query( 'COMMIT' );

		// Clear caches
		wp_cache_delete( 'qr_trackr_code_' . $id, 'qr_trackr' );
		wp_cache_delete( 'qr_trackr_stats_' . $id, 'qr_trackr' );
		wp_cache_delete( 'qr_trackr_all_codes', 'qr_trackr' );

		return true;

	} catch ( Exception $e ) {
		$wpdb->query( 'ROLLBACK' );
		return new WP_Error( 'delete_error', $e->getMessage() );
	}
}

/**
 * Check if current user can manage QR codes.
 *
 * @return bool Whether the current user can manage QR codes.
 */
function qr_trackr_current_user_can_manage() {
	static $can_manage = null;

	if ( null === $can_manage ) {
		$can_manage = current_user_can( 'manage_options' );
	}

	return $can_manage;
}

/**
 * Process QR code form submission.
 *
 * @return array|WP_Error Array of success data or error object.
 */
function qr_trackr_process_form() {
	if ( ! isset( $_POST['qr_trackr_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['qr_trackr_nonce'] ) ), 'qr_trackr_nonce' ) ) {
		return new WP_Error( 'invalid_nonce', esc_html__( 'Security check failed.', 'wp-qr-trackr' ) );
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return new WP_Error( 'permission_denied', esc_html__( 'You do not have permission to create QR codes.', 'wp-qr-trackr' ) );
	}

	$destination_type = isset( $_POST['destination_type'] ) ? sanitize_text_field( wp_unslash( $_POST['destination_type'] ) ) : '';
	$destination_url  = '';

	switch ( $destination_type ) {
		case 'post':
			$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
			if ( ! $post_id || ! get_post( $post_id ) ) {
				return new WP_Error( 'invalid_post', esc_html__( 'Please select a valid post.', 'wp-qr-trackr' ) );
			}
			$destination_url = get_permalink( $post_id );
			break;

		case 'external':
			$destination_url = isset( $_POST['external_url'] ) ? esc_url_raw( wp_unslash( $_POST['external_url'] ) ) : '';
			if ( empty( $destination_url ) || ! wp_http_validate_url( $destination_url ) ) {
				return new WP_Error( 'invalid_url', esc_html__( 'Please enter a valid URL.', 'wp-qr-trackr' ) );
			}
			break;

		case 'custom':
			$destination_url = isset( $_POST['custom_url'] ) ? esc_url_raw( wp_unslash( $_POST['custom_url'] ) ) : '';
			if ( empty( $destination_url ) || ! wp_http_validate_url( $destination_url ) ) {
				return new WP_Error( 'invalid_url', esc_html__( 'Please enter a valid URL.', 'wp-qr-trackr' ) );
			}
			break;

		default:
			return new WP_Error( 'invalid_type', esc_html__( 'Invalid destination type.', 'wp-qr-trackr' ) );
	}

	try {
		global $wpdb;
		$table_name = $wpdb->prefix . 'qr_trackr_links';

		// Insert QR code
		$result = $wpdb->insert(
			$table_name,
			array(
				'destination_url' => $destination_url,
				'created_at'      => current_time( 'mysql', true ),
				'qr_code'         => qr_trackr_generate_unique_qr_code(),
			),
			array( '%s', '%s', '%s' )
		);

		if ( false === $result ) {
			throw new Exception( esc_html__( 'Failed to create QR code.', 'wp-qr-trackr' ) );
		}

		$qr_id = $wpdb->insert_id;

		// Generate QR code image
		$qr_image = qr_trackr_generate_qr_image_for_link( $qr_id );
		if ( is_wp_error( $qr_image ) ) {
			throw new Exception( $qr_image->get_error_message() );
		}

		// Clear cache
		wp_cache_delete( 'qr_trackr_all_codes', 'qr_trackr' );

		return array(
			'id'       => $qr_id,
			'qr_image' => $qr_image,
			'message'  => esc_html__( 'QR code created successfully.', 'wp-qr-trackr' ),
		);

	} catch ( Exception $e ) {
		return new WP_Error( 'creation_error', $e->getMessage() );
	}
}

if ( function_exists( 'qr_trackr_debug_log' ) ) {
	qr_trackr_debug_log( 'Loaded module-admin.php.' );
}

