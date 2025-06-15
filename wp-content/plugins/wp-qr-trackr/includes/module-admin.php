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

// Load the list table class.
require_once QR_TRACKR_PLUGIN_DIR . 'includes/class-qr-trackr-list-table.php';

// Register admin menu and submenus.
add_action(
	'admin_menu',
	function () {
		// Check if menu already exists to prevent duplicates
		if ( ! empty( $GLOBALS['admin_page_hooks']['qr-trackr'] ) ) {
			qr_trackr_debug_log( 'QR Trackr menu already registered, skipping.' );
			return;
		}

		qr_trackr_debug_log( 'Registering admin menu and submenus.' );
		add_menu_page(
			__( 'QR Trackr', 'qr-trackr' ),
			'QR Trackr',
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
	echo '<form method="post" class="qr-trackr-create-form" style="margin-bottom:2em; background:#fafafa; padding:1em; border:1px solid #eee; max-width:600px;">';
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
			'post_status' => 'publish', // Only show published posts
		)
	);
	$preselect_post_id = isset( $_GET['new_post_id'] ) ? intval( $_GET['new_post_id'] ) : 0;
	foreach ( $posts as $post ) {
		$selected = ( 0 !== $preselect_post_id && $preselect_post_id === $post->ID ) ? ' selected' : '';
		echo '<option value="' . intval( $post->ID ) . '"' . esc_attr( $selected ) . '>' . esc_html( $post->post_title ) . ' (' . esc_html( ucfirst( $post->post_type ) ) . ')</option>';
	}
	echo '</select> ';
	echo '<button type="submit" class="button button-primary" id="qr-trackr-create-button">Create QR Code</button>';
	echo '</form>';

	// Add container for QR code list and messages
	echo '<div class="qr-trackr-container">';
	echo '<div class="qr-trackr-message"></div>';
	echo '<div class="qr-trackr-list">';
	$table = new QR_Trackr_List_Table();
	$table->prepare_items();
	echo '<form method="get">';
	echo '<input type="hidden" name="page" value="qr-trackr">';
	$table->search_box( 'Search Title', 'title' );
	$table->display();
	echo '</form>';
	echo '</div>'; // End qr-trackr-list
	echo '</div>'; // End qr-trackr-container
	echo '</div>'; // End wrap
}

/**
 * Render the QR Trackr Stats admin page.
 *
 * @return void
 */
function qr_trackr_admin_individual() {
	echo '<div class="wrap"><h1>QR Trackr Stats</h1>';
	global $wpdb;
	$scans_table = $wpdb->prefix . 'qr_trackr_scans'; // Safe table name.

	// Get total scans with caching.
	$total_scans = wp_cache_get( 'qr_trackr_total_scans' );
	if ( false === $total_scans ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Admin stats, safe table name, not user input, and not performance critical.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safe, built from $wpdb->prefix and static string.
		$total_scans = $wpdb->get_var( "SELECT COUNT(*) FROM `{$scans_table}`" );
		wp_cache_set( 'qr_trackr_total_scans', $total_scans, '', 300 ); // Cache for 5 minutes.
	}

	// Get most popular with caching.
	$most_popular = wp_cache_get( 'qr_trackr_most_popular' );
	if ( false === $most_popular ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Admin stats, safe table name, not user input, and not performance critical.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safe, built from $wpdb->prefix and static string.
		$most_popular = $wpdb->get_row( "SELECT link_id, COUNT(*) as scan_count FROM `{$scans_table}` GROUP BY link_id ORDER BY scan_count DESC LIMIT 1" );
		wp_cache_set( 'qr_trackr_most_popular', $most_popular, '', 300 ); // Cache for 5 minutes.
	}

	$most_popular_post = $most_popular ? get_post( $most_popular->link_id ) : null;
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
				'nonce'   => wp_create_nonce( 'qr_trackr_admin_nonce' ),
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
add_action('update_option_permalink_structure', 'qr_trackr_check_permalinks', 10, 2);

/**
 * Check and update permalink option when permalink structure changes.
 *
 * @param string $old_value The old permalink structure.
 * @param string $new_value The new permalink structure.
 * @return void
 */
function qr_trackr_check_permalinks($old_value, $new_value) {
    if ('' === $new_value) {
        update_option('qr_trackr_permalinks_plain', '1');
        qr_trackr_debug_log('Permalinks changed to plain. Option set.');
    } else {
        delete_option('qr_trackr_permalinks_plain');
        qr_trackr_debug_log('Permalinks changed to pretty. Option removed.');
    }
}

// Add hook to check permalinks on admin init
add_action('admin_init', 'qr_trackr_check_permalinks_on_init');

/**
 * Check permalink structure on admin init.
 *
 * @return void
 */
function qr_trackr_check_permalinks_on_init() {
    if ('' === get_option('permalink_structure')) {
        update_option('qr_trackr_permalinks_plain', '1');
    } else {
        delete_option('qr_trackr_permalinks_plain');
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
