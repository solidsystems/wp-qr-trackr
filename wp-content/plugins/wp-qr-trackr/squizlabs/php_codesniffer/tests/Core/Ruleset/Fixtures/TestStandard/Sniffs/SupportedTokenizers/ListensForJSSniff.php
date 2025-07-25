<?php
/**
 * Test fixture.
 *
 * @see \PHP_CodeSniffer\Tests\Core\Ruleset\PopulateTokenListenersSupportedTokenizersTest
 */

namespace Fixtures\TestStandard\Sniffs\SupportedTokenizers;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

class ListensForJSSniff implements Sniff {


	public $supportedTokenizers = array( 'JS' );

	public function register() {
		return array( T_WHITESPACE );
	}

	public function process( File $phpcsFile, $stackPtr ) {
		// Do something.
	}
}
