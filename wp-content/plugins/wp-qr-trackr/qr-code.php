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

function qr_trackr_generate_qr_image( $url, $size = 300, $post_id = null ) {
    if ( $post_id ) {
        $home = home_url();
        $url = trailingslashit( $home ) . 'qr-trackr/scan/' . intval( $post_id );
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