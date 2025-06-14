<?php
/**
 * Plugin Name: QR Trackr
 * Description: Generate and track QR codes for WordPress pages and posts. Adds QR code generation to listings and edit screens, and tracks scans with stats overview.
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: qr-trackr
 *
 * @package QR_Trackr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define plugin constants.
define( 'QR_TRACKR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'QR_TRACKR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// date_default_timezone_set( 'UTC' ); // Removed per WordPress coding standards.

// Include core files
require_once QR_TRACKR_PLUGIN_DIR . 'qr-code.php';

// Include the QR_Trackr_List_Table class
require_once QR_TRACKR_PLUGIN_DIR . 'includes/class-qr-trackr-list-table.php';

require_once QR_TRACKR_PLUGIN_DIR . 'includes/class-qr-trackr-cli-command.php';
require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-activation.php';
require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-admin.php';
require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-ajax.php';
require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-rewrite.php';
require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-debug.php';
require_once QR_TRACKR_PLUGIN_DIR . 'includes/module-utility.php';

/**
 * Plugin activation callback.
 * Creates custom tables for QR code scans and tracking links.
 */
function qr_trackr_activate() {
	// Create custom table for QR code scans.
	global $wpdb;
	$table_name      = $wpdb->prefix . 'qr_trackr_scans';
	$charset_collate = $wpdb->get_charset_collate();
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table creation, variable interpolation is safe here.
	$sql = "CREATE TABLE $table_name (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        post_id BIGINT UNSIGNED NOT NULL,
        scan_time DATETIME NOT NULL,
        user_agent TEXT,
        ip_address VARCHAR(45),
        PRIMARY KEY  (id),
        KEY post_id (post_id)
    ) $charset_collate;";
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );

	// Create table for tracking links.
	$links_table = $wpdb->prefix . 'qr_trackr_links';
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table creation, variable interpolation is safe here.
	$sql_links = "CREATE TABLE $links_table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        post_id BIGINT UNSIGNED NOT NULL,
        destination_url TEXT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY post_id (post_id)
    ) $charset_collate;";
	dbDelta( $sql_links );
}
register_activation_hook( __FILE__, 'qr_trackr_activate' );

// On plugin activation, check for pretty permalinks and store a flag if not set
register_activation_hook(
	__FILE__,
	function () {
		if ( get_option( 'permalink_structure' ) === '' ) {
			update_option( 'qr_trackr_permalinks_plain', '1' );
		} else {
			delete_option( 'qr_trackr_permalinks_plain' );
		}
	}
);

// Register admin menu
add_action(
	'admin_menu',
	function () {
		add_menu_page(
			__( 'QR Trackr Stats', 'qr-trackr' ),
			'QR Trackr Stats',
			'manage_options',
			'qr-trackr',
			'qr_trackr_admin_overview',
			'dashicons-qrcode',
			25
		);
		add_submenu_page(
			'qr-trackr',
			__( 'Overview', 'qr-trackr' ),
			__( 'Overview', 'qr-trackr' ),
			'manage_options',
			'qr-trackr',
			'qr_trackr_admin_overview'
		);
		add_submenu_page(
			'qr-trackr',
			__( 'Stats', 'qr-trackr' ),
			__( 'Stats', 'qr-trackr' ),
			'manage_options',
			'qr-trackr-individual',
			'qr_trackr_admin_individual'
		);
		add_submenu_page(
			'qr-trackr',
			__( 'Debug', 'qr-trackr' ),
			__( 'Debug', 'qr-trackr' ),
			'manage_options',
			'qr-trackr-debug',
			'qr_trackr_debug_settings_page'
		);
	}
);

/**
 * Renders the QR Trackr admin overview page.
 */
