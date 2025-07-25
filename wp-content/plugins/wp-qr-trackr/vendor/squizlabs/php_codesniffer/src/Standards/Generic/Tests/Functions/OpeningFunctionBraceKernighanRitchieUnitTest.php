<?php
/**
 * Unit test class for the OpeningFunctionBraceKernighanRitchie sniff.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Generic\Tests\Functions;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;

/**
 * Unit test class for the OpeningFunctionBraceKernighanRitchie sniff.
 *
 * @covers \PHP_CodeSniffer\Standards\Generic\Sniffs\Functions\OpeningFunctionBraceKernighanRitchieSniff
 */
final class OpeningFunctionBraceKernighanRitchieUnitTest extends AbstractSniffUnitTest {



	/**
	 * Get a list of CLI values to set before the file is tested.
	 *
	 * @param string                  $testFile The name of the file being tested.
	 * @param \PHP_CodeSniffer\Config $config   The config data for the test run.
	 *
	 * @return void
	 */
	public function setCliValues( $testFile, $config ) {
		if ( $testFile === 'OpeningFunctionBraceKernighanRitchieUnitTest.2.inc' ) {
			$config->tabWidth = 4;
		}
	}//end setCliValues()


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
			case 'OpeningFunctionBraceKernighanRitchieUnitTest.1.inc':
				return array(
					9   => 1,
					13  => 1,
					17  => 1,
					29  => 1,
					33  => 1,
					37  => 1,
					53  => 1,
					58  => 1,
					63  => 1,
					77  => 1,
					82  => 1,
					87  => 1,
					104 => 1,
					119 => 1,
					123 => 1,
					127 => 1,
					132 => 1,
					137 => 1,
					142 => 1,
					157 => 1,
					162 => 1,
					171 => 1,
					181 => 1,
					191 => 1,
					197 => 1,
					203 => 1,
					213 => 1,
					214 => 1,
					222 => 1,
					224 => 1,
					227 => 1,
				);
			case 'OpeningFunctionBraceKernighanRitchieUnitTest.2.inc':
				return array(
					6  => 1,
					10 => 1,
					14 => 1,
					18 => 1,
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
