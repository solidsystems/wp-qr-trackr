<?php
/**
 * QR Trackr Debug Script
 * 
 * Upload this file to your WordPress root directory and visit it in browser
 * to diagnose QR code and rewrite rule issues.
 * 
 * URL: http://localhost:8081/qr-debug.php
 */

// Load WordPress
require_once 'wp-config.php';
require_once 'wp-load.php';

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'Access denied. Please log in as an administrator.' );
}

echo '<h1>QR Trackr Debug Information</h1>';

// Check permalink structure
echo '<h2>1. Permalink Structure</h2>';
$permalink_structure = get_option( 'permalink_structure' );
if ( empty( $permalink_structure ) ) {
    echo '<p style="color: red;"><strong>❌ PROBLEM:</strong> Permalink structure is set to "Plain" - QR redirects will not work!</p>';
    echo '<p><strong>Fix:</strong> Go to <a href="' . admin_url( 'options-permalink.php' ) . '">Settings → Permalinks</a> and choose any structure other than "Plain".</p>';
} else {
    echo '<p style="color: green;"><strong>✅ OK:</strong> Pretty permalinks enabled: <code>' . esc_html( $permalink_structure ) . '</code></p>';
}

// Check rewrite rules
echo '<h2>2. Rewrite Rules</h2>';
global $wp_rewrite;
$rules = get_option( 'rewrite_rules' );
$qr_rule_found = false;
if ( is_array( $rules ) ) {
    foreach ( $rules as $pattern => $rewrite ) {
        if ( strpos( $pattern, 'qr/' ) !== false ) {
            echo '<p style="color: green;"><strong>✅ Found QR rewrite rule:</strong><br>';
            echo 'Pattern: <code>' . esc_html( $pattern ) . '</code><br>';
            echo 'Rewrite: <code>' . esc_html( $rewrite ) . '</code></p>';
            $qr_rule_found = true;
            break;
        }
    }
}

if ( ! $qr_rule_found ) {
    echo '<p style="color: red;"><strong>❌ PROBLEM:</strong> QR rewrite rules not found!</p>';
    echo '<p><strong>Fix:</strong> Try deactivating and reactivating the plugin.</p>';
}

// Check database table
echo '<h2>3. Database Table</h2>';
global $wpdb;
$table_name = $wpdb->prefix . 'qr_trackr_links';
$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) );

if ( $table_exists === $table_name ) {
    echo '<p style="color: green;"><strong>✅ OK:</strong> Database table exists: <code>' . esc_html( $table_name ) . '</code></p>';
    
    // Count QR codes
    $qr_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );
    echo '<p><strong>QR Codes in database:</strong> ' . absint( $qr_count ) . '</p>';
    
    // Show recent QR codes
    if ( $qr_count > 0 ) {
        $recent_qrs = $wpdb->get_results( "SELECT id, qr_code, destination_url FROM $table_name ORDER BY created_at DESC LIMIT 5" );
        echo '<h3>Recent QR Codes:</h3>';
        echo '<ul>';
        foreach ( $recent_qrs as $qr ) {
            $test_url = home_url( '/qr/' . $qr->qr_code );
            echo '<li>';
            echo '<strong>Code:</strong> ' . esc_html( $qr->qr_code ) . '<br>';
            echo '<strong>Destination:</strong> ' . esc_html( $qr->destination_url ) . '<br>';
            echo '<strong>Test URL:</strong> <a href="' . esc_url( $test_url ) . '" target="_blank">' . esc_html( $test_url ) . '</a>';
            echo '</li><br>';
        }
        echo '</ul>';
    }
} else {
    echo '<p style="color: red;"><strong>❌ PROBLEM:</strong> Database table does not exist!</p>';
    echo '<p><strong>Fix:</strong> Try deactivating and reactivating the plugin.</p>';
}

