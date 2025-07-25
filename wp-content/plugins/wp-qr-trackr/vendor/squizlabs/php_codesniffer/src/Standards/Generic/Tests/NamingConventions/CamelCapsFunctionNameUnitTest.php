<?php
/**
 * Unit test class for the CamelCapsFunctionName sniff.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Generic\Tests\NamingConventions;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;

/**
 * Unit test class for the CamelCapsFunctionName sniff.
 *
 * @covers \PHP_CodeSniffer\Standards\Generic\Sniffs\NamingConventions\CamelCapsFunctionNameSniff
 */
final class CamelCapsFunctionNameUnitTest extends AbstractSniffUnitTest {



	/**
	 * Returns the lines where errors should occur.
	 *
	 * The key of the array should represent the line number and the value
	 * should represent the number of errors that should occur on that line.
	 *
	 * @param string $testFile The name of the test file to process.
	 *
	 * @return array<int, int>
	 */
	public function getErrorList( $testFile = '' ) {
		switch ( $testFile ) {
			case 'CamelCapsFunctionNameUnitTest.1.inc':
				return array(
					10  => 1,
					11  => 1,
					12  => 1,
					13  => 1,
					16  => 1,
					17  => 1,
					20  => 1,
					21  => 1,
					24  => 1,
					25  => 1,
					30  => 1,
					31  => 1,
					50  => 1,
					52  => 1,
					53  => 2,
					57  => 1,
					58  => 1,
					59  => 1,
					60  => 1,
					61  => 1,
					62  => 1,
					63  => 1,
					64  => 1,
					65  => 1,
					66  => 1,
					67  => 1,
					68  => 2,
					69  => 1,
					71  => 1,
					72  => 1,
					73  => 2,
					118 => 1,
					144 => 1,
					146 => 1,
					147 => 2,
					158 => 1,
					159 => 1,
					179 => 1,
					180 => 2,
					183 => 1,
					184 => 1,
					189 => 1,
					197 => 1,
					204 => 1,
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
