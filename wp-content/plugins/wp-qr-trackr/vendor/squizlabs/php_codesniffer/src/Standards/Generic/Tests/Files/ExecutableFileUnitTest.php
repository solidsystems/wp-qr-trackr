<?php
/**
 * Unit test class for the ExecutableFile sniff.
 *
 * @author    Matthew Peveler <matt.peveler@gmail.com>
 * @copyright 2019 Matthew Peveler
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Generic\Tests\Files;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;

/**
 * Unit test class for the ExecutableFile sniff.
 *
 * @covers \PHP_CodeSniffer\Standards\Generic\Sniffs\Files\ExecutableFileSniff
 */
final class ExecutableFileUnitTest extends AbstractSniffUnitTest {



	/**
	 * Should this test be skipped for some reason.
	 *
	 * @return bool
	 */
	protected function shouldSkipTest() {
		// Skip on Windows which doesn't have the concept of executable files.
		return ( stripos( PHP_OS, 'WIN' ) === 0 );
	}//end shouldSkipTest()


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
		switch ( $testFile ) {
			case 'ExecutableFileUnitTest.2.inc':
			case 'ExecutableFileUnitTest.4.inc':
				return array( 1 => 1 );
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
	 * @param string $testFile The name of the file being tested.
	 *
	 * @return array<int, int>
	 */
	public function getWarningList( $testFile = '' ) {
		return array();
	}//end getWarningList()
}//end class
