<?php
/**
 * Unit test class for the MethodDeclaration sniff.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\PSR2\Tests\Methods;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;

/**
 * Unit test class for the MethodDeclaration sniff.
 *
 * @covers \PHP_CodeSniffer\Standards\PSR2\Sniffs\Methods\MethodDeclarationSniff
 */
final class MethodDeclarationUnitTest extends AbstractSniffUnitTest {



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
			9  => 1,
			11 => 1,
			13 => 1,
			15 => 3,
			24 => 1,
			34 => 1,
			36 => 1,
			38 => 1,
			40 => 3,
			50 => 1,
			52 => 1,
			54 => 1,
			56 => 3,
			63 => 2,
			73 => 1,
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
		return array(
			5  => 1,
			21 => 1,
			30 => 1,
			46 => 1,
			63 => 1,
			70 => 1,
		);
	}//end getWarningList()
}//end class
