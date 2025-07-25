<?php
/**
 * Check for duplicate style definitions in the same class.
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

class DuplicateStyleDefinitionSniff implements Sniff, DeprecatedSniff {


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

		// Find the content of each style definition name.
		$styleNames = array();

		$next = $stackPtr;
		$end  = $tokens[ $stackPtr ]['bracket_closer'];

		do {
			$next = $phpcsFile->findNext( array( T_STYLE, T_OPEN_CURLY_BRACKET ), ( $next + 1 ), $end );
			if ( $next === false ) {
				// Class definition is empty.
				break;
			}

			if ( $tokens[ $next ]['code'] === T_OPEN_CURLY_BRACKET ) {
				$next = $tokens[ $next ]['bracket_closer'];
				continue;
			}

			$name = $tokens[ $next ]['content'];
			if ( isset( $styleNames[ $name ] ) === true ) {
				$first = $styleNames[ $name ];
				$error = 'Duplicate style definition found; first defined on line %s';
				$data  = array( $tokens[ $first ]['line'] );
				$phpcsFile->addError( $error, $next, 'Found', $data );
			} else {
				$styleNames[ $name ] = $next;
			}
		} while ( $next !== false );
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
