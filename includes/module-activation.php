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

	// Note: Activation and deactivation hooks are now handled in the main plugin file

	// Create QR code post type.
	qrc_create_post_type();
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
	$table_name      = $wpdb->prefix . 'qr_code_links';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		post_id bigint(20) UNSIGNED NOT NULL,
		destination_url varchar(255) NOT NULL,
		created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		access_count int(11) DEFAULT 0 NOT NULL,
		PRIMARY KEY  (id)
	) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
}

/**
 * Remove the database tables upon plugin deactivation.
 *
 * @global wpdb $wpdb The WordPress database object.
 */
function qrc_deactivate() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'qr_code_links';
	// Only drop the table if the user has chosen to remove data upon deactivation.
	if ( get_option( 'qrc_remove_data_on_deactivation' ) ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.NoCaching -- Schema cleanup during deactivation, caching not applicable for table deletion.
		$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $table_name ) );
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

// Note: Activation hooks are now handled in the main plugin file to avoid conflicts
