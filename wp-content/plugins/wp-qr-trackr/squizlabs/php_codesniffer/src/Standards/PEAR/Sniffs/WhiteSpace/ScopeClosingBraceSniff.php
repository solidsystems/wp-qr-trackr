<?php
/**
 * Checks that the closing braces of scopes are aligned correctly.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\PEAR\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

class ScopeClosingBraceSniff implements Sniff {


	/**
	 * The number of spaces code should be indented.
	 *
	 * @var integer
	 */
	public $indent = 4;


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
	 * @param int                         $stackPtr  The position of the current token
	 *                                               in the stack passed in $tokens.
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

		$scopeStart = $tokens[ $stackPtr ]['scope_opener'];
		$scopeEnd   = $tokens[ $stackPtr ]['scope_closer'];

		// If the scope closer doesn't think it belongs to this scope opener
		// then the opener is sharing its closer with other tokens. We only
		// want to process the closer once, so skip this one.
		if ( isset( $tokens[ $scopeEnd ]['scope_condition'] ) === false
			|| $tokens[ $scopeEnd ]['scope_condition'] !== $stackPtr
		) {
			return;
		}

		// We need to actually find the first piece of content on this line,
		// because if this is a method with tokens before it (public, static etc)
		// or an if with an else before it, then we need to start the scope
		// checking from there, rather than the current token.
		$lineStart = ( $stackPtr - 1 );
		for ( $lineStart; $lineStart > 0; $lineStart-- ) {
			if ( strpos( $tokens[ $lineStart ]['content'], $phpcsFile->eolChar ) !== false ) {
				break;
			}
		}

		++$lineStart;

		$startColumn = 1;
		if ( $tokens[ $lineStart ]['code'] === T_WHITESPACE ) {
			$startColumn = $tokens[ ( $lineStart + 1 ) ]['column'];
		} elseif ( $tokens[ $lineStart ]['code'] === T_INLINE_HTML ) {
			$trimmed = ltrim( $tokens[ $lineStart ]['content'] );
			if ( $trimmed === '' ) {
				$startColumn = $tokens[ ( $lineStart + 1 ) ]['column'];
			} else {
				$startColumn = ( strlen( $tokens[ $lineStart ]['content'] ) - strlen( $trimmed ) );
			}
		}

		// Check that the closing brace is on it's own line.
		for ( $lastContent = ( $scopeEnd - 1 ); $lastContent > $scopeStart; $lastContent-- ) {
			if ( $tokens[ $lastContent ]['code'] === T_WHITESPACE || $tokens[ $lastContent ]['code'] === T_OPEN_TAG ) {
				continue;
			}

			if ( $tokens[ $lastContent ]['code'] === T_INLINE_HTML
				&& ltrim( $tokens[ $lastContent ]['content'] ) === ''
			) {
				continue;
			}

			break;
		}

		if ( $tokens[ $lastContent ]['line'] === $tokens[ $scopeEnd ]['line'] ) {
			$error = 'Closing brace must be on a line by itself';
			$fix   = $phpcsFile->addFixableError( $error, $scopeEnd, 'Line' );
			if ( $fix === true ) {
				$phpcsFile->fixer->addNewlineBefore( $scopeEnd );
			}

			return;
		}

		// Check now that the closing brace is lined up correctly.
		$lineStart = ( $scopeEnd - 1 );
		for ( $lineStart; $lineStart > 0; $lineStart-- ) {
			if ( strpos( $tokens[ $lineStart ]['content'], $phpcsFile->eolChar ) !== false ) {
				break;
			}
		}

		++$lineStart;

		$braceIndent = 0;
		if ( $tokens[ $lineStart ]['code'] === T_WHITESPACE ) {
			$braceIndent = ( $tokens[ ( $lineStart + 1 ) ]['column'] - 1 );
		} elseif ( $tokens[ $lineStart ]['code'] === T_INLINE_HTML ) {
			$trimmed = ltrim( $tokens[ $lineStart ]['content'] );
			if ( $trimmed === '' ) {
				$braceIndent = ( $tokens[ ( $lineStart + 1 ) ]['column'] - 1 );
			} else {
				$braceIndent = ( strlen( $tokens[ $lineStart ]['content'] ) - strlen( $trimmed ) - 1 );
			}
		}

		$fix = false;
		if ( $tokens[ $stackPtr ]['code'] === T_CASE
			|| $tokens[ $stackPtr ]['code'] === T_DEFAULT
		) {
			// BREAK statements should be indented n spaces from the
			// CASE or DEFAULT statement.
			$expectedIndent = ( $startColumn + $this->indent - 1 );
			if ( $braceIndent !== $expectedIndent ) {
				$error = 'Case breaking statement indented incorrectly; expected %s spaces, found %s';
				$data  = array(
					$expectedIndent,
					$braceIndent,
				);
				$fix   = $phpcsFile->addFixableError( $error, $scopeEnd, 'BreakIndent', $data );
			}
		} else {
			$expectedIndent = max( 0, ( $startColumn - 1 ) );
			if ( $braceIndent !== $expectedIndent ) {
				$error = 'Closing brace indented incorrectly; expected %s spaces, found %s';
				$data  = array(
					$expectedIndent,
					$braceIndent,
				);
				$fix   = $phpcsFile->addFixableError( $error, $scopeEnd, 'Indent', $data );
			}
		}//end if

		if ( $fix === true ) {
			$spaces = str_repeat( ' ', $expectedIndent );
			if ( $braceIndent === 0 ) {
				$phpcsFile->fixer->addContentBefore( $lineStart, $spaces );
			} else {
				$phpcsFile->fixer->replaceToken( $lineStart, ltrim( $tokens[ $lineStart ]['content'] ) );
				$phpcsFile->fixer->addContentBefore( $lineStart, $spaces );
			}
		}
	}//end process()
}//end class
