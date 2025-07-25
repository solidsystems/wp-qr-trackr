<?php
/**
 * Unit test class for the NullableWhitespace sniff.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2018 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\PSR12\Tests\Functions;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;

/**
 * Unit test class for the NullableWhitespace sniff.
 *
 * @covers \PHP_CodeSniffer\Standards\PSR12\Sniffs\Functions\NullableTypeDeclarationSniff
 */
final class NullableTypeDeclarationUnitTest extends AbstractSniffUnitTest {



	/**
	 * Returns the lines where errors should occur.
	 *
	 * The key of the array should represent the line number and the value
	 * should represent the number of errors that should occur on that line.
	 *
	 * @return array<int, int>
	 */
	protected function getErrorList() {
		return array(
			23 => 1,
			24 => 1,
			25 => 1,
			30 => 1,
			31 => 1,
			32 => 1,
			43 => 2,
			48 => 1,
			50 => 1,
			51 => 1,
			53 => 1,
			57 => 2,
			58 => 2,
			59 => 2,
			87 => 1,
			90 => 1,
			91 => 1,
			95 => 1,
		);
	}//end getErrorList()


	/**
	 * Returns the lines where warnings should occur.
	 *
	 * The key of the array should represent the line number and the value
	 * should represent the number of warnings that should occur on that line.
	 *
	 * @return array<int, int>
	 */
	protected function getWarningList() {
		return array();
	}//end getWarningList()
}//end class