function qr_trackr_admin_overview() {
	echo '<div class="wrap"><h1>QR Trackr Overview</h1>';
	// --- New QR code creation form. ---
	if ( isset( $_POST['qr_trackr_admin_new_qr_nonce'], $_POST['qr_trackr_admin_new_post_id'] ) ) {
		if ( wp_verify_nonce( $_POST['qr_trackr_admin_new_qr_nonce'], 'qr_trackr_admin_new_qr' ) ) {
			$new_post_id = intval( $_POST['qr_trackr_admin_new_post_id'] );
			if ( get_post( $new_post_id ) ) {
				$link          = qr_trackr_get_or_create_tracking_link( $new_post_id );
				$qr_url        = qr_trackr_generate_qr_image_for_link( $link->id );
				$tracking_link = trailingslashit( home_url() ) . 'qr-trackr/redirect/' . intval( $link->id );
				echo '<div class="notice notice-success is-dismissible"><p>New QR code created for <a href="' . esc_url( get_permalink( $new_post_id ) ) . '" target="_blank">' . esc_html( get_the_title( $new_post_id ) ) . '</a>.';
				if ( $qr_url ) {
					echo '<br><img src="' . esc_url( $qr_url ) . '" class="qr-trackr-qr-image" alt="QR Code">';
					echo '<br><a href="' . esc_url( $qr_url ) . '" download class="button">Download QR Code</a>';
				}
				echo '<br><strong>Tracking Link:</strong> <a href="' . esc_url( $tracking_link ) . '" target="_blank">' . esc_html( $tracking_link ) . '</a>';
				echo '</p></div>';
			} else {
				echo '<div class="notice notice-error is-dismissible"><p>Invalid post or page selected.</p></div>';
			}
		}
	}
	echo '<form method="post" style="margin-bottom:2em; background:#fafafa; padding:1em; border:1px solid #eee; max-width:600px;">';
	wp_nonce_field( 'qr_trackr_admin_new_qr', 'qr_trackr_admin_new_qr_nonce' );
	echo '<label for="qr_trackr_admin_new_post_id"><strong>Create QR code for:</strong></label> ';
	echo '<select name="qr_trackr_admin_new_post_id" id="qr_trackr_admin_new_post_id" required style="min-width:250px;">';
	echo '<option value="">Select a post or page...</option>';
	$posts             = get_posts(
		array(
			'post_type'   => array( 'post', 'page' ),
			'numberposts' => -1,
			'orderby'     => 'title',
			'order'       => 'ASC',
		)
	);
	$preselect_post_id = isset( $_GET['new_post_id'] ) ? intval( $_GET['new_post_id'] ) : 0;
	foreach ( $posts as $post ) {
		$selected = ( $preselect_post_id && $preselect_post_id == $post->ID ) ? ' selected' : '';
		echo '<option value="' . intval( $post->ID ) . '"' . $selected . '>' . esc_html( $post->post_title ) . ' (' . esc_html( ucfirst( $post->post_type ) ) . ')</option>';
	}
	echo '</select> ';
	echo '<button type="submit" class="button button-primary">Create QR Code</button>';
	echo '</form>';
	// --- End new QR code creation form. ---
	// Render the new WP_List_Table.
	$table = new QR_Trackr_List_Table();
	$table->prepare_items();
	echo '<form method="get">';
	echo '<input type="hidden" name="page" value="qr-trackr">';
	$table->search_box( 'Search Title', 'title' );
	$table->display();
	echo '</form>';
	echo '</div>';
}

/**
 * Renders the QR Trackr individual stats page.
 */
function qr_trackr_admin_individual() {
	echo '<div class="wrap"><h1>QR Trackr Stats</h1>';
	// General stats.
	global $wpdb;
	$scans_table = $wpdb->prefix . 'qr_trackr_scans';
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe.
	$total_scans = $wpdb->get_var( "SELECT COUNT(*) FROM $scans_table" );
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe.
	$most_popular      = $wpdb->get_row( "SELECT post_id, COUNT(*) as scan_count FROM $scans_table GROUP BY post_id ORDER BY scan_count DESC LIMIT 1" );
	$most_popular_post = $most_popular ? get_post( $most_popular->post_id ) : null;
	echo '<div style="margin-bottom:2em; background:#fafafa; padding:1em; border:1px solid #eee; max-width:600px;">';
	echo '<strong>Total QR Scans:</strong> ' . intval( $total_scans ) . '<br>';
	if ( $most_popular && $most_popular_post ) {
		echo '<strong>Most Popular QR:</strong> <a href="' . esc_url( get_permalink( $most_popular_post->ID ) ) . '" target="_blank">' . esc_html( $most_popular_post->post_title ) . '</a> (' . intval( $most_popular->scan_count ) . ' scans)';
	}
	echo '</div>';
	// Render the new WP_List_Table.
	$table = new QR_Trackr_List_Table();
	$table->prepare_items();
	echo '<form method="get">';
	echo '<input type="hidden" name="page" value="qr-trackr-individual">';
	$table->search_box( 'Search Title', 'title' );
	$table->display();
	echo '</form>';
	echo '</div>';
}

