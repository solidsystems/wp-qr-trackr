<?php
/**
 * A simple sniff for detecting a BOM definition that may corrupt application work.
 *
 * @author    Piotr Karas <office@mediaself.pl>
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2010-2014 mediaSELF Sp. z o.o.
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Generic\Sniffs\Files;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

class ByteOrderMarkSniff implements Sniff {


	/**
	 * List of supported BOM definitions.
	 *
	 * Use encoding names as keys and hex BOM representations as values.
	 *
	 * @var array
	 */
	protected $bomDefinitions = array(
		'UTF-8'       => 'efbbbf',
		'UTF-16 (BE)' => 'feff',
		'UTF-16 (LE)' => 'fffe',
	);


	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array<int|string>
	 */
	public function register() {
		return array( T_INLINE_HTML );
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
		// The BOM will be the very first token in the file.
		if ( $stackPtr !== 0 ) {
			return $phpcsFile->numTokens;
		}

		$tokens = $phpcsFile->getTokens();

		foreach ( $this->bomDefinitions as $bomName => $expectedBomHex ) {
			$bomByteLength = ( strlen( $expectedBomHex ) / 2 );
			$htmlBomHex    = bin2hex( substr( $tokens[ $stackPtr ]['content'], 0, $bomByteLength ) );
			if ( $htmlBomHex === $expectedBomHex ) {
				$errorData = array( $bomName );
				$error     = 'File contains %s byte order mark, which may corrupt your application';
				$phpcsFile->addError( $error, $stackPtr, 'Found', $errorData );
				$phpcsFile->recordMetric( $stackPtr, 'Using byte order mark', 'yes' );
				return $phpcsFile->numTokens;
			}
		}

		$phpcsFile->recordMetric( $stackPtr, 'Using byte order mark', 'no' );

		return $phpcsFile->numTokens;
	}//end process()
}//end class
