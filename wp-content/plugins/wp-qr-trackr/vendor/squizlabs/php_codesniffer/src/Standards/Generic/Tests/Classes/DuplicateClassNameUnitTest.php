<?php
/**
 * Unit test class for the DuplicateClassName sniff.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Generic\Tests\Classes;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;

/**
 * Unit test class for the DuplicateClassName sniff.
 *
 * @covers \PHP_CodeSniffer\Standards\Generic\Sniffs\Classes\DuplicateClassNameSniff
 */
final class DuplicateClassNameUnitTest extends AbstractSniffUnitTest {



	/**
	 * Returns the lines where errors should occur.
	 *
	 * The key of the array should represent the line number and the value
	 * should represent the number of errors that should occur on that line.
	 *
	 * @return array<int, int>
	 */
	public function getErrorList() {
		return array();
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
			case 'DuplicateClassNameUnitTest.1.inc':
				return array(
					10 => 1,
					11 => 1,
					12 => 1,
					13 => 1,
				);

			case 'DuplicateClassNameUnitTest.2.inc':
				return array(
					2 => 1,
					3 => 1,
					4 => 1,
					5 => 1,
				);

			case 'DuplicateClassNameUnitTest.5.inc':
				return array(
					3 => 1,
					7 => 1,
				);

			case 'DuplicateClassNameUnitTest.6.inc':
				return array( 10 => 1 );

			case 'DuplicateClassNameUnitTest.8.inc':
				return array(
					7 => 1,
					8 => 1,
				);

			case 'DuplicateClassNameUnitTest.9.inc':
				return array(
					3 => 1,
					4 => 1,
				);

			case 'DuplicateClassNameUnitTest.11.inc':
				return array( 13 => 1 );

			default:
				return array();
		}//end switch
	}//end getWarningList()
}//end class
