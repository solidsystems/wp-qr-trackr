<?php
/**
 * Checks that the closing braces of scopes are aligned correctly.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Squiz\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

class ScopeClosingBraceSniff implements Sniff {



	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array<int|string>
	 */
	public function register() {
		return Tokens::$scopeOpeners;
	}//end register()


	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile All the tokens found in the document.
	 * @param int                         $stackPtr  The position of the current token in the
	 *                                               stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process( File $phpcsFile, $stackPtr ) {
		$tokens = $phpcsFile->getTokens();

		// If this is an inline condition (ie. there is no scope opener), then
		// return, as this is not a new scope.
		if ( isset( $tokens[ $stackPtr ]['scope_closer'] ) === false ) {
			return;
		}

		// We need to actually find the first piece of content on this line,
		// as if this is a method with tokens before it (public, static etc)
		// or an if with an else before it, then we need to start the scope
		// checking from there, rather than the current token.
		$lineStart = $phpcsFile->findFirstOnLine( array( T_WHITESPACE, T_INLINE_HTML ), $stackPtr, true );
		while ( $tokens[ $lineStart ]['code'] === T_CONSTANT_ENCAPSED_STRING
			&& $tokens[ ( $lineStart - 1 ) ]['code'] === T_CONSTANT_ENCAPSED_STRING
		) {
			$lineStart = $phpcsFile->findFirstOnLine( array( T_WHITESPACE, T_INLINE_HTML ), ( $lineStart - 1 ), true );
		}

		$startColumn = $tokens[ $lineStart ]['column'];
		$scopeStart  = $tokens[ $stackPtr ]['scope_opener'];
		$scopeEnd    = $tokens[ $stackPtr ]['scope_closer'];

		// Check that the closing brace is on it's own line.
		$lastContent = $phpcsFile->findPrevious( array( T_INLINE_HTML, T_WHITESPACE, T_OPEN_TAG ), ( $scopeEnd - 1 ), $scopeStart, true );
		for ($lineStart = $scopeEnd; $tokens[ $lineStart ]['column'] > 1; $lineStart--);

		if ( $tokens[ $lastContent ]['line'] === $tokens[ $scopeEnd ]['line']
			|| ( $tokens[ $lineStart ]['code'] === T_INLINE_HTML
			&& trim( $tokens[ $lineStart ]['content'] ) !== '' )
		) {
			$error = 'Closing brace must be on a line by itself';
			$fix   = $phpcsFile->addFixableError( $error, $scopeEnd, 'ContentBefore' );
			if ( $fix === true ) {
				if ( $tokens[ $lastContent ]['line'] === $tokens[ $scopeEnd ]['line'] ) {
					$phpcsFile->fixer->addNewlineBefore( $scopeEnd );
				} else {
					$phpcsFile->fixer->addNewlineBefore( ( $lineStart + 1 ) );
				}
			}

			return;
		}

		// Check now that the closing brace is lined up correctly.
		$lineStart   = $phpcsFile->findFirstOnLine( array( T_WHITESPACE, T_INLINE_HTML ), $scopeEnd, true );
		$braceIndent = $tokens[ $lineStart ]['column'];
		if ( $tokens[ $stackPtr ]['code'] !== T_DEFAULT
			&& $tokens[ $stackPtr ]['code'] !== T_CASE
			&& $braceIndent !== $startColumn
		) {
			$error = 'Closing brace indented incorrectly; expected %s spaces, found %s';
			$data  = array(
				( $startColumn - 1 ),
				( $braceIndent - 1 ),
			);

			$fix = $phpcsFile->addFixableError( $error, $scopeEnd, 'Indent', $data );
			if ( $fix === true ) {
				$diff = ( $startColumn - $braceIndent );
				if ( $diff > 0 ) {
					$phpcsFile->fixer->addContentBefore( $lineStart, str_repeat( ' ', $diff ) );
				} else {
					$phpcsFile->fixer->substrToken( ( $lineStart - 1 ), 0, $diff );
				}
			}
		}//end if
	}//end process()
}//end class
