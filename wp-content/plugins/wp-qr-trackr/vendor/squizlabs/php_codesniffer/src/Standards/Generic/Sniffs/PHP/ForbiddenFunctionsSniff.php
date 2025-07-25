<?php
/**
 * Discourages the use of alias functions.
 *
 * Alias functions are kept in PHP for compatibility
 * with older versions. Can be used to forbid the use of any function.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Generic\Sniffs\PHP;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

class ForbiddenFunctionsSniff implements Sniff {


	/**
	 * A list of forbidden functions with their alternatives.
	 *
	 * The value is NULL if no alternative exists. IE, the
	 * function should just not be used.
	 *
	 * @var array<string, string|null>
	 */
	public $forbiddenFunctions = array(
		'sizeof' => 'count',
		'delete' => 'unset',
	);

	/**
	 * A cache of forbidden function names, for faster lookups.
	 *
	 * @var string[]
	 */
	protected $forbiddenFunctionNames = array();

	/**
	 * If true, forbidden functions will be considered regular expressions.
	 *
	 * @var boolean
	 */
	protected $patternMatch = false;

	/**
	 * If true, an error will be thrown; otherwise a warning.
	 *
	 * @var boolean
	 */
	public $error = true;


	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array<int|string>
	 */
	public function register() {
		// Everyone has had a chance to figure out what forbidden functions
		// they want to check for, so now we can cache out the list.
		$this->forbiddenFunctionNames = array_keys( $this->forbiddenFunctions );

		if ( $this->patternMatch === true ) {
			foreach ( $this->forbiddenFunctionNames as $i => $name ) {
				$this->forbiddenFunctionNames[ $i ] = '/' . $name . '/i';
			}

			return array( T_STRING );
		}

		// If we are not pattern matching, we need to work out what
		// tokens to listen for.
		$hasHaltCompiler = false;
		$string          = '<?php ';
		foreach ( $this->forbiddenFunctionNames as $name ) {
			if ( $name === '__halt_compiler' ) {
				$hasHaltCompiler = true;
			} else {
				$string .= $name . '();';
			}
		}

		if ( $hasHaltCompiler === true ) {
			$string .= '__halt_compiler();';
		}

		$register = array();

		$tokens = token_get_all( $string );
		array_shift( $tokens );
		foreach ( $tokens as $token ) {
			if ( is_array( $token ) === true ) {
				$register[] = $token[0];
			}
		}

		$this->forbiddenFunctionNames = array_map( 'strtolower', $this->forbiddenFunctionNames );
		$this->forbiddenFunctions     = array_combine( $this->forbiddenFunctionNames, $this->forbiddenFunctions );

		return array_unique( $register );
	}//end register()


	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
	 * @param int                         $stackPtr  The position of the current token in
	 *                                               the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process( File $phpcsFile, $stackPtr ) {
		$tokens = $phpcsFile->getTokens();

		$ignore = array(
			T_DOUBLE_COLON             => true,
			T_OBJECT_OPERATOR          => true,
			T_NULLSAFE_OBJECT_OPERATOR => true,
			T_FUNCTION                 => true,
			T_CONST                    => true,
			T_PUBLIC                   => true,
			T_PRIVATE                  => true,
			T_PROTECTED                => true,
			T_AS                       => true,
			T_NEW                      => true,
			T_INSTEADOF                => true,
			T_NS_SEPARATOR             => true,
			T_IMPLEMENTS               => true,
		);

		$prevToken = $phpcsFile->findPrevious( Tokens::$emptyTokens, ( $stackPtr - 1 ), null, true );

		// If function call is directly preceded by a NS_SEPARATOR it points to the
		// global namespace, so we should still catch it.
		if ( $tokens[ $prevToken ]['code'] === T_NS_SEPARATOR ) {
			$prevToken = $phpcsFile->findPrevious( Tokens::$emptyTokens, ( $prevToken - 1 ), null, true );
			if ( $tokens[ $prevToken ]['code'] === T_STRING ) {
				// Not in the global namespace.
				return;
			}
		}

		if ( isset( $ignore[ $tokens[ $prevToken ]['code'] ] ) === true ) {
			// Not a call to a PHP function.
			return;
		}

		$nextToken = $phpcsFile->findNext( Tokens::$emptyTokens, ( $stackPtr + 1 ), null, true );
		if ( isset( $ignore[ $tokens[ $nextToken ]['code'] ] ) === true ) {
			// Not a call to a PHP function.
			return;
		}

		if ( $tokens[ $stackPtr ]['code'] === T_STRING && $tokens[ $nextToken ]['code'] !== T_OPEN_PARENTHESIS ) {
			// Not a call to a PHP function.
			return;
		}

		if ( empty( $tokens[ $stackPtr ]['nested_attributes'] ) === false ) {
			// Class instantiation in attribute, not function call.
			return;
		}

		$function = strtolower( $tokens[ $stackPtr ]['content'] );
		$pattern  = null;

		if ( $this->patternMatch === true ) {
			$count   = 0;
			$pattern = preg_replace(
				$this->forbiddenFunctionNames,
				$this->forbiddenFunctionNames,
				$function,
				1,
				$count
			);

			if ( $count === 0 ) {
				return;
			}

			// Remove the pattern delimiters and modifier.
			$pattern = substr( $pattern, 1, -2 );
		} elseif ( in_array( $function, $this->forbiddenFunctionNames, true ) === false ) {
				return;
		}//end if

		$this->addError( $phpcsFile, $stackPtr, $tokens[ $stackPtr ]['content'], $pattern );
	}//end process()


	/**
	 * Generates the error or warning for this sniff.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
	 * @param int                         $stackPtr  The position of the forbidden function
	 *                                               in the token array.
	 * @param string                      $function  The name of the forbidden function.
	 * @param string                      $pattern   The pattern used for the match.
	 *
	 * @return void
	 */
	protected function addError( $phpcsFile, $stackPtr, $function, $pattern = null ) {
		$data  = array( $function );
		$error = 'The use of function %s() is ';
		if ( $this->error === true ) {
			$type   = 'Found';
			$error .= 'forbidden';
		} else {
			$type   = 'Discouraged';
			$error .= 'discouraged';
		}

		if ( $pattern === null ) {
			$pattern = strtolower( $function );
		}

		if ( $this->forbiddenFunctions[ $pattern ] !== null
			&& $this->forbiddenFunctions[ $pattern ] !== 'null'
		) {
			$type  .= 'WithAlternative';
			$data[] = $this->forbiddenFunctions[ $pattern ];
			$error .= '; use %s() instead';
		}

		if ( $this->error === true ) {
			$phpcsFile->addError( $error, $stackPtr, $type, $data );
		} else {
			$phpcsFile->addWarning( $error, $stackPtr, $type, $data );
		}
	}//end addError()
}//end class
