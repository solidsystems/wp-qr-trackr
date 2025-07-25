<?php
/**
 * Unit test class for the ClassInstantiation sniff.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\PSR12\Tests\Classes;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;

/**
 * Unit test class for the ClassInstantiation sniff.
 *
 * @covers \PHP_CodeSniffer\Standards\PSR12\Sniffs\Classes\ClassInstantiationSniff
 */
final class ClassInstantiationUnitTest extends AbstractSniffUnitTest {



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
			4  => 1,
			9  => 1,
			11 => 1,
			14 => 1,
			16 => 1,
			20 => 1,
			21 => 1,
			22 => 1,
			24 => 1,
			25 => 1,
			30 => 1,
			32 => 1,
			33 => 1,
			34 => 1,
			37 => 1,
			38 => 1,
			47 => 1,
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
