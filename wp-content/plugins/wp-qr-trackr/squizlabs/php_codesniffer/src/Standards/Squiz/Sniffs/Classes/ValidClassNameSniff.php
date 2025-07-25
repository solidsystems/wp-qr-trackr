<?php
/**
 * Ensures classes are in camel caps, and the first letter is capitalised.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Squiz\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Common;
use PHP_CodeSniffer\Util\Tokens;

class ValidClassNameSniff implements Sniff {



	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array<int|string>
	 */
	public function register() {
		return array(
			T_CLASS,
			T_INTERFACE,
			T_TRAIT,
			T_ENUM,
		);
	}//end register()


	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The current file being processed.
	 * @param int                         $stackPtr  The position of the current token in the
	 *                                               stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process( File $phpcsFile, $stackPtr ) {
		$tokens = $phpcsFile->getTokens();

		if ( isset( $tokens[ $stackPtr ]['scope_opener'] ) === false ) {
			$error = 'Possible parse error: %s missing opening or closing brace';
			$data  = array( $tokens[ $stackPtr ]['content'] );
			$phpcsFile->addWarning( $error, $stackPtr, 'MissingBrace', $data );
			return;
		}

		// Determine the name of the class or interface. Note that we cannot
		// simply look for the first T_STRING because a class name
		// starting with the number will be multiple tokens.
		$opener    = $tokens[ $stackPtr ]['scope_opener'];
		$nameStart = $phpcsFile->findNext( Tokens::$emptyTokens, ( $stackPtr + 1 ), $opener, true );
		$nameEnd   = $phpcsFile->findNext( ( Tokens::$emptyTokens + array( T_COLON => T_COLON ) ), $nameStart, $opener );
		if ( $nameEnd === false ) {
			$name = $tokens[ $nameStart ]['content'];
		} else {
			$name = trim( $phpcsFile->getTokensAsString( $nameStart, ( $nameEnd - $nameStart ) ) );
		}

		// Check for PascalCase format.
		$valid = Common::isCamelCaps( $name, true, true, false );
		if ( $valid === false ) {
			$type  = ucfirst( $tokens[ $stackPtr ]['content'] );
			$error = '%s name "%s" is not in PascalCase format';
			$data  = array(
				$type,
				$name,
			);
			$phpcsFile->addError( $error, $nameStart, 'NotCamelCaps', $data );
			$phpcsFile->recordMetric( $stackPtr, 'PascalCase class name', 'no' );
		} else {
			$phpcsFile->recordMetric( $stackPtr, 'PascalCase class name', 'yes' );
		}
	}//end process()
}//end class
