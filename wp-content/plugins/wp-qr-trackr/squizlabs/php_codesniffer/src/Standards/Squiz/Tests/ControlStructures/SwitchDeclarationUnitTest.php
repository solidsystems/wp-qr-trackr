<?php
/**
 * Unit test class for the SwitchDeclaration sniff.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Squiz\Tests\ControlStructures;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;

/**
 * Unit test class for the SwitchDeclaration sniff.
 *
 * @covers \PHP_CodeSniffer\Standards\Squiz\Sniffs\ControlStructures\SwitchDeclarationSniff
 */
final class SwitchDeclarationUnitTest extends AbstractSniffUnitTest {



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
			case 'SwitchDeclarationUnitTest.inc':
				return array(
					27  => 1,
					29  => 1,
					34  => 1,
					36  => 1,
					44  => 1,
					48  => 1,
					52  => 1,
					54  => 1,
					55  => 1,
					56  => 1,
					58  => 1,
					59  => 1,
					61  => 1,
					62  => 1,
					79  => 1,
					85  => 2,
					88  => 2,
					89  => 2,
					92  => 1,
					95  => 3,
					99  => 1,
					116 => 1,
					122 => 1,
					127 => 2,
					134 => 2,
					135 => 1,
					138 => 1,
					143 => 1,
					144 => 1,
					147 => 1,
					165 => 1,
					172 => 1,
					176 => 2,
					180 => 1,
					192 => 2,
					196 => 1,
					223 => 1,
					266 => 1,
					282 => 1,
					284 => 2,
					322 => 1,
					323 => 1,
					327 => 1,
					329 => 1,
					330 => 1,
					339 => 2,
					342 => 1,
				);

			case 'SwitchDeclarationUnitTest.js':
				return array(
					27  => 1,
					29  => 1,
					34  => 1,
					36  => 1,
					44  => 1,
					48  => 1,
					52  => 1,
					54  => 1,
					55  => 1,
					56  => 1,
					58  => 1,
					59  => 1,
					61  => 1,
					62  => 1,
					79  => 1,
					85  => 2,
					88  => 2,
					89  => 2,
					92  => 1,
					95  => 3,
					99  => 1,
					116 => 1,
					122 => 1,
					127 => 2,
					134 => 2,
					135 => 1,
					138 => 1,
					143 => 1,
					144 => 1,
					147 => 1,
					165 => 1,
					172 => 1,
					176 => 2,
					180 => 1,
					192 => 2,
					196 => 1,
					223 => 1,
					266 => 1,
					282 => 1,
					284 => 2,
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
	 * @param string $testFile The name of the file being tested.
	 *
	 * @return array<int, int>
	 */
	public function getWarningList( $testFile = '' ) {
		if ( $testFile === 'SwitchDeclarationUnitTest.js' ) {
			return array( 273 => 1 );
		}

		return array();
	}//end getWarningList()
}//end class
