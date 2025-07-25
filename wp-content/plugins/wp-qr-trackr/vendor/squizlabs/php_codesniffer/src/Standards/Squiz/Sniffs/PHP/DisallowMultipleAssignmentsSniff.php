<?php
/**
 * Ensures there is only one assignment on a line, and that it is the first thing on the line.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Squiz\Sniffs\PHP;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

class DisallowMultipleAssignmentsSniff implements Sniff {



	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array<int|string>
	 */
	public function register() {
		return array( T_EQUAL );
	}//end register()


	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
	 * @param int                         $stackPtr  The position of the current token in the
	 *                                               stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process( File $phpcsFile, $stackPtr ) {
		$tokens = $phpcsFile->getTokens();

		// Ignore default value assignments in function definitions.
		$function = $phpcsFile->findPrevious( array( T_FUNCTION, T_CLOSURE, T_FN ), ( $stackPtr - 1 ), null, false, null, true );
		if ( $function !== false ) {
			if ( isset( $tokens[ $function ]['parenthesis_closer'] ) === false ) {
				// Live coding/parse error. Bow out.
				return;
			}

			$opener = $tokens[ $function ]['parenthesis_opener'];
			$closer = $tokens[ $function ]['parenthesis_closer'];
			if ( $opener < $stackPtr && $closer > $stackPtr ) {
				return;
			}
		}

		// Ignore assignments in WHILE loop conditions.
		if ( isset( $tokens[ $stackPtr ]['nested_parenthesis'] ) === true ) {
			$nested = $tokens[ $stackPtr ]['nested_parenthesis'];
			foreach ( $nested as $opener => $closer ) {
				if ( isset( $tokens[ $opener ]['parenthesis_owner'] ) === true
					&& $tokens[ $tokens[ $opener ]['parenthesis_owner'] ]['code'] === T_WHILE
				) {
					return;
				}
			}
		}

		// Ignore member var definitions.
		if ( empty( $tokens[ $stackPtr ]['conditions'] ) === false ) {
			$conditions = $tokens[ $stackPtr ]['conditions'];
			end( $conditions );
			$deepestScope = key( $conditions );
			if ( isset( Tokens::$ooScopeTokens[ $tokens[ $deepestScope ]['code'] ] ) === true ) {
				return;
			}
		}

		/*
			The general rule is:
			Find an equal sign and go backwards along the line. If you hit an
			end bracket, skip to the opening bracket. When you find a variable,
			stop. That variable must be the first non-empty token on the line
			or in the statement. If not, throw an error.
		*/

		for ( $varToken = ( $stackPtr - 1 ); $varToken >= 0; $varToken-- ) {
			if ( in_array( $tokens[ $varToken ]['code'], array( T_SEMICOLON, T_OPEN_CURLY_BRACKET, T_CLOSE_TAG ), true ) === true ) {
				// We've reached the previous statement, so we didn't find a variable.
				return;
			}

			// Skip brackets.
			if ( isset( $tokens[ $varToken ]['parenthesis_opener'] ) === true && $tokens[ $varToken ]['parenthesis_opener'] < $varToken ) {
				$varToken = $tokens[ $varToken ]['parenthesis_opener'];
				continue;
			}

			if ( isset( $tokens[ $varToken ]['bracket_opener'] ) === true ) {
				$varToken = $tokens[ $varToken ]['bracket_opener'];
				continue;
			}

			if ( $tokens[ $varToken ]['code'] === T_VARIABLE ) {
				$prevNonEmpty = $phpcsFile->findPrevious( Tokens::$emptyTokens, ( $varToken - 1 ), null, true );
				if ( $tokens[ $prevNonEmpty ]['code'] === T_OBJECT_OPERATOR ) {
					// Dynamic property access, the real "start" variable still needs to be found.
					$varToken = $prevNonEmpty;
					continue;
				}

				// We found our variable.
				break;
			}
		}//end for

		if ( $varToken <= 0 ) {
			// Didn't find a variable.
			return;
		}

		$start = $phpcsFile->findStartOfStatement( $varToken );

		$allowed = Tokens::$emptyTokens;

		$allowed[ T_STRING ]       = T_STRING;
		$allowed[ T_NS_SEPARATOR ] = T_NS_SEPARATOR;
		$allowed[ T_DOUBLE_COLON ] = T_DOUBLE_COLON;
		$allowed[ T_ASPERAND ]     = T_ASPERAND;
		$allowed[ T_DOLLAR ]       = T_DOLLAR;
		$allowed[ T_SELF ]         = T_SELF;
		$allowed[ T_PARENT ]       = T_PARENT;
		$allowed[ T_STATIC ]       = T_STATIC;

		$varToken = $phpcsFile->findPrevious( $allowed, ( $varToken - 1 ), null, true );

		if ( $varToken < $start
			&& $tokens[ $varToken ]['code'] !== T_OPEN_PARENTHESIS
			&& $tokens[ $varToken ]['code'] !== T_OPEN_SQUARE_BRACKET
		) {
			$varToken = $start;
		}

		// Ignore the first part of FOR loops as we are allowed to
		// assign variables there even though the variable is not the
		// first thing on the line.
		if ( $tokens[ $varToken ]['code'] === T_OPEN_PARENTHESIS && isset( $tokens[ $varToken ]['parenthesis_owner'] ) === true ) {
			$owner = $tokens[ $varToken ]['parenthesis_owner'];
			if ( $tokens[ $owner ]['code'] === T_FOR ) {
				return;
			}
		}

		if ( $tokens[ $varToken ]['code'] === T_VARIABLE
			|| $tokens[ $varToken ]['code'] === T_OPEN_TAG
			|| $tokens[ $varToken ]['code'] === T_GOTO_LABEL
			|| $tokens[ $varToken ]['code'] === T_INLINE_THEN
			|| $tokens[ $varToken ]['code'] === T_INLINE_ELSE
			|| $tokens[ $varToken ]['code'] === T_SEMICOLON
			|| $tokens[ $varToken ]['code'] === T_CLOSE_PARENTHESIS
			|| isset( $allowed[ $tokens[ $varToken ]['code'] ] ) === true
		) {
			return;
		}

		$error     = 'Assignments must be the first block of code on a line';
		$errorCode = 'Found';

		if ( isset( $nested ) === true ) {
			$controlStructures = array(
				T_IF     => T_IF,
				T_ELSEIF => T_ELSEIF,
				T_SWITCH => T_SWITCH,
				T_CASE   => T_CASE,
				T_FOR    => T_FOR,
				T_MATCH  => T_MATCH,
			);
			foreach ( $nested as $opener => $closer ) {
				if ( isset( $tokens[ $opener ]['parenthesis_owner'] ) === true
					&& isset( $controlStructures[ $tokens[ $tokens[ $opener ]['parenthesis_owner'] ]['code'] ] ) === true
				) {
					$errorCode .= 'InControlStructure';
					break;
				}
			}
		}

		$phpcsFile->addError( $error, $stackPtr, $errorCode );
	}//end process()
}//end class
