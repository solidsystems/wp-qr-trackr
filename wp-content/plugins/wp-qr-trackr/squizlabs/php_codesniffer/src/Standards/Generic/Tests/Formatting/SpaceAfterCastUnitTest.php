<?php
/**
 * Unit test class for the SpaceAfterCast sniff.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Generic\Tests\Formatting;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;

/**
 * Unit test class for the SpaceAfterCast sniff.
 *
 * @covers \PHP_CodeSniffer\Standards\Generic\Sniffs\Formatting\SpaceAfterCastSniff
 */
final class SpaceAfterCastUnitTest extends AbstractSniffUnitTest {



	/**
	 * Returns the lines where errors should occur.
	 *
	 * The key of the array should represent the line number and the value
	 * should represent the number of errors that should occur on that line.
	 *
	 * @param string $testFile The name of the test file to run.
	 *
	 * @return array<int, int>
	 */
	public function getErrorList( $testFile = '' ) {
		switch ( $testFile ) {
			case 'SpaceAfterCastUnitTest.1.inc':
				return array(
					4   => 1,
					5   => 1,
					8   => 1,
					9   => 1,
					12  => 1,
					13  => 1,
					16  => 1,
					17  => 1,
					20  => 1,
					21  => 1,
					24  => 1,
					25  => 1,
					28  => 1,
					29  => 1,
					32  => 1,
					33  => 1,
					36  => 1,
					37  => 1,
					40  => 1,
					41  => 1,
					44  => 1,
					45  => 1,
					51  => 1,
					53  => 1,
					55  => 1,
					58  => 1,
					64  => 1,
					72  => 1,
					73  => 1,
					75  => 1,
					76  => 1,
					78  => 1,
					82  => 1,
					84  => 1,
					85  => 1,
					86  => 1,
					88  => 1,
					93  => 1,
					97  => 1,
					99  => 1,
					100 => 1,
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
