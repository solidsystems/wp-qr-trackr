<?php
/**
 * Ensures namespaces are declared correctly.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\PSR2\Sniffs\Namespaces;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

class NamespaceDeclarationSniff implements Sniff {



	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array<int|string>
	 */
	public function register() {
		return array( T_NAMESPACE );
	}//end register()


	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
	 * @param int                         $stackPtr  The position of the current token in
	 *                                               the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process( File $phpcsFile, $stackPtr ) {
		$tokens = $phpcsFile->getTokens();

		$nextNonEmpty = $phpcsFile->findNext( Tokens::$emptyTokens, ( $stackPtr + 1 ), null, true );
		if ( $tokens[ $nextNonEmpty ]['code'] === T_NS_SEPARATOR ) {
			// Namespace keyword as operator. Not a declaration.
			return;
		}

		$end = $phpcsFile->findEndOfStatement( $stackPtr );
		for ( $i = ( $end + 1 ); $i < ( $phpcsFile->numTokens - 1 ); $i++ ) {
			if ( $tokens[ $i ]['line'] === $tokens[ $end ]['line'] ) {
				continue;
			}

			break;
		}

		// The $i var now points to the first token on the line after the
		// namespace declaration, which must be a blank line.
		$next = $phpcsFile->findNext( T_WHITESPACE, $i, $phpcsFile->numTokens, true );
		if ( $next === false ) {
			return;
		}

		$diff = ( $tokens[ $next ]['line'] - $tokens[ $i ]['line'] );
		if ( $diff === 1 ) {
			return;
		}

		if ( $diff < 0 ) {
			$diff = 0;
		}

		$error = 'There must be one blank line after the namespace declaration';
		$fix   = $phpcsFile->addFixableError( $error, $stackPtr, 'BlankLineAfter' );

		if ( $fix === true ) {
			if ( $diff === 0 ) {
				$phpcsFile->fixer->addNewlineBefore( $i );
			} else {
				$phpcsFile->fixer->beginChangeset();
				for ( $x = $i; $x < $next; $x++ ) {
					if ( $tokens[ $x ]['line'] === $tokens[ $next ]['line'] ) {
						break;
					}

					$phpcsFile->fixer->replaceToken( $x, '' );
				}

				$phpcsFile->fixer->addNewline( $i );
				$phpcsFile->fixer->endChangeset();
			}
		}
	}//end process()
}//end class
