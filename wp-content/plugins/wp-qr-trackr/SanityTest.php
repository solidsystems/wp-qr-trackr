<?php
/**
 * SanityTest
 *
 * Basic sanity test for QR Trackr plugin test suite.
 *
 * @package QR_Trackr
 */

use Yoast\WPTestUtils\BrainMonkey\TestCase;

/**
 * Basic sanity test class for QR Trackr plugin.
 *
 * @coversNothing
 */
class SanityTest extends TestCase {
	/**
	 * Test that true is true (sanity check).
	 *
	 * @return void
	 */
	public function testSanity() {
		$this->assertTrue( true );
	}
}
