<?php
/**
 * Test QR Code Generation Script
 *
 * This script can be run from the WordPress admin to test QR code generation.
 * Add ?test_qr=1 to any admin page URL to run this test.
 */

// Only run if test parameter is present
if ( ! isset( $_GET['test_qr'] ) || ! current_user_can( 'manage_options' ) ) {
	return;
}

// Test QR code generation
echo '<div style="background: #fff; padding: 20px; margin: 20px; border: 1px solid #ccc;">';
echo '<h2>QR Code Generation Test</h2>';

// Check if function exists
echo '<p><strong>Function exists:</strong> ' . ( function_exists( 'qrc_generate_qr_code' ) ? 'YES' : 'NO' ) . '</p>';

// Check autoloader
$autoload_path = QR_TRACKR_PLUGIN_DIR . 'vendor/autoload.php';
echo '<p><strong>Autoloader path:</strong> ' . $autoload_path . '</p>';
echo '<p><strong>Autoloader exists:</strong> ' . ( file_exists( $autoload_path ) ? 'YES' : 'NO' ) . '</p>';

// Try to load autoloader manually
if ( file_exists( $autoload_path ) ) {
	echo '<p><strong>Loading autoloader manually...</strong></p>';
	require_once $autoload_path;
	echo '<p style="color: green;">Autoloader loaded successfully</p>';
} else {
	echo '<p style="color: red;">Autoloader file not found</p>';
}

// Check if Endroid library is available (suppress deprecation warnings)
$old_error_reporting = error_reporting();
error_reporting( $old_error_reporting & ~E_DEPRECATED );
$endroid_available = class_exists( 'Endroid\QrCode\QrCode' );
error_reporting( $old_error_reporting );

echo '<p><strong>Endroid library available:</strong> ' . ( $endroid_available ? 'YES' : 'NO' ) . '</p>';

// Check for any Endroid classes
$endroid_classes = array_filter(
	get_declared_classes(),
	function ( $class ) {
		return strpos( $class, 'Endroid' ) !== false;
	}
);
echo '<p><strong>Endroid classes found:</strong> ' . ( empty( $endroid_classes ) ? 'NONE' : implode( ', ', $endroid_classes ) ) . '</p>';

// Test direct class loading
if ( ! $endroid_available ) {
	echo '<p><strong>Trying direct file loading...</strong></p>';
	$qr_code_file = QR_TRACKR_PLUGIN_DIR . 'vendor/endroid/qr-code/src/QrCode.php';
	if ( file_exists( $qr_code_file ) ) {
		require_once $qr_code_file;
		echo '<p style="color: green;">QR Code file loaded directly</p>';

		$old_error_reporting = error_reporting();
		error_reporting( $old_error_reporting & ~E_DEPRECATED );
		$endroid_available_after = class_exists( 'Endroid\QrCode\QrCode' );
		error_reporting( $old_error_reporting );

		echo '<p><strong>Endroid library available after direct load:</strong> ' . ( $endroid_available_after ? 'YES' : 'NO' ) . '</p>';
	} else {
		echo '<p style="color: red;">QR Code file not found at: ' . $qr_code_file . '</p>';
	}
}

// Test QR code generation
if ( function_exists( 'qrc_generate_qr_code' ) ) {
	echo '<p><strong>Testing QR code generation...</strong></p>';

	$test_url = 'https://example.com/test';
	$result   = qrc_generate_qr_code( $test_url );

	if ( is_wp_error( $result ) ) {
		echo '<p style="color: red;"><strong>Error:</strong> ' . esc_html( $result->get_error_message() ) . '</p>';
	} else {
		echo '<p style="color: green;"><strong>Success!</strong> QR code generated: ' . esc_html( $result ) . '</p>';
		echo '<p><img src="' . esc_url( $result ) . '" style="border: 1px solid #ddd;" /></p>';
	}
} else {
	echo '<p style="color: red;"><strong>Error:</strong> qrc_generate_qr_code function not found</p>';
}

echo '</div>';
