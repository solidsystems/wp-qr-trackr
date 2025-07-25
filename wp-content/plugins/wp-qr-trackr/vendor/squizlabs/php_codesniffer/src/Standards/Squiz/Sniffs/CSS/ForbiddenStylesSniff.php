<?php
/**
 * Bans the use of some styles, such as deprecated or browser-specific styles.
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

class ForbiddenStylesSniff implements Sniff, DeprecatedSniff {


	/**
	 * A list of tokenizers this sniff supports.
	 *
	 * @var array
	 */
	public $supportedTokenizers = array( 'CSS' );

	/**
	 * A list of forbidden styles with their alternatives.
	 *
	 * The value is NULL if no alternative exists. i.e., the
	 * style should just not be used.
	 *
	 * @var array<string, string|null>
	 */
	protected $forbiddenStyles = array(
		'-moz-border-radius'             => 'border-radius',
		'-webkit-border-radius'          => 'border-radius',
		'-moz-border-radius-topleft'     => 'border-top-left-radius',
		'-moz-border-radius-topright'    => 'border-top-right-radius',
		'-moz-border-radius-bottomright' => 'border-bottom-right-radius',
		'-moz-border-radius-bottomleft'  => 'border-bottom-left-radius',
		'-moz-box-shadow'                => 'box-shadow',
		'-webkit-box-shadow'             => 'box-shadow',
	);

	/**
	 * A cache of forbidden style names, for faster lookups.
	 *
	 * @var string[]
	 */
	protected $forbiddenStyleNames = array();

	/**
	 * If true, forbidden styles will be considered regular expressions.
	 *
	 * @var boolean
	 */
	protected $patternMatch = false;

	/**
	 * If true, an error will be thrown; otherwise a warning.
	 *
	 * @var boolean
	 */
	public $error = true;


	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array<int|string>
	 */
	public function register() {
		$this->forbiddenStyleNames = array_keys( $this->forbiddenStyles );

		if ( $this->patternMatch === true ) {
			foreach ( $this->forbiddenStyleNames as $i => $name ) {
				$this->forbiddenStyleNames[ $i ] = '/' . $name . '/i';
			}
		}

		return array( T_STYLE );
	}//end register()


	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
	 * @param int                         $stackPtr  The position of the current token in
	 *                                               the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process( File $phpcsFile, $stackPtr ) {
		$tokens  = $phpcsFile->getTokens();
		$style   = strtolower( $tokens[ $stackPtr ]['content'] );
		$pattern = null;

		if ( $this->patternMatch === true ) {
			$count   = 0;
			$pattern = preg_replace(
				$this->forbiddenStyleNames,
				$this->forbiddenStyleNames,
				$style,
				1,
				$count
			);

			if ( $count === 0 ) {
				return;
			}

			// Remove the pattern delimiters and modifier.
			$pattern = substr( $pattern, 1, -2 );
		} elseif ( in_array( $style, $this->forbiddenStyleNames, true ) === false ) {
				return;
		}//end if

		$this->addError( $phpcsFile, $stackPtr, $style, $pattern );
	}//end process()


	/**
	 * Generates the error or warning for this sniff.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
	 * @param int                         $stackPtr  The position of the forbidden style
	 *                                               in the token array.
	 * @param string                      $style     The name of the forbidden style.
	 * @param string                      $pattern   The pattern used for the match.
	 *
	 * @return void
	 */
	protected function addError( $phpcsFile, $stackPtr, $style, $pattern = null ) {
		$data  = array( $style );
		$error = 'The use of style %s is ';
		if ( $this->error === true ) {
			$type   = 'Found';
			$error .= 'forbidden';
		} else {
			$type   = 'Discouraged';
			$error .= 'discouraged';
		}

		if ( $pattern === null ) {
			$pattern = $style;
		}

		if ( $this->forbiddenStyles[ $pattern ] !== null ) {
			$data[] = $this->forbiddenStyles[ $pattern ];
			if ( $this->error === true ) {
				$fix = $phpcsFile->addFixableError( $error . '; use %s instead', $stackPtr, $type . 'WithAlternative', $data );
			} else {
				$fix = $phpcsFile->addFixableWarning( $error . '; use %s instead', $stackPtr, $type . 'WithAlternative', $data );
			}

			if ( $fix === true ) {
				$phpcsFile->fixer->replaceToken( $stackPtr, $this->forbiddenStyles[ $pattern ] );
			}
		} elseif ( $this->error === true ) {
				$phpcsFile->addError( $error, $stackPtr, $type, $data );
		} else {
			$phpcsFile->addWarning( $error, $stackPtr, $type, $data );
		}
	}//end addError()


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
