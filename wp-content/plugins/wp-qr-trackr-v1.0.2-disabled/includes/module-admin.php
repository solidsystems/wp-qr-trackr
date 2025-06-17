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
	qr_trackr_debug_log('Loading module-admin.php...');
}

// Load the list table class.
require_once QR_TRACKR_PLUGIN_DIR . 'includes/class-qr-trackr-list-table.php';

/**
 * Initialize admin functionality
 */
function qr_trackr_admin_init() {
	add_action('admin_menu', 'qr_trackr_add_menu');
	add_action('admin_enqueue_scripts', 'qr_trackr_admin_enqueue_scripts');
}

/**
 * Add admin menu items
 */
function qr_trackr_add_menu() {
	add_menu_page(
		__('QR Trackr', 'wp-qr-trackr'),
		__('QR Trackr', 'wp-qr-trackr'),
		'manage_options',
		'qr-trackr',
		'qr_trackr_admin_page',
		'dashicons-qrcode',
		30
	);
	
	add_submenu_page(
		'qr-trackr',
		__('Stats', 'wp-qr-trackr'),
		__('Stats', 'wp-qr-trackr'),
		'manage_options',
		'qr-trackr-stats',
		'qr_trackr_stats_page'
	);

	add_submenu_page(
		'qr-trackr',
		__('Settings', 'wp-qr-trackr'),
		__('Settings', 'wp-qr-trackr'),
		'manage_options',
		'qr-trackr-settings',
		'qr_trackr_settings_page'
	);
}

/**
 * Enqueue admin scripts and styles
 */
function qr_trackr_admin_enqueue_scripts($hook) {
	if (strpos($hook, 'qr-trackr') === false) {
		return;
	}
	
	wp_enqueue_style(
		'qr-trackr-admin',
		QR_TRACKR_PLUGIN_URL . 'assets/css/admin.css',
		array(),
		QR_TRACKR_VERSION
	);
	
	wp_enqueue_script(
		'qr-trackr-admin',
		QR_TRACKR_PLUGIN_URL . 'assets/js/admin.js',
		array('jquery'),
		QR_TRACKR_VERSION,
		true
	);
	
	wp_localize_script('qr-trackr-admin', 'qrTrackrAdmin', array(
		'nonce' => wp_create_nonce('qr_trackr_admin'),
		'ajaxurl' => admin_url('admin-ajax.php'),
		'i18n' => array(
			'confirmDelete' => __('Are you sure you want to delete this QR code?', 'wp-qr-trackr'),
			'confirmRegenerate' => __('Are you sure you want to regenerate this QR code?', 'wp-qr-trackr')
		)
	));
}

/**
 * Render the main admin page
 */
