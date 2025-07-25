<?php
/**
 * Unit test class for the LowerCaseConstant sniff.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Generic\Tests\PHP;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;

/**
 * Unit test class for the LowerCaseConstant sniff.
 *
 * @covers \PHP_CodeSniffer\Standards\Generic\Sniffs\PHP\LowerCaseConstantSniff
 */
final class LowerCaseConstantUnitTest extends AbstractSniffUnitTest {



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
			case 'LowerCaseConstantUnitTest.1.inc':
				return array(
					7   => 1,
					10  => 1,
					15  => 1,
					16  => 1,
					23  => 1,
					26  => 1,
					31  => 1,
					32  => 1,
					39  => 1,
					42  => 1,
					47  => 1,
					48  => 1,
					70  => 1,
					71  => 1,
					87  => 1,
					89  => 1,
					90  => 1,
					92  => 2,
					94  => 2,
					95  => 1,
					100 => 2,
					104 => 1,
					108 => 1,
					118 => 1,
					119 => 1,
					120 => 1,
					121 => 1,
					125 => 1,
					129 => 1,
					149 => 1,
					153 => 1,
					167 => 1,
					169 => 1,
					171 => 1,
					173 => 1,
				);

			case 'LowerCaseConstantUnitTest.js':
				return array(
					2  => 1,
					3  => 1,
					4  => 1,
					7  => 1,
					8  => 1,
					12 => 1,
					13 => 1,
					14 => 1,
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
