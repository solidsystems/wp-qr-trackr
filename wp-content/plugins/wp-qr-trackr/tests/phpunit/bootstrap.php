<?php
/**
 * PHPUnit bootstrap file for WP QR Trackr plugin.
 *
 * @package wp-qr-trackr
 * @since 1.0.0
 */

// Load the WordPress test environment.
$_tests_dir = getenv('WP_TESTS_DIR');
if (!$_tests_dir) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin()
{
	require dirname(dirname(dirname(__FILE__))) . '/wp-qr-trackr.php';
}

require $_tests_dir . '/includes/bootstrap.php';

// Load the plugin after WordPress is loaded.
add_action('muplugins_loaded', '_manually_load_plugin');
