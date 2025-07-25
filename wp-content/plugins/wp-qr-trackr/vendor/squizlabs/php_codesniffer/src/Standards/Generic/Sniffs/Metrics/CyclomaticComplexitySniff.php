<?php
/**
 * Checks the cyclomatic complexity (McCabe) for functions.
 *
 * The cyclomatic complexity (also called McCabe code metrics)
 * indicates the complexity within a function by counting
 * the different paths the function includes.
 *
 * @author    Johann-Peter Hartmann <hartmann@mayflower.de>
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2007-2014 Mayflower GmbH
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Generic\Sniffs\Metrics;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

class CyclomaticComplexitySniff implements Sniff {


	/**
	 * A complexity higher than this value will throw a warning.
	 *
	 * @var integer
	 */
	public $complexity = 10;

	/**
	 * A complexity higher than this value will throw an error.
	 *
	 * @var integer
	 */
	public $absoluteComplexity = 20;


	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array<int|string>
	 */
	public function register() {
		return array( T_FUNCTION );
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

		// Ignore abstract and interface methods. Bail early when live coding.
		if ( isset( $tokens[ $stackPtr ]['scope_opener'], $tokens[ $stackPtr ]['scope_closer'] ) === false ) {
			return;
		}

		// Detect start and end of this function definition.
		$start = $tokens[ $stackPtr ]['scope_opener'];
		$end   = $tokens[ $stackPtr ]['scope_closer'];

		// Predicate nodes for PHP.
		$find = array(
			T_CASE                     => true,
			T_DEFAULT                  => true,
			T_CATCH                    => true,
			T_IF                       => true,
			T_FOR                      => true,
			T_FOREACH                  => true,
			T_WHILE                    => true,
			T_ELSEIF                   => true,
			T_INLINE_THEN              => true,
			T_COALESCE                 => true,
			T_COALESCE_EQUAL           => true,
			T_MATCH_ARROW              => true,
			T_NULLSAFE_OBJECT_OPERATOR => true,
		);

		$complexity = 1;

		// Iterate from start to end and count predicate nodes.
		for ( $i = ( $start + 1 ); $i < $end; $i++ ) {
			if ( isset( $find[ $tokens[ $i ]['code'] ] ) === true ) {
				++$complexity;
			}
		}

		if ( $complexity > $this->absoluteComplexity ) {
			$error = 'Function\'s cyclomatic complexity (%s) exceeds allowed maximum of %s';
			$data  = array(
				$complexity,
				$this->absoluteComplexity,
			);
			$phpcsFile->addError( $error, $stackPtr, 'MaxExceeded', $data );
		} elseif ( $complexity > $this->complexity ) {
			$warning = 'Function\'s cyclomatic complexity (%s) exceeds %s; consider refactoring the function';
			$data    = array(
				$complexity,
				$this->complexity,
			);
			$phpcsFile->addWarning( $warning, $stackPtr, 'TooHigh', $data );
		}
	}//end process()
}//end class
