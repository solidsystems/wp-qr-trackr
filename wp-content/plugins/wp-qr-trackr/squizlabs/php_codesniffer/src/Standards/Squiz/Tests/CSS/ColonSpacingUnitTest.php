<?php
/**
 * Unit test class for the ColonSpacing sniff.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Squiz\Tests\CSS;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;

/**
 * Unit test class for the ColonSpacing sniff.
 *
 * @covers \PHP_CodeSniffer\Standards\Squiz\Sniffs\CSS\ColonSpacingSniff
 */
final class ColonSpacingUnitTest extends AbstractSniffUnitTest {



	/**
	 * Returns the lines where errors should occur.
	 *
	 * The key of the array should represent the line number and the value
	 * should represent the number of errors that should occur on that line.
	 *
	 * @return array<int, int>
	 */
	public function getErrorList() {
		return array(
			3  => 1,
			4  => 2,
			5  => 1,
			6  => 1,
			8  => 1,
			27 => 1,
			28 => 1,
			29 => 1,
			30 => 1,
			32 => 1,
			41 => 1,
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
	public function getWarningList() {
		return array();
	}//end getWarningList()
}//end class
