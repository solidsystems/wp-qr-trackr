<?php
/**
 * Unit test class for the EndFileNewline sniff.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\PSR2\Tests\Files;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;

/**
 * Unit test class for the EndFileNewline sniff.
 *
 * @covers \PHP_CodeSniffer\Standards\PSR2\Sniffs\Files\EndFileNewlineSniff
 */
final class EndFileNewlineUnitTest extends AbstractSniffUnitTest {



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
			case 'EndFileNewlineUnitTest.1.inc':
			case 'EndFileNewlineUnitTest.3.inc':
			case 'EndFileNewlineUnitTest.6.inc':
			case 'EndFileNewlineUnitTest.7.inc':
			case 'EndFileNewlineUnitTest.9.inc':
			case 'EndFileNewlineUnitTest.10.inc':
				return array( 2 => 1 );
			case 'EndFileNewlineUnitTest.11.inc':
			case 'EndFileNewlineUnitTest.12.inc':
			case 'EndFileNewlineUnitTest.13.inc':
				return array( 1 => 1 );
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
		return array();
	}//end getWarningList()
}//end class
