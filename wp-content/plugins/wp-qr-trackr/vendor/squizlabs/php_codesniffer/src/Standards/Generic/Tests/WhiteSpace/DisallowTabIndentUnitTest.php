<?php
/**
 * Unit test class for the DisallowTabIndent sniff.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Generic\Tests\WhiteSpace;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;

/**
 * Unit test class for the DisallowTabIndent sniff.
 *
 * @covers \PHP_CodeSniffer\Standards\Generic\Sniffs\WhiteSpace\DisallowTabIndentSniff
 */
final class DisallowTabIndentUnitTest extends AbstractSniffUnitTest {



	/**
	 * Get a list of CLI values to set before the file is tested.
	 *
	 * @param string                  $testFile The name of the file being tested.
	 * @param \PHP_CodeSniffer\Config $config   The config data for the test run.
	 *
	 * @return void
	 */
	public function setCliValues( $testFile, $config ) {
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
			case 'DisallowTabIndentUnitTest.1.inc':
				return array(
					5   => 2,
					9   => 1,
					15  => 1,
					20  => 2,
					21  => 1,
					22  => 2,
					23  => 1,
					24  => 2,
					31  => 1,
					32  => 2,
					33  => 2,
					41  => 1,
					42  => 1,
					43  => 1,
					44  => 1,
					45  => 1,
					46  => 1,
					47  => 1,
					48  => 1,
					54  => 1,
					55  => 1,
					56  => 1,
					57  => 1,
					58  => 1,
					59  => 1,
					79  => 1,
					80  => 1,
					81  => 1,
					82  => 1,
					83  => 1,
					85  => 1,
					86  => 1,
					87  => 1,
					89  => 1,
					90  => 1,
					92  => 1,
					93  => 1,
					97  => 1,
					100 => 1,
				);

			case 'DisallowTabIndentUnitTest.2.inc':
				return array(
					6  => 1,
					7  => 1,
					8  => 1,
					9  => 1,
					10 => 1,
					11 => 1,
					12 => 1,
					13 => 1,
					19 => 1,
				);

			case 'DisallowTabIndentUnitTest.3.inc':
				if ( PHP_VERSION_ID >= 70300 ) {
					return array(
						7  => 1,
						13 => 1,
					);
				}

				// PHP 7.2 or lower: PHP version which doesn't support flexible heredocs/nowdocs yet.
				return array();

			case 'DisallowTabIndentUnitTest.js':
				return array(
					3 => 1,
					5 => 1,
					6 => 1,
				);

			case 'DisallowTabIndentUnitTest.css':
				return array(
					1 => 1,
					2 => 1,
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
