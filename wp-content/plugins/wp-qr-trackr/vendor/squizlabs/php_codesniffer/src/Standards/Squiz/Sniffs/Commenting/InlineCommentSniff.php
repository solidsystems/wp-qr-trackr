<?php
/**
 * Checks that there is adequate spacing between comments.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Squiz\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

class InlineCommentSniff implements Sniff {


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
			T_COMMENT,
			T_DOC_COMMENT_OPEN_TAG,
		);
	}//end register()


	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
	 * @param int                         $stackPtr  The position of the current token in the
	 *                                               stack passed in $tokens.
	 *
	 * @return void|int
	 */
	public function process( File $phpcsFile, $stackPtr ) {
		$tokens = $phpcsFile->getTokens();

		// If this is a function/class/interface doc block comment, skip it.
		// We are only interested in inline doc block comments, which are
		// not allowed.
		if ( $tokens[ $stackPtr ]['code'] === T_DOC_COMMENT_OPEN_TAG ) {
			$nextToken = $stackPtr;
			do {
				$nextToken = $phpcsFile->findNext( Tokens::$emptyTokens, ( $nextToken + 1 ), null, true );
				if ( $tokens[ $nextToken ]['code'] === T_ATTRIBUTE ) {
					$nextToken = $tokens[ $nextToken ]['attribute_closer'];
					continue;
				}

				break;
			} while ( true );

			$ignore = array(
				T_CLASS,
				T_INTERFACE,
				T_TRAIT,
				T_ENUM,
				T_FUNCTION,
				T_CLOSURE,
				T_PUBLIC,
				T_PRIVATE,
				T_PROTECTED,
				T_FINAL,
				T_STATIC,
				T_ABSTRACT,
				T_READONLY,
				T_CONST,
				T_PROPERTY,
				T_INCLUDE,
				T_INCLUDE_ONCE,
				T_REQUIRE,
				T_REQUIRE_ONCE,
			);

			if ( in_array( $tokens[ $nextToken ]['code'], $ignore, true ) === true ) {
				return;
			}

			if ( $phpcsFile->tokenizerType === 'JS' ) {
				// We allow block comments if a function or object
				// is being assigned to a variable.
				$ignore    = Tokens::$emptyTokens;
				$ignore[]  = T_EQUAL;
				$ignore[]  = T_STRING;
				$ignore[]  = T_OBJECT_OPERATOR;
				$nextToken = $phpcsFile->findNext( $ignore, ( $nextToken + 1 ), null, true );
				if ( $tokens[ $nextToken ]['code'] === T_FUNCTION
					|| $tokens[ $nextToken ]['code'] === T_CLOSURE
					|| $tokens[ $nextToken ]['code'] === T_OBJECT
					|| $tokens[ $nextToken ]['code'] === T_PROTOTYPE
				) {
					return;
				}
			}

			$prevToken = $phpcsFile->findPrevious(
				Tokens::$emptyTokens,
				( $stackPtr - 1 ),
				null,
				true
			);

			if ( $tokens[ $prevToken ]['code'] === T_OPEN_TAG ) {
				return;
			}

			if ( $tokens[ $stackPtr ]['content'] === '/**' ) {
				$error = 'Inline doc block comments are not allowed; use "/* Comment */" or "// Comment" instead';
				$phpcsFile->addError( $error, $stackPtr, 'DocBlock' );
			}
		}//end if

		if ( $tokens[ $stackPtr ]['content'][0] === '#' ) {
			$error = 'Perl-style comments are not allowed; use "// Comment" instead';
			$fix   = $phpcsFile->addFixableError( $error, $stackPtr, 'WrongStyle' );
			if ( $fix === true ) {
				$comment = ltrim( $tokens[ $stackPtr ]['content'], "# \t" );
				$phpcsFile->fixer->replaceToken( $stackPtr, "// $comment" );
			}
		}

		// We don't want end of block comments. Check if the last token before the
		// comment is a closing curly brace.
		$previousContent = $phpcsFile->findPrevious( T_WHITESPACE, ( $stackPtr - 1 ), null, true );
		if ( $tokens[ $previousContent ]['line'] === $tokens[ $stackPtr ]['line'] ) {
			if ( $tokens[ $previousContent ]['code'] === T_CLOSE_CURLY_BRACKET ) {
				return;
			}

			// Special case for JS files.
			if ( $tokens[ $previousContent ]['code'] === T_COMMA
				|| $tokens[ $previousContent ]['code'] === T_SEMICOLON
			) {
				$lastContent = $phpcsFile->findPrevious( T_WHITESPACE, ( $previousContent - 1 ), null, true );
				if ( $tokens[ $lastContent ]['code'] === T_CLOSE_CURLY_BRACKET ) {
					return;
				}
			}
		}

		// Only want inline comments.
		if ( substr( $tokens[ $stackPtr ]['content'], 0, 2 ) !== '//' ) {
			return;
		}

		$commentTokens = array( $stackPtr );

		$nextComment = $stackPtr;
		$lastComment = $stackPtr;
		while ( ( $nextComment = $phpcsFile->findNext( T_COMMENT, ( $nextComment + 1 ), null, false ) ) !== false ) {
			if ( $tokens[ $nextComment ]['line'] !== ( $tokens[ $lastComment ]['line'] + 1 ) ) {
				break;
			}

			// Only want inline comments.
			if ( substr( $tokens[ $nextComment ]['content'], 0, 2 ) !== '//' ) {
				break;
			}

			// There is a comment on the very next line. If there is
			// no code between the comments, they are part of the same
			// comment block.
			$prevNonWhitespace = $phpcsFile->findPrevious( T_WHITESPACE, ( $nextComment - 1 ), $lastComment, true );
			if ( $prevNonWhitespace !== $lastComment ) {
				break;
			}

			$commentTokens[] = $nextComment;
			$lastComment     = $nextComment;
		}//end while

		$commentText = '';
		foreach ( $commentTokens as $lastCommentToken ) {
			$comment = rtrim( $tokens[ $lastCommentToken ]['content'] );

			if ( trim( substr( $comment, 2 ) ) === '' ) {
				continue;
			}

			$spaceCount = 0;
			$tabFound   = false;

			$commentLength = strlen( $comment );
			for ( $i = 2; $i < $commentLength; $i++ ) {
				if ( $comment[ $i ] === "\t" ) {
					$tabFound = true;
					break;
				}

				if ( $comment[ $i ] !== ' ' ) {
					break;
				}

				++$spaceCount;
			}

			$fix = false;
			if ( $tabFound === true ) {
				$error = 'Tab found before comment text; expected "// %s" but found "%s"';
				$data  = array(
					ltrim( substr( $comment, 2 ) ),
					$comment,
				);
				$fix   = $phpcsFile->addFixableError( $error, $lastCommentToken, 'TabBefore', $data );
			} elseif ( $spaceCount === 0 ) {
				$error = 'No space found before comment text; expected "// %s" but found "%s"';
				$data  = array(
					substr( $comment, 2 ),
					$comment,
				);
				$fix   = $phpcsFile->addFixableError( $error, $lastCommentToken, 'NoSpaceBefore', $data );
			} elseif ( $spaceCount > 1 ) {
				$error = 'Expected 1 space before comment text but found %s; use block comment if you need indentation';
				$data  = array(
					$spaceCount,
					substr( $comment, ( 2 + $spaceCount ) ),
					$comment,
				);
				$fix   = $phpcsFile->addFixableError( $error, $lastCommentToken, 'SpacingBefore', $data );
			}//end if

			if ( $fix === true ) {
				$newComment = '// ' . ltrim( $tokens[ $lastCommentToken ]['content'], "/\t " );
				$phpcsFile->fixer->replaceToken( $lastCommentToken, $newComment );
			}

			$commentText .= trim( substr( $tokens[ $lastCommentToken ]['content'], 2 ) );
		}//end foreach

		if ( $commentText === '' ) {
			$error = 'Blank comments are not allowed';
			$fix   = $phpcsFile->addFixableError( $error, $stackPtr, 'Empty' );
			if ( $fix === true ) {
				$phpcsFile->fixer->replaceToken( $stackPtr, '' );
			}

			return ( $lastCommentToken + 1 );
		}

		if ( preg_match( '/^\p{Ll}/u', $commentText ) === 1 ) {
			$error = 'Inline comments must start with a capital letter';
			$phpcsFile->addError( $error, $stackPtr, 'NotCapital' );
		}

		// Only check the end of comment character if the start of the comment
		// is a letter, indicating that the comment is just standard text.
		if ( preg_match( '/^\p{L}/u', $commentText ) === 1 ) {
			$commentCloser   = $commentText[ ( strlen( $commentText ) - 1 ) ];
			$acceptedClosers = array(
				'full-stops'        => '.',
				'exclamation marks' => '!',
				'or question marks' => '?',
			);

			if ( in_array( $commentCloser, $acceptedClosers, true ) === false ) {
				$error = 'Inline comments must end in %s';
				$ender = '';
				foreach ( $acceptedClosers as $closerName => $symbol ) {
					$ender .= ' ' . $closerName . ',';
				}

				$ender = trim( $ender, ' ,' );
				$data  = array( $ender );
				$phpcsFile->addError( $error, $lastCommentToken, 'InvalidEndChar', $data );
			}
		}

		// Finally, the line below the last comment cannot be empty if this inline
		// comment is on a line by itself.
		if ( $tokens[ $previousContent ]['line'] < $tokens[ $stackPtr ]['line'] ) {
			$next = $phpcsFile->findNext( T_WHITESPACE, ( $lastCommentToken + 1 ), null, true );
			if ( $next === false ) {
				// Ignore if the comment is the last non-whitespace token in a file.
				return ( $lastCommentToken + 1 );
			}

			if ( $tokens[ $next ]['code'] === T_DOC_COMMENT_OPEN_TAG ) {
				// If this inline comment is followed by a docblock,
				// ignore spacing as docblock/function etc spacing rules
				// are likely to conflict with our rules.
				return ( $lastCommentToken + 1 );
			}

			$errorCode = 'SpacingAfter';

			if ( isset( $tokens[ $stackPtr ]['conditions'] ) === true ) {
				$conditions   = $tokens[ $stackPtr ]['conditions'];
				$type         = end( $conditions );
				$conditionPtr = key( $conditions );

				if ( ( $type === T_FUNCTION || $type === T_CLOSURE )
					&& $tokens[ $conditionPtr ]['scope_closer'] === $next
				) {
					$errorCode = 'SpacingAfterAtFunctionEnd';
				}
			}

			for ( $i = ( $lastCommentToken + 1 ); $i < $phpcsFile->numTokens; $i++ ) {
				if ( $tokens[ $i ]['line'] === ( $tokens[ $lastCommentToken ]['line'] + 1 ) ) {
					if ( $tokens[ $i ]['code'] !== T_WHITESPACE ) {
						return ( $lastCommentToken + 1 );
					}
				} elseif ( $tokens[ $i ]['line'] > ( $tokens[ $lastCommentToken ]['line'] + 1 ) ) {
					break;
				}
			}

			$error = 'There must be no blank line following an inline comment';
			$fix   = $phpcsFile->addFixableError( $error, $lastCommentToken, $errorCode );
			if ( $fix === true ) {
				$phpcsFile->fixer->beginChangeset();
				for ( $i = ( $lastCommentToken + 1 ); $i < $next; $i++ ) {
					if ( $tokens[ $i ]['line'] === $tokens[ $next ]['line'] ) {
						break;
					}

					$phpcsFile->fixer->replaceToken( $i, '' );
				}

				$phpcsFile->fixer->endChangeset();
			}
		}//end if

		return ( $lastCommentToken + 1 );
	}//end process()
}//end class
