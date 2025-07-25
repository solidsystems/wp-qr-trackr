<?php
/**
 * Unit test class for the FunctionCallArgumentSpacing sniff.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Generic\Tests\Functions;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;

/**
 * Unit test class for the FunctionCallArgumentSpacing sniff.
 *
 * @covers \PHP_CodeSniffer\Standards\Generic\Sniffs\Functions\FunctionCallArgumentSpacingSniff
 */
final class FunctionCallArgumentSpacingUnitTest extends AbstractSniffUnitTest {



	/**
	 * Returns the lines where errors should occur.
	 *
	 * The key of the array should represent the line number and the value
	 * should represent the number of errors that should occur on that line.
	 *
	 * @param string $testFile The name of the test file to process.
	 *
	 * @return array<int, int>
	 */
	public function getErrorList( $testFile = '' ) {
		switch ( $testFile ) {
			case 'FunctionCallArgumentSpacingUnitTest.1.inc':
				return array(
					5   => 1,
					6   => 1,
					7   => 2,
					8   => 1,
					11  => 1,
					12  => 1,
					13  => 1,
					42  => 3,
					43  => 3,
					45  => 1,
					46  => 2,
					79  => 1,
					82  => 1,
					93  => 1,
					105 => 1,
					107 => 1,
					108 => 2,
					114 => 1,
					115 => 1,
					119 => 1,
					125 => 2,
					130 => 2,
					131 => 1,
					132 => 2,
					133 => 2,
					134 => 1,
					154 => 2,
					155 => 1,
					162 => 2,
					170 => 1,
					177 => 1,
					190 => 2,
					191 => 2,
					197 => 1,
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
