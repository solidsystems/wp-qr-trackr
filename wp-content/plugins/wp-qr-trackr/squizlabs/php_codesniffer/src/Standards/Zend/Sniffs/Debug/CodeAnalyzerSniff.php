<?php
/**
 * Runs the Zend Code Analyzer (from Zend Studio) on the file.
 *
 * @author    Holger Kral <holger.kral@zend.com>
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 *
 * @deprecated 3.9.0
 */

namespace PHP_CodeSniffer\Standards\Zend\Sniffs\Debug;

use PHP_CodeSniffer\Sniffs\DeprecatedSniff;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Exceptions\RuntimeException;
use PHP_CodeSniffer\Util\Common;

class CodeAnalyzerSniff implements Sniff, DeprecatedSniff {



	/**
	 * Returns the token types that this sniff is interested in.
	 *
	 * @return array<int|string>
	 */
	public function register() {
		return array( T_OPEN_TAG );
	}//end register()


	/**
	 * Processes the tokens that this sniff is interested in.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The file where the token was found.
	 * @param int                         $stackPtr  The position in the stack where
	 *                                               the token was found.
	 *
	 * @return int
	 * @throws \PHP_CodeSniffer\Exceptions\RuntimeException If ZendCodeAnalyzer could not be run.
	 */
	public function process( File $phpcsFile, $stackPtr ) {
		$analyzerPath = Config::getExecutablePath( 'zend_ca' );
		if ( $analyzerPath === null ) {
			return $phpcsFile->numTokens;
		}

		$fileName = $phpcsFile->getFilename();

		// In the command, 2>&1 is important because the code analyzer sends its
		// findings to stderr. $output normally contains only stdout, so using 2>&1
		// will pipe even stderr to stdout.
		$cmd = Common::escapeshellcmd( $analyzerPath ) . ' ' . escapeshellarg( $fileName ) . ' 2>&1';

		// There is the possibility to pass "--ide" as an option to the analyzer.
		// This would result in an output format which would be easier to parse.
		// The problem here is that no cleartext error messages are returned; only
		// error-code-labels. So for a start we go for cleartext output.
		$exitCode = exec( $cmd, $output, $retval );

		// Variable $exitCode is the last line of $output if no error occurs, on
		// error it is numeric. Try to handle various error conditions and
		// provide useful error reporting.
		if ( is_numeric( $exitCode ) === true && $exitCode > 0 ) {
			if ( is_array( $output ) === true ) {
				$msg = implode( '\n', $output );
			}

			throw new RuntimeException( "Failed invoking ZendCodeAnalyzer, exitcode was [$exitCode], retval was [$retval], output was [$msg]" );
		}

		if ( is_array( $output ) === true ) {
			foreach ( $output as $finding ) {
				// The first two lines of analyzer output contain
				// something like this:
				// > Zend Code Analyzer 1.2.2
				// > Analyzing <filename>...
				// So skip these...
				$res = preg_match( '/^.+\(line ([0-9]+)\):(.+)$/', $finding, $regs );
				if ( empty( $regs ) === true || $res === false ) {
					continue;
				}

				$phpcsFile->addWarningOnLine( trim( $regs[2] ), $regs[1], 'ExternalTool' );
			}
		}

		// Ignore the rest of the file.
		return $phpcsFile->numTokens;
	}//end process()


	/**
	 * Provide the version number in which the sniff was deprecated.
	 *
	 * @return string
	 */
	public function getDeprecationVersion() {
		return 'v3.9.0';
	}//end getDeprecationVersion()


	/**
	 * Provide the version number in which the sniff will be removed.
	 *
	 * @return string
	 */
	public function getRemovalVersion() {
		return 'v4.0.0';
	}//end getRemovalVersion()


	/**
	 * Provide a custom message to display with the deprecation.
	 *
	 * @return string
	 */
	public function getDeprecationMessage() {
		return '';
	}//end getDeprecationMessage()
}//end class
