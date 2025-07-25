<?php
/**
 * Checks that end of line characters are correct.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Generic\Sniffs\Files;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

class LineEndingsSniff implements Sniff {


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
	 * The valid EOL character.
	 *
	 * @var string
	 */
	public $eolChar = '\n';


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
		$found = $phpcsFile->eolChar;
		$found = str_replace( "\n", '\n', $found );
		$found = str_replace( "\r", '\r', $found );

		$phpcsFile->recordMetric( $stackPtr, 'EOL char', $found );

		if ( $found === $this->eolChar ) {
			// Ignore the rest of the file.
			return $phpcsFile->numTokens;
		}

		// Check for single line files without an EOL. This is a very special
		// case and the EOL char is set to \n when this happens.
		if ( $found === '\n' ) {
			$tokens    = $phpcsFile->getTokens();
			$lastToken = ( $phpcsFile->numTokens - 1 );
			if ( $tokens[ $lastToken ]['line'] === 1
				&& $tokens[ $lastToken ]['content'] !== "\n"
			) {
				return $phpcsFile->numTokens;
			}
		}

		$error    = 'End of line character is invalid; expected "%s" but found "%s"';
		$expected = $this->eolChar;
		$expected = str_replace( "\n", '\n', $expected );
		$expected = str_replace( "\r", '\r', $expected );
		$data     = array(
			$expected,
			$found,
		);

		// Errors are always reported on line 1, no matter where the first PHP tag is.
		$fix = $phpcsFile->addFixableError( $error, 0, 'InvalidEOLChar', $data );

		if ( $fix === true ) {
			$tokens = $phpcsFile->getTokens();
			switch ( $this->eolChar ) {
				case '\n':
					$eolChar = "\n";
					break;
				case '\r':
					$eolChar = "\r";
					break;
				case '\r\n':
					$eolChar = "\r\n";
					break;
				default:
					$eolChar = $this->eolChar;
					break;
			}

			for ( $i = 0; $i < $phpcsFile->numTokens; $i++ ) {
				if ( isset( $tokens[ ( $i + 1 ) ] ) === true
					&& $tokens[ ( $i + 1 ) ]['line'] <= $tokens[ $i ]['line']
				) {
					continue;
				}

				// Token is the last on a line.
				if ( isset( $tokens[ $i ]['orig_content'] ) === true ) {
					$tokenContent = $tokens[ $i ]['orig_content'];
				} else {
					$tokenContent = $tokens[ $i ]['content'];
				}

				if ( $tokenContent === '' ) {
					// Special case for JS/CSS close tag.
					continue;
				}

				$newContent  = rtrim( $tokenContent, "\r\n" );
				$newContent .= $eolChar;
				if ( $tokenContent !== $newContent ) {
					$phpcsFile->fixer->replaceToken( $i, $newContent );
				}
			}//end for
		}//end if

		// Ignore the rest of the file.
		return $phpcsFile->numTokens;
	}//end process()
}//end class
