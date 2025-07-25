<?php
/**
 * Unit test class for the ControlStructureSpacing sniff.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2019 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\PSR12\Tests\ControlStructures;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;

/**
 * Unit test class for the ControlStructureSpacing sniff.
 *
 * @covers \PHP_CodeSniffer\Standards\PSR12\Sniffs\ControlStructures\ControlStructureSpacingSniff
 */
final class ControlStructureSpacingUnitTest extends AbstractSniffUnitTest {



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
			2   => 2,
			16  => 1,
			17  => 1,
			18  => 1,
			22  => 1,
			23  => 1,
			32  => 1,
			33  => 1,
			34  => 1,
			37  => 1,
			38  => 1,
			39  => 1,
			48  => 2,
			58  => 1,
			59  => 1,
			92  => 1,
			96  => 1,
			97  => 1,
			98  => 2,
			106 => 1,
			111 => 1,
			117 => 1,
			127 => 1,
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
