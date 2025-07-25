<?php
/**
 * Ensures JS classes don't contain duplicate property names.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 *
 * @deprecated 3.9.0
 */

namespace PHP_CodeSniffer\Standards\Squiz\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\DeprecatedSniff;
use PHP_CodeSniffer\Sniffs\Sniff;

class DuplicatePropertySniff implements Sniff, DeprecatedSniff {


	/**
	 * A list of tokenizers this sniff supports.
	 *
	 * @var array
	 */
	public $supportedTokenizers = array( 'JS' );


	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array<int|string>
	 */
	public function register() {
		return array( T_OBJECT );
	}//end register()


	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The current file being processed.
	 * @param int                         $stackPtr  The position of the current token in the
	 *                                               stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process( File $phpcsFile, $stackPtr ) {
		$tokens = $phpcsFile->getTokens();

		$properties   = array();
		$wantedTokens = array(
			T_PROPERTY,
			T_OBJECT,
		);

		$next = $phpcsFile->findNext( $wantedTokens, ( $stackPtr + 1 ), $tokens[ $stackPtr ]['bracket_closer'] );
		while ( $next !== false && $next < $tokens[ $stackPtr ]['bracket_closer'] ) {
			if ( $tokens[ $next ]['code'] === T_OBJECT ) {
				// Skip nested objects.
				$next = $tokens[ $next ]['bracket_closer'];
			} else {
				$propName = $tokens[ $next ]['content'];
				if ( isset( $properties[ $propName ] ) === true ) {
					$error = 'Duplicate property definition found for "%s"; previously defined on line %s';
					$data  = array(
						$propName,
						$tokens[ $properties[ $propName ] ]['line'],
					);
					$phpcsFile->addError( $error, $next, 'Found', $data );
				}

				$properties[ $propName ] = $next;
			}//end if

			$next = $phpcsFile->findNext( $wantedTokens, ( $next + 1 ), $tokens[ $stackPtr ]['bracket_closer'] );
		}//end while
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
