<?php
/**
 * PHPCSUtils, utility functions and classes for PHP_CodeSniffer sniff developers.
 *
 * @package   PHPCSUtils
 * @copyright 2019-2020 PHPCSUtils Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSUtils
 */

namespace PHPCSUtils\Utils;

use PHP_CodeSniffer\Files\File;

/**
 * Utility functions for use when examining token conditions.
 *
 * @since 1.0.0 The `Conditions::getCondition()` and `Conditions::hasCondition()`
 *              methods are based on and inspired by the methods of the same name in the
 *              PHPCS native `PHP_CodeSniffer\Files\File` class.
 *              Also see {@see \PHPCSUtils\BackCompat\BCFile}.
 */
final class Conditions {


	/**
	 * Retrieve the position of a condition for the passed token.
	 *
	 * If no types are specified, the first condition for the token - or if `$first=false`,
	 * the last condition - will be returned.
	 *
	 * Main differences with the PHPCS version:
	 * - The singular `$type` parameter has become the more flexible `$types` parameter allowing to
	 *   search for several types of conditions in one go.
	 * - The `$types` parameter is now optional.
	 *
	 * @see \PHP_CodeSniffer\Files\File::getCondition()   Original source.
	 * @see \PHPCSUtils\BackCompat\BCFile::getCondition() Cross-version compatible version of the original.
	 *
	 * @since 1.0.0
	 *
	 * @param \PHP_CodeSniffer\Files\File  $phpcsFile The file being scanned.
	 * @param int                          $stackPtr  The position of the token we are checking.
	 * @param int|string|array<int|string> $types     Optional. The type(s) of tokens to search for.
	 * @param bool                         $first     Optional. Whether to search for the first (outermost)
	 *                                                (`true`) or the last (innermost) condition (`false`) of
	 *                                                the specified type(s).
	 *
	 * @return int|false Integer stack pointer to the condition; or `FALSE` if the token
	 *                   does not have the condition or has no conditions at all.
	 */
	public static function getCondition( File $phpcsFile, $stackPtr, $types = array(), $first = true ) {
		$tokens = $phpcsFile->getTokens();

		// Check for the existence of the token.
		if ( isset( $tokens[ $stackPtr ] ) === false ) {
			return false;
		}

		// Make sure the token has conditions.
		if ( empty( $tokens[ $stackPtr ]['conditions'] ) ) {
			return false;
		}

		$types      = (array) $types;
		$conditions = $tokens[ $stackPtr ]['conditions'];

		if ( empty( $types ) === true ) {
			// No types specified, just return the first/last condition pointer.
			if ( $first === false ) {
				\end( $conditions );
			} else {
				\reset( $conditions );
			}

			return \key( $conditions );
		}

		if ( $first === false ) {
			$conditions = \array_reverse( $conditions, true );
		}

		foreach ( $conditions as $ptr => $type ) {
			if ( isset( $tokens[ $ptr ] ) === true
				&& \in_array( $type, $types, true ) === true
			) {
				// We found a token with the required type.
				return $ptr;
			}
		}

		return false;
	}

	/**
	 * Determine if the passed token has a condition of one of the passed types.
	 *
	 * This method is not significantly different from the PHPCS native version.
	 *
	 * @see \PHP_CodeSniffer\Files\File::hasCondition()   Original source.
	 * @see \PHPCSUtils\BackCompat\BCFile::hasCondition() Cross-version compatible version of the original.
	 *
	 * @since 1.0.0
	 *
	 * @param \PHP_CodeSniffer\Files\File  $phpcsFile The file being scanned.
	 * @param int                          $stackPtr  The position of the token we are checking.
	 * @param int|string|array<int|string> $types     The type(s) of tokens to search for.
	 *
	 * @return bool
	 */
	public static function hasCondition( File $phpcsFile, $stackPtr, $types ) {
		return ( self::getCondition( $phpcsFile, $stackPtr, $types ) !== false );
	}

	/**
	 * Return the position of the first (outermost) condition of a certain type for the passed token.
	 *
	 * If no types are specified, the first condition for the token, independently of type,
	 * will be returned.
	 *
	 * @since 1.0.0
	 *
	 * @param \PHP_CodeSniffer\Files\File  $phpcsFile The file where this token was found.
	 * @param int                          $stackPtr  The position of the token we are checking.
	 * @param int|string|array<int|string> $types     Optional. The type(s) of tokens to search for.
	 *
	 * @return int|false Integer stack pointer to the condition; or `FALSE` if the token
	 *                   does not have the condition or has no conditions at all.
	 */
	public static function getFirstCondition( File $phpcsFile, $stackPtr, $types = array() ) {
		return self::getCondition( $phpcsFile, $stackPtr, $types, true );
	}

	/**
	 * Return the position of the last (innermost) condition of a certain type for the passed token.
	 *
	 * If no types are specified, the last condition for the token, independently of type,
	 * will be returned.
	 *
	 * @since 1.0.0
	 *
	 * @param \PHP_CodeSniffer\Files\File  $phpcsFile The file where this token was found.
	 * @param int                          $stackPtr  The position of the token we are checking.
	 * @param int|string|array<int|string> $types     Optional. The type(s) of tokens to search for.
	 *
	 * @return int|false Integer stack pointer to the condition; or `FALSE` if the token
	 *                   does not have the condition or has no conditions at all.
	 */
	public static function getLastCondition( File $phpcsFile, $stackPtr, $types = array() ) {
		return self::getCondition( $phpcsFile, $stackPtr, $types, false );
	}
}