// Check uploads directory
echo '<h2>4. QR Code Images Directory</h2>';
$upload_dir = wp_upload_dir();
if ( ! empty( $upload_dir['error'] ) ) {
    echo '<p style="color: red;"><strong>❌ PROBLEM:</strong> Upload directory error: ' . esc_html( $upload_dir['error'] ) . '</p>';
} else {
    $qr_dir = trailingslashit( $upload_dir['basedir'] ) . 'qr-codes';
    $qr_url = trailingslashit( $upload_dir['baseurl'] ) . 'qr-codes';
    
    if ( ! file_exists( $qr_dir ) ) {
        echo '<p style="color: orange;"><strong>⚠️ WARNING:</strong> QR codes directory does not exist yet.</p>';
        echo '<p><strong>Path:</strong> <code>' . esc_html( $qr_dir ) . '</code></p>';
        echo '<p>This will be created automatically when first QR code is generated.</p>';
    } else {
        echo '<p style="color: green;"><strong>✅ OK:</strong> QR codes directory exists</p>';
        echo '<p><strong>Path:</strong> <code>' . esc_html( $qr_dir ) . '</code></p>';
        echo '<p><strong>URL:</strong> <a href="' . esc_url( $qr_url ) . '" target="_blank">' . esc_html( $qr_url ) . '</a></p>';
        
        // Check permissions
        if ( is_writable( $qr_dir ) ) {
            echo '<p style="color: green;"><strong>✅ OK:</strong> Directory is writable</p>';
        } else {
            echo '<p style="color: red;"><strong>❌ PROBLEM:</strong> Directory is not writable!</p>';
        }
        
        // List QR code files
        $qr_files = glob( $qr_dir . '/*.png' );
        if ( ! empty( $qr_files ) ) {
            echo '<p><strong>QR Code Images Found:</strong> ' . count( $qr_files ) . '</p>';
            echo '<ul>';
            foreach ( array_slice( $qr_files, 0, 5 ) as $file ) {
                $filename = basename( $file );
                $file_url = $qr_url . '/' . $filename;
                echo '<li><a href="' . esc_url( $file_url ) . '" target="_blank">' . esc_html( $filename ) . '</a></li>';
            }
            echo '</ul>';
        } else {
            echo '<p><strong>No QR code image files found yet.</strong></p>';
        }
    }
}

// Test QR code generation
echo '<h2>5. Test QR Code Generation</h2>';
if ( function_exists( 'qr_trackr_generate_qr_image' ) ) {
    echo '<p style="color: green;"><strong>✅ OK:</strong> QR generation function exists</p>';
    
    // Try generating a test QR code
    $test_result = qr_trackr_generate_qr_image( 'TEST123', array( 'size' => 100 ) );
    if ( is_wp_error( $test_result ) ) {
        echo '<p style="color: red;"><strong>❌ PROBLEM:</strong> QR generation failed: ' . esc_html( $test_result->get_error_message() ) . '</p>';
    } else {
        echo '<p style="color: green;"><strong>✅ OK:</strong> Test QR code generated successfully</p>';
        echo '<p><strong>Test QR URL:</strong> <a href="' . esc_url( $test_result ) . '" target="_blank">' . esc_html( $test_result ) . '</a></p>';
        echo '<p><img src="' . esc_url( $test_result ) . '" alt="Test QR Code" style="max-width: 100px;" /></p>';
    }
} else {
    echo '<p style="color: red;"><strong>❌ PROBLEM:</strong> QR generation function not found!</p>';
}

// Plugin status
echo '<h2>6. Plugin Status</h2>';
if ( is_plugin_active( 'wp-qr-trackr/wp-qr-trackr.php' ) ) {
    echo '<p style="color: green;"><strong>✅ OK:</strong> Plugin is active</p>';
} else {
    echo '<p style="color: red;"><strong>❌ PROBLEM:</strong> Plugin is not active!</p>';
}

echo '<h2>7. Quick Fixes</h2>';
echo '<p><strong>If QR redirects are not working:</strong></p>';
echo '<ol>';
echo '<li>Go to <a href="' . admin_url( 'options-permalink.php' ) . '">Settings → Permalinks</a></li>';
echo '<li>Make sure it\'s NOT set to "Plain"</li>';
echo '<li>Click "Save Changes" to flush rewrite rules</li>';
echo '<li>Try your QR URL again</li>';
echo '</ol>';

echo '<p><strong>If QR images are not showing:</strong></p>';
echo '<ol>';
echo '<li>Check that the uploads directory is writable</li>';
echo '<li>Try creating a new QR code to trigger image generation</li>';
echo '<li>Check browser developer tools for 404 errors on image URLs</li>';
echo '</ol>';

echo '<hr>';
echo '<p><em>Generated at: ' . current_time( 'Y-m-d H:i:s' ) . '</em></p>';
echo '<p><em>Delete this file after debugging for security.</em></p>'; 