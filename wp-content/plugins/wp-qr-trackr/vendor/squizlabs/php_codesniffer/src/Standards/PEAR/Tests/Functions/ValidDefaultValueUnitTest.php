<?php
/**
 * Unit test class for the ValidDefaultValue sniff.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\PEAR\Tests\Functions;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;

/**
 * Unit test class for the ValidDefaultValue sniff.
 *
 * @covers \PHP_CodeSniffer\Standards\PEAR\Sniffs\Functions\ValidDefaultValueSniff
 */
final class ValidDefaultValueUnitTest extends AbstractSniffUnitTest {



	/**
	 * Returns the lines where errors should occur.
	 *
	 * The key of the array should represent the line number and the value
	 * should represent the number of errors that should occur on that line.
	 *
	 * @param string $testFile The name of the file being tested.
	 *
	 * @return array<int, int>
	 */
	public function getErrorList( $testFile = '' ) {
		switch ( $testFile ) {
			case 'ValidDefaultValueUnitTest.1.inc':
				return array(
					29  => 1,
					34  => 1,
					39  => 1,
					71  => 1,
					76  => 1,
					81  => 1,
					91  => 1,
					99  => 1,
					101 => 1,
					106 => 1,
					114 => 1,
				);

			default:
				return array();
		}
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
