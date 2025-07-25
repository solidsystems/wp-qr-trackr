<?php
/**
 * Ensures that object indexes are written in dot notation.
 *
 * @author    Sertan Danis <sdanis@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 *
 * @deprecated 3.9.0
 */

namespace PHP_CodeSniffer\Standards\Squiz\Sniffs\Objects;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\DeprecatedSniff;
use PHP_CodeSniffer\Sniffs\Sniff;

class DisallowObjectStringIndexSniff implements Sniff, DeprecatedSniff {


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
		return array( T_OPEN_SQUARE_BRACKET );
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

		// Check if the next non whitespace token is a string.
		$index = $phpcsFile->findNext( T_WHITESPACE, ( $stackPtr + 1 ), null, true );
		if ( $tokens[ $index ]['code'] !== T_CONSTANT_ENCAPSED_STRING ) {
			return;
		}

		// Make sure it is the only thing in the square brackets.
		$next = $phpcsFile->findNext( T_WHITESPACE, ( $index + 1 ), null, true );
		if ( $tokens[ $next ]['code'] !== T_CLOSE_SQUARE_BRACKET ) {
			return;
		}

		// Allow indexes that have dots in them because we can't write
		// them in dot notation.
		$content = trim( $tokens[ $index ]['content'], '"\' ' );
		if ( strpos( $content, '.' ) !== false ) {
			return;
		}

		// Also ignore reserved words.
		if ( $content === 'super' ) {
			return;
		}

		// Token before the opening square bracket cannot be a var name.
		$prev = $phpcsFile->findPrevious( T_WHITESPACE, ( $stackPtr - 1 ), null, true );
		if ( $tokens[ $prev ]['code'] === T_STRING ) {
			$error = 'Object indexes must be written in dot notation';
			$phpcsFile->addError( $error, $prev, 'Found' );
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
		return 'Support for scanning JavaScript files will be removed completely in v4.0.0.';
	}//end getDeprecationMessage()
}//end class
