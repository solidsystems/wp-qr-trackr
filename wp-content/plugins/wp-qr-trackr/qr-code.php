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

// Add a filter for available QR shapes
function qr_trackr_get_available_shapes() {
    $shapes = [
        'standard' => __('Standard', 'qr-trackr'),
        // Pro plugin can add more via filter
    ];
    return apply_filters('qr_trackr_qr_shapes', $shapes);
}

// Update QR code generation to accept a shape parameter
function qr_trackr_generate_qr_image( $url, $size = 300, $post_id = null, $shape = 'standard' ) {
    if ( $post_id ) {
        $link = qr_trackr_get_or_create_tracking_link( $post_id );
        $home = home_url();
        $url = trailingslashit( $home ) . 'qr-trackr/redirect/' . intval( $link->id );
    } else {
        $url = esc_url_raw( $url );
    }
    $size = intval( $size );
    $shape = $shape ?: 'standard';
    // Generate a unique filename for the QR code, including shape
    $upload_dir = wp_upload_dir();
    $qr_dir = trailingslashit( $upload_dir['basedir'] ) . 'qr-trackr/';
    if ( ! file_exists( $qr_dir ) ) {
        wp_mkdir_p( $qr_dir );
    }
    $filename = 'qr_' . md5( $url . $size . $shape ) . '.png';
    $filepath = $qr_dir . $filename;
    $fileurl = trailingslashit( $upload_dir['baseurl'] ) . 'qr-trackr/' . $filename;
    if ( ! file_exists( $filepath ) ) {
        // Standard shape uses default QR code
        if ($shape === 'standard') {
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
        } else {
            // Allow custom shape generation via filter
            $custom = apply_filters('qr_trackr_generate_custom_shape', null, $url, $size, $shape, $filepath);
            if ($custom === null) {
                // fallback to standard if not handled
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
        }
    }
    return $fileurl;
}

function qr_trackr_generate_qr_image_for_link( $link_id, $size = 300, $shape = 'standard' ) {
    global $wpdb;
    $links_table = $wpdb->prefix . 'qr_trackr_links';
    $link = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $links_table WHERE id = %d", $link_id ) );
    if ( ! $link ) return '';
    $home = home_url();
    $url = trailingslashit( $home ) . 'qr-trackr/redirect/' . intval( $link->id );
    $size = intval( $size );
    $shape = $shape ?: 'standard';
    $upload_dir = wp_upload_dir();
    $qr_dir = trailingslashit( $upload_dir['basedir'] ) . 'qr-trackr/';
    if ( ! file_exists( $qr_dir ) ) {
        wp_mkdir_p( $qr_dir );
    }
    $filename = 'qr_' . md5( $url . $size . $shape ) . '.png';
    $filepath = $qr_dir . $filename;
    $fileurl = trailingslashit( $upload_dir['baseurl'] ) . 'qr-trackr/' . $filename;
    if ( ! file_exists( $filepath ) ) {
        if ($shape === 'standard') {
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
        } else {
            $custom = apply_filters('qr_trackr_generate_custom_shape', null, $url, $size, $shape, $filepath);
            if ($custom === null) {
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
        }
    }
    return $fileurl;
}

function qr_trackr_get_all_tracking_links_for_post( $post_id ) {
    global $wpdb;
    $links_table = $wpdb->prefix . 'qr_trackr_links';
    return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $links_table WHERE post_id = %d ORDER BY created_at DESC", $post_id ) );
} 