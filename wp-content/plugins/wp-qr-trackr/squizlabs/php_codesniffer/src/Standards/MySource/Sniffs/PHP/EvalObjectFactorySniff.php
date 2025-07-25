<?php
/**
 * Ensures that eval() is not used to create objects.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 *
 * @deprecated 3.9.0
 */

namespace PHP_CodeSniffer\Standards\MySource\Sniffs\PHP;

use PHP_CodeSniffer\Sniffs\DeprecatedSniff;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;

class EvalObjectFactorySniff implements Sniff, DeprecatedSniff {



	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array<int|string>
	 */
	public function register() {
		return array( T_EVAL );
	}//end register()


	/**
	 * Processes this sniff, when one of its tokens is encountered.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
	 * @param int                         $stackPtr  The position of the current token in
	 *                                               the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process( File $phpcsFile, $stackPtr ) {
		$tokens = $phpcsFile->getTokens();

		/*
			We need to find all strings that will be in the eval
			to determine if the "new" keyword is being used.
		*/

		$openBracket  = $phpcsFile->findNext( T_OPEN_PARENTHESIS, ( $stackPtr + 1 ) );
		$closeBracket = $tokens[ $openBracket ]['parenthesis_closer'];

		$strings = array();
		$vars    = array();

		for ( $i = ( $openBracket + 1 ); $i < $closeBracket; $i++ ) {
			if ( isset( Tokens::$stringTokens[ $tokens[ $i ]['code'] ] ) === true ) {
				$strings[ $i ] = $tokens[ $i ]['content'];
			} elseif ( $tokens[ $i ]['code'] === T_VARIABLE ) {
				$vars[ $i ] = $tokens[ $i ]['content'];
			}
		}

		/*
			We now have some variables that we need to expand into
			the strings that were assigned to them, if any.
		*/

		foreach ( $vars as $varPtr => $varName ) {
			while ( ( $prev = $phpcsFile->findPrevious( T_VARIABLE, ( $varPtr - 1 ) ) ) !== false ) {
				// Make sure this is an assignment of the variable. That means
				// it will be the first thing on the line.
				$prevContent = $phpcsFile->findPrevious( T_WHITESPACE, ( $prev - 1 ), null, true );
				if ( $tokens[ $prevContent ]['line'] === $tokens[ $prev ]['line'] ) {
					$varPtr = $prevContent;
					continue;
				}

				if ( $tokens[ $prev ]['content'] !== $varName ) {
					// This variable has a different name.
					$varPtr = $prevContent;
					continue;
				}

				// We found one.
				break;
			}//end while

			if ( $prev !== false ) {
				// Find all strings on the line.
				$lineEnd = $phpcsFile->findNext( T_SEMICOLON, ( $prev + 1 ) );
				for ( $i = ( $prev + 1 ); $i < $lineEnd; $i++ ) {
					if ( isset( Tokens::$stringTokens[ $tokens[ $i ]['code'] ] ) === true ) {
						$strings[ $i ] = $tokens[ $i ]['content'];
					}
				}
			}
		}//end foreach

		foreach ( $strings as $string ) {
			// If the string has "new" in it, it is not allowed.
			// We don't bother checking if the word "new" is printed to screen
			// because that is unlikely to happen. We assume the use
			// of "new" is for object instantiation.
			if ( strstr( $string, ' new ' ) !== false ) {
				$error = 'Do not use eval() to create objects dynamically; use reflection instead';
				$phpcsFile->addWarning( $error, $stackPtr, 'Found' );
			}
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
		return 'The MySource standard will be removed completely in v4.0.0.';
	}//end getDeprecationMessage()
}//end class
