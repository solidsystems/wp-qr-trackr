<?php
/**
 * Admin functionality for the QR Coder plugin.
 *
 * @package WP_QR_TRACKR
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Only load admin functionality in admin context.
if ( ! is_admin() ) {
	return;
}

/**
 * Register admin menu items.
 *
 * @since 1.0.0
 * @return void
 */
function qrc_admin_menu() {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
		qr_trackr_debug_log( 'QR Trackr: qrc_admin_menu() called. Hook: ' . current_filter() . ', User: ' . get_current_user_id() );
	}

	// Add main menu item.
	$hook = add_menu_page(
		__( 'QR Code Links', 'wp-qr-trackr' ),
		__( 'QR Codes', 'wp-qr-trackr' ),
		'manage_options',
		'qr-code-links',
		'qrc_admin_page',
		'dashicons-admin-links'
	);
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
		qr_trackr_debug_log( 'QR Trackr: Menu page added with hook: ' . ( $hook ? $hook : 'failed' ) );
	}

	// Add submenu items.
	$settings = add_submenu_page(
		'qr-code-links',
		__( 'Settings', 'wp-qr-trackr' ),
		__( 'Settings', 'wp-qr-trackr' ),
		'manage_options',
		'qr-code-settings',
		'qrc_settings_page'
	);
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
		qr_trackr_debug_log( 'QR Trackr: Settings page added with hook: ' . ( $settings ? $settings : 'failed' ) );
	}
}

/**
 * Register plugin settings.
 *
 * @since 1.0.0
 * @return void
 */
function qrc_register_settings() {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
		qr_trackr_debug_log( 'QR Trackr: qrc_register_settings() called' );
	}

	// Register a new settings section.
	add_settings_section(
		'qr_trackr_general_settings',
		__( 'General Settings', 'wp-qr-trackr' ),
		'qrc_general_settings_section_callback',
		'qr_trackr_settings'
	);

	// Register settings fields.
	register_setting( 'qr_trackr_settings', 'qr_trackr_qr_size' );
	register_setting( 'qr_trackr_settings', 'qr_trackr_tracking_enabled' );

	// Add settings fields.
	add_settings_field(
		'qr_trackr_qr_size',
		__( 'QR Code Size', 'wp-qr-trackr' ),
		'qrc_qr_size_field_callback',
		'qr_trackr_settings',
		'qr_trackr_general_settings'
	);

	add_settings_field(
		'qr_trackr_tracking_enabled',
		__( 'Enable Tracking', 'wp-qr-trackr' ),
		'qrc_tracking_enabled_field_callback',
		'qr_trackr_settings',
		'qr_trackr_general_settings'
	);
}

/**
 * Callback for the general settings section description.
 *
 * @since 1.0.0
 * @return void
 */
function qrc_general_settings_section_callback() {
	echo '<p>' . esc_html__( 'Configure general settings for QR code generation and tracking.', 'wp-qr-trackr' ) . '</p>';
}

/**
 * Callback for the QR code size field in settings.
 *
 * @since 1.0.0
 * @return void
 */
function qrc_qr_size_field_callback() {
	$size = get_option( 'qr_trackr_qr_size', '150' );
	echo '<input type="number" name="qr_trackr_qr_size" value="' . esc_attr( $size ) . '" class="small-text" /> px';
}

/**
 * Callback for the tracking enabled field in settings.
 *
 * @since 1.0.0
 * @return void
 */
function qrc_tracking_enabled_field_callback() {
	$enabled = get_option( 'qr_trackr_tracking_enabled', '1' );
	echo '<input type="checkbox" name="qr_trackr_tracking_enabled" value="1" ' . checked( '1', $enabled, false ) . ' />';
	echo '<span class="description">' . esc_html__( 'Track QR code scans and store analytics.', 'wp-qr-trackr' ) . '</span>';
}

/**
 * Display the admin page content.
 *
 * @since 1.0.0
 * @return void
 */
function qrc_admin_page() {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
		qr_trackr_debug_log( 'QR Trackr: qrc_admin_page() called' );
	}

	// Load list table class if not already loaded.
	if ( ! class_exists( 'QRC_Links_List_Table' ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
			qr_trackr_debug_log( 'QR Trackr: Loading list table class.' );
		}
		require_once __DIR__ . '/class-qrc-links-list-table.php';
	}

	// Create an instance of our list table class.
	$list_table = new QRC_Links_List_Table();
	$list_table->prepare_items();

	// Include the admin page template.
	include dirname( __DIR__ ) . '/templates/admin-page.php';
}

/**
 * Display the settings page content.
 *
 * @since 1.0.0
 * @return void
 */
function qrc_settings_page() {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
		qr_trackr_debug_log( 'QR Trackr: qrc_settings_page() called' );
	}

	// Include the settings page template.
	include dirname( __DIR__ ) . '/templates/settings-page.php';
}

// Register admin menu items.
add_action( 'admin_menu', 'qrc_admin_menu' );
if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
	qr_trackr_debug_log( 'QR Trackr: Added admin_menu action' );
}

// Register settings.
add_action( 'admin_init', 'qrc_register_settings' );
if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'qr_trackr_debug_log' ) ) {
	qr_trackr_debug_log( 'QR Trackr: Added admin_init action' );
}
