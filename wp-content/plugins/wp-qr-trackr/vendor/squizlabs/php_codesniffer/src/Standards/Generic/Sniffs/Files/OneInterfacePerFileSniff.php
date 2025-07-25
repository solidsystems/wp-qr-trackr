<?php
/**
 * Checks that only one interface is declared per file.
 *
 * @author    Andy Grunwald <andygrunwald@gmail.com>
 * @copyright 2010-2014 Andy Grunwald
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Generic\Sniffs\Files;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

class OneInterfacePerFileSniff implements Sniff {



	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array<int|string>
	 */
	public function register() {
		return array( T_INTERFACE );
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
		$start  = ( $stackPtr + 1 );
		if ( isset( $tokens[ $stackPtr ]['scope_closer'] ) === true ) {
			$start = ( $tokens[ $stackPtr ]['scope_closer'] + 1 );
		}

		$nextInterface = $phpcsFile->findNext( $this->register(), $start );
		if ( $nextInterface !== false ) {
			$error = 'Only one interface is allowed in a file';
			$phpcsFile->addError( $error, $nextInterface, 'MultipleFound' );
		}
	}//end process()
}//end class
