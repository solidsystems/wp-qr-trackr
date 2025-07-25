<?php
/**
 * Unit test class for the ValidClassName sniff.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\PEAR\Tests\NamingConventions;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;

/**
 * Unit test class for the ValidClassName sniff.
 *
 * @covers \PHP_CodeSniffer\Standards\PEAR\Sniffs\NamingConventions\ValidClassNameSniff
 */
final class ValidClassNameUnitTest extends AbstractSniffUnitTest {



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
			5  => 1,
			7  => 2,
			9  => 1,
			19 => 1,
			24 => 1,
			26 => 2,
			28 => 1,
			38 => 1,
			40 => 2,
			42 => 2,
			44 => 1,
			46 => 1,
			50 => 1,
			52 => 2,
			54 => 1,
			64 => 1,
			66 => 2,
			68 => 1,
			72 => 1,
			74 => 2,
			76 => 1,
			86 => 1,
			88 => 2,
			90 => 1,
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