// Enqueue admin scripts and styles, pass debug mode to JS
add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {
		if ( strpos( $hook, 'qr-trackr' ) !== false || $hook === 'post.php' || $hook === 'post-new.php' ) {
			wp_enqueue_style( 'qr-trackr-admin', QR_TRACKR_PLUGIN_URL . 'assets/admin.css' );
			wp_enqueue_script( 'qr-trackr-admin', QR_TRACKR_PLUGIN_URL . 'assets/admin.js', array( 'jquery' ), null, true );
			wp_localize_script( 'qr-trackr-admin', 'qrTrackrDebugMode', array( 'debug' => get_option( 'qr_trackr_debug_mode', '0' ) === '1' ? true : false ) );
			// Inline script for expanding/collapsing update destination rows
			add_action(
				'admin_footer',
				function () {
					echo '<script>
            jQuery(document).ready(function($){
                $(document).on("click", ".qr-trackr-update-link", function(e){
                    e.preventDefault();
                    var linkId = $(this).data("link-id");
                    $(".qr-trackr-update-row").hide();
                    $("#qr-trackr-update-row-"+linkId).show();
                });
                $(document).on("click", ".qr-trackr-cancel-update", function(e){
                    e.preventDefault();
                    var linkId = $(this).data("link-id");
                    $("#qr-trackr-update-row-"+linkId).hide();
                });
            });
                </script>';
				}
			);
		}
	}
);

// Add QR Trackr quicklink to post/page list rows
add_filter( 'post_row_actions', 'qr_trackr_row_action', 10, 2 );
add_filter( 'page_row_actions', 'qr_trackr_row_action', 10, 2 );
/**
 * Adds a QR Trackr quicklink to post/page list rows.
 *
 * @param array   $actions The existing row actions.
 * @param WP_Post $post The post object.
 * @return array Modified row actions.
 */
function qr_trackr_row_action( $actions, $post ) {
	if ( current_user_can( 'manage_options' ) ) {
		// Link to QR Trackr overview with ?new_post_id=ID to pre-select in the dropdown.
		$url                  = admin_url( 'admin.php?page=qr-trackr&new_post_id=' . intval( $post->ID ) );
		$actions['qr_trackr'] = '<a href="' . esc_url( $url ) . '">' . __( 'QR Trackr', 'qr-trackr' ) . '</a>';
	}
	return $actions;
}

// Handle QR code generation action from list
add_action(
	'admin_init',
	function () {
		if ( isset( $_GET['qr_trackr_generate'], $_GET['post'] ) && current_user_can( 'manage_options' ) ) {
			$post_id = intval( $_GET['post'] );
			$link    = qr_trackr_get_most_recent_tracking_link( $post_id );
			if ( $link ) {
				$qr_url        = qr_trackr_generate_qr_image_for_link( $link->id );
				$tracking_link = trailingslashit( home_url() ) . 'qr-trackr/redirect/' . intval( $link->id );
				$post_link     = get_permalink( $post_id );
				add_action(
					'admin_notices',
					function () use ( $qr_url, $tracking_link, $post_link ) {
						echo '<div class="notice notice-success is-dismissible"><p>';
						echo __( 'QR Code generated:', 'qr-trackr' ) . '<br>';
						echo '<img src="' . esc_url( $qr_url ) . '" class="qr-trackr-qr-image" alt="QR Code">';
						echo '<br><a href="' . esc_url( $qr_url ) . '" download class="button">' . __( 'Download QR Code', 'qr-trackr' ) . '</a>';
						echo '<br><strong>Post Link:</strong> <a href="' . esc_url( $post_link ) . '" target="_blank">' . esc_html( $post_link ) . '</a>';
						echo '<br><strong>Tracking Link:</strong> <a href="' . esc_url( $tracking_link ) . '" target="_blank">' . esc_html( $tracking_link ) . '</a>';
						echo '</p></div>';
					}
				);
			}
		}
	}
);

// Add scan count column to post/page list
add_filter( 'manage_posts_columns', 'qr_trackr_add_column' );
add_filter( 'manage_pages_columns', 'qr_trackr_add_column' );
/**
 * Adds a scan count column to post/page list tables.
 *
 * @param array $columns The existing columns.
 * @return array Modified columns.
 */
function qr_trackr_add_column( $columns ) {
	$columns['qr_trackr_scans'] = __( 'QR Scans', 'qr-trackr' );
	return $columns;
}
add_action( 'manage_posts_custom_column', 'qr_trackr_column_content', 10, 2 );
add_action( 'manage_pages_custom_column', 'qr_trackr_column_content', 10, 2 );
/**
 * Outputs the scan count for the custom column in post/page list tables.
 *
 * @param string $column The column name.
 * @param int    $post_id The post ID.
 */
