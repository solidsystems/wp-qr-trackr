<?php
/**
 * Runs gjslint on the file.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 *
 * @deprecated 3.9.0
 */

namespace PHP_CodeSniffer\Standards\Generic\Sniffs\Debug;

use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\DeprecatedSniff;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Common;

class ClosureLinterSniff implements Sniff, DeprecatedSniff {


	/**
	 * A list of error codes that should show errors.
	 *
	 * All other error codes will show warnings.
	 *
	 * @var array
	 */
	public $errorCodes = array();

	/**
	 * A list of error codes to ignore.
	 *
	 * @var array
	 */
	public $ignoreCodes = array();

	/**
	 * A list of tokenizers this sniff supports.
	 *
	 * @var array
	 */
	public $supportedTokenizers = array( 'JS' );


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
	 * @throws \PHP_CodeSniffer\Exceptions\RuntimeException If jslint.js could not be run.
	 */
	public function process( File $phpcsFile, $stackPtr ) {
		$lintPath = Config::getExecutablePath( 'gjslint' );
		if ( $lintPath === null ) {
			return $phpcsFile->numTokens;
		}

		$fileName = $phpcsFile->getFilename();

		$lintPath = Common::escapeshellcmd( $lintPath );
		$cmd      = $lintPath . ' --nosummary --notime --unix_mode ' . escapeshellarg( $fileName );
		exec( $cmd, $output, $retval );

		if ( is_array( $output ) === false ) {
			return $phpcsFile->numTokens;
		}

		foreach ( $output as $finding ) {
			$matches    = array();
			$numMatches = preg_match( '/^(.*):([0-9]+):\(.*?([0-9]+)\)(.*)$/', $finding, $matches );
			if ( $numMatches === 0 ) {
				continue;
			}

			// Skip error codes we are ignoring.
			$code = $matches[3];
			if ( in_array( $code, $this->ignoreCodes ) === true ) {
				continue;
			}

			$line  = (int) $matches[2];
			$error = trim( $matches[4] );

			$message = 'gjslint says: (%s) %s';
			$data    = array(
				$code,
				$error,
			);
			if ( in_array( $code, $this->errorCodes ) === true ) {
				$phpcsFile->addErrorOnLine( $message, $line, 'ExternalToolError', $data );
			} else {
				$phpcsFile->addWarningOnLine( $message, $line, 'ExternalTool', $data );
			}
		}//end foreach

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
		return 'Support for scanning JavaScript files will be removed completely in v4.0.0.';
	}//end getDeprecationMessage()
}//end class
