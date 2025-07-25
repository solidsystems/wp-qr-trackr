<?php
/**
 * Unit test class for the MemberVarScope sniff.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Squiz\Tests\Scope;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;

/**
 * Unit test class for the MemberVarScope sniff.
 *
 * @covers \PHP_CodeSniffer\Standards\Squiz\Sniffs\Scope\MemberVarScopeSniff
 */
final class MemberVarScopeUnitTest extends AbstractSniffUnitTest {



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
			7  => 1,
			25 => 1,
			29 => 1,
			33 => 1,
			39 => 1,
			41 => 1,
			66 => 2,
			67 => 1,
			75 => 1,
			80 => 1,
			81 => 1,
			82 => 1,
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
		// Warning from getMemberProperties() about parse error.
		return array( 71 => 1 );
	}//end getWarningList()
}//end class
