<?php
/**
 * Ensure sizes are defined using shorthand notation where possible.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 *
 * @deprecated 3.9.0
 */

namespace PHP_CodeSniffer\Standards\Squiz\Sniffs\CSS;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\DeprecatedSniff;
use PHP_CodeSniffer\Sniffs\Sniff;

class ShorthandSizeSniff implements Sniff, DeprecatedSniff {


	/**
	 * A list of tokenizers this sniff supports.
	 *
	 * @var array
	 */
	public $supportedTokenizers = array( 'CSS' );

	/**
	 * A list of styles that we shouldn't check.
	 *
	 * These have values that looks like sizes, but are not.
	 *
	 * @var array
	 */
	protected $excludeStyles = array(
		'background-position'      => 'background-position',
		'box-shadow'               => 'box-shadow',
		'transform-origin'         => 'transform-origin',
		'-webkit-transform-origin' => '-webkit-transform-origin',
		'-ms-transform-origin'     => '-ms-transform-origin',
	);


	/**
	 * Returns the token types that this sniff is interested in.
	 *
	 * @return array<int|string>
	 */
	public function register() {
		return array( T_STYLE );
	}//end register()


	/**
	 * Processes the tokens that this sniff is interested in.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The file where the token was found.
	 * @param int                         $stackPtr  The position in the stack where
	 *                                               the token was found.
	 *
	 * @return void
	 */
	public function process( File $phpcsFile, $stackPtr ) {
		$tokens = $phpcsFile->getTokens();

		// Some styles look like shorthand but are not actually a set of 4 sizes.
		$style = strtolower( $tokens[ $stackPtr ]['content'] );
		if ( isset( $this->excludeStyles[ $style ] ) === true ) {
			return;
		}

		$end = $phpcsFile->findNext( T_SEMICOLON, ( $stackPtr + 1 ) );
		if ( $end === false ) {
			// Live coding or parse error.
			return;
		}

		// Get the whole style content.
		$origContent = $phpcsFile->getTokensAsString( ( $stackPtr + 1 ), ( $end - $stackPtr - 1 ) );
		$origContent = trim( $origContent, ':' );
		$origContent = trim( $origContent );

		// Account for a !important annotation.
		$content = $origContent;
		if ( substr( $content, -10 ) === '!important' ) {
			$content = substr( $content, 0, -10 );
			$content = trim( $content );
		}

		// Check if this style value is a set of numbers with optional prefixes.
		$content = preg_replace( '/\s+/', ' ', $content );
		$values  = array();
		$num     = preg_match_all(
			'/(?:[0-9]+)(?:[a-zA-Z]{2}\s+|%\s+|\s+)/',
			$content . ' ',
			$values,
			PREG_SET_ORDER
		);

		// Only interested in styles that have multiple sizes defined.
		if ( $num < 2 ) {
			return;
		}

		// Rebuild the content we matched to ensure we got everything.
		$matched = '';
		foreach ( $values as $value ) {
			$matched .= $value[0];
		}

		if ( $content !== trim( $matched ) ) {
			return;
		}

		if ( $num === 3 ) {
			$expected = trim( $content . ' ' . $values[1][0] );
			$error    = 'Shorthand syntax not allowed here; use %s instead';
			$data     = array( $expected );
			$fix      = $phpcsFile->addFixableError( $error, $stackPtr, 'NotAllowed', $data );

			if ( $fix === true ) {
				$phpcsFile->fixer->beginChangeset();
				if ( substr( $origContent, -10 ) === '!important' ) {
					$expected .= ' !important';
				}

				$next = $phpcsFile->findNext( T_WHITESPACE, ( $stackPtr + 2 ), null, true );
				$phpcsFile->fixer->replaceToken( $next, $expected );
				for ( $next++; $next < $end; $next++ ) {
					$phpcsFile->fixer->replaceToken( $next, '' );
				}

				$phpcsFile->fixer->endChangeset();
			}

			return;
		}//end if

		if ( $num === 2 ) {
			if ( $values[0][0] !== $values[1][0] ) {
				// Both values are different, so it is already shorthand.
				return;
			}
		} elseif ( $values[0][0] !== $values[2][0] || $values[1][0] !== $values[3][0] ) {
			// Can't shorthand this.
			return;
		}

		if ( $values[0][0] === $values[1][0] ) {
			// All values are the same.
			$expected = trim( $values[0][0] );
		} else {
			$expected = trim( $values[0][0] ) . ' ' . trim( $values[1][0] );
		}

		$error = 'Size definitions must use shorthand if available; expected "%s" but found "%s"';
		$data  = array(
			$expected,
			$content,
		);

		$fix = $phpcsFile->addFixableError( $error, $stackPtr, 'NotUsed', $data );
		if ( $fix === true ) {
			$phpcsFile->fixer->beginChangeset();
			if ( substr( $origContent, -10 ) === '!important' ) {
				$expected .= ' !important';
			}

			$next = $phpcsFile->findNext( T_COLON, ( $stackPtr + 1 ) );
			$phpcsFile->fixer->addContent( $next, ' ' . $expected );
			for ( $next++; $next < $end; $next++ ) {
				$phpcsFile->fixer->replaceToken( $next, '' );
			}

			$phpcsFile->fixer->endChangeset();
		}
	}//end process()


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
		return 'Support for scanning CSS files will be removed completely in v4.0.0.';
	}//end getDeprecationMessage()
}//end class
