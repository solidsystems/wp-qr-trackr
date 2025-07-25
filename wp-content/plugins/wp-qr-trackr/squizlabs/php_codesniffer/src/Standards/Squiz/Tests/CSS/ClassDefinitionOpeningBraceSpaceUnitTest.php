<?php
/**
 * Unit test class for the ClassDefinitionOpeningBraceSpace sniff.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Squiz\Tests\CSS;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;

/**
 * Unit test class for the ClassDefinitionOpeningBraceSpace sniff.
 *
 * @covers \PHP_CodeSniffer\Standards\Squiz\Sniffs\CSS\ClassDefinitionOpeningBraceSpaceSniff
 */
final class ClassDefinitionOpeningBraceSpaceUnitTest extends AbstractSniffUnitTest {



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
			4  => 1,
			7  => 1,
			10 => 1,
			26 => 1,
			33 => 1,
			43 => 1,
			45 => 1,
			50 => 1,
			57 => 2,
			59 => 2,
			62 => 1,
			73 => 1,
			77 => 1,
			84 => 1,
			97 => 1,
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
