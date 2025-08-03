<?php
/**
 * Plugin activation and deactivation functionality.
 *
 * @package WP_QR_TRACKR
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Initializes the plugin hooks.
 */
function qrc_init() {
	// Add a settings link to the plugins page.
	add_filter( 'plugin_action_links_' . plugin_basename( QRC_PLUGIN_FILE ), 'qrc_add_settings_link' );

	// Note: Activation and deactivation hooks are now handled in the main plugin file.
}

/**
 * Add a settings link to the plugins page.
 *
 * @param array $links The existing links.
 * @return array The modified links.
 */
function qrc_add_settings_link( $links ) {
	$settings_link = '<a href="options-general.php?page=wp-qr-trackr">' . __( 'Settings', 'wp-qr-trackr' ) . '</a>';
	array_unshift( $links, $settings_link );
	return $links;
}

/**
 * Create the necessary database tables upon plugin activation.
 *
 * @global wpdb $wpdb The WordPress database object.
 */
function qrc_activate() {
	global $wpdb;
	$table_name      = $wpdb->prefix . 'qr_trackr_links';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		post_id bigint(20) UNSIGNED NULL,
		destination_url varchar(2048) NOT NULL,
		qr_code varchar(255) NOT NULL,
		qr_code_url varchar(2048) DEFAULT NULL,
		common_name varchar(255) DEFAULT NULL,
		referral_code varchar(100) DEFAULT NULL,
		scans int(11) DEFAULT 0 NOT NULL,
		access_count int(11) DEFAULT 0 NOT NULL,
		created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		updated_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		last_accessed datetime DEFAULT NULL,
		metadata text DEFAULT NULL,
		PRIMARY KEY  (id),
		KEY qr_code (qr_code),
		KEY post_id (post_id),
		KEY common_name (common_name),
		KEY referral_code (referral_code)
	) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$result = dbDelta( $sql );

	// Check if we need to upgrade existing table.
	qr_trackr_maybe_upgrade_database();

	// Log activation results for debugging.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'QR Trackr activation: Table creation result: ' . wp_json_encode( $result ) );
	}

	// Verify table was created successfully.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Table existence check during activation.
	$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );

	if ( $table_exists !== $table_name ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'QR Trackr activation: Failed to create table ' . $table_name . '. Last error: ' . $wpdb->last_error );
		}
		// Set an option to indicate activation had issues.
		update_option( 'qr_trackr_activation_error', 'Failed to create database table: ' . $table_name );
	} else {
		// Clear any previous activation errors.
		delete_option( 'qr_trackr_activation_error' );
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'QR Trackr activation: Table ' . $table_name . ' created successfully' );
		}
	}

	// Check permalink structure for rewrite rules.
	qr_trackr_check_permalink_structure();
}

/**
 * Upgrade database if needed for new features.
 *
 * @since 1.2.18
 * @return void
 */
function qr_trackr_maybe_upgrade_database() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';

	// Check if new fields exist.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Schema check during upgrade.
	$columns = $wpdb->get_results( "SHOW COLUMNS FROM {$table_name}" );

	$has_common_name   = false;
	$has_referral_code = false;

	foreach ( $columns as $column ) {
		if ( 'common_name' === $column->field ) {
			$has_common_name = true;
		}
		if ( 'referral_code' === $column->field ) {
			$has_referral_code = true;
		}
	}

	// Add missing columns.
	if ( ! $has_common_name ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Schema upgrade during activation.
		$wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN common_name varchar(255) DEFAULT NULL AFTER qr_code_url" );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Index creation during activation.
		$wpdb->query( "ALTER TABLE {$table_name} ADD KEY common_name (common_name)" );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'QR Trackr: Added common_name column to database' );
		}
	}

	if ( ! $has_referral_code ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Schema upgrade during activation.
		$wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN referral_code varchar(100) DEFAULT NULL AFTER common_name" );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Index creation during activation.
		$wpdb->query( "ALTER TABLE {$table_name} ADD KEY referral_code (referral_code)" );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'QR Trackr: Added referral_code column to database' );
		}
	}
}

/**
 * Remove the database tables upon plugin deactivation.
 *
 * @global wpdb $wpdb The WordPress database object.
 */
function qrc_deactivate() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_trackr_links';
	// Only drop the table if the user has chosen to remove data upon deactivation.
	if ( get_option( 'qrc_remove_data_on_deactivation' ) ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.NoCaching -- Schema cleanup during deactivation, caching not applicable for table deletion.
		$wpdb->query( "DROP TABLE IF EXISTS $table_name" );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'QR Trackr deactivation: Dropped table ' . $table_name );
		}
	}
}

/**
 * Create the QR Code post type.
 */
