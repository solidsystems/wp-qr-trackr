<?php
/**
 * Ensures that self and static are not used to call public methods in action classes.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 *
 * @deprecated 3.9.0
 */

namespace PHP_CodeSniffer\Standards\MySource\Sniffs\Channels;

use PHP_CodeSniffer\Sniffs\DeprecatedSniff;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;

class DisallowSelfActionsSniff implements Sniff, DeprecatedSniff {



	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array<int|string>
	 */
	public function register() {
		return array( T_CLASS );
	}//end register()


	/**
	 * Processes this sniff, when one of its tokens is encountered.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
	 * @param int                         $stackPtr  The position of the current token in
	 *                                               the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process( File $phpcsFile, $stackPtr ) {
		$tokens = $phpcsFile->getTokens();

		// We are not interested in abstract classes.
		$prev = $phpcsFile->findPrevious( T_WHITESPACE, ( $stackPtr - 1 ), null, true );
		if ( $prev !== false && $tokens[ $prev ]['code'] === T_ABSTRACT ) {
			return;
		}

		// We are only interested in Action classes.
		$classNameToken = $phpcsFile->findNext( T_WHITESPACE, ( $stackPtr + 1 ), null, true );
		$className      = $tokens[ $classNameToken ]['content'];
		if ( substr( $className, -7 ) !== 'Actions' ) {
			return;
		}

		$foundFunctions = array();
		$foundCalls     = array();

		// Find all static method calls in the form self::method() in the class.
		$classEnd = $tokens[ $stackPtr ]['scope_closer'];
		for ( $i = ( $classNameToken + 1 ); $i < $classEnd; $i++ ) {
			if ( $tokens[ $i ]['code'] !== T_DOUBLE_COLON ) {
				if ( $tokens[ $i ]['code'] === T_FUNCTION ) {
					// Cache the function information.
					$funcName  = $phpcsFile->findNext( T_STRING, ( $i + 1 ) );
					$funcScope = $phpcsFile->findPrevious( Tokens::$scopeModifiers, ( $i - 1 ) );

					$foundFunctions[ $tokens[ $funcName ]['content'] ] = strtolower( $tokens[ $funcScope ]['content'] );
				}

				continue;
			}

			$prevToken = $phpcsFile->findPrevious( T_WHITESPACE, ( $i - 1 ), null, true );
			if ( $tokens[ $prevToken ]['content'] !== 'self'
				&& $tokens[ $prevToken ]['content'] !== 'static'
			) {
				continue;
			}

			$funcNameToken = $phpcsFile->findNext( T_WHITESPACE, ( $i + 1 ), null, true );
			if ( $tokens[ $funcNameToken ]['code'] === T_VARIABLE ) {
				// We are only interested in function calls.
				continue;
			}

			$funcName = $tokens[ $funcNameToken ]['content'];

			// We've found the function, now we need to find it and see if it is
			// public, private or protected. If it starts with an underscore we
			// can assume it is private.
			if ( $funcName[0] === '_' ) {
				continue;
			}

			$foundCalls[ $i ] = array(
				'name' => $funcName,
				'type' => strtolower( $tokens[ $prevToken ]['content'] ),
			);
		}//end for

		$errorClassName = substr( $className, 0, -7 );

		foreach ( $foundCalls as $token => $funcData ) {
			if ( isset( $foundFunctions[ $funcData['name'] ] ) === false ) {
				// Function was not in this class, might have come from the parent.
				// Either way, we can't really check this.
				continue;
			} elseif ( $foundFunctions[ $funcData['name'] ] === 'public' ) {
				$type  = $funcData['type'];
				$error = "Static calls to public methods in Action classes must not use the $type keyword; use %s::%s() instead";
				$data  = array(
					$errorClassName,
					$funcName,
				);
				$phpcsFile->addError( $error, $token, 'Found' . ucfirst( $funcData['type'] ), $data );
			}
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
		return 'The MySource standard will be removed completely in v4.0.0.';
	}//end getDeprecationMessage()
}//end class
