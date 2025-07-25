<?php
/**
 * Checks the declaration of the class and its inheritance is correct.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Squiz\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\PSR2\Sniffs\Classes\ClassDeclarationSniff as PSR2ClassDeclarationSniff;
use PHP_CodeSniffer\Util\Tokens;

class ClassDeclarationSniff extends PSR2ClassDeclarationSniff {



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
		// We want all the errors from the PSR2 standard, plus some of our own.
		parent::process( $phpcsFile, $stackPtr );

		// Check that this is the only class or interface in the file.
		$nextClass = $phpcsFile->findNext( array( T_CLASS, T_INTERFACE ), ( $stackPtr + 1 ) );
		if ( $nextClass !== false ) {
			// We have another, so an error is thrown.
			$error = 'Only one interface or class is allowed in a file';
			$phpcsFile->addError( $error, $nextClass, 'MultipleClasses' );
		}
	}//end process()


	/**
	 * Processes the opening section of a class declaration.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
	 * @param int                         $stackPtr  The position of the current token
	 *                                               in the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function processOpen( File $phpcsFile, $stackPtr ) {
		parent::processOpen( $phpcsFile, $stackPtr );

		$tokens = $phpcsFile->getTokens();

		if ( $tokens[ ( $stackPtr - 1 ) ]['code'] === T_WHITESPACE ) {
			$prevContent = $tokens[ ( $stackPtr - 1 ) ]['content'];
			if ( $prevContent !== $phpcsFile->eolChar ) {
				$blankSpace = substr( $prevContent, strpos( $prevContent, $phpcsFile->eolChar ) );
				$spaces     = strlen( $blankSpace );

				if ( $tokens[ ( $stackPtr - 2 ) ]['code'] !== T_ABSTRACT
					&& $tokens[ ( $stackPtr - 2 ) ]['code'] !== T_FINAL
					&& $tokens[ ( $stackPtr - 2 ) ]['code'] !== T_READONLY
				) {
					if ( $spaces !== 0 ) {
						$type  = strtolower( $tokens[ $stackPtr ]['content'] );
						$error = 'Expected 0 spaces before %s keyword; %s found';
						$data  = array(
							$type,
							$spaces,
						);

						$fix = $phpcsFile->addFixableError( $error, $stackPtr, 'SpaceBeforeKeyword', $data );
						if ( $fix === true ) {
							$phpcsFile->fixer->replaceToken( ( $stackPtr - 1 ), '' );
						}
					}
				}
			}//end if
		}//end if
	}//end processOpen()


	/**
	 * Processes the closing section of a class declaration.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
	 * @param int                         $stackPtr  The position of the current token
	 *                                               in the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function processClose( File $phpcsFile, $stackPtr ) {
		$tokens = $phpcsFile->getTokens();
		if ( isset( $tokens[ $stackPtr ]['scope_closer'] ) === false ) {
			return;
		}

		$closeBrace = $tokens[ $stackPtr ]['scope_closer'];

		// Check that the closing brace has one blank line after it.
		for ( $nextContent = ( $closeBrace + 1 ); $nextContent < $phpcsFile->numTokens; $nextContent++ ) {
			// Ignore comments on the same line as the brace.
			if ( $tokens[ $nextContent ]['line'] === $tokens[ $closeBrace ]['line']
				&& ( $tokens[ $nextContent ]['code'] === T_WHITESPACE
				|| $tokens[ $nextContent ]['code'] === T_COMMENT
				|| isset( Tokens::$phpcsCommentTokens[ $tokens[ $nextContent ]['code'] ] ) === true )
			) {
				continue;
			}

			if ( $tokens[ $nextContent ]['code'] !== T_WHITESPACE ) {
				break;
			}
		}

		if ( $nextContent === $phpcsFile->numTokens ) {
			// Ignore the line check as this is the very end of the file.
			$difference = 1;
		} else {
			$difference = ( $tokens[ $nextContent ]['line'] - $tokens[ $closeBrace ]['line'] - 1 );
		}

		$lastContent = $phpcsFile->findPrevious( T_WHITESPACE, ( $closeBrace - 1 ), $stackPtr, true );

		if ( $difference === -1
			|| $tokens[ $lastContent ]['line'] === $tokens[ $closeBrace ]['line']
		) {
			$error = 'Closing %s brace must be on a line by itself';
			$data  = array( $tokens[ $stackPtr ]['content'] );
			$fix   = $phpcsFile->addFixableError( $error, $closeBrace, 'CloseBraceSameLine', $data );
			if ( $fix === true ) {
				if ( $difference === -1 ) {
					$phpcsFile->fixer->addNewlineBefore( $nextContent );
				}

				if ( $tokens[ $lastContent ]['line'] === $tokens[ $closeBrace ]['line'] ) {
					$phpcsFile->fixer->addNewlineBefore( $closeBrace );
				}
			}
		} elseif ( $tokens[ ( $closeBrace - 1 ) ]['code'] === T_WHITESPACE ) {
			$prevContent = $tokens[ ( $closeBrace - 1 ) ]['content'];
			if ( $prevContent !== $phpcsFile->eolChar ) {
				$blankSpace = substr( $prevContent, strpos( $prevContent, $phpcsFile->eolChar ) );
				$spaces     = strlen( $blankSpace );
				if ( $spaces !== 0 ) {
					if ( $tokens[ ( $closeBrace - 1 ) ]['line'] !== $tokens[ $closeBrace ]['line'] ) {
						$error = 'Expected 0 spaces before closing brace; newline found';
						$phpcsFile->addError( $error, $closeBrace, 'NewLineBeforeCloseBrace' );
					} else {
						$error = 'Expected 0 spaces before closing brace; %s found';
						$data  = array( $spaces );
						$fix   = $phpcsFile->addFixableError( $error, $closeBrace, 'SpaceBeforeCloseBrace', $data );
						if ( $fix === true ) {
							$phpcsFile->fixer->replaceToken( ( $closeBrace - 1 ), '' );
						}
					}
				}
			}
		}//end if

		if ( $difference !== -1 && $difference !== 1 ) {
			for ( $nextSignificant = $nextContent; $nextSignificant < $phpcsFile->numTokens; $nextSignificant++ ) {
				if ( $tokens[ $nextSignificant ]['code'] === T_WHITESPACE ) {
					continue;
				}

				if ( $tokens[ $nextSignificant ]['code'] === T_DOC_COMMENT_OPEN_TAG ) {
					$nextSignificant = $tokens[ $nextSignificant ]['comment_closer'];
					continue;
				}

				if ( $tokens[ $nextSignificant ]['code'] === T_ATTRIBUTE
					&& isset( $tokens[ $nextSignificant ]['attribute_closer'] ) === true
				) {
					$nextSignificant = $tokens[ $nextSignificant ]['attribute_closer'];
					continue;
				}

				break;
			}

			if ( $tokens[ $nextSignificant ]['code'] === T_FUNCTION ) {
				return;
			}

			$error = 'Closing brace of a %s must be followed by a single blank line; found %s';
			$data  = array(
				$tokens[ $stackPtr ]['content'],
				$difference,
			);
			$fix   = $phpcsFile->addFixableError( $error, $closeBrace, 'NewlinesAfterCloseBrace', $data );
			if ( $fix === true ) {
				if ( $difference === 0 ) {
					$first = $phpcsFile->findFirstOnLine( array(), $nextContent, true );
					$phpcsFile->fixer->addNewlineBefore( $first );
				} else {
					$phpcsFile->fixer->beginChangeset();
					for ( $i = ( $closeBrace + 1 ); $i < $nextContent; $i++ ) {
						if ( $tokens[ $i ]['line'] <= ( $tokens[ $closeBrace ]['line'] + 1 ) ) {
							continue;
						} elseif ( $tokens[ $i ]['line'] === $tokens[ $nextContent ]['line'] ) {
							break;
						}

						$phpcsFile->fixer->replaceToken( $i, '' );
					}

					$phpcsFile->fixer->endChangeset();
				}
			}
		}//end if
	}//end processClose()
}//end class
