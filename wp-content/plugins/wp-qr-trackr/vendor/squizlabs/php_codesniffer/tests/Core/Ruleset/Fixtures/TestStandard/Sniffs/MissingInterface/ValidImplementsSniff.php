<?php
/**
 * Test fixture.
 *
 * @see \PHP_CodeSniffer\Tests\Core\Ruleset\RegisterSniffsMissingInterfaceTest
 */

namespace Fixtures\TestStandard\Sniffs\MissingInterface;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

final class ValidImplementsSniff implements Sniff {


	public function register() {
		return array( T_OPEN_TAG );
	}

	public function process( File $phpcsFile, $stackPtr ) {
		// Do something.
	}
}
