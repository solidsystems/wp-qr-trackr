<?php
/**
 * Ensure each style definition has a semicolon and it is spaced correctly.
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
use PHP_CodeSniffer\Util\Tokens;

class SemicolonSpacingSniff implements Sniff, DeprecatedSniff {


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
		return array( T_STYLE );
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

		$nextStatement = $phpcsFile->findNext( array( T_STYLE, T_CLOSE_CURLY_BRACKET ), ( $stackPtr + 1 ) );
		if ( $nextStatement === false ) {
			return;
		}

		$ignore = Tokens::$emptyTokens;
		if ( $tokens[ $nextStatement ]['code'] === T_STYLE ) {
			// Allow for star-prefix hack.
			$ignore[] = T_MULTIPLY;
		}

		$endOfThisStatement = $phpcsFile->findPrevious( $ignore, ( $nextStatement - 1 ), null, true );
		if ( $tokens[ $endOfThisStatement ]['code'] !== T_SEMICOLON ) {
			$error = 'Style definitions must end with a semicolon';
			$phpcsFile->addError( $error, $endOfThisStatement, 'NotAtEnd' );
			return;
		}

		if ( $tokens[ ( $endOfThisStatement - 1 ) ]['code'] !== T_WHITESPACE ) {
			return;
		}

		// There is a semicolon, so now find the last token in the statement.
		$prevNonEmpty = $phpcsFile->findPrevious( Tokens::$emptyTokens, ( $endOfThisStatement - 1 ), null, true );
		$found        = $tokens[ ( $endOfThisStatement - 1 ) ]['length'];
		if ( $tokens[ $prevNonEmpty ]['line'] !== $tokens[ $endOfThisStatement ]['line'] ) {
			$found = 'newline';
		}

		$error = 'Expected 0 spaces before semicolon in style definition; %s found';
		$data  = array( $found );
		$fix   = $phpcsFile->addFixableError( $error, $prevNonEmpty, 'SpaceFound', $data );
		if ( $fix === true ) {
			$phpcsFile->fixer->beginChangeset();
			$phpcsFile->fixer->addContent( $prevNonEmpty, ';' );
			$phpcsFile->fixer->replaceToken( $endOfThisStatement, '' );

			for ( $i = ( $endOfThisStatement - 1 ); $i > $prevNonEmpty; $i-- ) {
				if ( $tokens[ $i ]['code'] !== T_WHITESPACE ) {
					break;
				}

				$phpcsFile->fixer->replaceToken( $i, '' );
			}

			$phpcsFile->fixer->endChangeset();
		}
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