function qrc_create_post_type() {
	$labels = array(
		'name'               => _x( 'QR Codes', 'post type general name', 'wp-qr-trackr' ),
		'singular_name'      => _x( 'QR Code', 'post type singular name', 'wp-qr-trackr' ),
		'add_new'            => _x( 'Add New', 'qr_code', 'wp-qr-trackr' ),
		'add_new_item'       => __( 'Add New QR Code', 'wp-qr-trackr' ),
		'edit_item'          => __( 'Edit QR Code', 'wp-qr-trackr' ),
		'new_item'           => __( 'New QR Code', 'wp-qr-trackr' ),
		'all_items'          => __( 'All QR Codes', 'wp-qr-trackr' ),
		'view_item'          => __( 'View QR Code', 'wp-qr-trackr' ),
		'search_items'       => __( 'Search QR Codes', 'wp-qr-trackr' ),
		'not_found'          => __( 'No qr codes found', 'wp-qr-trackr' ),
		'not_found_in_trash' => __( 'No qr codes found in the Trash', 'wp-qr-trackr' ),
		'parent_item_colon'  => '',
		'menu_name'          => __( 'QR Codes', 'wp-qr-trackr' ),
	);

	$args = array(
		'labels'             => $labels,
		'description'        => __( 'Holds our QR codes and their redirect data', 'wp-qr-trackr' ),
		'public'             => true,
		'menu_position'      => 5,
		'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'comments' ),
		'has_archive'        => true,
		'show_in_admin_bar'  => true,
		'show_in_nav_menus'  => true,
		'publicly_queryable' => true,
		'query_var'          => true,
		'rewrite'            => array( 'slug' => 'qr-code' ),
		'capability_type'    => 'post',
		'map_meta_cap'       => true,
		'menu_icon'          => 'dashicons-camera',
	);

	register_post_type( 'qr_code', $args );
}

/**
 * Initialize the plugin hooks (only if not already initialized).
 */
if ( ! has_action( 'plugins_loaded', 'qrc_init' ) ) {
	add_action( 'plugins_loaded', 'qrc_init' );
}

/**
 * Flush rewrite rules on activation.
 */
function qrc_flush_rewrite_rules() {
	qrc_create_post_type();
	flush_rewrite_rules();
}

/**
 * Check if pretty permalinks are enabled for rewrite rules.
 *
 * QR code tracking URLs require pretty permalinks to function properly.
 * This function checks the permalink structure and sets appropriate notices.
 *
 * @since 1.2.9
 * @return void
 */
function qr_trackr_check_permalink_structure() {
	$permalink_structure = get_option( 'permalink_structure' );

	if ( empty( $permalink_structure ) ) {
		// Plain permalinks are being used - QR codes won't work.
		$message = sprintf(
			/* translators: 1: Settings URL, 2: Permalink settings text */
			__( 'WP QR Trackr requires pretty permalinks to function properly. Please go to <a href="%1$s">%2$s</a> and choose any structure other than "Plain".', 'wp-qr-trackr' ),
			esc_url( admin_url( 'options-permalink.php' ) ),
			__( 'Settings → Permalinks', 'wp-qr-trackr' )
		);

		update_option( 'qr_trackr_permalink_warning', $message );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'QR Trackr activation: Plain permalinks detected - QR code redirects will not work' );
		}
	} else {
		// Pretty permalinks are enabled - clear any previous warnings.
		delete_option( 'qr_trackr_permalink_warning' );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'QR Trackr activation: Pretty permalinks detected - QR code redirects will work correctly' );
		}
	}
}

/**
 * Display admin notice for permalink structure requirement.
 *
 * @since 1.2.9
 * @return void
 */
function qr_trackr_permalink_admin_notice() {
	$warning = get_option( 'qr_trackr_permalink_warning' );

	if ( ! empty( $warning ) && current_user_can( 'manage_options' ) ) {
		printf(
			'<div class="notice notice-warning is-dismissible"><p><strong>%s</strong> %s</p></div>',
			esc_html__( 'QR Trackr Warning:', 'wp-qr-trackr' ),
			wp_kses_post( $warning )
		);
	}
}
add_action( 'admin_notices', 'qr_trackr_permalink_admin_notice' );

/**
 * Clear permalink warning when permalink structure is updated.
 *
 * @since 1.2.9
 * @return void
 */
function qr_trackr_permalink_structure_changed() {
	// Re-check permalink structure when it changes.
	qr_trackr_check_permalink_structure();
}
add_action( 'permalink_structure_changed', 'qr_trackr_permalink_structure_changed' );

// Note: Activation hooks are now handled in the main plugin file to avoid conflicts.
