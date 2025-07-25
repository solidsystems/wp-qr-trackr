<?php
/**
 * Unit test class for the UnusedFunctionParameter sniff.
 *
 * @author    Manuel Pichler <mapi@manuel-pichler.de>
 * @copyright 2007-2014 Manuel Pichler. All rights reserved.
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Generic\Tests\CodeAnalysis;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;

/**
 * Unit test class for the UnusedFunctionParameter sniff.
 *
 * @covers \PHP_CodeSniffer\Standards\Generic\Sniffs\CodeAnalysis\UnusedFunctionParameterSniff
 */
final class UnusedFunctionParameterUnitTest extends AbstractSniffUnitTest {



	/**
	 * Returns the lines where errors should occur.
	 *
	 * The key of the array should represent the line number and the value
	 * should represent the number of errors that should occur on that line.
	 *
	 * @return array<int, int>
	 */
	public function getErrorList() {
		return array();
	}//end getErrorList()


	/**
	 * Returns the lines where warnings should occur.
	 *
	 * The key of the array should represent the line number and the value
	 * should represent the number of warnings that should occur on that line.
	 *
	 * @param string $testFile The name of the file being tested.
	 *
	 * @return array<int, int>
	 */
	public function getWarningList( $testFile = '' ) {
		switch ( $testFile ) {
			case 'UnusedFunctionParameterUnitTest.1.inc':
				return array(
					3   => 1,
					7   => 1,
					78  => 1,
					94  => 1,
					100 => 1,
					106 => 1,
					117 => 1,
					121 => 2,
					125 => 2,
					163 => 1,
					172 => 1,
					228 => 2,
					232 => 2,
					244 => 2,
					248 => 2,
					271 => 1,
				);

			default:
				return array();
		}//end switch
	}//end getWarningList()
}//end class
