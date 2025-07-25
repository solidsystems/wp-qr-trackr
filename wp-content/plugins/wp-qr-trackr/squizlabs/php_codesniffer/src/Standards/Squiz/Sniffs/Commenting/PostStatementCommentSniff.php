<?php
/**
 * Checks to ensure that there are no comments after statements.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Squiz\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

class PostStatementCommentSniff implements Sniff {


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
	 * Exceptions to the rule.
	 *
	 * If post statement comments are found within the condition
	 * parenthesis of these structures, leave them alone.
	 *
	 * @var array
	 */
	private $controlStructureExceptions = array(
		T_IF      => true,
		T_ELSEIF  => true,
		T_SWITCH  => true,
		T_WHILE   => true,
		T_FOR     => true,
		T_FOREACH => true,
		T_MATCH   => true,
	);


	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array<int|string>
	 */
	public function register() {
		return array( T_COMMENT );
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

		if ( substr( $tokens[ $stackPtr ]['content'], 0, 2 ) !== '//' ) {
			return;
		}

		$commentLine = $tokens[ $stackPtr ]['line'];
		$lastContent = $phpcsFile->findPrevious( T_WHITESPACE, ( $stackPtr - 1 ), null, true );

		if ( $lastContent === false
			|| $tokens[ $lastContent ]['line'] !== $commentLine
			|| $tokens[ $stackPtr ]['column'] === 1
		) {
			return;
		}

		if ( $tokens[ $lastContent ]['code'] === T_CLOSE_CURLY_BRACKET ) {
			return;
		}

		// Special case for JS files and PHP closures.
		if ( $tokens[ $lastContent ]['code'] === T_COMMA
			|| $tokens[ $lastContent ]['code'] === T_SEMICOLON
		) {
			$lastContent = $phpcsFile->findPrevious( T_WHITESPACE, ( $lastContent - 1 ), null, true );
			if ( $lastContent === false || $tokens[ $lastContent ]['code'] === T_CLOSE_CURLY_BRACKET ) {
				return;
			}
		}

		// Special case for (trailing) comments within multi-line control structures.
		if ( isset( $tokens[ $stackPtr ]['nested_parenthesis'] ) === true ) {
			$nestedParens = $tokens[ $stackPtr ]['nested_parenthesis'];
			foreach ( $nestedParens as $open => $close ) {
				if ( isset( $tokens[ $open ]['parenthesis_owner'] ) === true
					&& isset( $this->controlStructureExceptions[ $tokens[ $tokens[ $open ]['parenthesis_owner'] ]['code'] ] ) === true
				) {
					return;
				}
			}
		}

		if ( $phpcsFile->tokenizerType === 'PHP'
			&& preg_match( '|^//[ \t]*@[^\s]+|', $tokens[ $stackPtr ]['content'] ) === 1
		) {
			$error = 'Annotations may not appear after statements';
			$phpcsFile->addError( $error, $stackPtr, 'AnnotationFound' );
			return;
		}

		$error = 'Comments may not appear after statements';
		$fix   = $phpcsFile->addFixableError( $error, $stackPtr, 'Found' );
		if ( $fix === true ) {
			$phpcsFile->fixer->addNewlineBefore( $stackPtr );
		}
	}//end process()
}//end class
