<?php
/**
 * Unit test class for the OperatorSpacing sniff.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\PSR12\Tests\Operators;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;

/**
 * Unit test class for the OperatorSpacing sniff.
 *
 * @covers \PHP_CodeSniffer\Standards\PSR12\Sniffs\Operators\OperatorSpacingSniff
 */
final class OperatorSpacingUnitTest extends AbstractSniffUnitTest {



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
			case 'OperatorSpacingUnitTest.1.inc':
				return array(
					2  => 1,
					3  => 2,
					4  => 1,
					5  => 2,
					6  => 4,
					9  => 3,
					10 => 2,
					11 => 3,
					13 => 3,
					14 => 2,
					18 => 1,
					20 => 1,
					22 => 2,
					23 => 2,
					26 => 1,
					37 => 4,
					39 => 1,
					40 => 1,
					44 => 2,
					47 => 2,
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
