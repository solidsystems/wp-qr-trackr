<?php
/**
 * Unit test class for the MultiLineFunctionDeclaration sniff.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Squiz\Tests\Functions;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;

/**
 * Unit test class for the MultiLineFunctionDeclaration sniff.
 *
 * @covers \PHP_CodeSniffer\Standards\Squiz\Sniffs\Functions\MultiLineFunctionDeclarationSniff
 */
final class MultiLineFunctionDeclarationUnitTest extends AbstractSniffUnitTest {



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
		if ( $testFile === 'MultiLineFunctionDeclarationUnitTest.inc' ) {
			$errors = array(
				2   => 1,
				3   => 1,
				4   => 2,
				5   => 1,
				7   => 1,
				11  => 1,
				12  => 1,
				13  => 1,
				16  => 1,
				36  => 1,
				43  => 2,
				48  => 1,
				81  => 1,
				82  => 2,
				88  => 1,
				102 => 2,
				137 => 1,
				141 => 2,
				142 => 1,
				158 => 1,
				160 => 1,
				182 => 2,
				186 => 2,
				190 => 2,
				194 => 1,
				195 => 1,
				233 => 1,
				234 => 1,
				235 => 1,
				236 => 1,
				244 => 1,
				245 => 1,
				246 => 1,
				247 => 1,
				248 => 1,
				249 => 1,
				250 => 1,
				251 => 1,
				252 => 1,
				253 => 1,
				254 => 1,
				318 => 1,
				323 => 1,
			);
		} else {
			$errors = array(
				2  => 1,
				3  => 1,
				4  => 2,
				5  => 1,
				7  => 1,
				11 => 1,
				12 => 1,
				13 => 1,
				16 => 1,
				26 => 1,
				36 => 1,
				43 => 2,
				48 => 1,
				65 => 1,
			);
		}//end if

		return $errors;
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
