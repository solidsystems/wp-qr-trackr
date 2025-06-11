<?php
/*
Plugin Name: QR Trackr
Description: Generate and track QR codes for WordPress pages and posts. Adds QR code generation to listings and edit screens, and tracks scans with stats overview.
Version: 1.0.0
Author: Your Name
Text Domain: qr-trackr
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define( 'QR_TRACKR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'QR_TRACKR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

date_default_timezone_set('UTC');

// Include core files
require_once QR_TRACKR_PLUGIN_DIR . 'qr-code.php';

// Register activation hook
function qr_trackr_activate() {
    // Create custom table for QR code scans
    global $wpdb;
    $table_name = $wpdb->prefix . 'qr_trackr_scans';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        post_id BIGINT UNSIGNED NOT NULL,
        scan_time DATETIME NOT NULL,
        user_agent TEXT,
        ip_address VARCHAR(45),
        PRIMARY KEY  (id),
        KEY post_id (post_id)
    ) $charset_collate;";
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
register_activation_hook( __FILE__, 'qr_trackr_activate' );

// Register admin menu
add_action( 'admin_menu', function() {
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
        __( 'Individual Stats', 'qr-trackr' ),
        __( 'Individual Stats', 'qr-trackr' ),
        'manage_options',
        'qr-trackr-individual',
        'qr_trackr_admin_individual'
    );
});

function qr_trackr_admin_overview() {
    echo '<div class="wrap"><h1>QR Trackr Overview</h1><p>Stats and usage overview will appear here.</p></div>';
}

function qr_trackr_admin_individual() {
    echo '<div class="wrap"><h1>QR Trackr Individual Stats</h1><p>Individual QR code stats will appear here.</p></div>';
}

// Enqueue admin scripts and styles
add_action( 'admin_enqueue_scripts', function($hook) {
    if ( strpos($hook, 'qr-trackr') !== false ) {
        wp_enqueue_style( 'qr-trackr-admin', QR_TRACKR_PLUGIN_URL . 'assets/admin.css' );
        wp_enqueue_script( 'qr-trackr-admin', QR_TRACKR_PLUGIN_URL . 'assets/admin.js', array('jquery'), null, true );
    }
});

// Add QR Trackr quicklink to post/page list rows
add_filter( 'post_row_actions', 'qr_trackr_row_action', 10, 2 );
add_filter( 'page_row_actions', 'qr_trackr_row_action', 10, 2 );
function qr_trackr_row_action( $actions, $post ) {
    if ( current_user_can( 'manage_options' ) ) {
        $url = add_query_arg( [
            'qr_trackr_generate' => 1,
            'post' => $post->ID,
        ], admin_url( 'edit.php' ) );
        $actions['qr_trackr'] = '<a href="' . esc_url( $url ) . '">' . __( 'QR Trackr', 'qr-trackr' ) . '</a>';
    }
    return $actions;
}

// Handle QR code generation action from list
add_action( 'admin_init', function() {
    if ( isset( $_GET['qr_trackr_generate'], $_GET['post'] ) && current_user_can( 'manage_options' ) ) {
        $post_id = intval( $_GET['post'] );
        $url = get_permalink( $post_id );
        $qr_url = qr_trackr_generate_qr_image( $url, 300, $post_id );
        add_action( 'admin_notices', function() use ( $qr_url ) {
            echo '<div class="notice notice-success is-dismissible"><p>';
            echo __( 'QR Code generated:', 'qr-trackr' ) . '<br>';
            echo '<img src="' . esc_url( $qr_url ) . '" class="qr-trackr-qr-image" alt="QR Code">';
            echo '<br><a href="' . esc_url( $qr_url ) . '" download class="button">' . __( 'Download QR Code', 'qr-trackr' ) . '</a>';
            echo '</p></div>';
        });
    }
});

// Add scan count column to post/page list
add_filter( 'manage_posts_columns', 'qr_trackr_add_column' );
add_filter( 'manage_pages_columns', 'qr_trackr_add_column' );
function qr_trackr_add_column( $columns ) {
    $columns['qr_trackr_scans'] = __( 'QR Scans', 'qr-trackr' );
    return $columns;
}
add_action( 'manage_posts_custom_column', 'qr_trackr_column_content', 10, 2 );
add_action( 'manage_pages_custom_column', 'qr_trackr_column_content', 10, 2 );
function qr_trackr_column_content( $column, $post_id ) {
    if ( $column === 'qr_trackr_scans' ) {
        global $wpdb;
        $table = $wpdb->prefix . 'qr_trackr_scans';
        $count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE post_id = %d", $post_id ) );
        echo intval( $count );
    }
}

// Add QR code generator to post/page edit screen
add_action( 'add_meta_boxes', function() {
    add_meta_box( 'qr_trackr_box', __( 'QR Trackr', 'qr-trackr' ), 'qr_trackr_meta_box', ['post', 'page'], 'side', 'default' );
});
function qr_trackr_meta_box( $post ) {
    $url = get_permalink( $post->ID );
    $qr_url = qr_trackr_generate_qr_image( $url, 300, $post->ID );
    echo '<p>' . __( 'Download this QR code for your page/post:', 'qr-trackr' ) . '</p>';
    echo '<img src="' . esc_url( $qr_url ) . '" class="qr-trackr-qr-image" alt="QR Code">';
    echo '<br><a href="' . esc_url( $qr_url ) . '" download class="button">' . __( 'Download QR Code', 'qr-trackr' ) . '</a>';
    // Show scan count
    global $wpdb;
    $table = $wpdb->prefix . 'qr_trackr_scans';
    $count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE post_id = %d", $post->ID ) );
    echo '<p><strong>' . __( 'Scans:', 'qr-trackr' ) . '</strong> ' . intval( $count ) . '</p>';
}

// Register QR scan endpoint
add_action( 'init', function() {
    add_rewrite_rule( '^qr-trackr/scan/([0-9]+)/?$', 'index.php?qr_trackr_scan=$matches[1]', 'top' );
});
add_filter( 'query_vars', function( $vars ) {
    $vars[] = 'qr_trackr_scan';
    return $vars;
});
add_action( 'template_redirect', function() {
    $post_id = get_query_var( 'qr_trackr_scan' );
    if ( $post_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'qr_trackr_scans';
        $wpdb->insert( $table, [
            'post_id' => intval( $post_id ),
            'scan_time' => current_time( 'mysql', 1 ),
            'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) : '',
            'ip_address' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( $_SERVER['REMOTE_ADDR'] ) : '',
        ] );
        $url = get_permalink( $post_id );
        if ( $url ) {
            wp_redirect( $url );
            exit;
        }
    }
});

// TODO: Add QR code generation, tracking, and UI integration 