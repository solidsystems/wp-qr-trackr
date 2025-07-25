<?php
/**
 * Unit test class for the DeprecatedFunctions sniff.
 *
 * @author    Juliette Reinders Folmer <phpcs_nospam@adviesenzo.nl>
 * @copyright 2019 Juliette Reinders Folmer. All rights reserved.
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Generic\Tests\PHP;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;

/**
 * Unit test class for the DeprecatedFunctions sniff.
 *
 * @covers \PHP_CodeSniffer\Standards\Generic\Sniffs\PHP\DeprecatedFunctionsSniff
 */
final class DeprecatedFunctionsUnitTest extends AbstractSniffUnitTest {



	/**
	 * Returns the lines where errors should occur.
	 *
	 * The key of the array should represent the line number and the value
	 * should represent the number of errors that should occur on that line.
	 *
	 * @return array<int, int>
	 */
	public function getErrorList() {
		$errors = array();

		if ( PHP_VERSION_ID >= 70200 && PHP_VERSION_ID < 80000 ) {
			$errors[3] = 1;
		}

		if ( PHP_VERSION_ID >= 70300 && PHP_VERSION_ID < 80000 ) {
			$errors[4] = 1;
		}

		if ( PHP_VERSION_ID >= 80000 ) {
			$errors[5] = 1;
		}

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
