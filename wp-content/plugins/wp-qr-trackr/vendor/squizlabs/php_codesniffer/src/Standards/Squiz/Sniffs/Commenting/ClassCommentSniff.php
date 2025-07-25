<?php
/**
 * Parses and verifies the class doc comment.
 *
 * Verifies that :
 * <ul>
 *  <li>A class doc comment exists.</li>
 *  <li>The comment uses the correct docblock style.</li>
 *  <li>There are no blank lines after the class comment.</li>
 *  <li>No tags are used in the docblock.</li>
 * </ul>
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Squiz\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

class ClassCommentSniff implements Sniff {



	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array<int|string>
	 */
	public function register() {
		return array( T_CLASS );
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
		$find   = array(
			T_ABSTRACT   => T_ABSTRACT,
			T_FINAL      => T_FINAL,
			T_READONLY   => T_READONLY,
			T_WHITESPACE => T_WHITESPACE,
		);

		$previousContent = null;
		for ( $commentEnd = ( $stackPtr - 1 ); $commentEnd >= 0; $commentEnd-- ) {
			if ( isset( $find[ $tokens[ $commentEnd ]['code'] ] ) === true ) {
				continue;
			}

			if ( $previousContent === null ) {
				$previousContent = $commentEnd;
			}

			if ( $tokens[ $commentEnd ]['code'] === T_ATTRIBUTE_END
				&& isset( $tokens[ $commentEnd ]['attribute_opener'] ) === true
			) {
				$commentEnd = $tokens[ $commentEnd ]['attribute_opener'];
				continue;
			}

			break;
		}

		if ( $tokens[ $commentEnd ]['code'] !== T_DOC_COMMENT_CLOSE_TAG
			&& $tokens[ $commentEnd ]['code'] !== T_COMMENT
		) {
			$class = $phpcsFile->getDeclarationName( $stackPtr );
			$phpcsFile->addError( 'Missing doc comment for class %s', $stackPtr, 'Missing', array( $class ) );
			$phpcsFile->recordMetric( $stackPtr, 'Class has doc comment', 'no' );
			return;
		}

		$phpcsFile->recordMetric( $stackPtr, 'Class has doc comment', 'yes' );

		if ( $tokens[ $commentEnd ]['code'] === T_COMMENT ) {
			$phpcsFile->addError( 'You must use "/**" style comments for a class comment', $stackPtr, 'WrongStyle' );
			return;
		}

		if ( $tokens[ $previousContent ]['line'] !== ( $tokens[ $stackPtr ]['line'] - 1 ) ) {
			$error = 'There must be no blank lines after the class comment';
			$phpcsFile->addError( $error, $commentEnd, 'SpacingAfter' );
		}

		$commentStart = $tokens[ $commentEnd ]['comment_opener'];
		foreach ( $tokens[ $commentStart ]['comment_tags'] as $tag ) {
			$error = '%s tag is not allowed in class comment';
			$data  = array( $tokens[ $tag ]['content'] );
			$phpcsFile->addWarning( $error, $tag, 'TagNotAllowed', $data );
		}
	}//end process()
}//end class
