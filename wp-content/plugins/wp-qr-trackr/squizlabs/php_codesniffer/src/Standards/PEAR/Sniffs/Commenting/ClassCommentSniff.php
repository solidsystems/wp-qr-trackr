<?php
/**
 * Parses and verifies the doc comments for classes.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\PEAR\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;

class ClassCommentSniff extends FileCommentSniff {



	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array<int|string>
	 */
	public function register() {
		return array(
			T_CLASS,
			T_INTERFACE,
			T_TRAIT,
			T_ENUM,
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
		$tokens    = $phpcsFile->getTokens();
		$type      = strtolower( $tokens[ $stackPtr ]['content'] );
		$errorData = array( $type );

		$find = array(
			T_ABSTRACT   => T_ABSTRACT,
			T_FINAL      => T_FINAL,
			T_READONLY   => T_READONLY,
			T_WHITESPACE => T_WHITESPACE,
		);

		for ( $commentEnd = ( $stackPtr - 1 ); $commentEnd >= 0; $commentEnd-- ) {
			if ( isset( $find[ $tokens[ $commentEnd ]['code'] ] ) === true ) {
				continue;
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
			$errorData[] = $phpcsFile->getDeclarationName( $stackPtr );
			$phpcsFile->addError( 'Missing doc comment for %s %s', $stackPtr, 'Missing', $errorData );
			$phpcsFile->recordMetric( $stackPtr, 'Class has doc comment', 'no' );
			return;
		}

		$phpcsFile->recordMetric( $stackPtr, 'Class has doc comment', 'yes' );

		if ( $tokens[ $commentEnd ]['code'] === T_COMMENT ) {
			$phpcsFile->addError( 'You must use "/**" style comments for a %s comment', $stackPtr, 'WrongStyle', $errorData );
			return;
		}

		// Check each tag.
		$this->processTags( $phpcsFile, $stackPtr, $tokens[ $commentEnd ]['comment_opener'] );
	}//end process()


	/**
	 * Process the version tag.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
	 * @param array                       $tags      The tokens for these tags.
	 *
	 * @return void
	 */
	protected function processVersion( $phpcsFile, array $tags ) {
		$tokens = $phpcsFile->getTokens();
		foreach ( $tags as $tag ) {
			if ( $tokens[ ( $tag + 2 ) ]['code'] !== T_DOC_COMMENT_STRING ) {
				// No content.
				continue;
			}

			$content = $tokens[ ( $tag + 2 ) ]['content'];
			if ( ( strstr( $content, 'Release:' ) === false ) ) {
				$error = 'Invalid version "%s" in doc comment; consider "Release: <package_version>" instead';
				$data  = array( $content );
				$phpcsFile->addWarning( $error, $tag, 'InvalidVersion', $data );
			}
		}
	}//end processVersion()
}//end class
