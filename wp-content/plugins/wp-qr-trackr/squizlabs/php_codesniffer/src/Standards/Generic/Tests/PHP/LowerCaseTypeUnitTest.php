<?php
/**
 * Unit test class for the LowerCaseType sniff.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Generic\Tests\PHP;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;

/**
 * Unit test class for the LowerCaseType sniff.
 *
 * @covers \PHP_CodeSniffer\Standards\Generic\Sniffs\PHP\LowerCaseTypeSniff
 */
final class LowerCaseTypeUnitTest extends AbstractSniffUnitTest {



	/**
	 * Returns the lines where errors should occur.
	 *
	 * The key of the array should represent the line number and the value
	 * should represent the number of errors that should occur on that line.
	 *
	 * @return array<int, int>
	 */
	public function getErrorList() {
		return array(
			14  => 1,
			15  => 1,
			16  => 1,
			17  => 1,
			18  => 1,
			21  => 4,
			22  => 3,
			23  => 3,
			25  => 1,
			26  => 2,
			27  => 2,
			32  => 4,
			36  => 1,
			37  => 1,
			38  => 1,
			39  => 1,
			43  => 2,
			44  => 1,
			46  => 1,
			49  => 1,
			51  => 2,
			53  => 1,
			55  => 2,
			60  => 1,
			61  => 1,
			62  => 1,
			63  => 1,
			64  => 1,
			65  => 1,
			66  => 1,
			67  => 1,
			68  => 1,
			69  => 1,
			71  => 3,
			72  => 2,
			73  => 3,
			74  => 3,
			78  => 3,
			82  => 2,
			85  => 1,
			94  => 5,
			96  => 4,
			105 => 1,
			106 => 1,
			107 => 1,
			111 => 1,
			112 => 1,
			113 => 1,
			114 => 1,
			117 => 1,
			118 => 1,
			119 => 1,
			122 => 3,
			123 => 2,
			124 => 3,
			125 => 3,
			129 => 2,
			131 => 1,
			134 => 1,
			135 => 1,
			136 => 1,
			139 => 2,
		);
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
		// Warning from getMemberProperties() about parse error.
		return array( 144 => 1 );
	}//end getWarningList()
}//end class
