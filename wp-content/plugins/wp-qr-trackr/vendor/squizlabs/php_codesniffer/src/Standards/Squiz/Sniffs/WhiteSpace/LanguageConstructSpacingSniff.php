<?php
/**
 * Ensures all language constructs contain a single space between themselves and their content.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 *
 * @deprecated 3.3.0 Use the Generic.WhiteSpace.LanguageConstructSpacing sniff instead.
 */

namespace PHP_CodeSniffer\Standards\Squiz\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\DeprecatedSniff;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util;

class LanguageConstructSpacingSniff implements Sniff, DeprecatedSniff {



	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array<int|string>
	 */
	public function register() {
		return array(
			T_ECHO,
			T_PRINT,
			T_RETURN,
			T_INCLUDE,
			T_INCLUDE_ONCE,
			T_REQUIRE,
			T_REQUIRE_ONCE,
			T_NEW,
		);
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

		if ( isset( $tokens[ ( $stackPtr + 1 ) ] ) === false ) {
			// Skip if there is no next token.
			return;
		}

		if ( $tokens[ ( $stackPtr + 1 ) ]['code'] === T_SEMICOLON ) {
			// No content for this language construct.
			return;
		}

		if ( $tokens[ ( $stackPtr + 1 ) ]['code'] === T_WHITESPACE ) {
			$content = $tokens[ ( $stackPtr + 1 ) ]['content'];
			if ( $content !== ' ' ) {
				$error = 'Language constructs must be followed by a single space; expected 1 space but found "%s"';
				$data  = array( Util\Common::prepareForOutput( $content ) );
				$fix   = $phpcsFile->addFixableError( $error, $stackPtr, 'IncorrectSingle', $data );
				if ( $fix === true ) {
					$phpcsFile->fixer->replaceToken( ( $stackPtr + 1 ), ' ' );
				}
			}
		} elseif ( $tokens[ ( $stackPtr + 1 ) ]['code'] !== T_OPEN_PARENTHESIS ) {
			$error = 'Language constructs must be followed by a single space; expected "%s" but found "%s"';
			$data  = array(
				$tokens[ $stackPtr ]['content'] . ' ' . $tokens[ ( $stackPtr + 1 ) ]['content'],
				$tokens[ $stackPtr ]['content'] . $tokens[ ( $stackPtr + 1 ) ]['content'],
			);
			$fix   = $phpcsFile->addFixableError( $error, $stackPtr, 'Incorrect', $data );
			if ( $fix === true ) {
				$phpcsFile->fixer->addContent( $stackPtr, ' ' );
			}
		}//end if
	}//end process()


	/**
	 * Provide the version number in which the sniff was deprecated.
	 *
	 * @return string
	 */
	public function getDeprecationVersion() {
		return 'v3.3.0';
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
		return 'Use the Generic.WhiteSpace.LanguageConstructSpacing sniff instead.';
	}//end getDeprecationMessage()
}//end class
