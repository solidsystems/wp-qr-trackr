<?php
/**
 * Allows tests that extend this class to listen for tokens within a particular scope.
 *
 * Below is a test that listens to methods that exist only within classes:
 * <code>
 * class ClassScopeTest extends PHP_CodeSniffer_Standards_AbstractScopeSniff
 * {
 *     public function __construct()
 *     {
 *         parent::__construct(array(T_CLASS), array(T_FUNCTION));
 *     }
 *
 *     protected function processTokenWithinScope(\PHP_CodeSniffer\Files\File $phpcsFile, $stackPtr, $currScope)
 *     {
 *         $className = $phpcsFile->getDeclarationName($currScope);
 *         $phpcsFile->addWarning('encountered a method within class '.$className, $stackPtr, 'MethodFound');
 *     }
 * }
 * </code>
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Sniffs;

use PHP_CodeSniffer\Exceptions\RuntimeException;
use PHP_CodeSniffer\Files\File;

abstract class AbstractScopeSniff implements Sniff {


	/**
	 * The token types that this test wishes to listen to within the scope.
	 *
	 * @var array
	 */
	private $tokens = array();

	/**
	 * The type of scope opener tokens that this test wishes to listen to.
	 *
	 * @var array<int|string>
	 */
	private $scopeTokens = array();

	/**
	 * True if this test should fire on tokens outside of the scope.
	 *
	 * @var boolean
	 */
	private $listenOutside = false;


	/**
	 * Constructs a new AbstractScopeTest.
	 *
	 * @param array   $scopeTokens   The type of scope the test wishes to listen to.
	 * @param array   $tokens        The tokens that the test wishes to listen to
	 *                               within the scope.
	 * @param boolean $listenOutside If true this test will also alert the
	 *                               extending class when a token is found outside
	 *                               the scope, by calling the
	 *                               processTokenOutsideScope method.
	 *
	 * @throws \PHP_CodeSniffer\Exceptions\RuntimeException If the specified tokens arrays are empty
	 *                                                      or invalid.
	 */
	public function __construct(
		array $scopeTokens,
		array $tokens,
		$listenOutside = false
	) {
		if ( empty( $scopeTokens ) === true ) {
			$error = 'The scope tokens list cannot be empty';
			throw new RuntimeException( $error );
		}

		if ( empty( $tokens ) === true ) {
			$error = 'The tokens list cannot be empty';
			throw new RuntimeException( $error );
		}

		$invalidScopeTokens = array_intersect( $scopeTokens, $tokens );
		if ( empty( $invalidScopeTokens ) === false ) {
			$invalid = implode( ', ', $invalidScopeTokens );
			$error   = "Scope tokens [$invalid] can't be in the tokens array";
			throw new RuntimeException( $error );
		}

		$this->listenOutside = $listenOutside;
		$this->scopeTokens   = array_flip( $scopeTokens );
		$this->tokens        = $tokens;
	}//end __construct()


	/**
	 * The method that is called to register the tokens this test wishes to
	 * listen to.
	 *
	 * DO NOT OVERRIDE THIS METHOD. Use the constructor of this class to register
	 * for the desired tokens and scope.
	 *
	 * @return array<int|string>
	 * @see    __constructor()
	 */
	final public function register() {
		return $this->tokens;
	}//end register()


	/**
	 * Processes the tokens that this test is listening for.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The file where this token was found.
	 * @param int                         $stackPtr  The position in the stack where this
	 *                                               token was found.
	 *
	 * @return void|int Optionally returns a stack pointer. The sniff will not be
	 *                  called again on the current file until the returned stack
	 *                  pointer is reached. Return `$phpcsFile->numTokens` to skip
	 *                  the rest of the file.
	 * @see    processTokenWithinScope()
	 */
	final public function process( File $phpcsFile, $stackPtr ) {
		$tokens = $phpcsFile->getTokens();

		$foundScope = false;
		$skipTokens = array();
		foreach ( $tokens[ $stackPtr ]['conditions'] as $scope => $code ) {
			if ( isset( $this->scopeTokens[ $code ] ) === true ) {
				$skipTokens[] = $this->processTokenWithinScope( $phpcsFile, $stackPtr, $scope );
				$foundScope   = true;
			}
		}

		if ( $this->listenOutside === true && $foundScope === false ) {
			$skipTokens[] = $this->processTokenOutsideScope( $phpcsFile, $stackPtr );
		}

		if ( empty( $skipTokens ) === false ) {
			return min( $skipTokens );
		}
	}//end process()


	/**
	 * Processes a token that is found within the scope that this test is
	 * listening to.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The file where this token was found.
	 * @param int                         $stackPtr  The position in the stack where this
	 *                                               token was found.
	 * @param int                         $currScope The position in the tokens array that
	 *                                               opened the scope that this test is
	 *                                               listening for.
	 *
	 * @return void|int Optionally returns a stack pointer. The sniff will not be
	 *                  called again on the current file until the returned stack
	 *                  pointer is reached. Return `$phpcsFile->numTokens` to skip
	 *                  the rest of the file.
	 */
	abstract protected function processTokenWithinScope( File $phpcsFile, $stackPtr, $currScope );


	/**
	 * Processes a token that is found outside the scope that this test is
	 * listening to.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The file where this token was found.
	 * @param int                         $stackPtr  The position in the stack where this
	 *                                               token was found.
	 *
	 * @return void|int Optionally returns a stack pointer. The sniff will not be
	 *                  called again on the current file until the returned stack
	 *                  pointer is reached. Return `$phpcsFile->numTokens` to skip
	 *                  the rest of the file.
	 */
	abstract protected function processTokenOutsideScope( File $phpcsFile, $stackPtr );
}//end class
