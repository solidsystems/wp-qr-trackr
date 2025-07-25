<?php
/**
 * Unit test class for the UnnecessaryStringConcat sniff.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Generic\Tests\Strings;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;

/**
 * Unit test class for the UnnecessaryStringConcat sniff.
 *
 * @covers \PHP_CodeSniffer\Standards\Generic\Sniffs\Strings\UnnecessaryStringConcatSniff
 */
final class UnnecessaryStringConcatUnitTest extends AbstractSniffUnitTest {



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
			case 'UnnecessaryStringConcatUnitTest.1.inc':
				return array(
					2  => 1,
					6  => 1,
					9  => 1,
					12 => 1,
					19 => 1,
					20 => 1,
				);

			case 'UnnecessaryStringConcatUnitTest.js':
				return array(
					1  => 1,
					8  => 1,
					11 => 1,
					14 => 1,
					15 => 1,
				);

			default:
				return array();
		}//end switch
	}//end getErrorList()


	/**
	 * Returns the lines where warnings should occur.
	 *
	 * The key of the array should represent the line number and the value
	 * should represent the number of warnings that should occur on that line.
	 *
	 * @param string $testFile The name of the file being tested.
	 *
	 * @return array<int, int>
	 */
	public function getWarningList( $testFile = '' ) {
		switch ( $testFile ) {
			case 'UnnecessaryStringConcatUnitTest.1.inc':
				return array(
					33 => 1,
				);

			default:
				return array();
		}
	}//end getWarningList()
}//end class
