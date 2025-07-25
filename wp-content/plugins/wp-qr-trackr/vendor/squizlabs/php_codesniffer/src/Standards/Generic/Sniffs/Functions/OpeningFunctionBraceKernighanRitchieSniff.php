<?php
/**
 * Checks that the opening brace of a function is on the same line as the function declaration.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Generic\Sniffs\Functions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

class OpeningFunctionBraceKernighanRitchieSniff implements Sniff {


	/**
	 * Should this sniff check function braces?
	 *
	 * @var boolean
	 */
	public $checkFunctions = true;

	/**
	 * Should this sniff check closure braces?
	 *
	 * @var boolean
	 */
	public $checkClosures = false;


	/**
	 * Registers the tokens that this sniff wants to listen for.
	 *
	 * @return array<int|string>
	 */
	public function register() {
		return array(
			T_FUNCTION,
			T_CLOSURE,
		);
	}//end register()


	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
	 * @param int                         $stackPtr  The position of the current token in the
	 *                                               stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process( File $phpcsFile, $stackPtr ) {
		$tokens = $phpcsFile->getTokens();

		if ( isset( $tokens[ $stackPtr ]['scope_opener'] ) === false ) {
			return;
		}

		if ( ( $tokens[ $stackPtr ]['code'] === T_FUNCTION
			&& (bool) $this->checkFunctions === false )
			|| ( $tokens[ $stackPtr ]['code'] === T_CLOSURE
			&& (bool) $this->checkClosures === false )
		) {
			return;
		}

		$openingBrace = $tokens[ $stackPtr ]['scope_opener'];

		// Find the end of the function declaration.
		$prev = $phpcsFile->findPrevious( Tokens::$emptyTokens, ( $openingBrace - 1 ), null, true );

		$functionLine = $tokens[ $prev ]['line'];
		$braceLine    = $tokens[ $openingBrace ]['line'];

		$lineDifference = ( $braceLine - $functionLine );

		$metricType = 'Function';
		if ( $tokens[ $stackPtr ]['code'] === T_CLOSURE ) {
			$metricType = 'Closure';
		}

		if ( $lineDifference > 0 ) {
			$phpcsFile->recordMetric( $stackPtr, "$metricType opening brace placement", 'new line' );
			$error = 'Opening brace should be on the same line as the declaration';
			$fix   = $phpcsFile->addFixableError( $error, $openingBrace, 'BraceOnNewLine' );
			if ( $fix === true ) {
				$phpcsFile->fixer->beginChangeset();
				$phpcsFile->fixer->addContent( $prev, ' {' );
				$phpcsFile->fixer->replaceToken( $openingBrace, '' );
				if ( $tokens[ ( $openingBrace + 1 ) ]['code'] === T_WHITESPACE
					&& $tokens[ ( $openingBrace + 2 ) ]['line'] > $tokens[ $openingBrace ]['line']
				) {
					// Brace is followed by a new line, so remove it to ensure we don't
					// leave behind a blank line at the top of the block.
					$phpcsFile->fixer->replaceToken( ( $openingBrace + 1 ), '' );

					if ( $tokens[ ( $openingBrace - 1 ) ]['code'] === T_WHITESPACE
						&& $tokens[ ( $openingBrace - 1 ) ]['line'] === $tokens[ $openingBrace ]['line']
						&& $tokens[ ( $openingBrace - 2 ) ]['line'] < $tokens[ $openingBrace ]['line']
					) {
						// Brace is preceded by indent, so remove it to ensure we don't
						// leave behind more indent than is required for the first line.
						$phpcsFile->fixer->replaceToken( ( $openingBrace - 1 ), '' );
					}
				}

				$phpcsFile->fixer->endChangeset();
			}//end if
		} else {
			$phpcsFile->recordMetric( $stackPtr, "$metricType opening brace placement", 'same line' );
		}//end if

		$ignore   = Tokens::$phpcsCommentTokens;
		$ignore[] = T_WHITESPACE;
		$next     = $phpcsFile->findNext( $ignore, ( $openingBrace + 1 ), null, true );
		if ( $tokens[ $next ]['line'] === $tokens[ $openingBrace ]['line'] ) {
			// Only throw this error when this is not an empty function.
			if ( $next !== $tokens[ $stackPtr ]['scope_closer']
				&& $tokens[ $next ]['code'] !== T_CLOSE_TAG
			) {
				$error = 'Opening brace must be the last content on the line';
				$fix   = $phpcsFile->addFixableError( $error, $openingBrace, 'ContentAfterBrace' );
				if ( $fix === true ) {
					$phpcsFile->fixer->addNewline( $openingBrace );
				}
			}
		}

		// Only continue checking if the opening brace looks good.
		if ( $lineDifference > 0 ) {
			return;
		}

		// Enforce a single space. Tabs not allowed.
		$spacing = $tokens[ ( $openingBrace - 1 ) ]['content'];
		if ( $tokens[ ( $openingBrace - 1 ) ]['code'] !== T_WHITESPACE ) {
			$length = 0;
		} elseif ( $spacing === "\t" ) {
			// Tab without tab-width set, so no tab replacement has taken place.
			$length = '\t';
		} else {
			$length = strlen( $spacing );
		}

		// If tab replacement is on, avoid confusing the user with a "expected 1 space, found 1"
		// message when the "1" found is actually a tab, not a space.
		if ( $length === 1
			&& isset( $tokens[ ( $openingBrace - 1 ) ]['orig_content'] ) === true
			&& $tokens[ ( $openingBrace - 1 ) ]['orig_content'] === "\t"
		) {
			$length = '\t';
		}

		if ( $length !== 1 ) {
			$error = 'Expected 1 space before opening brace; found %s';
			$data  = array( $length );
			$fix   = $phpcsFile->addFixableError( $error, $openingBrace, 'SpaceBeforeBrace', $data );
			if ( $fix === true ) {
				if ( $length === 0 ) {
					$phpcsFile->fixer->addContentBefore( $openingBrace, ' ' );
				} else {
					$phpcsFile->fixer->replaceToken( ( $openingBrace - 1 ), ' ' );
				}
			}
		}
	}//end process()
}//end class
