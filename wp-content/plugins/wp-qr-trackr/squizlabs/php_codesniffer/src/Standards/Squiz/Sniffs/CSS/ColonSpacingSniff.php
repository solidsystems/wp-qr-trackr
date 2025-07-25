<?php
/**
 * Ensure there is no space before a colon and one space after it.
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

class ColonSpacingSniff implements Sniff, DeprecatedSniff {


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
		return array( T_COLON );
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

		$prev = $phpcsFile->findPrevious( Tokens::$emptyTokens, ( $stackPtr - 1 ), null, true );
		if ( $tokens[ $prev ]['code'] !== T_STYLE ) {
			// The colon is not part of a style definition.
			return;
		}

		if ( $tokens[ $prev ]['content'] === 'progid' ) {
			// Special case for IE filters.
			return;
		}

		if ( $tokens[ ( $stackPtr - 1 ) ]['code'] === T_WHITESPACE ) {
			$error = 'There must be no space before a colon in a style definition';
			$fix   = $phpcsFile->addFixableError( $error, $stackPtr, 'Before' );
			if ( $fix === true ) {
				$phpcsFile->fixer->replaceToken( ( $stackPtr - 1 ), '' );
			}
		}

		$next = $phpcsFile->findNext( T_WHITESPACE, ( $stackPtr + 1 ), null, true );
		if ( $tokens[ $next ]['code'] === T_SEMICOLON || $tokens[ $next ]['code'] === T_STYLE ) {
			// Empty style definition, ignore it.
			return;
		}

		if ( $tokens[ ( $stackPtr + 1 ) ]['code'] !== T_WHITESPACE ) {
			$error = 'Expected 1 space after colon in style definition; 0 found';
			$fix   = $phpcsFile->addFixableError( $error, $stackPtr, 'NoneAfter' );
			if ( $fix === true ) {
				$phpcsFile->fixer->addContent( $stackPtr, ' ' );
			}
		} else {
			$content = $tokens[ ( $stackPtr + 1 ) ]['content'];
			if ( strpos( $content, $phpcsFile->eolChar ) === false ) {
				$length = strlen( $content );
				if ( $length !== 1 ) {
					$error = 'Expected 1 space after colon in style definition; %s found';
					$data  = array( $length );
					$fix   = $phpcsFile->addFixableError( $error, $stackPtr, 'After', $data );
					if ( $fix === true ) {
						$phpcsFile->fixer->replaceToken( ( $stackPtr + 1 ), ' ' );
					}
				}
			} else {
				$error = 'Expected 1 space after colon in style definition; newline found';
				$fix   = $phpcsFile->addFixableError( $error, $stackPtr, 'AfterNewline' );
				if ( $fix === true ) {
					$phpcsFile->fixer->replaceToken( ( $stackPtr + 1 ), ' ' );
				}
			}
		}//end if
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
