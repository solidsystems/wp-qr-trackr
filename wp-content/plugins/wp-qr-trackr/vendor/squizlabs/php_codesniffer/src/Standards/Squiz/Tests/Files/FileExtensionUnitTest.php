<?php
/**
 * Unit test class for the FileExtension sniff.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Squiz\Tests\Files;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;

/**
 * Unit test class for the FileExtension sniff.
 *
 * @covers \PHP_CodeSniffer\Standards\Squiz\Sniffs\Files\FileExtensionSniff
 */
final class FileExtensionUnitTest extends AbstractSniffUnitTest {



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
			case 'FileExtensionUnitTest.1.inc':
				return array( 1 => 1 );
			default:
				return array();
		}
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
