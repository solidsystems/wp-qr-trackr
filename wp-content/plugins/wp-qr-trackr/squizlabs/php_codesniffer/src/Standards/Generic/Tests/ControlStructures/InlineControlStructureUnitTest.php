<?php
/**
 * Unit test class for the InlineControlStructure sniff.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Generic\Tests\ControlStructures;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;

/**
 * Unit test class for the InlineControlStructure sniff.
 *
 * @covers \PHP_CodeSniffer\Standards\Generic\Sniffs\ControlStructures\InlineControlStructureSniff
 */
final class InlineControlStructureUnitTest extends AbstractSniffUnitTest {



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
			case 'InlineControlStructureUnitTest.1.inc':
				return array(
					3   => 1,
					7   => 1,
					11  => 1,
					13  => 1,
					15  => 1,
					17  => 1,
					23  => 1,
					45  => 1,
					46  => 1,
					49  => 1,
					62  => 1,
					66  => 1,
					78  => 1,
					109 => 1,
					120 => 1,
					128 => 1,
					134 => 1,
					142 => 1,
					143 => 1,
					144 => 1,
					150 => 1,
					158 => 1,
					159 => 1,
					162 => 1,
					163 => 1,
					164 => 1,
					167 => 1,
					168 => 1,
					170 => 1,
					178 => 1,
					185 => 1,
					188 => 2,
					191 => 1,
					195 => 1,
					198 => 1,
					204 => 1,
					205 => 1,
					222 => 1,
					232 => 1,
					235 => 1,
					236 => 1,
					238 => 1,
					242 => 1,
					260 => 1,
					269 => 1,
					278 => 1,
				);

			case 'InlineControlStructureUnitTest.1.js':
				return array(
					3  => 1,
					7  => 1,
					11 => 1,
					13 => 1,
					15 => 1,
					21 => 1,
					27 => 1,
					30 => 1,
					35 => 1,
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
