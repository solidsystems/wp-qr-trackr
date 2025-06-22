<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package QR_Trackr
 */

// Define a constant to indicate that tests are running.
define( 'WP_TESTS_RUNNING', true );

// Set a dummy HTTP_HOST to prevent warnings during CLI tests.
$_SERVER['HTTP_HOST'] = 'localhost';

// Load the WordPress test environment from the Docker container's path.
require_once '/var/www/html/wp-load.php';

// Load Composer autoloader.
require_once dirname( __DIR__ ) . '/vendor/autoload.php'; 