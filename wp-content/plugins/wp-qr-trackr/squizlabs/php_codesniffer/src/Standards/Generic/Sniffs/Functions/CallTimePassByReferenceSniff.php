<?php
/**
 * Ensures that variables are not passed by reference when calling a function.
 *
 * @author    Florian Grandel <jerico.dev@gmail.com>
 * @copyright 2009-2014 Florian Grandel
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 *
 * @deprecated 3.12.1
 */

namespace PHP_CodeSniffer\Standards\Generic\Sniffs\Functions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\DeprecatedSniff;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

class CallTimePassByReferenceSniff implements Sniff, DeprecatedSniff {



	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array<int|string>
	 */
	public function register() {
		return array(
			T_STRING,
			T_VARIABLE,
			T_ANON_CLASS,
			T_PARENT,
			T_SELF,
			T_STATIC,
		);
	}//end register()


	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
	 * @param int                         $stackPtr  The position of the current token
	 *                                               in the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process( File $phpcsFile, $stackPtr ) {
		$tokens = $phpcsFile->getTokens();

		$findTokens   = Tokens::$emptyTokens;
		$findTokens[] = T_BITWISE_AND;

		$prev = $phpcsFile->findPrevious( $findTokens, ( $stackPtr - 1 ), null, true );

		// Skip tokens that are the names of functions
		// within their definitions. For example: function myFunction...
		// "myFunction" is T_STRING but we should skip because it is not a
		// function or method *call*.
		$prevCode = $tokens[ $prev ]['code'];
		if ( $prevCode === T_FUNCTION ) {
			return;
		}

		// If the next non-whitespace token after the function or method call
		// is not an opening parenthesis then it cant really be a *call*.
		$functionName = $stackPtr;
		$openBracket  = $phpcsFile->findNext(
			Tokens::$emptyTokens,
			( $functionName + 1 ),
			null,
			true
		);

		if ( $openBracket === false || $tokens[ $openBracket ]['code'] !== T_OPEN_PARENTHESIS ) {
			return;
		}

		if ( isset( $tokens[ $openBracket ]['parenthesis_closer'] ) === false ) {
			return;
		}

		$closeBracket = $tokens[ $openBracket ]['parenthesis_closer'];

		$nextSeparator = $openBracket;
		$find          = array(
			T_VARIABLE,
			T_OPEN_SHORT_ARRAY,
		);

		while ( ( $nextSeparator = $phpcsFile->findNext( $find, ( $nextSeparator + 1 ), $closeBracket ) ) !== false ) {
			if ( $tokens[ $nextSeparator ]['code'] === T_OPEN_SHORT_ARRAY ) {
				$nextSeparator = $tokens[ $nextSeparator ]['bracket_closer'];
				continue;
			}

			// Make sure the variable belongs directly to this function call
			// and is not inside a nested function call or array.
			$brackets    = $tokens[ $nextSeparator ]['nested_parenthesis'];
			$lastBracket = array_pop( $brackets );
			if ( $lastBracket !== $closeBracket ) {
				continue;
			}

			$tokenBefore = $phpcsFile->findPrevious(
				Tokens::$emptyTokens,
				( $nextSeparator - 1 ),
				null,
				true
			);

			if ( $tokens[ $tokenBefore ]['code'] === T_BITWISE_AND ) {
				if ( $phpcsFile->isReference( $tokenBefore ) === false ) {
					continue;
				}

				// We also want to ignore references used in assignment
				// operations passed as function arguments, but isReference()
				// sees them as valid references (which they are).
				$tokenBefore = $phpcsFile->findPrevious(
					Tokens::$emptyTokens,
					( $tokenBefore - 1 ),
					null,
					true
				);

				if ( isset( Tokens::$assignmentTokens[ $tokens[ $tokenBefore ]['code'] ] ) === true ) {
					continue;
				}

				// T_BITWISE_AND represents a pass-by-reference.
				$error = 'Call-time pass-by-reference calls are prohibited';
				$phpcsFile->addError( $error, $tokenBefore, 'NotAllowed' );
			}//end if
		}//end while
	}//end process()


	/**
	 * Provide the version number in which the sniff was deprecated.
	 *
	 * @return string
	 */
	public function getDeprecationVersion() {
		return 'v3.12.1';
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
