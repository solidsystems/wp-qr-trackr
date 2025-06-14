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

// Register admin menu and submenus.
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
 * Render the QR Trackr Overview admin page.
 *
 * @return void
 */
function qr_trackr_admin_overview() {
	echo '<div class="wrap"><h1>QR Trackr Overview</h1>';
	if ( isset( $_POST['qr_trackr_admin_new_qr_nonce'], $_POST['qr_trackr_admin_new_post_id'] ) ) {
		$nonce       = isset( $_POST['qr_trackr_admin_new_qr_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['qr_trackr_admin_new_qr_nonce'] ) ) : '';
		$new_post_id = isset( $_POST['qr_trackr_admin_new_post_id'] ) ? intval( wp_unslash( $_POST['qr_trackr_admin_new_post_id'] ) ) : 0;
		if ( wp_verify_nonce( $nonce, 'qr_trackr_admin_new_qr' ) ) {
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
		$selected = ( 0 !== $preselect_post_id && $preselect_post_id === $post->ID ) ? ' selected' : '';
		echo '<option value="' . intval( $post->ID ) . '"' . esc_attr( $selected ) . '>' . esc_html( $post->post_title ) . ' (' . esc_html( ucfirst( $post->post_type ) ) . ')</option>';
	}
	echo '</select> ';
	echo '<button type="submit" class="button button-primary">Create QR Code</button>';
	echo '</form>';
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
 * Render the QR Trackr Stats admin page.
 *
 * @return void
 */
function qr_trackr_admin_individual() {
	echo '<div class="wrap"><h1>QR Trackr Stats</h1>';
	global $wpdb;
	$scans_table       = $wpdb->prefix . 'qr_trackr_scans'; // Safe table name.
	$total_scans       = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %s', $scans_table ) );
	$most_popular      = $wpdb->get_row( $wpdb->prepare( 'SELECT post_id, COUNT(*) as scan_count FROM %s GROUP BY post_id ORDER BY scan_count DESC LIMIT 1', $scans_table ) );
	$most_popular_post = $most_popular ? get_post( $most_popular->post_id ) : null;
	echo '<div style="margin-bottom:2em; background:#fafafa; padding:1em; border:1px solid #eee; max-width:600px;">';
	echo '<strong>Total QR Scans:</strong> ' . intval( $total_scans ) . '<br>';
	if ( $most_popular && $most_popular_post ) {
		echo '<strong>Most Popular QR:</strong> <a href="' . esc_url( get_permalink( $most_popular_post->ID ) ) . '" target="_blank">' . esc_html( $most_popular_post->post_title ) . '</a> (' . intval( $most_popular->scan_count ) . ' scans).';
	}
	echo '</div>';
	$table = new QR_Trackr_List_Table();
	$table->prepare_items();
	echo '<form method="get">';
	echo '<input type="hidden" name="page" value="qr-trackr-individual">';
	$table->search_box( 'Search Title', 'title' );
	$table->display();
	echo '</form>';
	echo '</div>';
}

/**
 * Render the QR Trackr Debug Settings admin page.
 *
 * @return void
 */
function qr_trackr_debug_settings_page() {
	if ( isset( $_POST['qr_trackr_debug_nonce'] ) ) {
		$nonce = sanitize_text_field( wp_unslash( $_POST['qr_trackr_debug_nonce'] ) );
		if ( wp_verify_nonce( $nonce, 'qr_trackr_debug_save' ) ) {
			update_option( 'qr_trackr_debug_mode', isset( $_POST['qr_trackr_debug_mode'] ) ? '1' : '0' );
			echo '<div class="notice notice-success is-dismissible"><p>Debug mode updated.</p></div>';
		}
	}
	$debug_mode = get_option( 'qr_trackr_debug_mode', '0' );
	echo '<div class="wrap"><h1>QR Trackr Debug Settings</h1>';
	echo '<form method="post">';
	wp_nonce_field( 'qr_trackr_debug_save', 'qr_trackr_debug_nonce' );
	echo '<label><input type="checkbox" name="qr_trackr_debug_mode" value="1"' . checked( $debug_mode, '1', false ) . '> Enable Debug Mode (output to JS console).</label><br><br>';
	echo '<button type="submit" class="button button-primary">Save</button>';
	echo '</form>';
	echo '</div>';
}

// Enqueue admin scripts and styles
add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {
		if ( false !== strpos( $hook, 'qr-trackr' ) || 'post.php' === $hook || 'post-new.php' === $hook ) {
			wp_enqueue_style( 'qr-trackr-admin', QR_TRACKR_PLUGIN_URL . 'assets/admin.css', array(), QR_TRACKR_VERSION );
			wp_enqueue_script( 'qr-trackr-admin', QR_TRACKR_PLUGIN_URL . 'assets/admin.js', array( 'jquery' ), QR_TRACKR_VERSION, true );
			wp_localize_script( 'qr-trackr-admin', 'qrTrackrDebugMode', array( 'debug' => ( '1' === get_option( 'qr_trackr_debug_mode', '0' ) ) ? true : false ) );
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

// Admin columns, row actions, notices
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
		$count = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM `' . $table . '` WHERE post_id = %d', $post_id ) );
		echo intval( $count );
	}
}
add_action(
	'admin_notices',
	function () {
		if ( '1' === get_option( 'qr_trackr_permalinks_plain' ) ) {
			$permalink_url = admin_url( 'options-permalink.php' );
			echo '<div class="notice notice-warning"><p>';
			echo '<strong>QR Trackr:</strong> Your WordPress site is using <strong>Plain</strong> permalinks. For user-friendly QR code links, please <a href="' . esc_url( $permalink_url ) . '"><strong>update your permalink settings</strong></a> to "Post name" and save.';
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
