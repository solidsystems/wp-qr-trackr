<?php
/**
 * Unit test class for the Indentation sniff.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Squiz\Tests\CSS;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;

/**
 * Unit test class for the Indentation sniff.
 *
 * @covers \PHP_CodeSniffer\Standards\Squiz\Sniffs\CSS\IndentationSniff
 */
final class IndentationUnitTest extends AbstractSniffUnitTest {



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
			case 'IndentationUnitTest.1.css':
				return array(
					2  => 1,
					3  => 1,
					5  => 1,
					6  => 1,
					7  => 1,
					12 => 1,
					30 => 1,
					32 => 1,
					50 => 1,
					52 => 1,
					53 => 1,
					66 => 1,
					67 => 1,
					68 => 1,
					69 => 1,
					70 => 1,
					71 => 1,
					72 => 1,
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
	 * @return array<int, int>
	 */
	public function getWarningList() {
		return array();
	}//end getWarningList()
}//end class
