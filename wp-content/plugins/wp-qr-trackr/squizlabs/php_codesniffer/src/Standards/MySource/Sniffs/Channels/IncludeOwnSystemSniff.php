<?php
/**
 * Ensures that a system does not include itself.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 *
 * @deprecated 3.9.0
 */

namespace PHP_CodeSniffer\Standards\MySource\Sniffs\Channels;

use PHP_CodeSniffer\Sniffs\DeprecatedSniff;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

class IncludeOwnSystemSniff implements Sniff, DeprecatedSniff {



	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array<int|string>
	 */
	public function register() {
		return array( T_DOUBLE_COLON );
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
		$fileName = $phpcsFile->getFilename();
		$matches  = array();
		if ( preg_match( '|/systems/(.*)/([^/]+)?actions.inc$|i', $fileName, $matches ) === 0 ) {
			// Not an actions file.
			return;
		}

		$ownClass = $matches[2];
		$tokens   = $phpcsFile->getTokens();

		$typeName = $phpcsFile->findNext( T_CONSTANT_ENCAPSED_STRING, ( $stackPtr + 2 ), null, false, true );
		$typeName = trim( $tokens[ $typeName ]['content'], " '" );
		switch ( strtolower( $tokens[ ( $stackPtr + 1 ) ]['content'] ) ) {
			case 'includesystem':
				$included = strtolower( $typeName );
				break;
			case 'includeasset':
				$included = strtolower( $typeName ) . 'assettype';
				break;
			case 'includewidget':
				$included = strtolower( $typeName ) . 'widgettype';
				break;
			default:
				return;
		}

		if ( $included === strtolower( $ownClass ) ) {
			$error = "You do not need to include \"%s\" from within the system's own actions file";
			$data  = array( $ownClass );
			$phpcsFile->addError( $error, $stackPtr, 'NotRequired', $data );
		}
	}//end process()


	/**
	 * Determines the included class name from given token.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The file where this token was found.
	 * @param array                       $tokens    The array of file tokens.
	 * @param int                         $stackPtr  The position in the tokens array of the
	 *                                               potentially included class.
	 *
	 * @return bool
	 */
	protected function getIncludedClassFromToken(
		$phpcsFile,
		array $tokens,
		$stackPtr
	) {

		return false;
	}//end getIncludedClassFromToken()


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