function qr_trackr_admin_page() {
	// Get all QR codes
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';
	$qr_codes = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
	
	?>
	<div class="wrap">
		<h1><?php _e('QR Trackr', 'wp-qr-trackr'); ?></h1>
		
		<div id="qr-trackr-message"></div>
		
		<div class="qr-trackr-create-form">
			<h2><?php _e('Create New QR Code', 'wp-qr-trackr'); ?></h2>
			<form id="qr-trackr-create-form" method="post">
				<?php wp_nonce_field('qr_trackr_nonce', 'qr_trackr_nonce'); ?>
				
				<div class="form-field">
					<label for="destination_type"><?php _e('Destination Type', 'wp-qr-trackr'); ?></label>
					<select name="destination_type" id="destination_type" required>
						<option value="post"><?php _e('WordPress Post/Page', 'wp-qr-trackr'); ?></option>
						<option value="external"><?php _e('External URL', 'wp-qr-trackr'); ?></option>
						<option value="custom"><?php _e('Custom URL', 'wp-qr-trackr'); ?></option>
					</select>
				</div>
				
				<div class="form-field post-select" style="display: none;">
					<label for="post_id"><?php _e('Select Post/Page', 'wp-qr-trackr'); ?></label>
					<select name="post_id" id="post_id">
						<?php
						$posts = get_posts(array(
							'post_type' => array('post', 'page'),
							'post_status' => 'publish',
							'numberposts' => -1
						));
						
						foreach ($posts as $post) {
							printf(
								'<option value="%s" data-url="%s">%s</option>',
								esc_attr($post->ID),
								esc_url(get_permalink($post->ID)),
								esc_html($post->post_title)
							);
						}
						?>
					</select>
				</div>
				
				<div class="form-field external-url" style="display: none;">
					<label for="external_url"><?php _e('External URL', 'wp-qr-trackr'); ?></label>
					<input type="url" name="external_url" id="external_url" placeholder="https://">
				</div>
				
				<div class="form-field custom-url" style="display: none;">
					<label for="custom_url"><?php _e('Custom URL', 'wp-qr-trackr'); ?></label>
					<input type="url" name="custom_url" id="custom_url" placeholder="https://">
				</div>
				
				<div class="form-field">
					<button type="submit" class="button button-primary"><?php _e('Create QR Code', 'wp-qr-trackr'); ?></button>
				</div>
			</form>
		</div>
		
		<div class="qr-trackr-list">
			<h2><?php _e('Your QR Codes', 'wp-qr-trackr'); ?></h2>
			
			<?php if (empty($qr_codes)): ?>
				<p><?php _e('No QR codes found.', 'wp-qr-trackr'); ?></p>
			<?php else: ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php _e('QR Code', 'wp-qr-trackr'); ?></th>
							<th><?php _e('Destination', 'wp-qr-trackr'); ?></th>
							<th><?php _e('Created', 'wp-qr-trackr'); ?></th>
							<th><?php _e('Scans', 'wp-qr-trackr'); ?></th>
							<th><?php _e('Last Scan', 'wp-qr-trackr'); ?></th>
							<th><?php _e('Actions', 'wp-qr-trackr'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($qr_codes as $qr_code): ?>
							<tr>
								<td>
									<?php 
									$qr_urls = qr_trackr_generate_qr_image_for_link($qr_code->id);
									if (!empty($qr_urls['png'])): ?>
										<img src="<?php echo esc_url($qr_urls['png']); ?>" alt="QR Code" width="100" style="display:block; margin-bottom:4px;">
										<span style="font-size:12px; color:#555;">
											<a href="<?php echo esc_url($qr_urls['png']); ?>" download title="Download PNG" style="margin-right:8px; text-decoration:none;">
												<span class="dashicons dashicons-media-default" style="vertical-align:middle;"></span> PNG
											</a>
											<a href="<?php echo esc_url($qr_urls['svg']); ?>" download title="Download SVG" style="text-decoration:none;">
												<span class="dashicons dashicons-media-code" style="vertical-align:middle;"></span> SVG
											</a>
										</span>
									<?php else: ?>
										<span class="qr-trackr-error"><?php _e('QR image not available', 'wp-qr-trackr'); ?></span>
									<?php endif; ?>
								</td>
								<td>
									<a href="<?php echo esc_url($qr_code->destination_url); ?>" target="_blank">
										<?php echo esc_html($qr_code->destination_url); ?>
									</a>
								</td>
								<td><?php echo esc_html($qr_code->created_at); ?></td>
								<td><?php echo esc_html($qr_code->access_count); ?></td>
								<td><?php echo $qr_code->last_accessed ? esc_html($qr_code->last_accessed) : __('Never', 'wp-qr-trackr'); ?></td>
								<td>
									<button class="button edit-qr" data-id="<?php echo esc_attr($qr_code->id); ?>">
										<?php _e('Edit', 'wp-qr-trackr'); ?>
									</button>
									<button class="button regenerate-qr" data-id="<?php echo esc_attr($qr_code->id); ?>">
										<?php _e('Regenerate', 'wp-qr-trackr'); ?>
									</button>
									<button class="button delete-qr" data-id="<?php echo esc_attr($qr_code->id); ?>">
										<?php _e('Delete', 'wp-qr-trackr'); ?>
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
		$("#destination_type").on("change", toggleFields);
		toggleFields();

		$("#qr-trackr-create-form").on("submit", function(e) {
			var type = $("#destination_type").val();
			var valid = true;
			var error = "";
			if (type === "post") {
				if (!$("#post_id").val()) {
					valid = false;
					error = "Please select a post or page.";
				}
			} else if (type === "external") {
				var url = $("#external_url").val();
				if (!url || !/^https?:\/\//.test(url)) {
					valid = false;
					error = "Please enter a valid external URL (must start with http:// or https://).";
				}
			} else if (type === "custom") {
				var url = $("#custom_url").val();
				if (!url || !/^https?:\/\//.test(url)) {
					valid = false;
					error = "Please enter a valid custom URL (must start with http:// or https://).";
				}
			}
			if (!valid) {
				e.preventDefault();
				alert(error);
			}
		});
	});
	</script>';
}

/**
 * Render the settings page
 */
