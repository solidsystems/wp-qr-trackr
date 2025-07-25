<?php
/**
 * Checks for unneeded whitespace.
 *
 * Checks that no whitespace precedes the first content of the file, exists
 * after the last content of the file, resides after content on any line, or
 * are two empty lines in functions.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Squiz\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

class SuperfluousWhitespaceSniff implements Sniff {


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
	 * If TRUE, whitespace rules are not checked for blank lines.
	 *
	 * Blank lines are those that contain only whitespace.
	 *
	 * @var boolean
	 */
	public $ignoreBlankLines = false;


	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array<int|string>
	 */
	public function register() {
		return array(
			T_OPEN_TAG,
			T_OPEN_TAG_WITH_ECHO,
			T_CLOSE_TAG,
			T_WHITESPACE,
			T_COMMENT,
			T_DOC_COMMENT_WHITESPACE,
			T_CLOSURE,
		);
	}//end register()


	/**
	 * Processes this sniff, when one of its tokens is encountered.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
	 * @param int                         $stackPtr  The position of the current token in the
	 *                                               stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process( File $phpcsFile, $stackPtr ) {
		$tokens = $phpcsFile->getTokens();

		if ( $tokens[ $stackPtr ]['code'] === T_OPEN_TAG ) {
			/*
				Check for start of file whitespace.
			*/

			if ( $phpcsFile->tokenizerType !== 'PHP' ) {
				// The first token is always the open tag inserted when tokenized
				// and the second token is always the first piece of content in
				// the file. If the second token is whitespace, there was
				// whitespace at the start of the file.
				if ( $tokens[ ( $stackPtr + 1 ) ]['code'] !== T_WHITESPACE ) {
					return;
				}

				if ( $phpcsFile->fixer->enabled === true ) {
					$stackPtr = $phpcsFile->findNext( T_WHITESPACE, ( $stackPtr + 1 ), null, true );
				}
			} else {
				// If it's the first token, then there is no space.
				if ( $stackPtr === 0 ) {
					return;
				}

				$beforeOpen = '';

				for ( $i = ( $stackPtr - 1 ); $i >= 0; $i-- ) {
					// If we find something that isn't inline html then there is something previous in the file.
					if ( $tokens[ $i ]['type'] !== 'T_INLINE_HTML' ) {
						return;
					}

					$beforeOpen .= $tokens[ $i ]['content'];
				}

				// If we have ended up with inline html make sure it isn't just whitespace.
				if ( preg_match( '`^[\pZ\s]+$`u', $beforeOpen ) !== 1 ) {
					return;
				}
			}//end if

			$fix = $phpcsFile->addFixableError( 'Additional whitespace found at start of file', $stackPtr, 'StartFile' );
			if ( $fix === true ) {
				$phpcsFile->fixer->beginChangeset();
				for ( $i = 0; $i < $stackPtr; $i++ ) {
					$phpcsFile->fixer->replaceToken( $i, '' );
				}

				$phpcsFile->fixer->endChangeset();
			}
		} elseif ( $tokens[ $stackPtr ]['code'] === T_CLOSE_TAG ) {
			/*
				Check for end of file whitespace.
			*/

			if ( $phpcsFile->tokenizerType === 'PHP' ) {
				if ( isset( $tokens[ ( $stackPtr + 1 ) ] ) === false ) {
					// The close PHP token is the last in the file.
					return;
				}

				$afterClose = '';

				for ( $i = ( $stackPtr + 1 ); $i < $phpcsFile->numTokens; $i++ ) {
					// If we find something that isn't inline HTML then there
					// is more to the file.
					if ( $tokens[ $i ]['type'] !== 'T_INLINE_HTML' ) {
						return;
					}

					$afterClose .= $tokens[ $i ]['content'];
				}

				// If we have ended up with inline html make sure it isn't just whitespace.
				if ( preg_match( '`^[\pZ\s]+$`u', $afterClose ) !== 1 ) {
					return;
				}
			} else {
				// The last token is always the close tag inserted when tokenized
				// and the second last token is always the last piece of content in
				// the file. If the second last token is whitespace, there was
				// whitespace at the end of the file.
				--$stackPtr;

				// The pointer is now looking at the last content in the file and
				// not the fake PHP end tag the tokenizer inserted.
				if ( $tokens[ $stackPtr ]['code'] !== T_WHITESPACE ) {
					return;
				}

				// Allow a single newline at the end of the last line in the file.
				if ( $tokens[ ( $stackPtr - 1 ) ]['code'] !== T_WHITESPACE
					&& $tokens[ $stackPtr ]['content'] === $phpcsFile->eolChar
				) {
					return;
				}
			}//end if

			$fix = $phpcsFile->addFixableError( 'Additional whitespace found at end of file', $stackPtr, 'EndFile' );
			if ( $fix === true ) {
				if ( $phpcsFile->tokenizerType !== 'PHP' ) {
					$prev     = $phpcsFile->findPrevious( T_WHITESPACE, ( $stackPtr - 1 ), null, true );
					$stackPtr = ( $prev + 1 );
				}

				$phpcsFile->fixer->beginChangeset();
				for ( $i = ( $stackPtr + 1 ); $i < $phpcsFile->numTokens; $i++ ) {
					$phpcsFile->fixer->replaceToken( $i, '' );
				}

				$phpcsFile->fixer->endChangeset();
			}
		} else {
			/*
				Check for end of line whitespace.
			*/

			// Ignore whitespace that is not at the end of a line.
			if ( isset( $tokens[ ( $stackPtr + 1 ) ]['line'] ) === true
				&& $tokens[ ( $stackPtr + 1 ) ]['line'] === $tokens[ $stackPtr ]['line']
			) {
				return;
			}

			// Ignore blank lines if required.
			if ( $this->ignoreBlankLines === true
				&& $tokens[ $stackPtr ]['code'] === T_WHITESPACE
				&& $tokens[ ( $stackPtr - 1 ) ]['line'] !== $tokens[ $stackPtr ]['line']
			) {
				return;
			}

			$tokenContent = rtrim( $tokens[ $stackPtr ]['content'], $phpcsFile->eolChar );
			if ( empty( $tokenContent ) === false ) {
				if ( $tokenContent !== rtrim( $tokenContent ) ) {
					$fix = $phpcsFile->addFixableError( 'Whitespace found at end of line', $stackPtr, 'EndLine' );
					if ( $fix === true ) {
						$phpcsFile->fixer->replaceToken( $stackPtr, rtrim( $tokenContent ) . $phpcsFile->eolChar );
					}
				}
			} elseif ( $tokens[ ( $stackPtr - 1 ) ]['content'] !== rtrim( $tokens[ ( $stackPtr - 1 ) ]['content'] )
				&& $tokens[ ( $stackPtr - 1 ) ]['line'] === $tokens[ $stackPtr ]['line']
			) {
				$fix = $phpcsFile->addFixableError( 'Whitespace found at end of line', ( $stackPtr - 1 ), 'EndLine' );
				if ( $fix === true ) {
					$phpcsFile->fixer->replaceToken( ( $stackPtr - 1 ), rtrim( $tokens[ ( $stackPtr - 1 ) ]['content'] ) );
				}
			}

			/*
				Check for multiple blank lines in a function.
			*/

			if ( ( $phpcsFile->hasCondition( $stackPtr, array( T_FUNCTION, T_CLOSURE ) ) === true )
				&& $tokens[ ( $stackPtr - 1 ) ]['line'] < $tokens[ $stackPtr ]['line']
				&& $tokens[ ( $stackPtr - 2 ) ]['line'] === $tokens[ ( $stackPtr - 1 ) ]['line']
			) {
				// Properties and functions in nested classes have their own rules for spacing.
				$conditions   = $tokens[ $stackPtr ]['conditions'];
				$deepestScope = end( $conditions );
				if ( $deepestScope === T_ANON_CLASS ) {
					return;
				}

				// This is an empty line and the line before this one is not
				// empty, so this could be the start of a multiple empty
				// line block.
				$next  = $phpcsFile->findNext( T_WHITESPACE, $stackPtr, null, true );
				$lines = ( $tokens[ $next ]['line'] - $tokens[ $stackPtr ]['line'] );
				if ( $lines > 1 ) {
					$error = 'Functions must not contain multiple empty lines in a row; found %s empty lines';
					$fix   = $phpcsFile->addFixableError( $error, $stackPtr, 'EmptyLines', array( $lines ) );
					if ( $fix === true ) {
						$phpcsFile->fixer->beginChangeset();
						$i = $stackPtr;
						while ( $tokens[ $i ]['line'] !== $tokens[ $next ]['line'] ) {
							$phpcsFile->fixer->replaceToken( $i, '' );
							++$i;
						}

						$phpcsFile->fixer->addNewlineBefore( $i );
						$phpcsFile->fixer->endChangeset();
					}
				}
			}//end if
		}//end if
	}//end process()
}//end class
