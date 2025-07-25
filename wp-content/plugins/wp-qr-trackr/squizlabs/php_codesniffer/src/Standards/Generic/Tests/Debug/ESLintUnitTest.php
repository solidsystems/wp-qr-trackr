<?php
/**
 * Unit test class for the ESLint sniff.
 *
 * @author    Juliette Reinders Folmer <phpcs_nospam@adviesenzo.nl>
 * @copyright 2019 Juliette Reinders Folmer. All rights reserved.
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Generic\Tests\Debug;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;
use PHP_CodeSniffer\Config;

/**
 * Unit test class for the ESLint sniff.
 *
 * @covers \PHP_CodeSniffer\Standards\Generic\Sniffs\Debug\ESLintSniff
 */
final class ESLintUnitTest extends AbstractSniffUnitTest {


	/**
	 * Basic ESLint config to use for testing the sniff.
	 *
	 * @var string
	 */
	const ESLINT_CONFIG = '{
    "parserOptions": {
        "ecmaVersion": 5,
        "sourceType": "script",
        "ecmaFeatures": {}
    },
    "rules": {
        "no-undef": 2,
        "no-unused-vars": 2
    }
}';


	/**
	 * Sets up this unit test.
	 *
	 * @before
	 *
	 * @return void
	 */
	protected function setUpPrerequisites() {
		parent::setUpPrerequisites();

		$cwd = getcwd();
		file_put_contents( $cwd . '/.eslintrc.json', self::ESLINT_CONFIG );

		putenv( 'ESLINT_USE_FLAT_CONFIG=false' );
	}//end setUpPrerequisites()


	/**
	 * Remove artifact.
	 *
	 * @after
	 *
	 * @return void
	 */
	protected function resetProperties() {
		$cwd = getcwd();
		unlink( $cwd . '/.eslintrc.json' );
	}//end resetProperties()


	/**
	 * Should this test be skipped for some reason.
	 *
	 * @return bool
	 */
	protected function shouldSkipTest() {
		$eslintPath = Config::getExecutablePath( 'eslint' );
		if ( $eslintPath === null ) {
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
		return array( 1 => 2 );
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