function qr_trackr_settings_page() {
	?>
	<div class="wrap">
		<h1><?php _e('QR Trackr Settings', 'wp-qr-trackr'); ?></h1>
		
		<form method="post" action="options.php">
			<?php
			settings_fields('qr_trackr_options');
			do_settings_sections('qr_trackr_options');
			?>
			
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="qr_trackr_verify_destinations">
							<?php _e('Verify Destinations', 'wp-qr-trackr'); ?>
						</label>
					</th>
					<td>
						<input type="checkbox" 
						   id="qr_trackr_verify_destinations" 
						   name="qr_trackr_verify_destinations" 
						   value="1" 
						   <?php checked(get_option('qr_trackr_verify_destinations'), '1'); ?>>
						<p class="description">
							<?php _e('When enabled, the plugin will verify that external URLs are accessible before creating QR codes.', 'wp-qr-trackr'); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="qr_trackr_debug_mode">
							<?php _e('Debug Mode', 'wp-qr-trackr'); ?>
						</label>
					</th>
					<td>
						<input type="checkbox" 
						   id="qr_trackr_debug_mode" 
						   name="qr_trackr_debug_mode" 
						   value="1" 
						   <?php checked(get_option('qr_trackr_debug_mode'), '1'); ?>>
						<p class="description">
							<?php _e('Enable debug logging for QR Trackr. Debug logs will be written to the PHP error log or debug.log.', 'wp-qr-trackr'); ?>
						</p>
					</td>
				</tr>
			</table>
			
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

// Enqueue admin scripts and styles.
add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {
		// Only load on QR Trackr admin pages
		if ( ! in_array( $hook, array( 'toplevel_page_qr-trackr', 'qr-trackr_page_qr-trackr-individual' ), true ) ) {
			return;
		}

		// Enqueue admin styles.
		wp_enqueue_style(
			'qr-trackr-admin',
			QR_TRACKR_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			QR_TRACKR_VERSION
		);

		// Enqueue admin scripts.
		wp_enqueue_script(
			'qr-trackr-admin',
			QR_TRACKR_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			QR_TRACKR_VERSION,
			true
		);

		// Localize script with nonce and AJAX URL.
		wp_localize_script(
			'qr-trackr-admin',
			'qrTrackrAdmin',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'qr_trackr_admin' ),
			)
		);

		// Localize the script with new data
		wp_localize_script(
			'qr-trackr-admin',
			'qrTrackrNonce',
			array(
				'nonce' => wp_create_nonce( 'qr_trackr_nonce' ),
			)
		);

		// Add debug mode to JavaScript
		wp_localize_script(
			'qr-trackr-admin',
			'qrTrackrDebugMode',
			array(
				'debug' => qr_trackr_is_debug_enabled(),
			)
		);
	}
);

// Admin columns, row actions, notices.
add_filter( 'post_row_actions', 'qr_trackr_row_action', 10, 2 );
add_filter( 'page_row_actions', 'qr_trackr_row_action', 10, 2 );
/**
 * Add QR Trackr row action to posts and pages.
 *
 * @param array   $actions Existing row actions.
 * @param WP_Post $post    The post object.
 * @return array Modified row actions.
 */
function qr_trackr_row_action( $actions, $post ) {
	if ( current_user_can( 'manage_options' ) ) {
		$url                  = admin_url( 'admin.php?page=qr-trackr&new_post_id=' . intval( $post->ID ) );
		$actions['qr_trackr'] = '<a href="' . esc_url( $url ) . '">' . __( 'QR Trackr', 'qr-trackr' ) . '</a>';
	}
	return $actions;
}
add_filter( 'manage_posts_columns', 'qr_trackr_add_column' );
add_filter( 'manage_pages_columns', 'qr_trackr_add_column' );
/**
 * Add QR Scans column to posts and pages list tables.
 *
 * @param array $columns Existing columns.
 * @return array Modified columns.
 */
function qr_trackr_add_column( $columns ) {
	$columns['qr_trackr_scans'] = __( 'QR Scans', 'qr-trackr' );
	return $columns;
}
add_action( 'manage_posts_custom_column', 'qr_trackr_column_content', 10, 2 );
add_action( 'manage_pages_custom_column', 'qr_trackr_column_content', 10, 2 );
/**
 * Render QR Scans column content.
 *
 * @param string $column  Column name.
 * @param int    $post_id Post ID.
 * @return void
 */
