<?php
/**
 * Unit test class for the RequireStrictType sniff.
 *
 * @author    Sertan Danis <sdanis@squiz.net>
 * @copyright 2006-2019 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Generic\Tests\PHP;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;

/**
 * Unit test class for the RequireStrictType sniff.
 *
 * @covers \PHP_CodeSniffer\Standards\Generic\Sniffs\PHP\RequireStrictTypesSniff
 */
final class RequireStrictTypesUnitTest extends AbstractSniffUnitTest {



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
			case 'RequireStrictTypesUnitTest.2.inc':
			case 'RequireStrictTypesUnitTest.5.inc':
			case 'RequireStrictTypesUnitTest.6.inc':
			case 'RequireStrictTypesUnitTest.10.inc':
				return array( 1 => 1 );

			default:
				return array();
		}
	}//end getErrorList()


	/**
	 * Returns the lines where warnings should occur.
	 *
	 * @param string $testFile The name of the file being tested.
	 *
	 * @return array<int, int>
	 */
	public function getWarningList( $testFile = '' ) {
		switch ( $testFile ) {
			case 'RequireStrictTypesUnitTest.11.inc':
			case 'RequireStrictTypesUnitTest.12.inc':
			case 'RequireStrictTypesUnitTest.14.inc':
			case 'RequireStrictTypesUnitTest.15.inc':
				return array( 3 => 1 );

			default:
				return array();
		}
	}//end getWarningList()
}//end class
