<?php
/**
 * Unit test class for the DisallowYodaConditions sniff.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Generic\Tests\ControlStructures;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;

/**
 * Unit test class for the DisallowYodaConditions sniff.
 *
 * @covers \PHP_CodeSniffer\Standards\Generic\Sniffs\ControlStructures\DisallowYodaConditionsSniff
 */
final class DisallowYodaConditionsUnitTest extends AbstractSniffUnitTest {



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
			7   => 1,
			8   => 1,
			12  => 1,
			13  => 2,
			18  => 1,
			19  => 1,
			24  => 1,
			25  => 1,
			30  => 1,
			31  => 1,
			40  => 1,
			47  => 1,
			48  => 1,
			50  => 1,
			52  => 1,
			57  => 1,
			58  => 1,
			62  => 1,
			68  => 1,
			97  => 3,
			98  => 3,
			105 => 1,
			128 => 1,
			129 => 2,
			130 => 1,
			131 => 1,
			133 => 1,
			139 => 1,
			140 => 1,
			141 => 1,
			142 => 1,
			156 => 1,
			160 => 1,
			167 => 1,
			173 => 1,
			174 => 1,
			183 => 1,
			184 => 1,
			185 => 1,
			186 => 1,
			187 => 1,
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
		return array();
	}//end getWarningList()
}//end class
