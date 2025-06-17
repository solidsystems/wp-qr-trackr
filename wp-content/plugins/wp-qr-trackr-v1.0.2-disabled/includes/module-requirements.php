<?php
/**
 * Requirements Module
 * 
 * Handles plugin requirements checking and library management.
 */

if (!defined('ABSPATH')) {
	exit;
}

if ( function_exists( 'qr_trackr_debug_log' ) ) {
	qr_trackr_debug_log('Loading module-requirements.php...');
}

/**
 * Check if the QR code library is available
 */
function qr_trackr_check_library() {
	if (class_exists('Endroid\QrCode\QrCode')) {
		return true;
	}
	
	// Check if Composer autoload exists
	$composer_autoload = plugin_dir_path(dirname(__FILE__)) . 'vendor/autoload.php';
	if (file_exists($composer_autoload)) {
		require_once $composer_autoload;
		return class_exists('Endroid\QrCode\QrCode');
	}
	
	return false;
}

/**
 * Check if pretty permalinks are enabled
 * 
 * @return bool True if pretty permalinks are enabled
 */
function qr_trackr_check_permalinks() {
	static $permalink_status = null;
	
	// Return cached status if available
	if ($permalink_status !== null) {
		return $permalink_status;
	}
	
	// Check if we've already logged this status
	$last_logged = get_option('qr_trackr_permalink_status_logged');
	$current_time = time();
	
	// Only log if we haven't logged in the last hour
	if (!$last_logged || ($current_time - $last_logged) > 3600) {
		$permalink_status = get_option('permalink_structure') !== '';
		
		if (!$permalink_status) {
			qr_trackr_debug_log('Pretty permalinks not enabled');
		}
		
		update_option('qr_trackr_permalink_status_logged', $current_time);
	} else {
		$permalink_status = get_option('permalink_structure') !== '';
	}
	
	return $permalink_status;
}

/**
 * Check if the uploads directory is writable
 */
function qr_trackr_check_uploads() {
	$upload_dir = wp_upload_dir();
	return wp_is_writable($upload_dir['basedir']);
}

/**
 * Get all plugin requirements status
 */
function qr_trackr_check_requirements() {
	$requirements = array(
		'library' => qr_trackr_check_library(),
		'permalinks' => qr_trackr_check_permalinks(),
		'uploads' => qr_trackr_check_uploads()
	);
	
	// Store requirements status
	update_option('qr_trackr_requirements', $requirements);
	
	return $requirements;
}

/**
 * Display admin notice for missing requirements
 */
function qr_trackr_requirements_notice() {
	$requirements = qr_trackr_check_requirements();
	$missing = array();
	
	if (!$requirements['library']) {
		$missing[] = 'QR code library (Endroid QR Code)';
	}
	
	if (!$requirements['permalinks']) {
		$missing[] = 'Pretty permalinks (<a href="' . esc_url(admin_url('options-permalink.php')) . '" target="_blank">update your permalink settings</a>)';
	}
	
	if (!$requirements['uploads']) {
		$missing[] = 'Writable uploads directory';
	}
	
	if (!empty($missing)) {
		?>
		<div class="notice notice-error">
			<p><strong>QR Trackr:</strong> The following requirements are missing:</p>
			<ul>
				<?php foreach ($missing as $item): ?>
					<li><?php echo $item; ?></li>
					<?php if (strpos($item, 'Pretty permalinks') !== false): ?>
						<li style="margin-top:0; margin-bottom:1em; color:#666; font-size:90%; list-style:none;">"Post name" is the most commonly used setting.</li>
					<?php endif; ?>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}
}
add_action('admin_notices', 'qr_trackr_requirements_notice');

if ( function_exists( 'qr_trackr_debug_log' ) ) {
	qr_trackr_debug_log('Loaded module-requirements.php.');
} 