function qr_trackr_column_content( $column, $post_id ) {
	if ( $column === 'qr_trackr_scans' ) {
		global $wpdb;
		$table = $wpdb->prefix . 'qr_trackr_scans';
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe.
		$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE post_id = %d", $post_id ) );
		echo intval( $count );
	}
}

// Add QR code generator to post/page edit screen
// (Removed: add_action( 'add_meta_boxes', ... ) and qr_trackr_meta_box function)

// Helper: Render minimal QR list HTML for a post
/**
 * Renders a minimal QR list HTML for a post.
 *
 * @param int $post_id The post ID.
 * @return string HTML output.
 */
function qr_trackr_render_qr_list_html( $post_id ) {
	global $wpdb;
	$links_table = $wpdb->prefix . 'qr_trackr_links';
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe.
	$links = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $links_table WHERE post_id = %d ORDER BY created_at DESC", $post_id ) );
	if ( ! $links ) {
		return '<div class="qr-trackr-list"><p>No QR codes found.</p></div>';
	}
	$html  = '<div class="qr-trackr-list"><table class="widefat"><thead><tr>';
	$html .= '<th>ID</th><th>QR Code</th><th>Tracking Link</th>';
	$html .= '</tr></thead><tbody>';
	foreach ( $links as $link ) {
		$qr_url        = qr_trackr_generate_qr_image_for_link( $link->id );
		$tracking_link = trailingslashit( home_url() ) . 'qr-trackr/redirect/' . intval( $link->id );
		$html         .= '<tr>';
		$html         .= '<td>' . intval( $link->id ) . '</td>';
		$html         .= '<td>';
		if ( $qr_url ) {
			$html .= '<img src="' . esc_url( $qr_url ) . '" style="max-width:60px; display:block; margin-bottom:4px;" alt="QR Code">';
			$html .= '<a href="' . esc_url( $qr_url ) . '" download class="button">Download</a>';
		}
		$html .= '</td>';
		$html .= '<td><a href="' . esc_url( $tracking_link ) . '" target="_blank">' . esc_html( $tracking_link ) . '</a></td>';
		$html .= '</tr>';
	}
	$html .= '</tbody></table></div>';
	return $html;
}

/**
 * Handles AJAX requests for QR code creation.
 *
 * @return void
 */
add_action(
	'wp_ajax_qr_trackr_create_qr_ajax',
	function () {
		$post_id = intval( $_POST['post_id'] ?? 0 );
		$nonce   = $_POST['qr_trackr_new_qr_nonce'] ?? '';
		// Verify nonce before processing.
		if ( ! wp_verify_nonce( $nonce, 'qr_trackr_admin_new_qr' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce.' ) );
			wp_die();
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( array( 'message' => 'You do not have permission to edit this post.' ) );
		}
		global $wpdb;
		$links_table     = $wpdb->prefix . 'qr_trackr_links';
		$destination_url = get_permalink( $post_id );
		if ( ! $destination_url ) {
			wp_send_json_error( array( 'message' => 'Could not determine permalink for this post.' ) );
		}
		$result = $wpdb->insert(
			$links_table,
			array(
				'post_id'         => $post_id,
				'destination_url' => esc_url_raw( $destination_url ),
			)
		);
		if ( $result === false ) {
			wp_send_json_error( array( 'message' => 'Insert failed: ' . $wpdb->last_error ) );
		}
		$link_id = $wpdb->insert_id;
		// Generate QR code image and get URLs
		$qr_url        = qr_trackr_generate_qr_image_for_link( $link_id );
		$tracking_link = trailingslashit( home_url() ) . 'qr-trackr/redirect/' . intval( $link_id );
		$html          = qr_trackr_render_qr_list_html( $post_id );
		$qr_image_html = $qr_url ? '<div class="qr-trackr-ajax-qr"><img src="' . esc_url( $qr_url ) . '" class="qr-trackr-qr-image" alt="QR Code"><br><a href="' . esc_url( $qr_url ) . '" download class="button">Download QR Code</a><br><strong>Tracking Link:</strong> <a href="' . esc_url( $tracking_link ) . '" target="_blank">' . esc_html( $tracking_link ) . '</a></div>' : '';
		wp_send_json_success(
			array(
				'html'          => $html,
				'qr_image_html' => $qr_image_html,
			)
		);
	}
);

/**
 * Logs debug output to the PHP error log if debug mode is enabled.
 *
 * @param string $msg  The debug message.
 * @param mixed  $data Optional. Additional data to log.
 */
function qr_trackr_debug_log( $msg, $data = null ) {
	if ( get_option( 'qr_trackr_debug_mode', '0' ) === '1' ) {
		$out = '[QR Trackr Debug] ' . date( 'Y-m-d H:i:s' ) . ' ' . $msg;
		if ( $data !== null ) {
			$out .= ' ' . ( is_string( $data ) ? $data : json_encode( $data ) );
		}
		error_log( $out );
	}
}

// Handle destination URL update.
/**
 * Handles destination URL updates for QR Trackr links on post save.
 *
 * @param int $post_id The post ID.
 */
add_action(
	'save_post',
	function ( $post_id ) {
		if ( isset( $_POST['qr_trackr_dest_nonce'], $_POST['qr_trackr_dest_url'], $_POST['qr_trackr_link_id'] ) ) {
			if ( ! wp_verify_nonce( $_POST['qr_trackr_dest_nonce'], 'qr_trackr_update_dest_' . $post_id ) ) {
				return;
			}
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}
			global $wpdb;
			$links_table = $wpdb->prefix . 'qr_trackr_links';
			$link_id     = intval( $_POST['qr_trackr_link_id'] );
			$dest_url    = esc_url_raw( $_POST['qr_trackr_dest_url'] );
			$wpdb->update( $links_table, array( 'destination_url' => $dest_url ), array( 'id' => $link_id ) );
		}
	}
);

