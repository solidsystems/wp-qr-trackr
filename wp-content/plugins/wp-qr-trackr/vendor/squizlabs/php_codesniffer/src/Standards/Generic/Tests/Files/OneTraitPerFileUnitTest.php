<?php
/**
 * Unit test class for the OneTraitPerFile sniff.
 *
 * @author    Alexander Obuhovich <aik.bold@gmail.com>
 * @copyright 2010-2014 Alexander Obuhovich
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Generic\Tests\Files;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;

/**
 * Unit test class for the OneTraitPerFile sniff.
 *
 * @covers \PHP_CodeSniffer\Standards\Generic\Sniffs\Files\OneTraitPerFileSniff
 */
final class OneTraitPerFileUnitTest extends AbstractSniffUnitTest {



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
			6  => 1,
			10 => 1,
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
		return array();
	}//end getWarningList()
}//end class
