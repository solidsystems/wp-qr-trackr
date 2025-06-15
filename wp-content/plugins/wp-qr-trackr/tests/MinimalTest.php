<?php
/**
 * MinimalTest PHPUnit test case for QR Trackr plugin.
 *
 * Provides a minimal test to assert the test suite is functioning.
 *
 * @package QR_Trackr\Tests
 */

use Yoast\WPTestUtils\BrainMonkey\TestCase;

/**
 * MinimalTest class for basic true assertion.
 *
 * Provides a minimal test case to verify PHPUnit is working.
 *
 * @package QR_Trackr\Tests
 */
class MinimalTest extends TestCase {
	/**
	 * Test that true is true.
	 *
	 * Ensures the test suite is running correctly.
	 *
	 * @return void
	 */
	public function testTrue() {
		$this->assertTrue( true );
	}
}
