<?php
/**
 * Unit test class for the ClassDefinitionClosingBraceSpace sniff.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Squiz\Tests\CSS;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;

/**
 * Unit test class for the ClassDefinitionClosingBraceSpace sniff.
 *
 * @covers \PHP_CodeSniffer\Standards\Squiz\Sniffs\CSS\ClassDefinitionClosingBraceSpaceSniff
 */
final class ClassDefinitionClosingBraceSpaceUnitTest extends AbstractSniffUnitTest {



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
			2  => 1,
			11 => 1,
			44 => 1,
			47 => 1,
			51 => 1,
			53 => 1,
			57 => 1,
			59 => 1,
			67 => 1,
			69 => 1,
			81 => 2,
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
