<?php
/**
 * Checks that control structures have the correct spacing around brackets.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Squiz\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

class ControlStructureSpacingSniff implements Sniff {


	/**
	 * A list of tokenizers this sniff supports.
	 *
	 * @var array
	 */
	public $supportedTokenizers = array(
		'PHP',
		'JS',
	);


	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array<int|string>
	 */
	public function register() {
		return array(
			T_IF,
			T_WHILE,
			T_FOREACH,
			T_FOR,
			T_SWITCH,
			T_DO,
			T_ELSE,
			T_ELSEIF,
			T_TRY,
			T_CATCH,
			T_FINALLY,
			T_MATCH,
		);
	}//end register()


	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
	 * @param int                         $stackPtr  The position of the current token
	 *                                               in the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process( File $phpcsFile, $stackPtr ) {
		$tokens = $phpcsFile->getTokens();

		if ( isset( $tokens[ $stackPtr ]['parenthesis_opener'] ) === true
			&& isset( $tokens[ $stackPtr ]['parenthesis_closer'] ) === true
		) {
			$parenOpener = $tokens[ $stackPtr ]['parenthesis_opener'];
			$parenCloser = $tokens[ $stackPtr ]['parenthesis_closer'];
			if ( $tokens[ ( $parenOpener + 1 ) ]['code'] === T_WHITESPACE ) {
				$gap = $tokens[ ( $parenOpener + 1 ) ]['length'];

				if ( $gap === 0 ) {
					$phpcsFile->recordMetric( $stackPtr, 'Spaces after control structure open parenthesis', 'newline' );
					$gap = 'newline';
				} else {
					$phpcsFile->recordMetric( $stackPtr, 'Spaces after control structure open parenthesis', $gap );
				}

				$error = 'Expected 0 spaces after opening bracket; %s found';
				$data  = array( $gap );
				$fix   = $phpcsFile->addFixableError( $error, ( $parenOpener + 1 ), 'SpacingAfterOpenBrace', $data );
				if ( $fix === true ) {
					$phpcsFile->fixer->replaceToken( ( $parenOpener + 1 ), '' );
				}
			} else {
				$phpcsFile->recordMetric( $stackPtr, 'Spaces after control structure open parenthesis', 0 );
			}

			if ( $tokens[ $parenOpener ]['line'] === $tokens[ $parenCloser ]['line']
				&& $tokens[ ( $parenCloser - 1 ) ]['code'] === T_WHITESPACE
			) {
				$gap   = $tokens[ ( $parenCloser - 1 ) ]['length'];
				$error = 'Expected 0 spaces before closing bracket; %s found';
				$data  = array( $gap );
				$fix   = $phpcsFile->addFixableError( $error, ( $parenCloser - 1 ), 'SpaceBeforeCloseBrace', $data );
				if ( $fix === true ) {
					$phpcsFile->fixer->replaceToken( ( $parenCloser - 1 ), '' );
				}

				if ( $gap === 0 ) {
					$phpcsFile->recordMetric( $stackPtr, 'Spaces before control structure close parenthesis', 'newline' );
				} else {
					$phpcsFile->recordMetric( $stackPtr, 'Spaces before control structure close parenthesis', $gap );
				}
			} else {
				$phpcsFile->recordMetric( $stackPtr, 'Spaces before control structure close parenthesis', 0 );
			}
		}//end if

		if ( isset( $tokens[ $stackPtr ]['scope_closer'] ) === false ) {
			return;
		}

		$scopeOpener = $tokens[ $stackPtr ]['scope_opener'];
		$scopeCloser = $tokens[ $stackPtr ]['scope_closer'];

		for ( $firstContent = ( $scopeOpener + 1 ); $firstContent < $phpcsFile->numTokens; $firstContent++ ) {
			$code = $tokens[ $firstContent ]['code'];

			if ( $code === T_WHITESPACE
				|| ( $code === T_INLINE_HTML
				&& trim( $tokens[ $firstContent ]['content'] ) === '' )
			) {
				continue;
			}

			// Skip all empty tokens on the same line as the opener.
			if ( $tokens[ $firstContent ]['line'] === $tokens[ $scopeOpener ]['line']
				&& ( isset( Tokens::$emptyTokens[ $code ] ) === true
				|| $code === T_CLOSE_TAG )
			) {
				continue;
			}

			break;
		}

		// We ignore spacing for some structures that tend to have their own rules.
		$ignore = array(
			T_FUNCTION             => true,
			T_CLASS                => true,
			T_INTERFACE            => true,
			T_TRAIT                => true,
			T_ENUM                 => true,
			T_DOC_COMMENT_OPEN_TAG => true,
		);

		if ( isset( $ignore[ $tokens[ $firstContent ]['code'] ] ) === false
			&& $tokens[ $firstContent ]['line'] >= ( $tokens[ $scopeOpener ]['line'] + 2 )
		) {
			$gap = ( $tokens[ $firstContent ]['line'] - $tokens[ $scopeOpener ]['line'] - 1 );
			$phpcsFile->recordMetric( $stackPtr, 'Blank lines at start of control structure', $gap );

			$error = 'Blank line found at start of control structure';
			$fix   = $phpcsFile->addFixableError( $error, $scopeOpener, 'SpacingAfterOpen' );

			if ( $fix === true ) {
				$phpcsFile->fixer->beginChangeset();
				$i = ( $scopeOpener + 1 );
				while ( $tokens[ $i ]['line'] !== $tokens[ $firstContent ]['line'] ) {
					// Start removing content from the line after the opener.
					if ( $tokens[ $i ]['line'] !== $tokens[ $scopeOpener ]['line'] ) {
						$phpcsFile->fixer->replaceToken( $i, '' );
					}

					++$i;
				}

				$phpcsFile->fixer->endChangeset();
			}
		} else {
			$phpcsFile->recordMetric( $stackPtr, 'Blank lines at start of control structure', 0 );
		}//end if

		if ( $firstContent !== $scopeCloser ) {
			$lastContent = $phpcsFile->findPrevious(
				T_WHITESPACE,
				( $scopeCloser - 1 ),
				null,
				true
			);

			$lastNonEmptyContent = $phpcsFile->findPrevious(
				Tokens::$emptyTokens,
				( $scopeCloser - 1 ),
				null,
				true
			);

			$checkToken = $lastContent;
			if ( isset( $tokens[ $lastNonEmptyContent ]['scope_condition'] ) === true ) {
				$checkToken = $tokens[ $lastNonEmptyContent ]['scope_condition'];
			}

			if ( isset( $ignore[ $tokens[ $checkToken ]['code'] ] ) === false
				&& $tokens[ $lastContent ]['line'] <= ( $tokens[ $scopeCloser ]['line'] - 2 )
			) {
				$errorToken = $scopeCloser;
				for ( $i = ( $scopeCloser - 1 ); $i > $lastContent; $i-- ) {
					if ( $tokens[ $i ]['line'] < $tokens[ $scopeCloser ]['line'] ) {
						$errorToken = $i;
						break;
					}
				}

				$gap = ( $tokens[ $scopeCloser ]['line'] - $tokens[ $lastContent ]['line'] - 1 );
				$phpcsFile->recordMetric( $stackPtr, 'Blank lines at end of control structure', $gap );

				$error = 'Blank line found at end of control structure';
				$fix   = $phpcsFile->addFixableError( $error, $errorToken, 'SpacingBeforeClose' );

				if ( $fix === true ) {
					$phpcsFile->fixer->beginChangeset();
					for ( $i = ( $scopeCloser - 1 ); $i > $lastContent; $i-- ) {
						if ( $tokens[ $i ]['line'] === $tokens[ $scopeCloser ]['line'] ) {
							continue;
						}

						if ( $tokens[ $i ]['line'] === $tokens[ $lastContent ]['line'] ) {
							break;
						}

						$phpcsFile->fixer->replaceToken( $i, '' );
					}

					$phpcsFile->fixer->endChangeset();
				}
			} else {
				$phpcsFile->recordMetric( $stackPtr, 'Blank lines at end of control structure', 0 );
			}//end if
		}//end if

		if ( $tokens[ $stackPtr ]['code'] === T_MATCH ) {
			// Move the scope closer to the semicolon/comma.
			$next = $phpcsFile->findNext( Tokens::$emptyTokens, ( $scopeCloser + 1 ), null, true );
			if ( $next !== false
				&& ( $tokens[ $next ]['code'] === T_SEMICOLON || $tokens[ $next ]['code'] === T_COMMA )
			) {
				$scopeCloser = $next;
			}
		}

		$trailingContent = $phpcsFile->findNext(
			T_WHITESPACE,
			( $scopeCloser + 1 ),
			null,
			true
		);

		if ( $tokens[ $trailingContent ]['code'] === T_COMMENT
			|| isset( Tokens::$phpcsCommentTokens[ $tokens[ $trailingContent ]['code'] ] ) === true
		) {
			// Special exception for code where the comment about
			// an ELSE or ELSEIF is written between the control structures.
			$nextCode = $phpcsFile->findNext(
				Tokens::$emptyTokens,
				( $scopeCloser + 1 ),
				null,
				true
			);

			if ( $tokens[ $nextCode ]['code'] === T_ELSE
				|| $tokens[ $nextCode ]['code'] === T_ELSEIF
				|| $tokens[ $trailingContent ]['line'] === $tokens[ $scopeCloser ]['line']
			) {
				$trailingContent = $nextCode;
			}
		}//end if

		if ( $tokens[ $trailingContent ]['code'] === T_ELSE ) {
			if ( $tokens[ $stackPtr ]['code'] === T_IF ) {
				// IF with ELSE.
				return;
			}
		}

		if ( $tokens[ $trailingContent ]['code'] === T_WHILE
			&& $tokens[ $stackPtr ]['code'] === T_DO
		) {
			// DO with WHILE.
			return;
		}

		if ( $tokens[ $trailingContent ]['code'] === T_CLOSE_TAG ) {
			// At the end of the script or embedded code.
			return;
		}

		if ( isset( $tokens[ $trailingContent ]['scope_condition'] ) === true
			&& $tokens[ $trailingContent ]['scope_condition'] !== $trailingContent
			&& isset( $tokens[ $trailingContent ]['scope_opener'] ) === true
			&& $tokens[ $trailingContent ]['scope_opener'] !== $trailingContent
		) {
			// Another control structure's closing brace.
			$owner = $tokens[ $trailingContent ]['scope_condition'];
			if ( $tokens[ $owner ]['code'] === T_FUNCTION ) {
				// The next content is the closing brace of a function
				// so normal function rules apply and we can ignore it.
				return;
			}

			if ( $tokens[ $owner ]['code'] === T_CLOSURE
				&& ( $phpcsFile->hasCondition( $stackPtr, array( T_FUNCTION, T_CLOSURE ) ) === true
				|| isset( $tokens[ $stackPtr ]['nested_parenthesis'] ) === true )
			) {
				return;
			}

			if ( $tokens[ $trailingContent ]['line'] !== ( $tokens[ $scopeCloser ]['line'] + 1 ) ) {
				$error = 'Blank line found after control structure';
				$fix   = $phpcsFile->addFixableError( $error, $scopeCloser, 'LineAfterClose' );

				if ( $fix === true ) {
					$phpcsFile->fixer->beginChangeset();
					$i = ( $scopeCloser + 1 );
					while ( $tokens[ $i ]['line'] !== $tokens[ $trailingContent ]['line'] ) {
						$phpcsFile->fixer->replaceToken( $i, '' );
						++$i;
					}

					$phpcsFile->fixer->addNewline( $scopeCloser );
					$phpcsFile->fixer->endChangeset();
				}
			}
		} elseif ( $tokens[ $trailingContent ]['code'] !== T_ELSE
			&& $tokens[ $trailingContent ]['code'] !== T_ELSEIF
			&& $tokens[ $trailingContent ]['code'] !== T_CATCH
			&& $tokens[ $trailingContent ]['code'] !== T_FINALLY
			&& $tokens[ $trailingContent ]['line'] === ( $tokens[ $scopeCloser ]['line'] + 1 )
		) {
			$error = 'No blank line found after control structure';
			$fix   = $phpcsFile->addFixableError( $error, $scopeCloser, 'NoLineAfterClose' );
			if ( $fix === true ) {
				$trailingContent = $phpcsFile->findNext(
					T_WHITESPACE,
					( $scopeCloser + 1 ),
					null,
					true
				);

				if ( ( $tokens[ $trailingContent ]['code'] === T_COMMENT
					|| isset( Tokens::$phpcsCommentTokens[ $tokens[ $trailingContent ]['code'] ] ) === true )
					&& $tokens[ $trailingContent ]['line'] === $tokens[ $scopeCloser ]['line']
				) {
					$phpcsFile->fixer->addNewline( $trailingContent );
				} else {
					$phpcsFile->fixer->addNewline( $scopeCloser );
				}
			}
		}//end if
	}//end process()
}//end class
