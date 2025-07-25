<?php
/**
 * Verifies that operators have valid spacing surrounding them.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Squiz\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

class OperatorSpacingSniff implements Sniff {


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
	 * Allow newlines instead of spaces.
	 *
	 * @var boolean
	 */
	public $ignoreNewlines = false;

	/**
	 * Don't check spacing for assignment operators.
	 *
	 * This allows multiple assignment statements to be aligned.
	 *
	 * @var boolean
	 */
	public $ignoreSpacingBeforeAssignments = true;

	/**
	 * A list of tokens that aren't considered as operands.
	 *
	 * @var string[]
	 */
	private $nonOperandTokens = array();


	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array<int|string>
	 */
	public function register() {
		/*
			First we setup an array of all the tokens that can come before
			a T_MINUS or T_PLUS token to indicate that the token is not being
			used as an operator.
		*/

		// Trying to operate on a negative value; eg. ($var * -1).
		$this->nonOperandTokens = Tokens::$operators;

		// Trying to compare a negative value; eg. ($var === -1).
		$this->nonOperandTokens += Tokens::$comparisonTokens;

		// Trying to compare a negative value; eg. ($var || -1 === $b).
		$this->nonOperandTokens += Tokens::$booleanOperators;

		// Trying to assign a negative value; eg. ($var = -1).
		$this->nonOperandTokens += Tokens::$assignmentTokens;

		// Returning/printing a negative value; eg. (return -1).
		$this->nonOperandTokens += array(
			T_RETURN      => T_RETURN,
			T_ECHO        => T_ECHO,
			T_EXIT        => T_EXIT,
			T_PRINT       => T_PRINT,
			T_YIELD       => T_YIELD,
			T_FN_ARROW    => T_FN_ARROW,
			T_MATCH_ARROW => T_MATCH_ARROW,
		);

		// Trying to use a negative value; eg. myFunction($var, -2).
		$this->nonOperandTokens += array(
			T_CASE                => T_CASE,
			T_COLON               => T_COLON,
			T_COMMA               => T_COMMA,
			T_INLINE_ELSE         => T_INLINE_ELSE,
			T_INLINE_THEN         => T_INLINE_THEN,
			T_OPEN_CURLY_BRACKET  => T_OPEN_CURLY_BRACKET,
			T_OPEN_PARENTHESIS    => T_OPEN_PARENTHESIS,
			T_OPEN_SHORT_ARRAY    => T_OPEN_SHORT_ARRAY,
			T_OPEN_SQUARE_BRACKET => T_OPEN_SQUARE_BRACKET,
			T_STRING_CONCAT       => T_STRING_CONCAT,
		);

		// Casting a negative value; eg. (array) -$a.
		$this->nonOperandTokens += Tokens::$castTokens;

		/*
			These are the tokens the sniff is looking for.
		*/

		$targets   = Tokens::$comparisonTokens;
		$targets  += Tokens::$operators;
		$targets  += Tokens::$assignmentTokens;
		$targets[] = T_INLINE_THEN;
		$targets[] = T_INLINE_ELSE;
		$targets[] = T_INSTANCEOF;

		// Also register the contexts we want to specifically skip over.
		$targets[] = T_DECLARE;

		return $targets;
	}//end register()


	/**
	 * Processes this sniff, when one of its tokens is encountered.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The current file being checked.
	 * @param int                         $stackPtr  The position of the current token in
	 *                                               the stack passed in $tokens.
	 *
	 * @return void|int Optionally returns a stack pointer. The sniff will not be
	 *                  called again on the current file until the returned stack
	 *                  pointer is reached. Return `$phpcsFile->numTokens` to skip
	 *                  the rest of the file.
	 */
	public function process( File $phpcsFile, $stackPtr ) {
		$tokens = $phpcsFile->getTokens();

		// Skip over declare statements as those should be handled by different sniffs.
		if ( $tokens[ $stackPtr ]['code'] === T_DECLARE ) {
			if ( isset( $tokens[ $stackPtr ]['parenthesis_closer'] ) === false ) {
				// Parse error / live coding.
				return $phpcsFile->numTokens;
			}

			return $tokens[ $stackPtr ]['parenthesis_closer'];
		}

		if ( $this->isOperator( $phpcsFile, $stackPtr ) === false ) {
			return;
		}

		if ( $tokens[ $stackPtr ]['code'] === T_BITWISE_AND ) {
			// Check there is one space before the & operator.
			if ( $tokens[ ( $stackPtr - 1 ) ]['code'] !== T_WHITESPACE ) {
				$error = 'Expected 1 space before "&" operator; 0 found';
				$fix   = $phpcsFile->addFixableError( $error, $stackPtr, 'NoSpaceBeforeAmp' );
				if ( $fix === true ) {
					$phpcsFile->fixer->addContentBefore( $stackPtr, ' ' );
				}

				$phpcsFile->recordMetric( $stackPtr, 'Space before operator', 0 );
			} else {
				if ( $tokens[ ( $stackPtr - 2 ) ]['line'] !== $tokens[ $stackPtr ]['line'] ) {
					$found = 'newline';
				} else {
					$found = $tokens[ ( $stackPtr - 1 ) ]['length'];
				}

				$phpcsFile->recordMetric( $stackPtr, 'Space before operator', $found );
				if ( $found !== 1
					&& ( $found !== 'newline' || $this->ignoreNewlines === false )
				) {
					$error = 'Expected 1 space before "&" operator; %s found';
					$data  = array( $found );
					$fix   = $phpcsFile->addFixableError( $error, $stackPtr, 'SpacingBeforeAmp', $data );
					if ( $fix === true ) {
						$phpcsFile->fixer->replaceToken( ( $stackPtr - 1 ), ' ' );
					}
				}
			}//end if

			$hasNext = $phpcsFile->findNext( T_WHITESPACE, ( $stackPtr + 1 ), null, true );
			if ( $hasNext === false ) {
				// Live coding/parse error at end of file.
				return;
			}

			// Check there is one space after the & operator.
			if ( $tokens[ ( $stackPtr + 1 ) ]['code'] !== T_WHITESPACE ) {
				$error = 'Expected 1 space after "&" operator; 0 found';
				$fix   = $phpcsFile->addFixableError( $error, $stackPtr, 'NoSpaceAfterAmp' );
				if ( $fix === true ) {
					$phpcsFile->fixer->addContent( $stackPtr, ' ' );
				}

				$phpcsFile->recordMetric( $stackPtr, 'Space after operator', 0 );
			} else {
				if ( $tokens[ ( $stackPtr + 2 ) ]['line'] !== $tokens[ $stackPtr ]['line'] ) {
					$found = 'newline';
				} else {
					$found = $tokens[ ( $stackPtr + 1 ) ]['length'];
				}

				$phpcsFile->recordMetric( $stackPtr, 'Space after operator', $found );
				if ( $found !== 1
					&& ( $found !== 'newline' || $this->ignoreNewlines === false )
				) {
					$error = 'Expected 1 space after "&" operator; %s found';
					$data  = array( $found );
					$fix   = $phpcsFile->addFixableError( $error, $stackPtr, 'SpacingAfterAmp', $data );
					if ( $fix === true ) {
						$phpcsFile->fixer->replaceToken( ( $stackPtr + 1 ), ' ' );
					}
				}
			}//end if

			return;
		}//end if

		$operator = $tokens[ $stackPtr ]['content'];

		if ( $tokens[ ( $stackPtr - 1 ) ]['code'] !== T_WHITESPACE
			&& ( ( $tokens[ ( $stackPtr - 1 ) ]['code'] === T_INLINE_THEN
			&& $tokens[ ( $stackPtr ) ]['code'] === T_INLINE_ELSE ) === false )
		) {
			$error = "Expected 1 space before \"$operator\"; 0 found";
			$fix   = $phpcsFile->addFixableError( $error, $stackPtr, 'NoSpaceBefore' );
			if ( $fix === true ) {
				$phpcsFile->fixer->addContentBefore( $stackPtr, ' ' );
			}

			$phpcsFile->recordMetric( $stackPtr, 'Space before operator', 0 );
		} elseif ( isset( Tokens::$assignmentTokens[ $tokens[ $stackPtr ]['code'] ] ) === false
			|| $this->ignoreSpacingBeforeAssignments === false
		) {
			// Throw an error for assignments only if enabled using the sniff property
			// because other standards allow multiple spaces to align assignments.
			$prevNonWhitespace = $phpcsFile->findPrevious( T_WHITESPACE, ( $stackPtr - 1 ), null, true );
			if ( $tokens[ $prevNonWhitespace ]['line'] !== $tokens[ $stackPtr ]['line'] ) {
				$found = 'newline';
			} else {
				$found = $tokens[ ( $stackPtr - 1 ) ]['length'];
			}

			$phpcsFile->recordMetric( $stackPtr, 'Space before operator', $found );
			if ( $found !== 1
				&& ( $found !== 'newline' || $this->ignoreNewlines === false )
			) {
				$error = 'Expected 1 space before "%s"; %s found';
				$data  = array(
					$operator,
					$found,
				);

				if ( isset( Tokens::$commentTokens[ $tokens[ $prevNonWhitespace ]['code'] ] ) === true ) {
					// Throw a non-fixable error if the token on the previous line is a comment token,
					// as in that case it's not for the sniff to decide where the comment should be moved to
					// and it would get us into unfixable situations as the new line char is included
					// in the contents of the comment token.
					$phpcsFile->addError( $error, $stackPtr, 'SpacingBefore', $data );
				} else {
					$fix = $phpcsFile->addFixableError( $error, $stackPtr, 'SpacingBefore', $data );
					if ( $fix === true ) {
						$phpcsFile->fixer->beginChangeset();
						if ( $found === 'newline' ) {
							$i = ( $stackPtr - 2 );
							while ( $tokens[ $i ]['code'] === T_WHITESPACE ) {
								$phpcsFile->fixer->replaceToken( $i, '' );
								--$i;
							}
						}

						$phpcsFile->fixer->replaceToken( ( $stackPtr - 1 ), ' ' );
						$phpcsFile->fixer->endChangeset();
					}
				}//end if
			}//end if
		}//end if

		$hasNext = $phpcsFile->findNext( T_WHITESPACE, ( $stackPtr + 1 ), null, true );
		if ( $hasNext === false ) {
			// Live coding/parse error at end of file.
			return;
		}

		if ( $tokens[ ( $stackPtr + 1 ) ]['code'] !== T_WHITESPACE ) {
			// Skip short ternary such as: "$foo = $bar ?: true;".
			if ( ( $tokens[ $stackPtr ]['code'] === T_INLINE_THEN
				&& $tokens[ ( $stackPtr + 1 ) ]['code'] === T_INLINE_ELSE )
			) {
				return;
			}

			$error = "Expected 1 space after \"$operator\"; 0 found";
			$fix   = $phpcsFile->addFixableError( $error, $stackPtr, 'NoSpaceAfter' );
			if ( $fix === true ) {
				$phpcsFile->fixer->addContent( $stackPtr, ' ' );
			}

			$phpcsFile->recordMetric( $stackPtr, 'Space after operator', 0 );
		} else {
			if ( isset( $tokens[ ( $stackPtr + 2 ) ] ) === true
				&& $tokens[ ( $stackPtr + 2 ) ]['line'] !== $tokens[ $stackPtr ]['line']
			) {
				$found = 'newline';
			} else {
				$found = $tokens[ ( $stackPtr + 1 ) ]['length'];
			}

			$phpcsFile->recordMetric( $stackPtr, 'Space after operator', $found );
			if ( $found !== 1
				&& ( $found !== 'newline' || $this->ignoreNewlines === false )
			) {
				$error = 'Expected 1 space after "%s"; %s found';
				$data  = array(
					$operator,
					$found,
				);

				$nextNonWhitespace = $phpcsFile->findNext( T_WHITESPACE, ( $stackPtr + 1 ), null, true );
				if ( $nextNonWhitespace !== false
					&& isset( Tokens::$commentTokens[ $tokens[ $nextNonWhitespace ]['code'] ] ) === true
					&& $found === 'newline'
				) {
					// Don't auto-fix when it's a comment or PHPCS annotation on a new line as
					// it causes fixer conflicts and can cause the meaning of annotations to change.
					$phpcsFile->addError( $error, $stackPtr, 'SpacingAfter', $data );
				} else {
					$fix = $phpcsFile->addFixableError( $error, $stackPtr, 'SpacingAfter', $data );
					if ( $fix === true ) {
						$phpcsFile->fixer->replaceToken( ( $stackPtr + 1 ), ' ' );
					}
				}
			}//end if
		}//end if
	}//end process()


	/**
	 * Checks if an operator is actually a different type of token in the current context.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The current file being checked.
	 * @param int                         $stackPtr  The position of the operator in
	 *                                               the stack.
	 *
	 * @return boolean
	 */
	protected function isOperator( File $phpcsFile, $stackPtr ) {
		$tokens = $phpcsFile->getTokens();

		if ( $tokens[ $stackPtr ]['code'] === T_DECLARE ) {
			return false;
		}

		// Skip default values in function declarations.
		// Skip declare statements.
		if ( $tokens[ $stackPtr ]['code'] === T_EQUAL ) {
			if ( isset( $tokens[ $stackPtr ]['nested_parenthesis'] ) === true ) {
				$parenthesis = array_keys( $tokens[ $stackPtr ]['nested_parenthesis'] );
				$bracket     = array_pop( $parenthesis );
				if ( isset( $tokens[ $bracket ]['parenthesis_owner'] ) === true ) {
					$function = $tokens[ $bracket ]['parenthesis_owner'];
					if ( $tokens[ $function ]['code'] === T_FUNCTION
						|| $tokens[ $function ]['code'] === T_CLOSURE
						|| $tokens[ $function ]['code'] === T_FN
					) {
						return false;
					}
				}
			}
		}

		if ( $tokens[ $stackPtr ]['code'] === T_EQUAL ) {
			// Skip for '=&' case.
			if ( isset( $tokens[ ( $stackPtr + 1 ) ] ) === true
				&& $tokens[ ( $stackPtr + 1 ) ]['code'] === T_BITWISE_AND
			) {
				return false;
			}
		}

		if ( $tokens[ $stackPtr ]['code'] === T_BITWISE_AND ) {
			// If it's not a reference, then we expect one space either side of the
			// bitwise operator.
			if ( $phpcsFile->isReference( $stackPtr ) === true ) {
				return false;
			}
		}

		if ( $tokens[ $stackPtr ]['code'] === T_MINUS || $tokens[ $stackPtr ]['code'] === T_PLUS ) {
			// Check minus spacing, but make sure we aren't just assigning
			// a minus value or returning one.
			$prev = $phpcsFile->findPrevious( Tokens::$emptyTokens, ( $stackPtr - 1 ), null, true );
			if ( isset( $this->nonOperandTokens[ $tokens[ $prev ]['code'] ] ) === true ) {
				return false;
			}
		}//end if

		return true;
	}//end isOperator()
}//end class
