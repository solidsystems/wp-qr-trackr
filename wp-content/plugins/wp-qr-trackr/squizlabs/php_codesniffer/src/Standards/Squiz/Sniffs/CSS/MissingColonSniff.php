<?php
/**
 * Ensure that all style definitions have a colon.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 *
 * @deprecated 3.9.0
 */

namespace PHP_CodeSniffer\Standards\Squiz\Sniffs\CSS;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\DeprecatedSniff;
use PHP_CodeSniffer\Sniffs\Sniff;

class MissingColonSniff implements Sniff, DeprecatedSniff {


	/**
	 * A list of tokenizers this sniff supports.
	 *
	 * @var array
	 */
	public $supportedTokenizers = array( 'CSS' );


	/**
	 * Returns the token types that this sniff is interested in.
	 *
	 * @return array<int|string>
	 */
	public function register() {
		return array( T_OPEN_CURLY_BRACKET );
	}//end register()


	/**
	 * Processes the tokens that this sniff is interested in.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The file where the token was found.
	 * @param int                         $stackPtr  The position in the stack where
	 *                                               the token was found.
	 *
	 * @return void
	 */
	public function process( File $phpcsFile, $stackPtr ) {
		$tokens = $phpcsFile->getTokens();

		if ( isset( $tokens[ $stackPtr ]['bracket_closer'] ) === false ) {
			// Syntax error or live coding, bow out.
			return;
		}

		$lastLine = $tokens[ $stackPtr ]['line'];
		$end      = $tokens[ $stackPtr ]['bracket_closer'];

		// Do not check nested style definitions as, for example, in @media style rules.
		$nested = $phpcsFile->findNext( T_OPEN_CURLY_BRACKET, ( $stackPtr + 1 ), $end );
		if ( $nested !== false ) {
			return;
		}

		$foundColon  = false;
		$foundString = false;
		for ( $i = ( $stackPtr + 1 ); $i <= $end; $i++ ) {
			if ( $tokens[ $i ]['line'] !== $lastLine ) {
				// We changed lines.
				if ( $foundColon === false && $foundString !== false ) {
					// We didn't find a colon on the previous line.
					$error = 'No style definition found on line; check for missing colon';
					$phpcsFile->addError( $error, $foundString, 'Found' );
				}

				$foundColon  = false;
				$foundString = false;
				$lastLine    = $tokens[ $i ]['line'];
			}

			if ( $tokens[ $i ]['code'] === T_STRING ) {
				$foundString = $i;
			} elseif ( $tokens[ $i ]['code'] === T_COLON ) {
				$foundColon = $i;
			}
		}//end for
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
		return 'Support for scanning CSS files will be removed completely in v4.0.0.';
	}//end getDeprecationMessage()
}//end class