/**
 * Registers custom rewrite rules for QR Trackr endpoints.
 */
add_action(
	'init',
	function () {
		add_rewrite_rule( '^qr-trackr/scan/([0-9]+)/?$', 'index.php?qr_trackr_scan=$matches[1]', 'top' );
		add_rewrite_rule( '^qr-trackr/redirect/([0-9]+)/?$', 'index.php?qr_trackr_redirect=$matches[1]', 'top' );
	}
);
/**
 * Adds custom query vars for QR Trackr endpoints.
 *
 * @param array $vars The existing query vars.
 * @return array Modified query vars.
 */
add_filter(
	'query_vars',
	function ( $vars ) {
		$vars[] = 'qr_trackr_scan';
		$vars[] = 'qr_trackr_redirect';
		return $vars;
	}
);
/**
 * Handles QR Trackr redirect endpoint and logs scans.
 */
add_action(
	'template_redirect',
	function () {
		$redirect_id = intval( get_query_var( 'qr_trackr_redirect' ) );
		if ( $redirect_id ) {
			global $wpdb;
			$links_table = $wpdb->prefix . 'qr_trackr_links';
			$scans_table = $wpdb->prefix . 'qr_trackr_scans';
			$link        = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $links_table WHERE id = %d", $redirect_id ) );
			if ( $link ) {
				// Log the scan.
				$wpdb->insert(
					$scans_table,
					array(
						'post_id'    => intval( $link->post_id ),
						'scan_time'  => current_time( 'mysql', 1 ),
						'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) : '',
						'ip_address' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( $_SERVER['REMOTE_ADDR'] ) : '',
					)
				);
				// Redirect to destination URL.
				wp_safe_redirect( $link->destination_url );
				exit;
			} else {
				status_header( 404 );
				nocache_headers();
				include get_404_template();
				exit;
			}
		}
	}
);

// TODO: Add QR code generation, tracking, and UI integration

add_action(
	'init',
	function () {
		global $wp_rewrite;
		$permalink_structure = get_option( 'permalink_structure' );
		error_log( '[QR Trackr] Permalink structure: ' . $permalink_structure );
		if ( function_exists( 'get_permalink' ) ) {
			$sample_post = get_posts(
				array(
					'numberposts' => 1,
					'post_type'   => 'post',
				)
			);
			$sample_page = get_posts(
				array(
					'numberposts' => 1,
					'post_type'   => 'page',
				)
			);
			if ( $sample_post ) {
				error_log( '[QR Trackr] Example post permalink: ' . get_permalink( $sample_post[0]->ID ) );
			}
			if ( $sample_page ) {
				error_log( '[QR Trackr] Example page permalink: ' . get_permalink( $sample_page[0]->ID ) );
			}
		}
	}
);

