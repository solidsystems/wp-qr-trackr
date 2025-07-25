<?php
/**
 * Checks that traits are suffixed by Trait.
 *
 * @author  Anna Borzenko <annnechko@gmail.com>
 * @license https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Generic\Sniffs\NamingConventions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

class TraitNameSuffixSniff implements Sniff {



	/**
	 * Registers the tokens that this sniff wants to listen for.
	 *
	 * @return array<int|string>
	 */
	public function register() {
		return array( T_TRAIT );
	}//end register()


	/**
	 * Processes this sniff, when one of its tokens is encountered.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
	 * @param int                         $stackPtr  The position of the current token
	 *                                               in the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process( File $phpcsFile, $stackPtr ) {
		$traitName = $phpcsFile->getDeclarationName( $stackPtr );
		if ( $traitName === null ) {
			// Live coding or parse error. Bow out.
			return;
		}

		$suffix = substr( $traitName, -5 );
		if ( strtolower( $suffix ) !== 'trait' ) {
			$phpcsFile->addError( 'Trait names must be suffixed with "Trait"; found "%s"', $stackPtr, 'Missing', array( $traitName ) );
		}
	}//end process()
}//end class
