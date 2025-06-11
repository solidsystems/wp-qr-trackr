<?php
// QR Code generation logic for QR Trackr

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Use PHP QR Code library (https://github.com/kazuhikoarase/qrcode-generator/tree/master/php)
// For now, use a simple implementation or placeholder function

require_once QR_TRACKR_PLUGIN_DIR . 'vendor/autoload.php';
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\RoundBlockSizeMode;

function qr_trackr_get_or_create_tracking_link( $post_id ) {
    global $wpdb;
    $links_table = $wpdb->prefix . 'qr_trackr_links';
    $link = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $links_table WHERE post_id = %d", $post_id ) );
    if ( $link ) {
        return $link;
    } else {
        $destination_url = get_permalink( $post_id );
        $wpdb->insert( $links_table, [
            'post_id' => intval( $post_id ),
            'destination_url' => esc_url_raw( $destination_url ),
        ] );
        $id = $wpdb->insert_id;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $links_table WHERE id = %d", $id ) );
    }
}

function qr_trackr_generate_qr_image( $url, $size = 300, $post_id = null ) {
    if ( $post_id ) {
        $link = qr_trackr_get_or_create_tracking_link( $post_id );
        $home = home_url();
        $url = trailingslashit( $home ) . 'qr-trackr/redirect/' . intval( $link->id );
    } else {
        $url = esc_url_raw( $url );
    }
    $size = intval( $size );
    // Generate a unique filename for the QR code
    $upload_dir = wp_upload_dir();
    $qr_dir = trailingslashit( $upload_dir['basedir'] ) . 'qr-trackr/';
    if ( ! file_exists( $qr_dir ) ) {
        wp_mkdir_p( $qr_dir );
    }
    $filename = 'qr_' . md5( $url . $size ) . '.png';
    $filepath = $qr_dir . $filename;
    $fileurl = trailingslashit( $upload_dir['baseurl'] ) . 'qr-trackr/' . $filename;
    if ( ! file_exists( $filepath ) ) {
        $qr = new QrCode(
            data: $url,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Low,
            size: $size,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255)
        );
        $writer = new PngWriter();
        $result = $writer->write($qr);
        file_put_contents($filepath, $result->getString());
    }
    return $fileurl;
}

function qr_trackr_generate_qr_image_for_link( $link_id, $size = 300 ) {
    global $wpdb;
    $links_table = $wpdb->prefix . 'qr_trackr_links';
    $link = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $links_table WHERE id = %d", $link_id ) );
    if ( ! $link ) return '';
    $home = home_url();
    $url = trailingslashit( $home ) . 'qr-trackr/redirect/' . intval( $link->id );
    $size = intval( $size );
    $upload_dir = wp_upload_dir();
    $qr_dir = trailingslashit( $upload_dir['basedir'] ) . 'qr-trackr/';
    if ( ! file_exists( $qr_dir ) ) {
        wp_mkdir_p( $qr_dir );
    }
    $filename = 'qr_' . md5( $url . $size ) . '.png';
    $filepath = $qr_dir . $filename;
    $fileurl = trailingslashit( $upload_dir['baseurl'] ) . 'qr-trackr/' . $filename;
    if ( ! file_exists( $filepath ) ) {
        $qr = new QrCode(
            data: $url,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Low,
            size: $size,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255)
        );
        $writer = new PngWriter();
        $result = $writer->write($qr);
        file_put_contents($filepath, $result->getString());
    }
    return $fileurl;
}

function qr_trackr_get_all_tracking_links_for_post( $post_id ) {
    global $wpdb;
    $links_table = $wpdb->prefix . 'qr_trackr_links';
    return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $links_table WHERE post_id = %d ORDER BY created_at DESC", $post_id ) );
} 