function qr_trackr_column_content( $column, $post_id ) {
	if ( 'qr_trackr_scans' === $column ) {
		global $wpdb;
		$table = $wpdb->prefix . 'qr_trackr_scans'; // Safe table name.

		// Get scan count with caching.
		$cache_key = 'qr_trackr_scans_' . $post_id;
		$count     = wp_cache_get( $cache_key );
		if ( false === $count ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Admin column, safe table name, not user input, not performance critical.
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safe, built from $wpdb->prefix and static string.
			$count = $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}` WHERE post_id = " . intval( $post_id ) );
			wp_cache_set( $cache_key, $count, '', 300 ); // Cache for 5 minutes.
		}
		echo intval( $count );
	}
}

// Add hook to check permalink settings when they are updated
add_action('update_option_permalink_structure', 'qr_trackr_handle_permalink_change', 10, 2);

/**
 * Handle permalink structure changes and update plugin options accordingly.
 *
 * @param string $old_value The old permalink structure.
 * @param string $new_value The new permalink structure.
 * @return void
 */
function qr_trackr_handle_permalink_change($old_value, $new_value) {
    if ('' === $new_value) {
        update_option('qr_trackr_permalinks_plain', '1');
        qr_trackr_debug_log('Permalinks changed to plain. Option set.');
    } else {
        delete_option('qr_trackr_permalinks_plain');
        qr_trackr_debug_log('Permalinks changed to pretty. Option removed.');
    }
}

// Update the admin notice to be more specific
add_action(
	'admin_notices',
	function () {
		if ('1' === get_option('qr_trackr_permalinks_plain')) {
			$permalink_url = admin_url('options-permalink.php');
			echo '<div class="notice notice-warning is-dismissible"><p>';
			echo '<strong>QR Trackr:</strong> Your WordPress site is using <strong>Plain</strong> permalinks. For user-friendly QR code links, please <a href="' . esc_url($permalink_url) . '"><strong>update your permalink settings</strong></a> to "Post name" and save.';
			echo '</p></div>';
		}
	}
);

add_action(
	'add_meta_boxes',
	function () {
		add_meta_box(
			'qr_trackr_meta_box',
			'QR Trackr',
			'qr_trackr_render_meta_box',
			'post',
			'side',
			'default'
		);
	}
);

/**
 * Render QR Trackr meta box in post editor.
 *
 * @param WP_Post $post The post object.
 * @return void
 */
function qr_trackr_render_meta_box( $post ) {
	$recent_link   = qr_trackr_get_most_recent_tracking_link( $post->ID );
	$qr_url        = $recent_link ? qr_trackr_generate_qr_image_for_link( $recent_link->id ) : '';
	$tracking_link = $recent_link ? trailingslashit( home_url() ) . 'qr-trackr/redirect/' . intval( $recent_link->id ) : '';
	wp_nonce_field( 'qr_trackr_update_dest_' . $post->ID, 'qr_trackr_dest_nonce' );
	$link_id  = $recent_link ? intval( $recent_link->id ) : 0;
	$dest_url = $recent_link ? esc_url( $recent_link->destination_url ) : '';
	?>
	<div class="qr-trackr-meta-box">
		<?php if ( $qr_url ) : ?>
			<img src="<?php echo esc_url( $qr_url ); ?>" style="max-width:100%; margin-bottom:8px;" alt="QR Code">
		<?php endif; ?>
		<?php if ( $tracking_link ) : ?>
			<p><strong>Tracking Link:</strong> <a href="<?php echo esc_url( $tracking_link ); ?>" target="_blank"><?php echo esc_html( $tracking_link ); ?></a></p>
		<?php endif; ?>
		<input type="hidden" name="qr_trackr_link_id" value="<?php echo esc_attr( $link_id ); ?>">
		<label for="qr_trackr_dest_url"><strong>Destination URL:</strong></label>
		<input type="url" name="qr_trackr_dest_url" id="qr_trackr_dest_url" value="<?php echo esc_attr( $dest_url ); ?>" style="width:100%;">
		<button type="submit" class="button button-secondary" style="margin-top:8px;">Update Destination</button>
	</div>
	<?php
}

// Add AJAX handler for getting posts
add_action( 'wp_ajax_qr_trackr_get_posts', function() {
	check_ajax_referer( 'qr_trackr_nonce', 'nonce' );
	
	$posts = get_posts(
		array(
			'post_type'   => array( 'post', 'page' ),
			'numberposts' => -1,
			'orderby'     => 'title',
			'order'       => 'ASC',
			'post_status' => 'publish',
		)
	);
	
	$formatted_posts = array_map(
		function( $post ) {
			return array(
				'ID'         => $post->ID,
				'post_title' => $post->post_title,
				'post_type'  => $post->post_type,
				'permalink'  => get_permalink( $post->ID ),
			);
		},
		$posts
	);
	
	wp_send_json_success( $formatted_posts );
});

// Register settings
function qr_trackr_register_settings() {
	register_setting('qr_trackr_options', 'qr_trackr_verify_destinations');
	register_setting('qr_trackr_options', 'qr_trackr_debug_mode');
}
add_action('admin_init', 'qr_trackr_register_settings');

// Add AJAX handler for updating destinations
add_action('wp_ajax_qr_trackr_update_destination', function() {
	// Verify nonce
	if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'qr_trackr_admin')) {
		wp_send_json_error('Invalid security token.');
	}

	// Check user capabilities
	if (!current_user_can('manage_options')) {
		wp_send_json_error('You do not have permission to edit this link.');
	}

	// Validate and sanitize input
	$link_id = isset($_POST['link_id']) ? intval($_POST['link_id']) : 0;
	$destination = isset($_POST['destination']) ? esc_url_raw($_POST['destination']) : '';

	if (!$link_id) {
		wp_send_json_error('Invalid link ID.');
	}

	if (empty($destination)) {
		wp_send_json_error('Destination URL cannot be empty.');
	}

	// Validate URL
	$parsed_url = parse_url($destination);
	if ($parsed_url === false || !isset($parsed_url['scheme']) || !isset($parsed_url['host'])) {
		wp_send_json_error('Invalid URL format. Please include http:// or https://.');
	}

	// Check if URL is accessible (only if verification is enabled)
	$verify_destinations = get_option('qr_trackr_verify_destinations', '1');
	if ($verify_destinations === '1') {
		$response = wp_remote_head($destination, array(
			'timeout' => 5,
			'redirection' => 5,
			'httpversion' => '1.1',
			'user-agent' => 'WordPress/' . get_bloginfo('version'),
			'blocking' => true,
			'headers' => array(),
			'cookies' => array(),
			'sslverify' => true,
		));

		if (is_wp_error($response)) {
			wp_send_json_error('Warning: Could not verify URL accessibility. Please ensure the URL is correct and accessible.');
		} elseif (wp_remote_retrieve_response_code($response) >= 400) {
			wp_send_json_error('Warning: The URL appears to be inaccessible (HTTP ' . wp_remote_retrieve_response_code($response) . '). Please verify the URL is correct.');
		}
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';

	// Check if link exists
	$existing_link = $wpdb->get_row($wpdb->prepare(
		"SELECT * FROM $table_name WHERE id = %d",
		$link_id
	));

	if (!$existing_link) {
		wp_send_json_error('Link not found.');
	}

	// Check for duplicate destinations
	$duplicate = $wpdb->get_row($wpdb->prepare(
		"SELECT * FROM $table_name WHERE destination_url = %s AND id != %d",
		$destination,
		$link_id
	));

	if ($duplicate) {
		wp_send_json_error('A QR code for this destination already exists.');
	}

	// Update the destination
	$result = $wpdb->update(
		$table_name,
		array('destination_url' => $destination),
		array('id' => $link_id),
		array('%s'),
		array('%d')
	);

	if ($result === false) {
		wp_send_json_error('Error updating destination. Please try again.');
	}

	// Regenerate QR code
	$qr_image = qr_trackr_generate_qr_image_for_link($link_id);
	if (!$qr_image) {
		wp_send_json_error('Destination updated but QR code regeneration failed. Please try regenerating the QR code manually.');
	}

	wp_send_json_success('Destination updated successfully.');
});

function qr_trackr_stats_page() {
	global $wpdb;
	$table = $wpdb->prefix . 'qr_trackr_scans';
	$total_scans = $wpdb->get_var("SELECT COUNT(*) FROM $table");
	?>
	<div class="wrap">
		<h1><?php _e('QR Trackr Stats', 'wp-qr-trackr'); ?></h1>
		<p><strong><?php _e('Total QR Code Scans:', 'wp-qr-trackr'); ?></strong> <?php echo intval($total_scans); ?></p>
	</div>
	<?php
}

// Add AJAX handler for getting link data
add_action('wp_ajax_qr_trackr_get_link', function() {
	if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'qr_trackr_admin')) {
		wp_send_json_error('Invalid security token.');
	}

	if (!current_user_can('manage_options')) {
		wp_send_json_error('You do not have permission to view this link.');
	}

	$link_id = isset($_POST['link_id']) ? intval($_POST['link_id']) : 0;
	if (!$link_id) {
		wp_send_json_error('Invalid link ID.');
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';
	$link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $link_id));

	if (!$link) {
		wp_send_json_error('Link not found.');
	}

	wp_send_json_success($link);
});

if ( function_exists( 'qr_trackr_debug_log' ) ) {
	qr_trackr_debug_log('Loaded module-admin.php.');
}
