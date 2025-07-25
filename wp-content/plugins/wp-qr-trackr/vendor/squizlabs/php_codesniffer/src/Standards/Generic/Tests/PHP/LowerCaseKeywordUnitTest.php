<?php
/**
 * Unit test class for the LowerCaseKeyword sniff.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Generic\Tests\PHP;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;

/**
 * Unit test class for the LowerCaseKeyword sniff.
 *
 * @covers \PHP_CodeSniffer\Standards\Generic\Sniffs\PHP\LowerCaseKeywordSniff
 */
final class LowerCaseKeywordUnitTest extends AbstractSniffUnitTest {



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
			10 => 3,
			11 => 4,
			12 => 1,
			13 => 3,
			14 => 7,
			15 => 1,
			19 => 1,
			20 => 1,
			21 => 1,
			25 => 1,
			28 => 1,
			31 => 1,
			32 => 1,
			35 => 1,
			39 => 2,
			42 => 1,
			44 => 1,
			47 => 1,
			48 => 1,
			52 => 3,
			54 => 1,
			57 => 2,
			58 => 1,
			60 => 1,
			68 => 1,
			69 => 1,
			70 => 1,
			71 => 1,
			72 => 1,
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
