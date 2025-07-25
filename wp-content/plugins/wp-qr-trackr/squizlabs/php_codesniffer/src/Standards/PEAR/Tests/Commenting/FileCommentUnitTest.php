<?php
/**
 * Unit test class for the FileComment sniff.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\PEAR\Tests\Commenting;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;

/**
 * Unit test class for the FileComment sniff.
 *
 * @covers \PHP_CodeSniffer\Standards\PEAR\Sniffs\Commenting\FileCommentSniff
 */
final class FileCommentUnitTest extends AbstractSniffUnitTest {



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
			case 'FileCommentUnitTest.1.inc':
				return array(
					21 => 1,
					23 => 2,
					24 => 1,
					26 => 1,
					28 => 1,
					29 => 1,
					30 => 1,
					31 => 1,
					32 => 2,
					33 => 1,
					34 => 1,
					35 => 1,
					40 => 2,
					41 => 2,
					43 => 1,
				);

			case 'FileCommentUnitTest.2.inc':
			case 'FileCommentUnitTest.3.inc':
			case 'FileCommentUnitTest.4.inc':
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
		switch ( $testFile ) {
			case 'FileCommentUnitTest.1.inc':
				return array(
					29 => 1,
					30 => 1,
					34 => 1,
					43 => 1,
				);

			default:
				return array();
		}//end switch
	}//end getWarningList()
}//end class
