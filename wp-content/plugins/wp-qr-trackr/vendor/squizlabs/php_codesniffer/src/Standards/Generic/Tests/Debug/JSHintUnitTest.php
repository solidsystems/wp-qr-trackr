<?php
/**
 * Unit test class for the JSHint sniff.
 *
 * @author    Juliette Reinders Folmer <phpcs_nospam@adviesenzo.nl>
 * @copyright 2019 Juliette Reinders Folmer. All rights reserved.
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Generic\Tests\Debug;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;
use PHP_CodeSniffer\Config;

/**
 * Unit test class for the JSHint sniff.
 *
 * @covers \PHP_CodeSniffer\Standards\Generic\Sniffs\Debug\JSHintSniff
 */
final class JSHintUnitTest extends AbstractSniffUnitTest {



	/**
	 * Should this test be skipped for some reason.
	 *
	 * @return bool
	 */
	protected function shouldSkipTest() {
		$jshintPath = Config::getExecutablePath( 'jshint' );
		if ( $jshintPath === null ) {
			return true;
		}

		return false;
	}//end shouldSkipTest()


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
	 * @return array<int, int>
	 */
	public function getWarningList() {
		return array( 3 => 2 );
	}//end getWarningList()
}//end class
