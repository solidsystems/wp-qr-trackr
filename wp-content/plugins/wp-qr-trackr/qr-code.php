<?php
/**
 * QR Code generation module for QR Trackr.
 *
 * Handles all QR code image generation and shape logic.
 * All DB helpers are now in module-utility.php.
 *
 * @package QR_Trackr
 */

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

/**
 * Returns available QR shapes (filterable).
 *
 * @return array Filtered array of available QR shapes.
 */
function qr_trackr_get_available_shapes() {
	$shapes = array(
		'standard' => __( 'Standard', 'qr-trackr' ),
		// Pro plugin can add more via filter
	);
	return apply_filters( 'qr_trackr_qr_shapes', $shapes );
}

/**
 * Generate a QR code image for a given URL or post.
 *
 * @param string   $url The URL to encode (ignored if $post_id is set).
 * @param int      $size The size of the QR code image.
 * @param int|null $post_id Optional. If set, generates for the post's tracking link.
 * @param string   $shape The QR code shape.
 * @return string URL to the generated QR code image.
 */
function qr_trackr_generate_qr_image( $url, $size = 300, $post_id = null, $shape = 'standard' ) {
	if ( null !== $post_id && $post_id ) {
		// Use utility to get or create tracking link
		$link = function_exists( 'qr_trackr_get_or_create_tracking_link' ) ? qr_trackr_get_or_create_tracking_link( $post_id ) : null;
		if ( null !== $link && $link ) {
			$home = home_url();
			$url  = trailingslashit( $home ) . 'qr-trackr/redirect/' . intval( $link->id );
		}
	} else {
		$url = esc_url_raw( $url );
	}
	$size       = intval( $size );
	$shape      = ( $shape ) ? $shape : 'standard';
	$upload_dir = wp_upload_dir();
	$qr_dir     = trailingslashit( $upload_dir['basedir'] ) . 'qr-trackr/';
	if ( ! file_exists( $qr_dir ) ) {
		wp_mkdir_p( $qr_dir );
	}
	$filename = 'qr_' . md5( $url . $size . $shape ) . '.png';
	$filepath = $qr_dir . $filename;
	$fileurl  = trailingslashit( $upload_dir['baseurl'] ) . 'qr-trackr/' . $filename;
	if ( ! file_exists( $filepath ) ) {
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		if ( 'standard' === $shape ) {
			$qr     = new QrCode(
				data: $url,
				encoding: new Encoding( 'UTF-8' ),
				errorCorrectionLevel: ErrorCorrectionLevel::Low,
				size: $size,
				margin: 10,
				roundBlockSizeMode: RoundBlockSizeMode::Margin,
				foregroundColor: new Color( 0, 0, 0 ),
				backgroundColor: new Color( 255, 255, 255 )
			);
			$writer = new PngWriter();
			$result = $writer->write( $qr );
			$wp_filesystem->put_contents( $filepath, $result->getString(), FS_CHMOD_FILE );
		} else {
			// Allow custom shape generation via filter
			$custom = apply_filters( 'qr_trackr_generate_custom_shape', null, $url, $size, $shape, $filepath );
			if ( null === $custom ) {
				$qr     = new QrCode(
					data: $url,
					encoding: new Encoding( 'UTF-8' ),
					errorCorrectionLevel: ErrorCorrectionLevel::Low,
					size: $size,
					margin: 10,
					roundBlockSizeMode: RoundBlockSizeMode::Margin,
					foregroundColor: new Color( 0, 0, 0 ),
					backgroundColor: new Color( 255, 255, 255 )
				);
				$writer = new PngWriter();
				$result = $writer->write( $qr );
				$wp_filesystem->put_contents( $filepath, $result->getString(), FS_CHMOD_FILE );
			}
		}
	}
	return $fileurl;
}
