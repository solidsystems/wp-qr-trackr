<?php
/**
 * Ensures logical operators 'and' and 'or' are not used.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Squiz\Sniffs\Operators;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

class ValidLogicalOperatorsSniff implements Sniff {



	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array<int|string>
	 */
	public function register() {
		return array(
			T_LOGICAL_AND,
			T_LOGICAL_OR,
		);
	}//end register()


	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The current file being scanned.
	 * @param int                         $stackPtr  The position of the current token in the
	 *                                               stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process( File $phpcsFile, $stackPtr ) {
		$tokens = $phpcsFile->getTokens();

		$replacements = array(
			'and' => '&&',
			'or'  => '||',
		);

		$operator = strtolower( $tokens[ $stackPtr ]['content'] );
		if ( isset( $replacements[ $operator ] ) === false ) {
			return;
		}

		$error = 'Logical operator "%s" is prohibited; use "%s" instead';
		$data  = array(
			$operator,
			$replacements[ $operator ],
		);
		$phpcsFile->addError( $error, $stackPtr, 'NotAllowed', $data );
	}//end process()
}//end class
