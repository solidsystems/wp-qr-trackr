<?php
/**
 * Checks for unused function parameters.
 *
 * This sniff checks that all function parameters are used in the function body.
 * One exception is made for empty function bodies or function bodies that only
 * contain comments. This could be useful for the classes that implement an
 * interface that defines multiple methods but the implementation only needs some
 * of them.
 *
 * @author    Manuel Pichler <mapi@manuel-pichler.de>
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2007-2014 Manuel Pichler. All rights reserved.
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Generic\Sniffs\CodeAnalysis;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

class UnusedFunctionParameterSniff implements Sniff {


	/**
	 * The list of class type hints which will be ignored.
	 *
	 * @var array
	 */
	public $ignoreTypeHints = array();

	/**
	 * A list of all PHP magic methods with fixed method signatures.
	 *
	 * Note: `__construct()` and `__invoke()` are excluded on purpose
	 * as their method signature is not fixed.
	 *
	 * @var array
	 */
	private $magicMethods = array(
		'__destruct'    => true,
		'__call'        => true,
		'__callstatic'  => true,
		'__get'         => true,
		'__set'         => true,
		'__isset'       => true,
		'__unset'       => true,
		'__sleep'       => true,
		'__wakeup'      => true,
		'__serialize'   => true,
		'__unserialize' => true,
		'__tostring'    => true,
		'__set_state'   => true,
		'__clone'       => true,
		'__debuginfo'   => true,
	);


	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array<int|string>
	 */
	public function register() {
		return array(
			T_FUNCTION,
			T_CLOSURE,
			T_FN,
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
		$tokens = $phpcsFile->getTokens();
		$token  = $tokens[ $stackPtr ];

		// Skip broken function declarations.
		if ( isset( $token['scope_opener'] ) === false || isset( $token['parenthesis_opener'] ) === false ) {
			return;
		}

		$errorCode  = 'Found';
		$implements = false;

		if ( $token['code'] === T_FUNCTION ) {
			$classPtr = $phpcsFile->getCondition( $stackPtr, T_CLASS );
			if ( $classPtr !== false ) {
				// Check for magic methods and ignore these as the method signature cannot be changed.
				$methodName = $phpcsFile->getDeclarationName( $stackPtr );
				if ( empty( $methodName ) === false ) {
					$methodNameLc = strtolower( $methodName );
					if ( isset( $this->magicMethods[ $methodNameLc ] ) === true ) {
						return;
					}
				}

				// Check for extends/implements and adjust the error code when found.
				$implements = $phpcsFile->findImplementedInterfaceNames( $classPtr );
				$extends    = $phpcsFile->findExtendedClassName( $classPtr );
				if ( $extends !== false ) {
					$errorCode .= 'InExtendedClass';
				} elseif ( $implements !== false ) {
					$errorCode .= 'InImplementedInterface';
				}
			}
		}//end if

		$params       = array();
		$methodParams = $phpcsFile->getMethodParameters( $stackPtr );

		// Skip when no parameters found.
		$methodParamsCount = count( $methodParams );
		if ( $methodParamsCount === 0 ) {
			return;
		}

		foreach ( $methodParams as $param ) {
			if ( isset( $param['property_visibility'] ) === true ) {
				// Ignore constructor property promotion.
				continue;
			}

			$params[ $param['name'] ] = $stackPtr;
		}

		$next = ++$token['scope_opener'];
		$end  = --$token['scope_closer'];

		// Check the end token for arrow functions as
		// they can end at a content token due to not having
		// a clearly defined closing token.
		if ( $token['code'] === T_FN ) {
			++$end;
		}

		$foundContent = false;
		$validTokens  = array(
			T_HEREDOC              => T_HEREDOC,
			T_NOWDOC               => T_NOWDOC,
			T_END_HEREDOC          => T_END_HEREDOC,
			T_END_NOWDOC           => T_END_NOWDOC,
			T_DOUBLE_QUOTED_STRING => T_DOUBLE_QUOTED_STRING,
		);
		$validTokens += Tokens::$emptyTokens;

		for ( ; $next <= $end; ++$next ) {
			$token = $tokens[ $next ];
			$code  = $token['code'];

			// Ignorable tokens.
			if ( isset( Tokens::$emptyTokens[ $code ] ) === true ) {
				continue;
			}

			if ( $foundContent === false ) {
				// A throw statement as the first content indicates an interface method.
				if ( $code === T_THROW && $implements !== false ) {
					return;
				}

				// A return statement as the first content indicates an interface method.
				if ( $code === T_RETURN ) {
					$firstNonEmptyTokenAfterReturn = $phpcsFile->findNext( Tokens::$emptyTokens, ( $next + 1 ), null, true );
					if ( $tokens[ $firstNonEmptyTokenAfterReturn ]['code'] === T_SEMICOLON && $implements !== false ) {
						return;
					}

					$secondNonEmptyTokenAfterReturn = $phpcsFile->findNext(
						Tokens::$emptyTokens,
						( $firstNonEmptyTokenAfterReturn + 1 ),
						null,
						true
					);

					if ( $secondNonEmptyTokenAfterReturn !== false
						&& $tokens[ $secondNonEmptyTokenAfterReturn ]['code'] === T_SEMICOLON
						&& $implements !== false
					) {
						// There is a return <token>.
						return;
					}
				}//end if
			}//end if

			$foundContent = true;

			if ( $code === T_VARIABLE && isset( $params[ $token['content'] ] ) === true ) {
				unset( $params[ $token['content'] ] );
			} elseif ( $code === T_DOLLAR ) {
				$nextToken = $phpcsFile->findNext( T_WHITESPACE, ( $next + 1 ), null, true );
				if ( $tokens[ $nextToken ]['code'] === T_OPEN_CURLY_BRACKET ) {
					$nextToken = $phpcsFile->findNext( T_WHITESPACE, ( $nextToken + 1 ), null, true );
					if ( $tokens[ $nextToken ]['code'] === T_STRING ) {
						$varContent = '$' . $tokens[ $nextToken ]['content'];
						if ( isset( $params[ $varContent ] ) === true ) {
							unset( $params[ $varContent ] );
						}
					}
				}
			} elseif ( $code === T_DOUBLE_QUOTED_STRING
				|| $code === T_START_HEREDOC
				|| $code === T_START_NOWDOC
			) {
				// Tokenize strings that can contain variables.
				// Make sure the string is re-joined if it occurs over multiple lines.
				$content = $token['content'];
				for ( $i = ( $next + 1 ); $i <= $end; $i++ ) {
					if ( isset( $validTokens[ $tokens[ $i ]['code'] ] ) === true ) {
						$content .= $tokens[ $i ]['content'];
						++$next;
					} else {
						break;
					}
				}

				$stringTokens = token_get_all( sprintf( '<?php %s;?>', $content ) );
				foreach ( $stringTokens as $stringPtr => $stringToken ) {
					if ( is_array( $stringToken ) === false ) {
						continue;
					}

					$varContent = '';
					if ( $stringToken[0] === T_DOLLAR_OPEN_CURLY_BRACES ) {
						$varContent = '$' . $stringTokens[ ( $stringPtr + 1 ) ][1];
					} elseif ( $stringToken[0] === T_VARIABLE ) {
						$varContent = $stringToken[1];
					}

					if ( $varContent !== '' && isset( $params[ $varContent ] ) === true ) {
						unset( $params[ $varContent ] );
					}
				}
			}//end if
		}//end for

		if ( $foundContent === true && count( $params ) > 0 ) {
			$error = 'The method parameter %s is never used';

			// If there is only one parameter and it is unused, no need for additional errorcode toggling logic.
			if ( $methodParamsCount === 1 ) {
				foreach ( $params as $paramName => $position ) {
					if ( in_array( $methodParams[0]['type_hint'], $this->ignoreTypeHints, true ) === true ) {
						continue;
					}

					$data = array( $paramName );
					$phpcsFile->addWarning( $error, $position, $errorCode, $data );
				}

				return;
			}

			$foundLastUsed = false;
			$lastIndex     = ( $methodParamsCount - 1 );
			$errorInfo     = array();
			for ( $i = $lastIndex; $i >= 0; --$i ) {
				if ( $foundLastUsed !== false ) {
					if ( isset( $params[ $methodParams[ $i ]['name'] ] ) === true ) {
						$errorInfo[ $methodParams[ $i ]['name'] ] = array(
							'position'  => $params[ $methodParams[ $i ]['name'] ],
							'errorcode' => $errorCode . 'BeforeLastUsed',
							'typehint'  => $methodParams[ $i ]['type_hint'],
						);
					}
				} elseif ( isset( $params[ $methodParams[ $i ]['name'] ] ) === false ) {
						$foundLastUsed = true;
				} else {
					$errorInfo[ $methodParams[ $i ]['name'] ] = array(
						'position'  => $params[ $methodParams[ $i ]['name'] ],
						'errorcode' => $errorCode . 'AfterLastUsed',
						'typehint'  => $methodParams[ $i ]['type_hint'],
					);
				}
			}//end for

			if ( count( $errorInfo ) > 0 ) {
				$errorInfo = array_reverse( $errorInfo );
				foreach ( $errorInfo as $paramName => $info ) {
					if ( in_array( $info['typehint'], $this->ignoreTypeHints, true ) === true ) {
						continue;
					}

					$data = array( $paramName );
					$phpcsFile->addWarning( $error, $info['position'], $info['errorcode'], $data );
				}
			}
		}//end if
	}//end process()
}//end class
