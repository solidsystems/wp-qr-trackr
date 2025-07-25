<?php
/**
 * Ensures that getRequestData() is used to access super globals.
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

class GetRequestDataSniff implements Sniff, DeprecatedSniff {



	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array<int|string>
	 */
	public function register() {
		return array( T_VARIABLE );
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

		$varName = $tokens[ $stackPtr ]['content'];
		if ( $varName !== '$_REQUEST'
			&& $varName !== '$_GET'
			&& $varName !== '$_POST'
			&& $varName !== '$_FILES'
		) {
			return;
		}

		// The only place these super globals can be accessed directly is
		// in the getRequestData() method of the Security class.
		$inClass = false;
		foreach ( $tokens[ $stackPtr ]['conditions'] as $i => $type ) {
			if ( $tokens[ $i ]['code'] === T_CLASS ) {
				$className = $phpcsFile->findNext( T_STRING, $i );
				$className = $tokens[ $className ]['content'];
				if ( strtolower( $className ) === 'security' ) {
					$inClass = true;
				} else {
					// We don't have nested classes.
					break;
				}
			} elseif ( $inClass === true && $tokens[ $i ]['code'] === T_FUNCTION ) {
				$funcName = $phpcsFile->findNext( T_STRING, $i );
				$funcName = $tokens[ $funcName ]['content'];
				if ( strtolower( $funcName ) === 'getrequestdata' ) {
					// This is valid.
					return;
				} else {
					// We don't have nested functions.
					break;
				}
			}//end if
		}//end foreach

		// If we get to here, the super global was used incorrectly.
		// First find out how it is being used.
		$globalName = strtolower( substr( $varName, 2 ) );
		$usedVar    = '';

		$openBracket = $phpcsFile->findNext( T_WHITESPACE, ( $stackPtr + 1 ), null, true );
		if ( $tokens[ $openBracket ]['code'] === T_OPEN_SQUARE_BRACKET ) {
			$closeBracket = $tokens[ $openBracket ]['bracket_closer'];
			$usedVar      = $phpcsFile->getTokensAsString( ( $openBracket + 1 ), ( $closeBracket - $openBracket - 1 ) );
		}

		$type  = 'SuperglobalAccessed';
		$error = 'The %s super global must not be accessed directly; use Security::getRequestData(';
		$data  = array( $varName );
		if ( $usedVar !== '' ) {
			$type  .= 'WithVar';
			$error .= '%s, \'%s\'';
			$data[] = $usedVar;
			$data[] = $globalName;
		}

		$error .= ') instead';
		$phpcsFile->addError( $error, $stackPtr, $type, $data );
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
