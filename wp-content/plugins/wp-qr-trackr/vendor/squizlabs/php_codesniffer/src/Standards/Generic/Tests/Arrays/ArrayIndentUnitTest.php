<?php
/**
 * Unit test class for the ArrayIndent sniff.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Generic\Tests\Arrays;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;

/**
 * Unit test class for the ArrayIndent sniff.
 *
 * @covers \PHP_CodeSniffer\Standards\Generic\Sniffs\Arrays\ArrayIndentSniff
 */
final class ArrayIndentUnitTest extends AbstractSniffUnitTest {



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
			14  => 1,
			15  => 1,
			17  => 1,
			30  => 1,
			31  => 1,
			33  => 1,
			41  => 1,
			62  => 1,
			63  => 1,
			69  => 1,
			77  => 1,
			78  => 1,
			79  => 1,
			85  => 1,
			86  => 1,
			87  => 1,
			88  => 1,
			98  => 1,
			110 => 1,
			119 => 1,
			126 => 1,
			127 => 1,
			133 => 1,
			141 => 1,
			142 => 1,
			143 => 1,
			149 => 1,
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
