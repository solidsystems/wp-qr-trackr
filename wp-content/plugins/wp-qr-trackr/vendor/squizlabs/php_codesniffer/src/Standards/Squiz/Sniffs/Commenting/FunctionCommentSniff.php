<?php
/**
 * Parses and verifies the doc comments for functions.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Squiz\Sniffs\Commenting;

use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\PEAR\Sniffs\Commenting\FunctionCommentSniff as PEARFunctionCommentSniff;
use PHP_CodeSniffer\Util\Common;

class FunctionCommentSniff extends PEARFunctionCommentSniff {


	/**
	 * Whether to skip inheritdoc comments.
	 *
	 * @var boolean
	 */
	public $skipIfInheritdoc = false;

	/**
	 * The current PHP version.
	 *
	 * @var integer|string|null
	 */
	private $phpVersion = null;


	/**
	 * Process the return comment of this function comment.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile    The file being scanned.
	 * @param int                         $stackPtr     The position of the current token
	 *                                                  in the stack passed in $tokens.
	 * @param int                         $commentStart The position in the stack where the comment started.
	 *
	 * @return void
	 */
	protected function processReturn( File $phpcsFile, $stackPtr, $commentStart ) {
		$tokens = $phpcsFile->getTokens();
		$return = null;

		if ( $this->skipIfInheritdoc === true ) {
			if ( $this->checkInheritdoc( $phpcsFile, $stackPtr, $commentStart ) === true ) {
				return;
			}
		}

		foreach ( $tokens[ $commentStart ]['comment_tags'] as $tag ) {
			if ( $tokens[ $tag ]['content'] === '@return' ) {
				if ( $return !== null ) {
					$error = 'Only 1 @return tag is allowed in a function comment';
					$phpcsFile->addError( $error, $tag, 'DuplicateReturn' );
					return;
				}

				$return = $tag;
			}
		}

		// Skip constructor and destructor.
		$methodName      = $phpcsFile->getDeclarationName( $stackPtr );
		$isSpecialMethod = in_array( $methodName, $this->specialMethods, true );

		if ( $return !== null ) {
			$content = $tokens[ ( $return + 2 ) ]['content'];
			if ( empty( $content ) === true || $tokens[ ( $return + 2 ) ]['code'] !== T_DOC_COMMENT_STRING ) {
				$error = 'Return type missing for @return tag in function comment';
				$phpcsFile->addError( $error, $return, 'MissingReturnType' );
			} else {
				// Support both a return type and a description.
				preg_match( '`^((?:\|?(?:array\([^\)]*\)|[\\\\a-z0-9\[\]]+))*)( .*)?`i', $content, $returnParts );
				if ( isset( $returnParts[1] ) === false ) {
					return;
				}

				$returnType = $returnParts[1];

				// Check return type (can be multiple, separated by '|').
				$typeNames      = explode( '|', $returnType );
				$suggestedNames = array();
				foreach ( $typeNames as $typeName ) {
					$suggestedName = Common::suggestType( $typeName );
					if ( in_array( $suggestedName, $suggestedNames, true ) === false ) {
						$suggestedNames[] = $suggestedName;
					}
				}

				$suggestedType = implode( '|', $suggestedNames );
				if ( $returnType !== $suggestedType ) {
					$error = 'Expected "%s" but found "%s" for function return type';
					$data  = array(
						$suggestedType,
						$returnType,
					);
					$fix   = $phpcsFile->addFixableError( $error, $return, 'InvalidReturn', $data );
					if ( $fix === true ) {
						$replacement = $suggestedType;
						if ( empty( $returnParts[2] ) === false ) {
							$replacement .= $returnParts[2];
						}

						$phpcsFile->fixer->replaceToken( ( $return + 2 ), $replacement );
						unset( $replacement );
					}
				}

				// If the return type is void, make sure there is
				// no return statement in the function.
				if ( $returnType === 'void' ) {
					if ( isset( $tokens[ $stackPtr ]['scope_closer'] ) === true ) {
						$endToken = $tokens[ $stackPtr ]['scope_closer'];
						for ( $returnToken = $stackPtr; $returnToken < $endToken; $returnToken++ ) {
							if ( $tokens[ $returnToken ]['code'] === T_CLOSURE
								|| $tokens[ $returnToken ]['code'] === T_ANON_CLASS
							) {
								$returnToken = $tokens[ $returnToken ]['scope_closer'];
								continue;
							}

							if ( $tokens[ $returnToken ]['code'] === T_RETURN
								|| $tokens[ $returnToken ]['code'] === T_YIELD
								|| $tokens[ $returnToken ]['code'] === T_YIELD_FROM
							) {
								break;
							}
						}

						if ( $returnToken !== $endToken ) {
							// If the function is not returning anything, just
							// exiting, then there is no problem.
							$semicolon = $phpcsFile->findNext( T_WHITESPACE, ( $returnToken + 1 ), null, true );
							if ( $tokens[ $semicolon ]['code'] !== T_SEMICOLON ) {
								$error = 'Function return type is void, but function contains return statement';
								$phpcsFile->addError( $error, $return, 'InvalidReturnVoid' );
							}
						}
					}//end if
				} elseif ( $returnType !== 'mixed'
					&& $returnType !== 'never'
					&& in_array( 'void', $typeNames, true ) === false
				) {
					// If return type is not void, never, or mixed, there needs to be a
					// return statement somewhere in the function that returns something.
					if ( isset( $tokens[ $stackPtr ]['scope_closer'] ) === true ) {
						$endToken = $tokens[ $stackPtr ]['scope_closer'];
						for ( $returnToken = $stackPtr; $returnToken < $endToken; $returnToken++ ) {
							if ( $tokens[ $returnToken ]['code'] === T_CLOSURE
								|| $tokens[ $returnToken ]['code'] === T_ANON_CLASS
							) {
								$returnToken = $tokens[ $returnToken ]['scope_closer'];
								continue;
							}

							if ( $tokens[ $returnToken ]['code'] === T_RETURN
								|| $tokens[ $returnToken ]['code'] === T_YIELD
								|| $tokens[ $returnToken ]['code'] === T_YIELD_FROM
							) {
								break;
							}
						}

						if ( $returnToken === $endToken ) {
							$error = 'Function return type is not void, but function has no return statement';
							$phpcsFile->addError( $error, $return, 'InvalidNoReturn' );
						} else {
							$semicolon = $phpcsFile->findNext( T_WHITESPACE, ( $returnToken + 1 ), null, true );
							if ( $tokens[ $semicolon ]['code'] === T_SEMICOLON ) {
								$error = 'Function return type is not void, but function is returning void here';
								$phpcsFile->addError( $error, $returnToken, 'InvalidReturnNotVoid' );
							}
						}
					}//end if
				}//end if
			}//end if
		} else {
			if ( $isSpecialMethod === true ) {
				return;
			}

			$error = 'Missing @return tag in function comment';
			$phpcsFile->addError( $error, $tokens[ $commentStart ]['comment_closer'], 'MissingReturn' );
		}//end if
	}//end processReturn()


	/**
	 * Process any throw tags that this function comment has.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile    The file being scanned.
	 * @param int                         $stackPtr     The position of the current token
	 *                                                  in the stack passed in $tokens.
	 * @param int                         $commentStart The position in the stack where the comment started.
	 *
	 * @return void
	 */
	protected function processThrows( File $phpcsFile, $stackPtr, $commentStart ) {
		$tokens = $phpcsFile->getTokens();

		if ( $this->skipIfInheritdoc === true ) {
			if ( $this->checkInheritdoc( $phpcsFile, $stackPtr, $commentStart ) === true ) {
				return;
			}
		}

		foreach ( $tokens[ $commentStart ]['comment_tags'] as $pos => $tag ) {
			if ( $tokens[ $tag ]['content'] !== '@throws' ) {
				continue;
			}

			$exception = null;
			$comment   = null;
			if ( $tokens[ ( $tag + 2 ) ]['code'] === T_DOC_COMMENT_STRING ) {
				$matches = array();
				preg_match( '/([^\s]+)(?:\s+(.*))?/', $tokens[ ( $tag + 2 ) ]['content'], $matches );
				$exception = $matches[1];
				if ( isset( $matches[2] ) === true && trim( $matches[2] ) !== '' ) {
					$comment = $matches[2];
				}
			}

			if ( $exception === null ) {
				$error = 'Exception type and comment missing for @throws tag in function comment';
				$phpcsFile->addError( $error, $tag, 'InvalidThrows' );
			} elseif ( $comment === null ) {
				$error = 'Comment missing for @throws tag in function comment';
				$phpcsFile->addError( $error, $tag, 'EmptyThrows' );
			} else {
				// Any strings until the next tag belong to this comment.
				if ( isset( $tokens[ $commentStart ]['comment_tags'][ ( $pos + 1 ) ] ) === true ) {
					$end = $tokens[ $commentStart ]['comment_tags'][ ( $pos + 1 ) ];
				} else {
					$end = $tokens[ $commentStart ]['comment_closer'];
				}

				for ( $i = ( $tag + 3 ); $i < $end; $i++ ) {
					if ( $tokens[ $i ]['code'] === T_DOC_COMMENT_STRING ) {
						$comment .= ' ' . $tokens[ $i ]['content'];
					}
				}

				$comment = trim( $comment );

				// Starts with a capital letter and ends with a fullstop.
				$firstChar = $comment[0];
				if ( strtoupper( $firstChar ) !== $firstChar ) {
					$error = '@throws tag comment must start with a capital letter';
					$phpcsFile->addError( $error, ( $tag + 2 ), 'ThrowsNotCapital' );
				}

				$lastChar = substr( $comment, -1 );
				if ( $lastChar !== '.' ) {
					$error = '@throws tag comment must end with a full stop';
					$phpcsFile->addError( $error, ( $tag + 2 ), 'ThrowsNoFullStop' );
				}
			}//end if
		}//end foreach
	}//end processThrows()


	/**
	 * Process the function parameter comments.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile    The file being scanned.
	 * @param int                         $stackPtr     The position of the current token
	 *                                                  in the stack passed in $tokens.
	 * @param int                         $commentStart The position in the stack where the comment started.
	 *
	 * @return void
	 */
	protected function processParams( File $phpcsFile, $stackPtr, $commentStart ) {
		if ( $this->phpVersion === null ) {
			$this->phpVersion = Config::getConfigData( 'php_version' );
			if ( $this->phpVersion === null ) {
				$this->phpVersion = PHP_VERSION_ID;
			}
		}

		$tokens = $phpcsFile->getTokens();

		if ( $this->skipIfInheritdoc === true ) {
			if ( $this->checkInheritdoc( $phpcsFile, $stackPtr, $commentStart ) === true ) {
				return;
			}
		}

		$params  = array();
		$maxType = 0;
		$maxVar  = 0;
		foreach ( $tokens[ $commentStart ]['comment_tags'] as $pos => $tag ) {
			if ( $tokens[ $tag ]['content'] !== '@param' ) {
				continue;
			}

			$type         = '';
			$typeSpace    = 0;
			$var          = '';
			$varSpace     = 0;
			$comment      = '';
			$commentLines = array();
			if ( $tokens[ ( $tag + 2 ) ]['code'] === T_DOC_COMMENT_STRING ) {
				$matches = array();
				preg_match( '/((?:(?![$.]|&(?=\$)).)*)(?:((?:\.\.\.)?(?:\$|&)[^\s]+)(?:(\s+)(.*))?)?/', $tokens[ ( $tag + 2 ) ]['content'], $matches );

				if ( empty( $matches ) === false ) {
					$typeLen   = strlen( $matches[1] );
					$type      = trim( $matches[1] );
					$typeSpace = ( $typeLen - strlen( $type ) );
					$typeLen   = strlen( $type );
					if ( $typeLen > $maxType ) {
						$maxType = $typeLen;
					}
				}

				if ( $tokens[ ( $tag + 2 ) ]['content'][0] === '$' ) {
					$error = 'Missing parameter type';
					$phpcsFile->addError( $error, $tag, 'MissingParamType' );
				} elseif ( isset( $matches[2] ) === true ) {
					$var    = $matches[2];
					$varLen = strlen( $var );
					if ( $varLen > $maxVar ) {
						$maxVar = $varLen;
					}

					if ( isset( $matches[4] ) === true ) {
						$varSpace       = strlen( $matches[3] );
						$comment        = $matches[4];
						$commentLines[] = array(
							'comment' => $comment,
							'token'   => ( $tag + 2 ),
							'indent'  => $varSpace,
						);

						// Any strings until the next tag belong to this comment.
						if ( isset( $tokens[ $commentStart ]['comment_tags'][ ( $pos + 1 ) ] ) === true ) {
							$end = $tokens[ $commentStart ]['comment_tags'][ ( $pos + 1 ) ];
						} else {
							$end = $tokens[ $commentStart ]['comment_closer'];
						}

						for ( $i = ( $tag + 3 ); $i < $end; $i++ ) {
							if ( $tokens[ $i ]['code'] === T_DOC_COMMENT_STRING ) {
								$indent = 0;
								if ( $tokens[ ( $i - 1 ) ]['code'] === T_DOC_COMMENT_WHITESPACE ) {
									$indent = $tokens[ ( $i - 1 ) ]['length'];
								}

								$comment       .= ' ' . $tokens[ $i ]['content'];
								$commentLines[] = array(
									'comment' => $tokens[ $i ]['content'],
									'token'   => $i,
									'indent'  => $indent,
								);
							}
						}
					} else {
						$error = 'Missing parameter comment';
						$phpcsFile->addError( $error, $tag, 'MissingParamComment' );
						$commentLines[] = array( 'comment' => '' );
					}//end if
				} else {
					$error = 'Missing parameter name';
					$phpcsFile->addError( $error, $tag, 'MissingParamName' );
				}//end if
			} else {
				$error = 'Missing parameter type';
				$phpcsFile->addError( $error, $tag, 'MissingParamType' );
			}//end if

			$params[] = array(
				'tag'          => $tag,
				'type'         => $type,
				'var'          => $var,
				'comment'      => $comment,
				'commentLines' => $commentLines,
				'type_space'   => $typeSpace,
				'var_space'    => $varSpace,
			);
		}//end foreach

		$realParams  = $phpcsFile->getMethodParameters( $stackPtr );
		$foundParams = array();

		// We want to use ... for all variable length arguments, so added
		// this prefix to the variable name so comparisons are easier.
		foreach ( $realParams as $pos => $param ) {
			if ( $param['variable_length'] === true ) {
				$realParams[ $pos ]['name'] = '...' . $realParams[ $pos ]['name'];
			}
		}

		foreach ( $params as $pos => $param ) {
			// If the type is empty, the whole line is empty.
			if ( $param['type'] === '' ) {
				continue;
			}

			// Check the param type value.
			$typeNames          = explode( '|', $param['type'] );
			$suggestedTypeNames = array();

			foreach ( $typeNames as $typeName ) {
				if ( $typeName === '' ) {
					continue;
				}

				// Strip nullable operator.
				if ( $typeName[0] === '?' ) {
					$typeName = substr( $typeName, 1 );
				}

				$suggestedName        = Common::suggestType( $typeName );
				$suggestedTypeNames[] = $suggestedName;

				if ( count( $typeNames ) > 1 ) {
					continue;
				}

				// Check type hint for array and custom type.
				$suggestedTypeHint = '';
				if ( strpos( $suggestedName, 'array' ) !== false || substr( $suggestedName, -2 ) === '[]' ) {
					$suggestedTypeHint = 'array';
				} elseif ( strpos( $suggestedName, 'callable' ) !== false ) {
					$suggestedTypeHint = 'callable';
				} elseif ( strpos( $suggestedName, 'callback' ) !== false ) {
					$suggestedTypeHint = 'callable';
				} elseif ( in_array( $suggestedName, Common::$allowedTypes, true ) === false ) {
					$suggestedTypeHint = $suggestedName;
				}

				if ( $this->phpVersion >= 70000 ) {
					if ( $suggestedName === 'string' ) {
						$suggestedTypeHint = 'string';
					} elseif ( $suggestedName === 'int' || $suggestedName === 'integer' ) {
						$suggestedTypeHint = 'int';
					} elseif ( $suggestedName === 'float' ) {
						$suggestedTypeHint = 'float';
					} elseif ( $suggestedName === 'bool' || $suggestedName === 'boolean' ) {
						$suggestedTypeHint = 'bool';
					}
				}

				if ( $this->phpVersion >= 70200 ) {
					if ( $suggestedName === 'object' ) {
						$suggestedTypeHint = 'object';
					}
				}

				if ( $this->phpVersion >= 80000 ) {
					if ( $suggestedName === 'mixed' ) {
						$suggestedTypeHint = 'mixed';
					}
				}

				if ( $suggestedTypeHint !== '' && isset( $realParams[ $pos ] ) === true && $param['var'] !== '' ) {
					$typeHint = $realParams[ $pos ]['type_hint'];

					// Remove namespace prefixes when comparing.
					$compareTypeHint = substr( $suggestedTypeHint, ( strlen( $typeHint ) * -1 ) );

					if ( $typeHint === '' ) {
						$error = 'Type hint "%s" missing for %s';
						$data  = array(
							$suggestedTypeHint,
							$param['var'],
						);

						$errorCode = 'TypeHintMissing';
						if ( $suggestedTypeHint === 'string'
							|| $suggestedTypeHint === 'int'
							|| $suggestedTypeHint === 'float'
							|| $suggestedTypeHint === 'bool'
						) {
							$errorCode = 'Scalar' . $errorCode;
						}

						$phpcsFile->addError( $error, $stackPtr, $errorCode, $data );
					} elseif ( $typeHint !== $compareTypeHint && $typeHint !== '?' . $compareTypeHint ) {
						$error = 'Expected type hint "%s"; found "%s" for %s';
						$data  = array(
							$suggestedTypeHint,
							$typeHint,
							$param['var'],
						);
						$phpcsFile->addError( $error, $stackPtr, 'IncorrectTypeHint', $data );
					}//end if
				} elseif ( $suggestedTypeHint === '' && isset( $realParams[ $pos ] ) === true ) {
					$typeHint = $realParams[ $pos ]['type_hint'];
					if ( $typeHint !== '' ) {
						$error = 'Unknown type hint "%s" found for %s';
						$data  = array(
							$typeHint,
							$param['var'],
						);
						$phpcsFile->addError( $error, $stackPtr, 'InvalidTypeHint', $data );
					}
				}//end if
			}//end foreach

			$suggestedType = implode( '|', $suggestedTypeNames );
			if ( $param['type'] !== $suggestedType ) {
				$error = 'Expected "%s" but found "%s" for parameter type';
				$data  = array(
					$suggestedType,
					$param['type'],
				);

				$fix = $phpcsFile->addFixableError( $error, $param['tag'], 'IncorrectParamVarName', $data );
				if ( $fix === true ) {
					$phpcsFile->fixer->beginChangeset();

					$content  = $suggestedType;
					$content .= str_repeat( ' ', $param['type_space'] );
					$content .= $param['var'];
					$content .= str_repeat( ' ', $param['var_space'] );
					if ( isset( $param['commentLines'][0] ) === true ) {
						$content .= $param['commentLines'][0]['comment'];
					}

					$phpcsFile->fixer->replaceToken( ( $param['tag'] + 2 ), $content );

					// Fix up the indent of additional comment lines.
					foreach ( $param['commentLines'] as $lineNum => $line ) {
						if ( $lineNum === 0
							|| $param['commentLines'][ $lineNum ]['indent'] === 0
						) {
							continue;
						}

						$diff      = ( strlen( $param['type'] ) - strlen( $suggestedType ) );
						$newIndent = ( $param['commentLines'][ $lineNum ]['indent'] - $diff );
						$phpcsFile->fixer->replaceToken(
							( $param['commentLines'][ $lineNum ]['token'] - 1 ),
							str_repeat( ' ', $newIndent )
						);
					}

					$phpcsFile->fixer->endChangeset();
				}//end if
			}//end if

			if ( $param['var'] === '' ) {
				continue;
			}

			$foundParams[] = $param['var'];

			// Check number of spaces after the type.
			$this->checkSpacingAfterParamType( $phpcsFile, $param, $maxType );

			// Make sure the param name is correct.
			if ( isset( $realParams[ $pos ] ) === true ) {
				$realName     = $realParams[ $pos ]['name'];
				$paramVarName = $param['var'];

				if ( $param['var'][0] === '&' ) {
					// Even when passed by reference, the variable name in $realParams does not have
					// a leading '&'. This sniff will accept both '&$var' and '$var' in these cases.
					$paramVarName = substr( $param['var'], 1 );

					// This makes sure that the 'MissingParamTag' check won't throw a false positive.
					$foundParams[ ( count( $foundParams ) - 1 ) ] = $paramVarName;

					if ( $realParams[ $pos ]['pass_by_reference'] !== true && $realName === $paramVarName ) {
						// Don't complain about this unless the param name is otherwise correct.
						$error = 'Doc comment for parameter %s is prefixed with "&" but parameter is not passed by reference';
						$code  = 'ParamNameUnexpectedAmpersandPrefix';
						$data  = array( $paramVarName );

						// We're not offering an auto-fix here because we can't tell if the docblock
						// is wrong, or the parameter should be passed by reference.
						$phpcsFile->addError( $error, $param['tag'], $code, $data );
					}
				}

				if ( $realName !== $paramVarName ) {
					$code = 'ParamNameNoMatch';
					$data = array(
						$paramVarName,
						$realName,
					);

					$error = 'Doc comment for parameter %s does not match ';
					if ( strtolower( $paramVarName ) === strtolower( $realName ) ) {
						$error .= 'case of ';
						$code   = 'ParamNameNoCaseMatch';
					}

					$error .= 'actual variable name %s';

					$phpcsFile->addError( $error, $param['tag'], $code, $data );
				}//end if
			} elseif ( substr( $param['var'], -4 ) !== ',...' ) {
				// We must have an extra parameter comment.
				$error = 'Superfluous parameter comment';
				$phpcsFile->addError( $error, $param['tag'], 'ExtraParamComment' );
			}//end if

			if ( $param['comment'] === '' ) {
				continue;
			}

			// Check number of spaces after the var name.
			$this->checkSpacingAfterParamName( $phpcsFile, $param, $maxVar );

			// Param comments must start with a capital letter and end with a full stop.
			if ( preg_match( '/^(\p{Ll}|\P{L})/u', $param['comment'] ) === 1 ) {
				$error = 'Parameter comment must start with a capital letter';
				$phpcsFile->addError( $error, $param['tag'], 'ParamCommentNotCapital' );
			}

			$lastChar = substr( $param['comment'], -1 );
			if ( $lastChar !== '.' ) {
				$error = 'Parameter comment must end with a full stop';
				$phpcsFile->addError( $error, $param['tag'], 'ParamCommentFullStop' );
			}
		}//end foreach

		$realNames = array();
		foreach ( $realParams as $realParam ) {
			$realNames[] = $realParam['name'];
		}

		// Report missing comments.
		$diff = array_diff( $realNames, $foundParams );
		foreach ( $diff as $neededParam ) {
			$error = 'Doc comment for parameter "%s" missing';
			$data  = array( $neededParam );
			$phpcsFile->addError( $error, $commentStart, 'MissingParamTag', $data );
		}
	}//end processParams()


	/**
	 * Check the spacing after the type of a parameter.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
	 * @param array                       $param     The parameter to be checked.
	 * @param int                         $maxType   The maxlength of the longest parameter type.
	 * @param int                         $spacing   The number of spaces to add after the type.
	 *
	 * @return void
	 */
	protected function checkSpacingAfterParamType( File $phpcsFile, $param, $maxType, $spacing = 1 ) {
		// Check number of spaces after the type.
		$spaces = ( $maxType - strlen( $param['type'] ) + $spacing );
		if ( $param['type_space'] !== $spaces ) {
			$error = 'Expected %s spaces after parameter type; %s found';
			$data  = array(
				$spaces,
				$param['type_space'],
			);

			$fix = $phpcsFile->addFixableError( $error, $param['tag'], 'SpacingAfterParamType', $data );
			if ( $fix === true ) {
				$phpcsFile->fixer->beginChangeset();

				$content  = $param['type'];
				$content .= str_repeat( ' ', $spaces );
				$content .= $param['var'];
				$content .= str_repeat( ' ', $param['var_space'] );
				$content .= $param['commentLines'][0]['comment'];
				$phpcsFile->fixer->replaceToken( ( $param['tag'] + 2 ), $content );

				// Fix up the indent of additional comment lines.
				$diff = ( $param['type_space'] - $spaces );
				foreach ( $param['commentLines'] as $lineNum => $line ) {
					if ( $lineNum === 0
						|| $param['commentLines'][ $lineNum ]['indent'] === 0
					) {
						continue;
					}

					$newIndent = ( $param['commentLines'][ $lineNum ]['indent'] - $diff );
					if ( $newIndent <= 0 ) {
						continue;
					}

					$phpcsFile->fixer->replaceToken(
						( $param['commentLines'][ $lineNum ]['token'] - 1 ),
						str_repeat( ' ', $newIndent )
					);
				}

				$phpcsFile->fixer->endChangeset();
			}//end if
		}//end if
	}//end checkSpacingAfterParamType()


	/**
	 * Check the spacing after the name of a parameter.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
	 * @param array                       $param     The parameter to be checked.
	 * @param int                         $maxVar    The maxlength of the longest parameter name.
	 * @param int                         $spacing   The number of spaces to add after the type.
	 *
	 * @return void
	 */
	protected function checkSpacingAfterParamName( File $phpcsFile, $param, $maxVar, $spacing = 1 ) {
		// Check number of spaces after the var name.
		$spaces = ( $maxVar - strlen( $param['var'] ) + $spacing );
		if ( $param['var_space'] !== $spaces ) {
			$error = 'Expected %s spaces after parameter name; %s found';
			$data  = array(
				$spaces,
				$param['var_space'],
			);

			$fix = $phpcsFile->addFixableError( $error, $param['tag'], 'SpacingAfterParamName', $data );
			if ( $fix === true ) {
				$phpcsFile->fixer->beginChangeset();

				$content  = $param['type'];
				$content .= str_repeat( ' ', $param['type_space'] );
				$content .= $param['var'];
				$content .= str_repeat( ' ', $spaces );
				$content .= $param['commentLines'][0]['comment'];
				$phpcsFile->fixer->replaceToken( ( $param['tag'] + 2 ), $content );

				// Fix up the indent of additional comment lines.
				foreach ( $param['commentLines'] as $lineNum => $line ) {
					if ( $lineNum === 0
						|| $param['commentLines'][ $lineNum ]['indent'] === 0
					) {
						continue;
					}

					$diff      = ( $param['var_space'] - $spaces );
					$newIndent = ( $param['commentLines'][ $lineNum ]['indent'] - $diff );
					if ( $newIndent <= 0 ) {
						continue;
					}

					$phpcsFile->fixer->replaceToken(
						( $param['commentLines'][ $lineNum ]['token'] - 1 ),
						str_repeat( ' ', $newIndent )
					);
				}

				$phpcsFile->fixer->endChangeset();
			}//end if
		}//end if
	}//end checkSpacingAfterParamName()


	/**
	 * Determines whether the whole comment is an inheritdoc comment.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile    The file being scanned.
	 * @param int                         $stackPtr     The position of the current token
	 *                                                  in the stack passed in $tokens.
	 * @param int                         $commentStart The position in the stack where the comment started.
	 *
	 * @return boolean TRUE if the docblock contains only {@inheritdoc} (case-insensitive).
	 */
	protected function checkInheritdoc( File $phpcsFile, $stackPtr, $commentStart ) {
		$tokens = $phpcsFile->getTokens();

		$allowedTokens = array(
			T_DOC_COMMENT_OPEN_TAG,
			T_DOC_COMMENT_WHITESPACE,
			T_DOC_COMMENT_STAR,
		);
		for ( $i = $commentStart; $i <= $tokens[ $commentStart ]['comment_closer']; $i++ ) {
			if ( in_array( $tokens[ $i ]['code'], $allowedTokens ) === false ) {
				$trimmedContent = strtolower( trim( $tokens[ $i ]['content'] ) );

				if ( $trimmedContent === '{@inheritdoc}' ) {
					return true;
				} else {
					return false;
				}
			}
		}

		return false;
	}//end checkInheritdoc()
}//end class
