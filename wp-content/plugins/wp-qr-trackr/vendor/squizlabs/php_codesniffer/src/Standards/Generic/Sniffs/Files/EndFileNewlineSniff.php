<?php
/**
 * Ensures the file ends with a newline character.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Generic\Sniffs\Files;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

class EndFileNewlineSniff implements Sniff {


	/**
	 * A list of tokenizers this sniff supports.
	 *
	 * @var array
	 */
	public $supportedTokenizers = array(
		'PHP',
		'JS',
		'CSS',
	);


	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array<int|string>
	 */
	public function register() {
		return array(
			T_OPEN_TAG,
			T_OPEN_TAG_WITH_ECHO,
		);
	}//end register()


	/**
	 * Processes this sniff, when one of its tokens is encountered.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
	 * @param int                         $stackPtr  The position of the current token in
	 *                                               the stack passed in $tokens.
	 *
	 * @return int
	 */
	public function process( File $phpcsFile, $stackPtr ) {
		// Skip to the end of the file.
		$tokens   = $phpcsFile->getTokens();
		$stackPtr = ( $phpcsFile->numTokens - 1 );

		if ( $tokens[ $stackPtr ]['content'] === '' ) {
			--$stackPtr;
		}

		$eolCharLen = strlen( $phpcsFile->eolChar );
		$lastChars  = substr( $tokens[ $stackPtr ]['content'], ( $eolCharLen * -1 ) );
		if ( $lastChars !== $phpcsFile->eolChar ) {
			$phpcsFile->recordMetric( $stackPtr, 'Newline at EOF', 'no' );

			$error = 'File must end with a newline character';
			$fix   = $phpcsFile->addFixableError( $error, $stackPtr, 'NotFound' );
			if ( $fix === true ) {
				$phpcsFile->fixer->addNewline( $stackPtr );
			}
		} else {
			$phpcsFile->recordMetric( $stackPtr, 'Newline at EOF', 'yes' );
		}

		// Ignore the rest of the file.
		return $phpcsFile->numTokens;
	}//end process()
}//end class
