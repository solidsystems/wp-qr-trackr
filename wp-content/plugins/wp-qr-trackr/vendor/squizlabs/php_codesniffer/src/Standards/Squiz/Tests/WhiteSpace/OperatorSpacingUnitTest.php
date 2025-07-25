<?php
/**
 * Unit test class for the OperatorSpacing sniff.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Squiz\Tests\WhiteSpace;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;

/**
 * Unit test class for the OperatorSpacing sniff.
 *
 * @covers \PHP_CodeSniffer\Standards\Squiz\Sniffs\WhiteSpace\OperatorSpacingSniff
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
					4   => 1,
					5   => 2,
					6   => 1,
					7   => 1,
					8   => 2,
					11  => 1,
					12  => 2,
					13  => 1,
					14  => 1,
					15  => 2,
					18  => 1,
					19  => 2,
					20  => 1,
					21  => 1,
					22  => 2,
					25  => 1,
					26  => 2,
					27  => 1,
					28  => 1,
					29  => 2,
					32  => 1,
					33  => 2,
					34  => 1,
					35  => 1,
					36  => 2,
					40  => 2,
					42  => 2,
					44  => 2,
					45  => 1,
					46  => 2,
					53  => 4,
					54  => 3,
					59  => 10,
					64  => 1,
					77  => 4,
					78  => 1,
					79  => 1,
					80  => 2,
					81  => 1,
					84  => 6,
					85  => 6,
					87  => 4,
					88  => 5,
					90  => 4,
					91  => 5,
					128 => 4,
					132 => 1,
					133 => 1,
					135 => 1,
					136 => 1,
					140 => 1,
					141 => 1,
					174 => 1,
					177 => 1,
					178 => 1,
					179 => 1,
					185 => 2,
					191 => 4,
					194 => 1,
					195 => 1,
					196 => 2,
					199 => 1,
					200 => 1,
					201 => 2,
					239 => 1,
					246 => 1,
					265 => 2,
					266 => 2,
					271 => 2,
					487 => 1,
					488 => 1,
					493 => 1,
					494 => 1,
					499 => 1,
					504 => 1,
				);

			case 'OperatorSpacingUnitTest.js':
				return array(
					4   => 1,
					5   => 2,
					6   => 1,
					7   => 1,
					8   => 2,
					11  => 1,
					12  => 2,
					13  => 1,
					14  => 1,
					15  => 2,
					18  => 1,
					19  => 2,
					20  => 1,
					21  => 1,
					22  => 2,
					25  => 1,
					26  => 2,
					27  => 1,
					28  => 1,
					29  => 2,
					32  => 1,
					33  => 2,
					34  => 1,
					35  => 1,
					36  => 2,
					40  => 2,
					42  => 2,
					44  => 2,
					45  => 1,
					46  => 2,
					55  => 4,
					65  => 1,
					66  => 1,
					68  => 1,
					69  => 1,
					73  => 1,
					74  => 1,
					100 => 1,
					103 => 2,
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
