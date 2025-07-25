<?php
/**
 * Checks that all file names are lowercased.
 *
 * @author    Andy Grunwald <andygrunwald@gmail.com>
 * @copyright 2010-2014 Andy Grunwald
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Generic\Sniffs\Files;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

class LowercasedFilenameSniff implements Sniff {



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
		$filename = $phpcsFile->getFilename();
		if ( $filename === 'STDIN' ) {
			return $phpcsFile->numTokens;
		}

		$filename          = basename( $filename );
		$lowercaseFilename = strtolower( $filename );
		if ( $filename !== $lowercaseFilename ) {
			$data  = array(
				$filename,
				$lowercaseFilename,
			);
			$error = 'Filename "%s" doesn\'t match the expected filename "%s"';
			$phpcsFile->addError( $error, $stackPtr, 'NotFound', $data );
			$phpcsFile->recordMetric( $stackPtr, 'Lowercase filename', 'no' );
		} else {
			$phpcsFile->recordMetric( $stackPtr, 'Lowercase filename', 'yes' );
		}

		// Ignore the rest of the file.
		return $phpcsFile->numTokens;
	}//end process()
}//end class
