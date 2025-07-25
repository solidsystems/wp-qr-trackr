<?php
/**
 * Unit test class for the DisallowSpaceIndent sniff.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Generic\Tests\WhiteSpace;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;

/**
 * Unit test class for the DisallowSpaceIndent sniff.
 *
 * @covers \PHP_CodeSniffer\Standards\Generic\Sniffs\WhiteSpace\DisallowSpaceIndentSniff
 */
final class DisallowSpaceIndentUnitTest extends AbstractSniffUnitTest {



	/**
	 * Get a list of CLI values to set before the file is tested.
	 *
	 * @param string                  $testFile The name of the file being tested.
	 * @param \PHP_CodeSniffer\Config $config   The config data for the test run.
	 *
	 * @return void
	 */
	public function setCliValues( $testFile, $config ) {
		if ( $testFile === 'DisallowSpaceIndentUnitTest.2.inc' ) {
			return;
		}

		$config->tabWidth = 4;
	}//end setCliValues()


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
			case 'DisallowSpaceIndentUnitTest.1.inc':
			case 'DisallowSpaceIndentUnitTest.2.inc':
				return array(
					5   => 1,
					9   => 1,
					15  => 1,
					22  => 1,
					24  => 1,
					30  => 1,
					35  => 1,
					50  => 1,
					55  => 1,
					57  => 1,
					58  => 1,
					59  => 1,
					60  => 1,
					65  => 1,
					66  => 1,
					67  => 1,
					68  => 1,
					69  => 1,
					70  => 1,
					73  => 1,
					77  => 1,
					81  => 1,
					104 => 1,
					105 => 1,
					106 => 1,
					107 => 1,
					108 => 1,
					110 => 1,
					111 => 1,
					112 => 1,
					114 => 1,
					115 => 1,
					117 => 1,
					118 => 1,
					123 => 1,
				);

			case 'DisallowSpaceIndentUnitTest.3.inc':
				return array(
					2  => 1,
					5  => 1,
					10 => 1,
					12 => 1,
					13 => 1,
					14 => 1,
					15 => 1,
				);

			case 'DisallowSpaceIndentUnitTest.4.inc':
				if ( PHP_VERSION_ID >= 70300 ) {
					return array(
						7  => 1,
						13 => 1,
					);
				}

				// PHP 7.2 or lower: PHP version which doesn't support flexible heredocs/nowdocs yet.
				return array();

			case 'DisallowSpaceIndentUnitTest.js':
				return array( 3 => 1 );

			case 'DisallowSpaceIndentUnitTest.css':
				return array( 2 => 1 );

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
