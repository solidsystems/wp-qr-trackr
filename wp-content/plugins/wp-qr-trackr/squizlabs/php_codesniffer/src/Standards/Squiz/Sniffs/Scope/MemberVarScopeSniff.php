<?php
/**
 * Verifies that class members have scope modifiers.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Squiz\Sniffs\Scope;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\AbstractVariableSniff;

class MemberVarScopeSniff extends AbstractVariableSniff {



	/**
	 * Processes the function tokens within the class.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The file where this token was found.
	 * @param int                         $stackPtr  The position where the token was found.
	 *
	 * @return void
	 */
	protected function processMemberVar( File $phpcsFile, $stackPtr ) {
		$tokens     = $phpcsFile->getTokens();
		$properties = $phpcsFile->getMemberProperties( $stackPtr );

		if ( $properties === array() || $properties['scope_specified'] !== false ) {
			return;
		}

		if ( $properties['set_scope'] === false ) {
			$error = 'Scope modifier not specified for member variable "%s"';
			$data  = array( $tokens[ $stackPtr ]['content'] );
			$phpcsFile->addError( $error, $stackPtr, 'Missing', $data );
		} else {
			$error = 'Read scope modifier not specified for member variable "%s"';
			$data  = array( $tokens[ $stackPtr ]['content'] );
			$phpcsFile->addError( $error, $stackPtr, 'AsymReadMissing', $data );
		}
	}//end processMemberVar()


	/**
	 * Processes normal variables.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The file where this token was found.
	 * @param int                         $stackPtr  The position where the token was found.
	 *
	 * @return void
	 */
	protected function processVariable( File $phpcsFile, $stackPtr ) {
		/*
			We don't care about normal variables.
		*/
	}//end processVariable()


	/**
	 * Processes variables in double quoted strings.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The file where this token was found.
	 * @param int                         $stackPtr  The position where the token was found.
	 *
	 * @return void
	 */
	protected function processVariableInString( File $phpcsFile, $stackPtr ) {
		/*
			We don't care about normal variables.
		*/
	}//end processVariableInString()
}//end class
