<?php
/**
 * Unit test class for the ObjectOperatorIndent sniff.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\PEAR\Tests\WhiteSpace;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;

/**
 * Unit test class for the ObjectOperatorIndent sniff.
 *
 * @covers \PHP_CodeSniffer\Standards\PEAR\Sniffs\WhiteSpace\ObjectOperatorIndentSniff
 */
final class ObjectOperatorIndentUnitTest extends AbstractSniffUnitTest {



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
			3   => 2,
			6   => 1,
			15  => 1,
			27  => 1,
			37  => 1,
			38  => 1,
			48  => 1,
			49  => 1,
			50  => 1,
			65  => 1,
			69  => 1,
			73  => 1,
			79  => 1,
			80  => 1,
			81  => 1,
			82  => 1,
			95  => 1,
			103 => 1,
			119 => 2,
			122 => 1,
			131 => 1,
			134 => 1,
			140 => 1,
			141 => 1,
			142 => 1,
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