function qr_trackr_get_most_recent_tracking_link( $post_id ) {
	global $wpdb;
	$links_table = $wpdb->prefix . 'qr_trackr_links';
	return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $links_table WHERE post_id = %d ORDER BY created_at DESC LIMIT 1", $post_id ) );
}

// Add debug mode option and toggle UI in admin
function qr_trackr_debug_settings_page() {
	if ( isset( $_POST['qr_trackr_debug_nonce'] ) && wp_verify_nonce( $_POST['qr_trackr_debug_nonce'], 'qr_trackr_debug_save' ) ) {
		update_option( 'qr_trackr_debug_mode', isset( $_POST['qr_trackr_debug_mode'] ) ? '1' : '0' );
		echo '<div class="notice notice-success is-dismissible"><p>Debug mode updated.</p></div>';
	}
	$debug_mode = get_option( 'qr_trackr_debug_mode', '0' );
	echo '<div class="wrap"><h1>QR Trackr Debug Settings</h1>';
	echo '<form method="post">';
	wp_nonce_field( 'qr_trackr_debug_save', 'qr_trackr_debug_nonce' );
	echo '<label><input type="checkbox" name="qr_trackr_debug_mode" value="1"' . checked( $debug_mode, '1', false ) . '> Enable Debug Mode (output to JS console)</label><br><br>';
	echo '<button type="submit" class="button button-primary">Save</button>';
	echo '</form>';
	echo '</div>';
}

// Output timestamped debug info to JS console if debug mode is enabled, and log button click
add_action(
	'admin_footer',
	function () {
		global $post, $pagenow;
		$now      = date( 'Y-m-d H:i:s' );
		$debug    = array(
			'timestamp' => $now,
			'pagenow'   => $pagenow,
			'post_id'   => isset( $post->ID ) ? $post->ID : null,
			'user'      => wp_get_current_user()->user_login,
		);
		$qr_links = array();
		if ( isset( $post->ID ) ) {
			$links = qr_trackr_get_all_tracking_links_for_post( $post->ID );
			foreach ( $links as $link ) {
				$qr_links[] = array(
					'id'              => $link->id,
					'tracking_link'   => trailingslashit( home_url() ) . 'qr-trackr/redirect/' . intval( $link->id ),
					'destination_url' => $link->destination_url,
					'created_at'      => $link->created_at,
				);
			}
		}
		$debug['qr_trackr_links'] = $qr_links;
		// No debug <script> output here
		// Robust button click logging (optional, can be removed if not needed)
	}
);

// Migration/verification for qr_trackr_links table schema
add_action(
	'init',
	function () {
		global $wpdb;
		$links_table = $wpdb->prefix . 'qr_trackr_links';
		$columns     = $wpdb->get_results( "SHOW COLUMNS FROM $links_table", ARRAY_A );
		$expected    = array( 'id', 'post_id', 'destination_url', 'created_at', 'updated_at' );
		$actual      = array_map(
			function ( $col ) {
				return $col['Field'];
			},
			$columns
		);
		$missing     = array_diff( $expected, $actual );
		if ( $missing ) {
			error_log( '[QR Trackr MIGRATE] Missing columns in qr_trackr_links: ' . implode( ', ', $missing ) );
			// Try to add missing columns
			if ( in_array( 'created_at', $missing ) ) {
				$wpdb->query( "ALTER TABLE $links_table ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP" );
			}
			if ( in_array( 'updated_at', $missing ) ) {
				$wpdb->query( "ALTER TABLE $links_table ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP" );
			}
		} else {
			error_log( '[QR Trackr MIGRATE] qr_trackr_links schema OK: ' . implode( ', ', $actual ) );
		}
	}
);

// On admin_init, check permalinks and show notice if needed
add_action(
	'admin_init',
	function () {
		if ( get_option( 'permalink_structure' ) === '' ) {
			update_option( 'qr_trackr_permalinks_plain', '1' );
		} else {
			delete_option( 'qr_trackr_permalinks_plain' );
		}
	}
);
add_action(
	'admin_notices',
	function () {
		if ( get_option( 'qr_trackr_permalinks_plain' ) === '1' ) {
			$permalink_url = admin_url( 'options-permalink.php' );
			echo '<div class="notice notice-warning"><p>';
			echo '<strong>QR Trackr:</strong> Your WordPress site is using <strong>Plain</strong> permalinks. For user-friendly QR code links, please <a href="' . esc_url( $permalink_url ) . '"><strong>update your permalink settings</strong></a> to "Post name" and save.';
			echo '</p></div>';
		}
	}
);
