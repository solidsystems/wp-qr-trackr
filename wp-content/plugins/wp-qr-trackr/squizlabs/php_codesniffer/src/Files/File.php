<?php
/**
 * Represents a piece of content being checked during the run.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Files;

use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Exceptions\RuntimeException;
use PHP_CodeSniffer\Exceptions\TokenizerException;
use PHP_CodeSniffer\Fixer;
use PHP_CodeSniffer\Ruleset;
use PHP_CodeSniffer\Util\Common;
use PHP_CodeSniffer\Util\Tokens;

class File {


	/**
	 * The absolute path to the file associated with this object.
	 *
	 * @var string
	 */
	public $path = '';

	/**
	 * The content of the file.
	 *
	 * @var string
	 */
	protected $content = '';

	/**
	 * The config data for the run.
	 *
	 * @var \PHP_CodeSniffer\Config
	 */
	public $config = null;

	/**
	 * The ruleset used for the run.
	 *
	 * @var \PHP_CodeSniffer\Ruleset
	 */
	public $ruleset = null;

	/**
	 * If TRUE, the entire file is being ignored.
	 *
	 * @var boolean
	 */
	public $ignored = false;

	/**
	 * The EOL character this file uses.
	 *
	 * @var string
	 */
	public $eolChar = '';

	/**
	 * The Fixer object to control fixing errors.
	 *
	 * @var \PHP_CodeSniffer\Fixer
	 */
	public $fixer = null;

	/**
	 * The tokenizer being used for this file.
	 *
	 * @var \PHP_CodeSniffer\Tokenizers\Tokenizer
	 */
	public $tokenizer = null;

	/**
	 * The name of the tokenizer being used for this file.
	 *
	 * @var string
	 */
	public $tokenizerType = 'PHP';

	/**
	 * Was the file loaded from cache?
	 *
	 * If TRUE, the file was loaded from a local cache.
	 * If FALSE, the file was tokenized and processed fully.
	 *
	 * @var boolean
	 */
	public $fromCache = false;

	/**
	 * The number of tokens in this file.
	 *
	 * Stored here to save calling count() everywhere.
	 *
	 * @var integer
	 */
	public $numTokens = 0;

	/**
	 * The tokens stack map.
	 *
	 * @var array
	 */
	protected $tokens = array();

	/**
	 * The errors raised from sniffs.
	 *
	 * @var array
	 * @see getErrors()
	 */
	protected $errors = array();

	/**
	 * The warnings raised from sniffs.
	 *
	 * @var array
	 * @see getWarnings()
	 */
	protected $warnings = array();

	/**
	 * The metrics recorded by sniffs.
	 *
	 * @var array
	 * @see getMetrics()
	 */
	protected $metrics = array();

	/**
	 * The metrics recorded for each token.
	 *
	 * Stops the same metric being recorded for the same token twice.
	 *
	 * @var array
	 * @see getMetrics()
	 */
	private $metricTokens = array();

	/**
	 * The total number of errors raised.
	 *
	 * @var integer
	 */
	protected $errorCount = 0;

	/**
	 * The total number of warnings raised.
	 *
	 * @var integer
	 */
	protected $warningCount = 0;

	/**
	 * The total number of errors and warnings that can be fixed.
	 *
	 * @var integer
	 */
	protected $fixableCount = 0;

	/**
	 * The total number of errors and warnings that were fixed.
	 *
	 * @var integer
	 */
	protected $fixedCount = 0;

	/**
	 * TRUE if errors are being replayed from the cache.
	 *
	 * @var boolean
	 */
	protected $replayingErrors = false;

	/**
	 * An array of sniffs that are being ignored.
	 *
	 * @var array
	 */
	protected $ignoredListeners = array();

	/**
	 * An array of message codes that are being ignored.
	 *
	 * @var array
	 */
	protected $ignoredCodes = array();

	/**
	 * An array of sniffs listening to this file's processing.
	 *
	 * @var \PHP_CodeSniffer\Sniffs\Sniff[]
	 */
	protected $listeners = array();

	/**
	 * The class name of the sniff currently processing the file.
	 *
	 * @var string
	 */
	protected $activeListener = '';

	/**
	 * An array of sniffs being processed and how long they took.
	 *
	 * @var array
	 * @see getListenerTimes()
	 */
	protected $listenerTimes = array();

	/**
	 * A cache of often used config settings to improve performance.
	 *
	 * Storing them here saves 10k+ calls to __get() in the Config class.
	 *
	 * @var array
	 */
	protected $configCache = array();


	/**
	 * Constructs a file.
	 *
	 * @param string                   $path    The absolute path to the file to process.
	 * @param \PHP_CodeSniffer\Ruleset $ruleset The ruleset used for the run.
	 * @param \PHP_CodeSniffer\Config  $config  The config data for the run.
	 *
	 * @return void
	 */
	public function __construct( $path, Ruleset $ruleset, Config $config ) {
		$this->path    = $path;
		$this->ruleset = $ruleset;
		$this->config  = $config;
		$this->fixer   = new Fixer();

		$parts     = explode( '.', $path );
		$extension = array_pop( $parts );
		if ( isset( $config->extensions[ $extension ] ) === true ) {
			$this->tokenizerType = $config->extensions[ $extension ];
		} else {
			// Revert to default.
			$this->tokenizerType = 'PHP';
		}

		$this->configCache['cache']           = $this->config->cache;
		$this->configCache['sniffs']          = array_map( 'strtolower', $this->config->sniffs );
		$this->configCache['exclude']         = array_map( 'strtolower', $this->config->exclude );
		$this->configCache['errorSeverity']   = $this->config->errorSeverity;
		$this->configCache['warningSeverity'] = $this->config->warningSeverity;
		$this->configCache['recordErrors']    = $this->config->recordErrors;
		$this->configCache['trackTime']       = $this->config->trackTime;
		$this->configCache['ignorePatterns']  = $this->ruleset->ignorePatterns;
		$this->configCache['includePatterns'] = $this->ruleset->includePatterns;
	}//end __construct()


	/**
	 * Set the content of the file.
	 *
	 * Setting the content also calculates the EOL char being used.
	 *
	 * @param string $content The file content.
	 *
	 * @return void
	 */
	public function setContent( $content ) {
		$this->content = $content;
		$this->tokens  = array();

		try {
			$this->eolChar = Common::detectLineEndings( $content );
		} catch ( RuntimeException $e ) {
			$this->addWarningOnLine( $e->getMessage(), 1, 'Internal.DetectLineEndings' );
			return;
		}
	}//end setContent()


	/**
	 * Reloads the content of the file.
	 *
	 * By default, we have no idea where our content comes from,
	 * so we can't do anything.
	 *
	 * @return void
	 */
	public function reloadContent() {
	}//end reloadContent()


	/**
	 * Disables caching of this file.
	 *
	 * @return void
	 */
	public function disableCaching() {
		$this->configCache['cache'] = false;
	}//end disableCaching()


	/**
	 * Starts the stack traversal and tells listeners when tokens are found.
	 *
	 * @return void
	 */
	public function process() {
		if ( $this->ignored === true ) {
			return;
		}

		$this->errors       = array();
		$this->warnings     = array();
		$this->errorCount   = 0;
		$this->warningCount = 0;
		$this->fixableCount = 0;

		$this->parse();

		// Check if tokenizer errors cause this file to be ignored.
		if ( $this->ignored === true ) {
			return;
		}

		$this->fixer->startFile( $this );

		if ( PHP_CODESNIFFER_VERBOSITY > 2 ) {
			echo "\t*** START TOKEN PROCESSING ***" . PHP_EOL;
		}

		$foundCode        = false;
		$listenerIgnoreTo = array();
		$inTests          = defined( 'PHP_CODESNIFFER_IN_TESTS' );
		$checkAnnotations = $this->config->annotations;

		// Foreach of the listeners that have registered to listen for this
		// token, get them to process it.
		foreach ( $this->tokens as $stackPtr => $token ) {
			// Check for ignored lines.
			if ( $checkAnnotations === true
				&& ( $token['code'] === T_COMMENT
				|| $token['code'] === T_PHPCS_IGNORE_FILE
				|| $token['code'] === T_PHPCS_SET
				|| $token['code'] === T_DOC_COMMENT_STRING
				|| $token['code'] === T_DOC_COMMENT_TAG
				|| ( $inTests === true && $token['code'] === T_INLINE_HTML ) )
			) {
				$commentText      = ltrim( $this->tokens[ $stackPtr ]['content'], " \t/*#" );
				$commentTextLower = strtolower( $commentText );
				if ( strpos( $commentText, '@codingStandards' ) !== false ) {
					if ( strpos( $commentText, '@codingStandardsIgnoreFile' ) !== false ) {
						// Ignoring the whole file, just a little late.
						$this->errors       = array();
						$this->warnings     = array();
						$this->errorCount   = 0;
						$this->warningCount = 0;
						$this->fixableCount = 0;
						return;
					} elseif ( strpos( $commentText, '@codingStandardsChangeSetting' ) !== false ) {
						$start   = strpos( $commentText, '@codingStandardsChangeSetting' );
						$comment = substr( $commentText, ( $start + 30 ) );
						$parts   = explode( ' ', $comment );
						if ( count( $parts ) >= 2 ) {
							$sniffParts = explode( '.', $parts[0] );
							if ( count( $sniffParts ) >= 3 ) {
								// If the sniff code is not known to us, it has not been registered in this run.
								// But don't throw an error as it could be there for a different standard to use.
								if ( isset( $this->ruleset->sniffCodes[ $parts[0] ] ) === true ) {
									$listenerCode  = array_shift( $parts );
									$propertyCode  = array_shift( $parts );
									$settings      = array(
										'value' => rtrim( implode( ' ', $parts ), " */\r\n" ),
										'scope' => 'sniff',
									);
									$listenerClass = $this->ruleset->sniffCodes[ $listenerCode ];
									$this->ruleset->setSniffProperty( $listenerClass, $propertyCode, $settings );
								}
							}
						}
					}//end if
				} elseif ( substr( $commentTextLower, 0, 16 ) === 'phpcs:ignorefile'
					|| substr( $commentTextLower, 0, 17 ) === '@phpcs:ignorefile'
				) {
					// Ignoring the whole file, just a little late.
					$this->errors       = array();
					$this->warnings     = array();
					$this->errorCount   = 0;
					$this->warningCount = 0;
					$this->fixableCount = 0;
					return;
				} elseif ( substr( $commentTextLower, 0, 9 ) === 'phpcs:set'
					|| substr( $commentTextLower, 0, 10 ) === '@phpcs:set'
				) {
					if ( isset( $token['sniffCode'] ) === true ) {
						$listenerCode = $token['sniffCode'];
						if ( isset( $this->ruleset->sniffCodes[ $listenerCode ] ) === true ) {
							$propertyCode  = $token['sniffProperty'];
							$settings      = array(
								'value' => $token['sniffPropertyValue'],
								'scope' => 'sniff',
							);
							$listenerClass = $this->ruleset->sniffCodes[ $listenerCode ];
							$this->ruleset->setSniffProperty( $listenerClass, $propertyCode, $settings );
						}
					}
				}//end if
			}//end if

			if ( PHP_CODESNIFFER_VERBOSITY > 2 ) {
				$type    = $token['type'];
				$content = Common::prepareForOutput( $token['content'] );
				echo "\t\tProcess token $stackPtr: $type => $content" . PHP_EOL;
			}

			if ( $token['code'] !== T_INLINE_HTML ) {
				$foundCode = true;
			}

			if ( isset( $this->ruleset->tokenListeners[ $token['code'] ] ) === false ) {
				continue;
			}

			foreach ( $this->ruleset->tokenListeners[ $token['code'] ] as $listenerData ) {
				if ( isset( $this->ignoredListeners[ $listenerData['class'] ] ) === true
					|| ( isset( $listenerIgnoreTo[ $listenerData['class'] ] ) === true
					&& $listenerIgnoreTo[ $listenerData['class'] ] > $stackPtr )
				) {
					// This sniff is ignoring past this token, or the whole file.
					continue;
				}

				// Make sure this sniff supports the tokenizer
				// we are currently using.
				$class = $listenerData['class'];

				if ( isset( $listenerData['tokenizers'][ $this->tokenizerType ] ) === false ) {
					continue;
				}

				if ( trim( $this->path, '\'"' ) !== 'STDIN' ) {
					// If the file path matches one of our ignore patterns, skip it.
					// While there is support for a type of each pattern
					// (absolute or relative) we don't actually support it here.
					foreach ( $listenerData['ignore'] as $pattern ) {
						// We assume a / directory separator, as do the exclude rules
						// most developers write, so we need a special case for any system
						// that is different.
						if ( DIRECTORY_SEPARATOR === '\\' ) {
							$pattern = str_replace( '/', '\\\\', $pattern );
						}

						$pattern = '`' . $pattern . '`i';
						if ( preg_match( $pattern, $this->path ) === 1 ) {
							$this->ignoredListeners[ $class ] = true;
							continue( 2 );
						}
					}

					// If the file path does not match one of our include patterns, skip it.
					// While there is support for a type of each pattern
					// (absolute or relative) we don't actually support it here.
					if ( empty( $listenerData['include'] ) === false ) {
						$included = false;
						foreach ( $listenerData['include'] as $pattern ) {
							// We assume a / directory separator, as do the exclude rules
							// most developers write, so we need a special case for any system
							// that is different.
							if ( DIRECTORY_SEPARATOR === '\\' ) {
								$pattern = str_replace( '/', '\\\\', $pattern );
							}

							$pattern = '`' . $pattern . '`i';
							if ( preg_match( $pattern, $this->path ) === 1 ) {
								$included = true;
								break;
							}
						}

						if ( $included === false ) {
							$this->ignoredListeners[ $class ] = true;
							continue;
						}
					}//end if
				}//end if

				$this->activeListener = $class;

				if ( $this->configCache['trackTime'] === true ) {
					$startTime = microtime( true );
				}

				if ( PHP_CODESNIFFER_VERBOSITY > 2 ) {
					echo "\t\t\tProcessing " . $this->activeListener . '... ';
				}

				$ignoreTo = $this->ruleset->sniffs[ $class ]->process( $this, $stackPtr );
				if ( $ignoreTo !== null ) {
					$listenerIgnoreTo[ $this->activeListener ] = $ignoreTo;
				}

				if ( $this->configCache['trackTime'] === true ) {
					$timeTaken = ( microtime( true ) - $startTime );
					if ( isset( $this->listenerTimes[ $this->activeListener ] ) === false ) {
						$this->listenerTimes[ $this->activeListener ] = 0;
					}

					$this->listenerTimes[ $this->activeListener ] += $timeTaken;
				}

				if ( PHP_CODESNIFFER_VERBOSITY > 2 ) {
					$timeTaken = round( ( $timeTaken ), 4 );
					echo "DONE in $timeTaken seconds" . PHP_EOL;
				}

				$this->activeListener = '';
			}//end foreach
		}//end foreach

		// If short open tags are off but the file being checked uses
		// short open tags, the whole content will be inline HTML
		// and nothing will be checked. So try and handle this case.
		// We don't show this error for STDIN because we can't be sure the content
		// actually came directly from the user. It could be something like
		// refs from a Git pre-push hook.
		if ( $foundCode === false && $this->tokenizerType === 'PHP' && $this->path !== 'STDIN' ) {
			$shortTags = (bool) ini_get( 'short_open_tag' );
			if ( $shortTags === false ) {
				$error = 'No PHP code was found in this file and short open tags are not allowed by this install of PHP. This file may be using short open tags but PHP does not allow them.';
				$this->addWarning( $error, null, 'Internal.NoCodeFound' );
			}
		}

		if ( PHP_CODESNIFFER_VERBOSITY > 2 ) {
			echo "\t*** END TOKEN PROCESSING ***" . PHP_EOL;
			echo "\t*** START SNIFF PROCESSING REPORT ***" . PHP_EOL;

			arsort( $this->listenerTimes, SORT_NUMERIC );
			foreach ( $this->listenerTimes as $listener => $timeTaken ) {
				echo "\t$listener: " . round( ( $timeTaken ), 4 ) . ' secs' . PHP_EOL;
			}

			echo "\t*** END SNIFF PROCESSING REPORT ***" . PHP_EOL;
		}

		$this->fixedCount += $this->fixer->getFixCount();
	}//end process()


	/**
	 * Tokenizes the file and prepares it for the test run.
	 *
	 * @return void
	 */
	public function parse() {
		if ( empty( $this->tokens ) === false ) {
			// File has already been parsed.
			return;
		}

		try {
			$tokenizerClass  = 'PHP_CodeSniffer\Tokenizers\\' . $this->tokenizerType;
			$this->tokenizer = new $tokenizerClass( $this->content, $this->config, $this->eolChar );
			$this->tokens    = $this->tokenizer->getTokens();
		} catch ( TokenizerException $e ) {
			$this->ignored = true;
			$this->addWarning( $e->getMessage(), null, 'Internal.Tokenizer.Exception' );
			if ( PHP_CODESNIFFER_VERBOSITY > 0 ) {
				echo "[$this->tokenizerType => tokenizer error]... ";
				if ( PHP_CODESNIFFER_VERBOSITY > 1 ) {
					echo PHP_EOL;
				}
			}

			return;
		}

		$this->numTokens = count( $this->tokens );

		// Check for mixed line endings as these can cause tokenizer errors and we
		// should let the user know that the results they get may be incorrect.
		// This is done by removing all backslashes, removing the newline char we
		// detected, then converting newlines chars into text. If any backslashes
		// are left at the end, we have additional newline chars in use.
		$contents = str_replace( '\\', '', $this->content );
		$contents = str_replace( $this->eolChar, '', $contents );
		$contents = str_replace( "\n", '\n', $contents );
		$contents = str_replace( "\r", '\r', $contents );
		if ( strpos( $contents, '\\' ) !== false ) {
			$error = 'File has mixed line endings; this may cause incorrect results';
			$this->addWarningOnLine( $error, 1, 'Internal.LineEndings.Mixed' );
		}

		if ( PHP_CODESNIFFER_VERBOSITY > 0 ) {
			if ( $this->numTokens === 0 ) {
				$numLines = 0;
			} else {
				$numLines = $this->tokens[ ( $this->numTokens - 1 ) ]['line'];
			}

			echo "[$this->tokenizerType => $this->numTokens tokens in $numLines lines]... ";
			if ( PHP_CODESNIFFER_VERBOSITY > 1 ) {
				echo PHP_EOL;
			}
		}
	}//end parse()


	/**
	 * Returns the token stack for this file.
	 *
	 * @return array
	 */
	public function getTokens() {
		return $this->tokens;
	}//end getTokens()


	/**
	 * Remove vars stored in this file that are no longer required.
	 *
	 * @return void
	 */
	public function cleanUp() {
		$this->listenerTimes = null;
		$this->content       = null;
		$this->tokens        = null;
		$this->metricTokens  = null;
		$this->tokenizer     = null;
		$this->fixer         = null;
		$this->config        = null;
		$this->ruleset       = null;
	}//end cleanUp()


	/**
	 * Records an error against a specific token in the file.
	 *
	 * @param string   $error    The error message.
	 * @param int|null $stackPtr The stack position where the error occurred.
	 * @param string   $code     A violation code unique to the sniff message.
	 * @param array    $data     Replacements for the error message.
	 * @param int      $severity The severity level for this error. A value of 0
	 *                           will be converted into the default severity level.
	 * @param boolean  $fixable  Can the error be fixed by the sniff?
	 *
	 * @return boolean
	 */
	public function addError(
		$error,
		$stackPtr,
		$code,
		$data = array(),
		$severity = 0,
		$fixable = false
	) {
		if ( $stackPtr === null ) {
			$line   = 1;
			$column = 1;
		} else {
			$line   = $this->tokens[ $stackPtr ]['line'];
			$column = $this->tokens[ $stackPtr ]['column'];
		}

		return $this->addMessage( true, $error, $line, $column, $code, $data, $severity, $fixable );
	}//end addError()


	/**
	 * Records a warning against a specific token in the file.
	 *
	 * @param string   $warning  The error message.
	 * @param int|null $stackPtr The stack position where the error occurred.
	 * @param string   $code     A violation code unique to the sniff message.
	 * @param array    $data     Replacements for the warning message.
	 * @param int      $severity The severity level for this warning. A value of 0
	 *                           will be converted into the default severity level.
	 * @param boolean  $fixable  Can the warning be fixed by the sniff?
	 *
	 * @return boolean
	 */
	public function addWarning(
		$warning,
		$stackPtr,
		$code,
		$data = array(),
		$severity = 0,
		$fixable = false
	) {
		if ( $stackPtr === null ) {
			$line   = 1;
			$column = 1;
		} else {
			$line   = $this->tokens[ $stackPtr ]['line'];
			$column = $this->tokens[ $stackPtr ]['column'];
		}

		return $this->addMessage( false, $warning, $line, $column, $code, $data, $severity, $fixable );
	}//end addWarning()


	/**
	 * Records an error against a specific line in the file.
	 *
	 * @param string $error    The error message.
	 * @param int    $line     The line on which the error occurred.
	 * @param string $code     A violation code unique to the sniff message.
	 * @param array  $data     Replacements for the error message.
	 * @param int    $severity The severity level for this error. A value of 0
	 *                         will be converted into the default severity level.
	 *
	 * @return boolean
	 */
	public function addErrorOnLine(
		$error,
		$line,
		$code,
		$data = array(),
		$severity = 0
	) {
		return $this->addMessage( true, $error, $line, 1, $code, $data, $severity, false );
	}//end addErrorOnLine()


	/**
	 * Records a warning against a specific line in the file.
	 *
	 * @param string $warning  The error message.
	 * @param int    $line     The line on which the warning occurred.
	 * @param string $code     A violation code unique to the sniff message.
	 * @param array  $data     Replacements for the warning message.
	 * @param int    $severity The severity level for this warning. A value of 0 will
	 *                         will be converted into the default severity level.
	 *
	 * @return boolean
	 */
	public function addWarningOnLine(
		$warning,
		$line,
		$code,
		$data = array(),
		$severity = 0
	) {
		return $this->addMessage( false, $warning, $line, 1, $code, $data, $severity, false );
	}//end addWarningOnLine()


	/**
	 * Records a fixable error against a specific token in the file.
	 *
	 * Returns true if the error was recorded and should be fixed.
	 *
	 * @param string $error    The error message.
	 * @param int    $stackPtr The stack position where the error occurred.
	 * @param string $code     A violation code unique to the sniff message.
	 * @param array  $data     Replacements for the error message.
	 * @param int    $severity The severity level for this error. A value of 0
	 *                         will be converted into the default severity level.
	 *
	 * @return boolean
	 */
	public function addFixableError(
		$error,
		$stackPtr,
		$code,
		$data = array(),
		$severity = 0
	) {
		$recorded = $this->addError( $error, $stackPtr, $code, $data, $severity, true );
		if ( $recorded === true && $this->fixer->enabled === true ) {
			return true;
		}

		return false;
	}//end addFixableError()


	/**
	 * Records a fixable warning against a specific token in the file.
	 *
	 * Returns true if the warning was recorded and should be fixed.
	 *
	 * @param string $warning  The error message.
	 * @param int    $stackPtr The stack position where the error occurred.
	 * @param string $code     A violation code unique to the sniff message.
	 * @param array  $data     Replacements for the warning message.
	 * @param int    $severity The severity level for this warning. A value of 0
	 *                         will be converted into the default severity level.
	 *
	 * @return boolean
	 */
	public function addFixableWarning(
		$warning,
		$stackPtr,
		$code,
		$data = array(),
		$severity = 0
	) {
		$recorded = $this->addWarning( $warning, $stackPtr, $code, $data, $severity, true );
		if ( $recorded === true && $this->fixer->enabled === true ) {
			return true;
		}

		return false;
	}//end addFixableWarning()


	/**
	 * Adds an error to the error stack.
	 *
	 * @param boolean $error    Is this an error message?
	 * @param string  $message  The text of the message.
	 * @param int     $line     The line on which the message occurred.
	 * @param int     $column   The column at which the message occurred.
	 * @param string  $code     A violation code unique to the sniff message.
	 * @param array   $data     Replacements for the message.
	 * @param int     $severity The severity level for this message. A value of 0
	 *                          will be converted into the default severity level.
	 * @param boolean $fixable  Can the problem be fixed by the sniff?
	 *
	 * @return boolean
	 */
	protected function addMessage( $error, $message, $line, $column, $code, $data, $severity, $fixable ) {
		// Check if this line is ignoring all message codes.
		if ( isset( $this->tokenizer->ignoredLines[ $line ]['.all'] ) === true ) {
			return false;
		}

		// Work out which sniff generated the message.
		$parts = explode( '.', $code );
		if ( $parts[0] === 'Internal' ) {
			// An internal message.
			$listenerCode = '';
			if ( $this->activeListener !== '' ) {
				$listenerCode = Common::getSniffCode( $this->activeListener );
			}

			$sniffCode  = $code;
			$checkCodes = array( $sniffCode );
		} else {
			if ( $parts[0] !== $code ) {
				// The full message code has been passed in.
				$sniffCode    = $code;
				$listenerCode = substr( $sniffCode, 0, strrpos( $sniffCode, '.' ) );
			} else {
				$listenerCode = Common::getSniffCode( $this->activeListener );
				$sniffCode    = $listenerCode . '.' . $code;
				$parts        = explode( '.', $sniffCode );
			}

			$checkCodes = array(
				$sniffCode,
				$parts[0] . '.' . $parts[1] . '.' . $parts[2],
				$parts[0] . '.' . $parts[1],
				$parts[0],
			);
		}//end if

		if ( isset( $this->tokenizer->ignoredLines[ $line ] ) === true ) {
			// Check if this line is ignoring this specific message.
			$ignored = false;
			foreach ( $checkCodes as $checkCode ) {
				if ( isset( $this->tokenizer->ignoredLines[ $line ][ $checkCode ] ) === true ) {
					$ignored = true;
					break;
				}
			}

			// If it is ignored, make sure there is no exception in place.
			if ( $ignored === true
				&& isset( $this->tokenizer->ignoredLines[ $line ]['.except'] ) === true
			) {
				foreach ( $checkCodes as $checkCode ) {
					if ( isset( $this->tokenizer->ignoredLines[ $line ]['.except'][ $checkCode ] ) === true ) {
						$ignored = false;
						break;
					}
				}
			}

			if ( $ignored === true ) {
				return false;
			}
		}//end if

		$includeAll = true;
		if ( $this->configCache['cache'] === false
			|| $this->configCache['recordErrors'] === false
		) {
			$includeAll = false;
		}

		// Filter out any messages for sniffs that shouldn't have run
		// due to the use of the --sniffs command line argument.
		if ( $includeAll === false
			&& ( ( empty( $this->configCache['sniffs'] ) === false
			&& in_array( strtolower( $listenerCode ), $this->configCache['sniffs'], true ) === false )
			|| ( empty( $this->configCache['exclude'] ) === false
			&& in_array( strtolower( $listenerCode ), $this->configCache['exclude'], true ) === true ) )
		) {
			return false;
		}

		// If we know this sniff code is being ignored for this file, return early.
		foreach ( $checkCodes as $checkCode ) {
			if ( isset( $this->ignoredCodes[ $checkCode ] ) === true ) {
				return false;
			}
		}

		$oppositeType = 'warning';
		if ( $error === false ) {
			$oppositeType = 'error';
		}

		foreach ( $checkCodes as $checkCode ) {
			// Make sure this message type has not been set to the opposite message type.
			if ( isset( $this->ruleset->ruleset[ $checkCode ]['type'] ) === true
				&& $this->ruleset->ruleset[ $checkCode ]['type'] === $oppositeType
			) {
				$error = ! $error;
				break;
			}
		}

		if ( $error === true ) {
			$configSeverity = $this->configCache['errorSeverity'];
			$messageCount   = &$this->errorCount;
			$messages       = &$this->errors;
		} else {
			$configSeverity = $this->configCache['warningSeverity'];
			$messageCount   = &$this->warningCount;
			$messages       = &$this->warnings;
		}

		if ( $includeAll === false && $configSeverity === 0 ) {
			// Don't bother doing any processing as these messages are just going to
			// be hidden in the reports anyway.
			return false;
		}

		if ( $severity === 0 ) {
			$severity = 5;
		}

		foreach ( $checkCodes as $checkCode ) {
			// Make sure we are interested in this severity level.
			if ( isset( $this->ruleset->ruleset[ $checkCode ]['severity'] ) === true ) {
				$severity = $this->ruleset->ruleset[ $checkCode ]['severity'];
				break;
			}
		}

		if ( $includeAll === false && $configSeverity > $severity ) {
			return false;
		}

		// Make sure we are not ignoring this file.
		$included = null;
		if ( trim( $this->path, '\'"' ) === 'STDIN' ) {
			$included = true;
		} else {
			foreach ( $checkCodes as $checkCode ) {
				$patterns = null;

				if ( isset( $this->configCache['includePatterns'][ $checkCode ] ) === true ) {
					$patterns  = $this->configCache['includePatterns'][ $checkCode ];
					$excluding = false;
				} elseif ( isset( $this->configCache['ignorePatterns'][ $checkCode ] ) === true ) {
					$patterns  = $this->configCache['ignorePatterns'][ $checkCode ];
					$excluding = true;
				}

				if ( $patterns === null ) {
					continue;
				}

				foreach ( $patterns as $pattern => $type ) {
					// While there is support for a type of each pattern
					// (absolute or relative) we don't actually support it here.
					$replacements = array(
						'\\,' => ',',
						'*'   => '.*',
					);

					// We assume a / directory separator, as do the exclude rules
					// most developers write, so we need a special case for any system
					// that is different.
					if ( DIRECTORY_SEPARATOR === '\\' ) {
						$replacements['/'] = '\\\\';
					}

					$pattern = '`' . strtr( $pattern, $replacements ) . '`i';
					$matched = preg_match( $pattern, $this->path );

					if ( $matched === 0 ) {
						if ( $excluding === false && $included === null ) {
							// This file path is not being included.
							$included = false;
						}

						continue;
					}

					if ( $excluding === true ) {
						// This file path is being excluded.
						$this->ignoredCodes[ $checkCode ] = true;
						return false;
					}

					// This file path is being included.
					$included = true;
					break;
				}//end foreach
			}//end foreach
		}//end if

		if ( $included === false ) {
			// There were include rules set, but this file
			// path didn't match any of them.
			return false;
		}

		++$messageCount;
		if ( $fixable === true ) {
			++$this->fixableCount;
		}

		if ( $this->configCache['recordErrors'] === false
			&& $includeAll === false
		) {
			return true;
		}

		// See if there is a custom error message format to use.
		// But don't do this if we are replaying errors because replayed
		// errors have already used the custom format and have had their
		// data replaced.
		if ( $this->replayingErrors === false
			&& isset( $this->ruleset->ruleset[ $sniffCode ]['message'] ) === true
		) {
			$message = $this->ruleset->ruleset[ $sniffCode ]['message'];
		}

		if ( empty( $data ) === false ) {
			$message = vsprintf( $message, $data );
		}

		if ( isset( $messages[ $line ] ) === false ) {
			$messages[ $line ] = array();
		}

		if ( isset( $messages[ $line ][ $column ] ) === false ) {
			$messages[ $line ][ $column ] = array();
		}

		$messages[ $line ][ $column ][] = array(
			'message'  => $message,
			'source'   => $sniffCode,
			'listener' => $this->activeListener,
			'severity' => $severity,
			'fixable'  => $fixable,
		);

		if ( PHP_CODESNIFFER_VERBOSITY > 1
			&& $this->fixer->enabled === true
			&& $fixable === true
		) {
			@ob_end_clean();
			echo "\tE: [Line $line] $message ($sniffCode)" . PHP_EOL;
			ob_start();
		}

		return true;
	}//end addMessage()


	/**
	 * Record a metric about the file being examined.
	 *
	 * @param int    $stackPtr The stack position where the metric was recorded.
	 * @param string $metric   The name of the metric being recorded.
	 * @param string $value    The value of the metric being recorded.
	 *
	 * @return boolean
	 */
	public function recordMetric( $stackPtr, $metric, $value ) {
		if ( isset( $this->metrics[ $metric ] ) === false ) {
			$this->metrics[ $metric ]                   = array( 'values' => array( $value => 1 ) );
			$this->metricTokens[ $metric ][ $stackPtr ] = true;
		} elseif ( isset( $this->metricTokens[ $metric ][ $stackPtr ] ) === false ) {
			$this->metricTokens[ $metric ][ $stackPtr ] = true;
			if ( isset( $this->metrics[ $metric ]['values'][ $value ] ) === false ) {
				$this->metrics[ $metric ]['values'][ $value ] = 1;
			} else {
				++$this->metrics[ $metric ]['values'][ $value ];
			}
		}

		return true;
	}//end recordMetric()


	/**
	 * Returns the number of errors raised.
	 *
	 * @return int
	 */
	public function getErrorCount() {
		return $this->errorCount;
	}//end getErrorCount()


	/**
	 * Returns the number of warnings raised.
	 *
	 * @return int
	 */
	public function getWarningCount() {
		return $this->warningCount;
	}//end getWarningCount()


	/**
	 * Returns the number of fixable errors/warnings raised.
	 *
	 * @return int
	 */
	public function getFixableCount() {
		return $this->fixableCount;
	}//end getFixableCount()


	/**
	 * Returns the number of fixed errors/warnings.
	 *
	 * @return int
	 */
	public function getFixedCount() {
		return $this->fixedCount;
	}//end getFixedCount()


	/**
	 * Returns the list of ignored lines.
	 *
	 * @return array
	 */
	public function getIgnoredLines() {
		return $this->tokenizer->ignoredLines;
	}//end getIgnoredLines()


	/**
	 * Returns the errors raised from processing this file.
	 *
	 * @return array
	 */
	public function getErrors() {
		return $this->errors;
	}//end getErrors()


	/**
	 * Returns the warnings raised from processing this file.
	 *
	 * @return array
	 */
	public function getWarnings() {
		return $this->warnings;
	}//end getWarnings()


	/**
	 * Returns the metrics found while processing this file.
	 *
	 * @return array
	 */
	public function getMetrics() {
		return $this->metrics;
	}//end getMetrics()


	/**
	 * Returns the time taken processing this file for each invoked sniff.
	 *
	 * @return array
	 */
	public function getListenerTimes() {
		return $this->listenerTimes;
	}//end getListenerTimes()


	/**
	 * Returns the absolute filename of this file.
	 *
	 * @return string
	 */
	public function getFilename() {
		return $this->path;
	}//end getFilename()


	/**
	 * Returns the declaration name for classes, interfaces, traits, enums, and functions.
	 *
	 * @param int $stackPtr The position of the declaration token which
	 *                      declared the class, interface, trait, or function.
	 *
	 * @return string|null The name of the class, interface, trait, or function;
	 *                     or NULL if the function or class is anonymous.
	 * @throws \PHP_CodeSniffer\Exceptions\RuntimeException If the specified token is not of type
	 *                                                      T_FUNCTION, T_CLASS, T_ANON_CLASS,
	 *                                                      T_CLOSURE, T_TRAIT, T_ENUM, or T_INTERFACE.
	 */
	public function getDeclarationName( $stackPtr ) {
		$tokenCode = $this->tokens[ $stackPtr ]['code'];

		if ( $tokenCode === T_ANON_CLASS || $tokenCode === T_CLOSURE ) {
			return null;
		}

		if ( $tokenCode !== T_FUNCTION
			&& $tokenCode !== T_CLASS
			&& $tokenCode !== T_INTERFACE
			&& $tokenCode !== T_TRAIT
			&& $tokenCode !== T_ENUM
		) {
			throw new RuntimeException( 'Token type "' . $this->tokens[ $stackPtr ]['type'] . '" is not T_FUNCTION, T_CLASS, T_INTERFACE, T_TRAIT or T_ENUM' );
		}

		if ( $tokenCode === T_FUNCTION
			&& strtolower( $this->tokens[ $stackPtr ]['content'] ) !== 'function'
		) {
			// This is a function declared without the "function" keyword.
			// So this token is the function name.
			return $this->tokens[ $stackPtr ]['content'];
		}

		$stopPoint = $this->numTokens;
		if ( isset( $this->tokens[ $stackPtr ]['parenthesis_opener'] ) === true ) {
			// For functions, stop searching at the parenthesis opener.
			$stopPoint = $this->tokens[ $stackPtr ]['parenthesis_opener'];
		} elseif ( isset( $this->tokens[ $stackPtr ]['scope_opener'] ) === true ) {
			// For OO tokens, stop searching at the open curly.
			$stopPoint = $this->tokens[ $stackPtr ]['scope_opener'];
		}

		$content = null;
		for ( $i = $stackPtr; $i < $stopPoint; $i++ ) {
			if ( $this->tokens[ $i ]['code'] === T_STRING ) {
				$content = $this->tokens[ $i ]['content'];
				break;
			}
		}

		return $content;
	}//end getDeclarationName()


	/**
	 * Returns the method parameters for the specified function token.
	 *
	 * Also supports passing in a USE token for a closure use group.
	 *
	 * Each parameter is in the following format:
	 *
	 * <code>
	 *   0 => array(
	 *         'name'                => string,        // The variable name.
	 *         'token'               => integer,       // The stack pointer to the variable name.
	 *         'content'             => string,        // The full content of the variable definition.
	 *         'has_attributes'      => boolean,       // Does the parameter have one or more attributes attached ?
	 *         'pass_by_reference'   => boolean,       // Is the variable passed by reference?
	 *         'reference_token'     => integer|false, // The stack pointer to the reference operator
	 *                                                 // or FALSE if the param is not passed by reference.
	 *         'variable_length'     => boolean,       // Is the param of variable length through use of `...` ?
	 *         'variadic_token'      => integer|false, // The stack pointer to the ... operator
	 *                                                 // or FALSE if the param is not variable length.
	 *         'type_hint'           => string,        // The type hint for the variable.
	 *         'type_hint_token'     => integer|false, // The stack pointer to the start of the type hint
	 *                                                 // or FALSE if there is no type hint.
	 *         'type_hint_end_token' => integer|false, // The stack pointer to the end of the type hint
	 *                                                 // or FALSE if there is no type hint.
	 *         'nullable_type'       => boolean,       // TRUE if the type is preceded by the nullability
	 *                                                 // operator.
	 *         'comma_token'         => integer|false, // The stack pointer to the comma after the param
	 *                                                 // or FALSE if this is the last param.
	 *        )
	 * </code>
	 *
	 * Parameters with default values have additional array indexes of:
	 *         'default'             => string,  // The full content of the default value.
	 *         'default_token'       => integer, // The stack pointer to the start of the default value.
	 *         'default_equal_token' => integer, // The stack pointer to the equals sign.
	 *
	 * Parameters declared using PHP 8 constructor property promotion, have these additional array indexes:
	 *         'property_visibility' => string,        // The property visibility as declared.
	 *         'visibility_token'    => integer|false, // The stack pointer to the visibility modifier token
	 *                                                 // or FALSE if the visibility is not explicitly declared.
	 *         'property_readonly'   => boolean,       // TRUE if the readonly keyword was found.
	 *         'readonly_token'      => integer,       // The stack pointer to the readonly modifier token.
	 *                                                 // This index will only be set if the property is readonly.
	 *
	 * ... and if the promoted property uses asymmetric visibility, these additional array indexes will also be available:
	 *         'set_visibility'       => string,       // The property set-visibility as declared.
	 *         'set_visibility_token' => integer,      // The stack pointer to the set-visibility modifier token.
	 *
	 * @param int $stackPtr The position in the stack of the function token
	 *                      to acquire the parameters for.
	 *
	 * @return array
	 * @throws \PHP_CodeSniffer\Exceptions\RuntimeException If the specified $stackPtr is not of
	 *                                                      type T_FUNCTION, T_CLOSURE, T_USE,
	 *                                                      or T_FN.
	 */
	public function getMethodParameters( $stackPtr ) {
		if ( $this->tokens[ $stackPtr ]['code'] !== T_FUNCTION
			&& $this->tokens[ $stackPtr ]['code'] !== T_CLOSURE
			&& $this->tokens[ $stackPtr ]['code'] !== T_USE
			&& $this->tokens[ $stackPtr ]['code'] !== T_FN
		) {
			throw new RuntimeException( '$stackPtr must be of type T_FUNCTION or T_CLOSURE or T_USE or T_FN' );
		}

		if ( $this->tokens[ $stackPtr ]['code'] === T_USE ) {
			$opener = $this->findNext( T_OPEN_PARENTHESIS, ( $stackPtr + 1 ) );
			if ( $opener === false || isset( $this->tokens[ $opener ]['parenthesis_owner'] ) === true ) {
				throw new RuntimeException( '$stackPtr was not a valid T_USE' );
			}
		} else {
			if ( isset( $this->tokens[ $stackPtr ]['parenthesis_opener'] ) === false ) {
				// Live coding or syntax error, so no params to find.
				return array();
			}

			$opener = $this->tokens[ $stackPtr ]['parenthesis_opener'];
		}

		if ( isset( $this->tokens[ $opener ]['parenthesis_closer'] ) === false ) {
			// Live coding or syntax error, so no params to find.
			return array();
		}

		$closer = $this->tokens[ $opener ]['parenthesis_closer'];

		$vars               = array();
		$currVar            = null;
		$paramStart         = ( $opener + 1 );
		$defaultStart       = null;
		$equalToken         = null;
		$paramCount         = 0;
		$hasAttributes      = false;
		$passByReference    = false;
		$referenceToken     = false;
		$variableLength     = false;
		$variadicToken      = false;
		$typeHint           = '';
		$typeHintToken      = false;
		$typeHintEndToken   = false;
		$nullableType       = false;
		$visibilityToken    = null;
		$setVisibilityToken = null;
		$readonlyToken      = null;

		for ( $i = $paramStart; $i <= $closer; $i++ ) {
			// Check to see if this token has a parenthesis or bracket opener. If it does
			// it's likely to be an array which might have arguments in it. This
			// could cause problems in our parsing below, so lets just skip to the
			// end of it.
			if ( $this->tokens[ $i ]['code'] !== T_TYPE_OPEN_PARENTHESIS
				&& isset( $this->tokens[ $i ]['parenthesis_opener'] ) === true
			) {
				// Don't do this if it's the close parenthesis for the method.
				if ( $i !== $this->tokens[ $i ]['parenthesis_closer'] ) {
					$i = $this->tokens[ $i ]['parenthesis_closer'];
					continue;
				}
			}

			if ( isset( $this->tokens[ $i ]['bracket_opener'] ) === true ) {
				if ( $i !== $this->tokens[ $i ]['bracket_closer'] ) {
					$i = $this->tokens[ $i ]['bracket_closer'];
					continue;
				}
			}

			switch ( $this->tokens[ $i ]['code'] ) {
				case T_ATTRIBUTE:
					$hasAttributes = true;

					// Skip to the end of the attribute.
					$i = $this->tokens[ $i ]['attribute_closer'];
					break;
				case T_BITWISE_AND:
					if ( $defaultStart === null ) {
						$passByReference = true;
						$referenceToken  = $i;
					}
					break;
				case T_VARIABLE:
					$currVar = $i;
					break;
				case T_ELLIPSIS:
					$variableLength = true;
					$variadicToken  = $i;
					break;
				case T_CALLABLE:
					if ( $typeHintToken === false ) {
						$typeHintToken = $i;
					}

					$typeHint        .= $this->tokens[ $i ]['content'];
					$typeHintEndToken = $i;
					break;
				case T_SELF:
				case T_PARENT:
				case T_STATIC:
					// Self and parent are valid, static invalid, but was probably intended as type hint.
					if ( isset( $defaultStart ) === false ) {
						if ( $typeHintToken === false ) {
							$typeHintToken = $i;
						}

						$typeHint        .= $this->tokens[ $i ]['content'];
						$typeHintEndToken = $i;
					}
					break;
				case T_STRING:
					// This is a string, so it may be a type hint, but it could
					// also be a constant used as a default value.
					$prevComma = false;
					for ( $t = $i; $t >= $opener; $t-- ) {
						if ( $this->tokens[ $t ]['code'] === T_COMMA ) {
							$prevComma = $t;
							break;
						}
					}

					if ( $prevComma !== false ) {
						$nextEquals = false;
						for ( $t = $prevComma; $t < $i; $t++ ) {
							if ( $this->tokens[ $t ]['code'] === T_EQUAL ) {
								$nextEquals = $t;
								break;
							}
						}

						if ( $nextEquals !== false ) {
							break;
						}
					}

					if ( $defaultStart === null ) {
						if ( $typeHintToken === false ) {
							$typeHintToken = $i;
						}

						$typeHint        .= $this->tokens[ $i ]['content'];
						$typeHintEndToken = $i;
					}
					break;
				case T_NAMESPACE:
				case T_NS_SEPARATOR:
				case T_TYPE_UNION:
				case T_TYPE_INTERSECTION:
				case T_TYPE_OPEN_PARENTHESIS:
				case T_TYPE_CLOSE_PARENTHESIS:
				case T_FALSE:
				case T_TRUE:
				case T_NULL:
					// Part of a type hint or default value.
					if ( $defaultStart === null ) {
						if ( $typeHintToken === false ) {
							$typeHintToken = $i;
						}

						$typeHint        .= $this->tokens[ $i ]['content'];
						$typeHintEndToken = $i;
					}
					break;
				case T_NULLABLE:
					if ( $defaultStart === null ) {
						$nullableType     = true;
						$typeHint        .= $this->tokens[ $i ]['content'];
						$typeHintEndToken = $i;
					}
					break;
				case T_PUBLIC:
				case T_PROTECTED:
				case T_PRIVATE:
					if ( $defaultStart === null ) {
						$visibilityToken = $i;
					}
					break;
				case T_PUBLIC_SET:
				case T_PROTECTED_SET:
				case T_PRIVATE_SET:
					if ( $defaultStart === null ) {
						$setVisibilityToken = $i;
					}
					break;
				case T_READONLY:
					if ( $defaultStart === null ) {
						$readonlyToken = $i;
					}
					break;
				case T_CLOSE_PARENTHESIS:
				case T_COMMA:
					// If it's null, then there must be no parameters for this
					// method.
					if ( $currVar === null ) {
						continue 2;
					}

					$vars[ $paramCount ]            = array();
					$vars[ $paramCount ]['token']   = $currVar;
					$vars[ $paramCount ]['name']    = $this->tokens[ $currVar ]['content'];
					$vars[ $paramCount ]['content'] = trim( $this->getTokensAsString( $paramStart, ( $i - $paramStart ) ) );

					if ( $defaultStart !== null ) {
						$vars[ $paramCount ]['default']             = trim( $this->getTokensAsString( $defaultStart, ( $i - $defaultStart ) ) );
						$vars[ $paramCount ]['default_token']       = $defaultStart;
						$vars[ $paramCount ]['default_equal_token'] = $equalToken;
					}

					$vars[ $paramCount ]['has_attributes']      = $hasAttributes;
					$vars[ $paramCount ]['pass_by_reference']   = $passByReference;
					$vars[ $paramCount ]['reference_token']     = $referenceToken;
					$vars[ $paramCount ]['variable_length']     = $variableLength;
					$vars[ $paramCount ]['variadic_token']      = $variadicToken;
					$vars[ $paramCount ]['type_hint']           = $typeHint;
					$vars[ $paramCount ]['type_hint_token']     = $typeHintToken;
					$vars[ $paramCount ]['type_hint_end_token'] = $typeHintEndToken;
					$vars[ $paramCount ]['nullable_type']       = $nullableType;

					if ( $visibilityToken !== null || $setVisibilityToken !== null || $readonlyToken !== null ) {
						$vars[ $paramCount ]['property_visibility'] = 'public';
						$vars[ $paramCount ]['visibility_token']    = false;

						if ( $visibilityToken !== null ) {
							$vars[ $paramCount ]['property_visibility'] = $this->tokens[ $visibilityToken ]['content'];
							$vars[ $paramCount ]['visibility_token']    = $visibilityToken;
						}

						if ( $setVisibilityToken !== null ) {
							$vars[ $paramCount ]['set_visibility']       = $this->tokens[ $setVisibilityToken ]['content'];
							$vars[ $paramCount ]['set_visibility_token'] = $setVisibilityToken;
						}

						$vars[ $paramCount ]['property_readonly'] = false;
						if ( $readonlyToken !== null ) {
							$vars[ $paramCount ]['property_readonly'] = true;
							$vars[ $paramCount ]['readonly_token']    = $readonlyToken;
						}
					}

					if ( $this->tokens[ $i ]['code'] === T_COMMA ) {
						$vars[ $paramCount ]['comma_token'] = $i;
					} else {
						$vars[ $paramCount ]['comma_token'] = false;
					}

					// Reset the vars, as we are about to process the next parameter.
					$currVar            = null;
					$paramStart         = ( $i + 1 );
					$defaultStart       = null;
					$equalToken         = null;
					$hasAttributes      = false;
					$passByReference    = false;
					$referenceToken     = false;
					$variableLength     = false;
					$variadicToken      = false;
					$typeHint           = '';
					$typeHintToken      = false;
					$typeHintEndToken   = false;
					$nullableType       = false;
					$visibilityToken    = null;
					$setVisibilityToken = null;
					$readonlyToken      = null;

					++$paramCount;
					break;
				case T_EQUAL:
					$defaultStart = $this->findNext( Tokens::$emptyTokens, ( $i + 1 ), null, true );
					$equalToken   = $i;
					break;
			}//end switch
		}//end for

		return $vars;
	}//end getMethodParameters()


	/**
	 * Returns the visibility and implementation properties of a method.
	 *
	 * The format of the return value is:
	 * <code>
	 *   array(
	 *    'scope'                 => string,        // Public, private, or protected
	 *    'scope_specified'       => boolean,       // TRUE if the scope keyword was found.
	 *    'return_type'           => string,        // The return type of the method.
	 *    'return_type_token'     => integer|false, // The stack pointer to the start of the return type
	 *                                              // or FALSE if there is no return type.
	 *    'return_type_end_token' => integer|false, // The stack pointer to the end of the return type
	 *                                              // or FALSE if there is no return type.
	 *    'nullable_return_type'  => boolean,       // TRUE if the return type is preceded by the
	 *                                              // nullability operator.
	 *    'is_abstract'           => boolean,       // TRUE if the abstract keyword was found.
	 *    'is_final'              => boolean,       // TRUE if the final keyword was found.
	 *    'is_static'             => boolean,       // TRUE if the static keyword was found.
	 *    'has_body'              => boolean,       // TRUE if the method has a body
	 *   );
	 * </code>
	 *
	 * @param int $stackPtr The position in the stack of the function token to
	 *                      acquire the properties for.
	 *
	 * @return array
	 * @throws \PHP_CodeSniffer\Exceptions\RuntimeException If the specified position is not a
	 *                                                      T_FUNCTION, T_CLOSURE, or T_FN token.
	 */
	public function getMethodProperties( $stackPtr ) {
		if ( $this->tokens[ $stackPtr ]['code'] !== T_FUNCTION
			&& $this->tokens[ $stackPtr ]['code'] !== T_CLOSURE
			&& $this->tokens[ $stackPtr ]['code'] !== T_FN
		) {
			throw new RuntimeException( '$stackPtr must be of type T_FUNCTION or T_CLOSURE or T_FN' );
		}

		if ( $this->tokens[ $stackPtr ]['code'] === T_FUNCTION ) {
			$valid = array(
				T_PUBLIC      => T_PUBLIC,
				T_PRIVATE     => T_PRIVATE,
				T_PROTECTED   => T_PROTECTED,
				T_STATIC      => T_STATIC,
				T_FINAL       => T_FINAL,
				T_ABSTRACT    => T_ABSTRACT,
				T_WHITESPACE  => T_WHITESPACE,
				T_COMMENT     => T_COMMENT,
				T_DOC_COMMENT => T_DOC_COMMENT,
			);
		} else {
			$valid = array(
				T_STATIC      => T_STATIC,
				T_WHITESPACE  => T_WHITESPACE,
				T_COMMENT     => T_COMMENT,
				T_DOC_COMMENT => T_DOC_COMMENT,
			);
		}

		$scope          = 'public';
		$scopeSpecified = false;
		$isAbstract     = false;
		$isFinal        = false;
		$isStatic       = false;

		for ( $i = ( $stackPtr - 1 ); $i > 0; $i-- ) {
			if ( isset( $valid[ $this->tokens[ $i ]['code'] ] ) === false ) {
				break;
			}

			switch ( $this->tokens[ $i ]['code'] ) {
				case T_PUBLIC:
					$scope          = 'public';
					$scopeSpecified = true;
					break;
				case T_PRIVATE:
					$scope          = 'private';
					$scopeSpecified = true;
					break;
				case T_PROTECTED:
					$scope          = 'protected';
					$scopeSpecified = true;
					break;
				case T_ABSTRACT:
					$isAbstract = true;
					break;
				case T_FINAL:
					$isFinal = true;
					break;
				case T_STATIC:
					$isStatic = true;
					break;
			}//end switch
		}//end for

		$returnType         = '';
		$returnTypeToken    = false;
		$returnTypeEndToken = false;
		$nullableReturnType = false;
		$hasBody            = true;

		if ( isset( $this->tokens[ $stackPtr ]['parenthesis_closer'] ) === true ) {
			$scopeOpener = null;
			if ( isset( $this->tokens[ $stackPtr ]['scope_opener'] ) === true ) {
				$scopeOpener = $this->tokens[ $stackPtr ]['scope_opener'];
			}

			$valid = array(
				T_STRING                 => T_STRING,
				T_CALLABLE               => T_CALLABLE,
				T_SELF                   => T_SELF,
				T_PARENT                 => T_PARENT,
				T_STATIC                 => T_STATIC,
				T_FALSE                  => T_FALSE,
				T_TRUE                   => T_TRUE,
				T_NULL                   => T_NULL,
				T_NAMESPACE              => T_NAMESPACE,
				T_NS_SEPARATOR           => T_NS_SEPARATOR,
				T_TYPE_UNION             => T_TYPE_UNION,
				T_TYPE_INTERSECTION      => T_TYPE_INTERSECTION,
				T_TYPE_OPEN_PARENTHESIS  => T_TYPE_OPEN_PARENTHESIS,
				T_TYPE_CLOSE_PARENTHESIS => T_TYPE_CLOSE_PARENTHESIS,
			);

			for ( $i = $this->tokens[ $stackPtr ]['parenthesis_closer']; $i < $this->numTokens; $i++ ) {
				if ( ( $scopeOpener === null && $this->tokens[ $i ]['code'] === T_SEMICOLON )
					|| ( $scopeOpener !== null && $i === $scopeOpener )
				) {
					// End of function definition.
					break;
				}

				if ( $this->tokens[ $i ]['code'] === T_USE ) {
					// Skip over closure use statements.
					for ($j = ( $i + 1 ); $j < $this->numTokens && isset( Tokens::$emptyTokens[ $this->tokens[ $j ]['code'] ] ) === true; $j++);
					if ( $this->tokens[ $j ]['code'] === T_OPEN_PARENTHESIS ) {
						if ( isset( $this->tokens[ $j ]['parenthesis_closer'] ) === false ) {
							// Live coding/parse error, stop parsing.
							break;
						}

						$i = $this->tokens[ $j ]['parenthesis_closer'];
						continue;
					}
				}

				if ( $this->tokens[ $i ]['code'] === T_NULLABLE ) {
					$nullableReturnType = true;
				}

				if ( isset( $valid[ $this->tokens[ $i ]['code'] ] ) === true ) {
					if ( $returnTypeToken === false ) {
						$returnTypeToken = $i;
					}

					$returnType        .= $this->tokens[ $i ]['content'];
					$returnTypeEndToken = $i;
				}
			}//end for

			if ( $this->tokens[ $stackPtr ]['code'] === T_FN ) {
				$bodyToken = T_FN_ARROW;
			} else {
				$bodyToken = T_OPEN_CURLY_BRACKET;
			}

			$end     = $this->findNext( array( $bodyToken, T_SEMICOLON ), $this->tokens[ $stackPtr ]['parenthesis_closer'] );
			$hasBody = $this->tokens[ $end ]['code'] === $bodyToken;
		}//end if

		if ( $returnType !== '' && $nullableReturnType === true ) {
			$returnType = '?' . $returnType;
		}

		return array(
			'scope'                 => $scope,
			'scope_specified'       => $scopeSpecified,
			'return_type'           => $returnType,
			'return_type_token'     => $returnTypeToken,
			'return_type_end_token' => $returnTypeEndToken,
			'nullable_return_type'  => $nullableReturnType,
			'is_abstract'           => $isAbstract,
			'is_final'              => $isFinal,
			'is_static'             => $isStatic,
			'has_body'              => $hasBody,
		);
	}//end getMethodProperties()


	/**
	 * Returns the visibility and implementation properties of a class member var.
	 *
	 * The format of the return value is:
	 *
	 * <code>
	 *   array(
	 *    'scope'           => string,        // Public, private, or protected.
	 *    'scope_specified' => boolean,       // TRUE if the scope was explicitly specified.
	 *    'set_scope'       => string|false,  // Scope for asymmetric visibility.
	 *                                        // Either public, private, or protected or
	 *                                        // FALSE if no set scope is specified.
	 *    'is_static'       => boolean,       // TRUE if the static keyword was found.
	 *    'is_readonly'     => boolean,       // TRUE if the readonly keyword was found.
	 *    'is_final'        => boolean,       // TRUE if the final keyword was found.
	 *    'type'            => string,        // The type of the var (empty if no type specified).
	 *    'type_token'      => integer|false, // The stack pointer to the start of the type
	 *                                        // or FALSE if there is no type.
	 *    'type_end_token'  => integer|false, // The stack pointer to the end of the type
	 *                                        // or FALSE if there is no type.
	 *    'nullable_type'   => boolean,       // TRUE if the type is preceded by the nullability
	 *                                        // operator.
	 *   );
	 * </code>
	 *
	 * @param int $stackPtr The position in the stack of the T_VARIABLE token to
	 *                      acquire the properties for.
	 *
	 * @return array
	 * @throws \PHP_CodeSniffer\Exceptions\RuntimeException If the specified position is not a
	 *                                                      T_VARIABLE token, or if the position is not
	 *                                                      a class member variable.
	 */
	public function getMemberProperties( $stackPtr ) {
		if ( $this->tokens[ $stackPtr ]['code'] !== T_VARIABLE ) {
			throw new RuntimeException( '$stackPtr must be of type T_VARIABLE' );
		}

		$conditions = array_keys( $this->tokens[ $stackPtr ]['conditions'] );
		$ptr        = array_pop( $conditions );
		if ( isset( $this->tokens[ $ptr ] ) === false
			|| ( $this->tokens[ $ptr ]['code'] !== T_CLASS
			&& $this->tokens[ $ptr ]['code'] !== T_ANON_CLASS
			&& $this->tokens[ $ptr ]['code'] !== T_TRAIT )
		) {
			if ( isset( $this->tokens[ $ptr ] ) === true
				&& ( $this->tokens[ $ptr ]['code'] === T_INTERFACE
				|| $this->tokens[ $ptr ]['code'] === T_ENUM )
			) {
				// T_VARIABLEs in interfaces/enums can actually be method arguments
				// but they won't be seen as being inside the method because there
				// are no scope openers and closers for abstract methods. If it is in
				// parentheses, we can be pretty sure it is a method argument.
				if ( isset( $this->tokens[ $stackPtr ]['nested_parenthesis'] ) === false
					|| empty( $this->tokens[ $stackPtr ]['nested_parenthesis'] ) === true
				) {
					$error = 'Possible parse error: %ss may not include member vars';
					$code  = sprintf( 'Internal.ParseError.%sHasMemberVar', ucfirst( $this->tokens[ $ptr ]['content'] ) );
					$data  = array( strtolower( $this->tokens[ $ptr ]['content'] ) );
					$this->addWarning( $error, $stackPtr, $code, $data );
					return array();
				}
			} else {
				throw new RuntimeException( '$stackPtr is not a class member var' );
			}
		}//end if

		// Make sure it's not a method parameter.
		if ( empty( $this->tokens[ $stackPtr ]['nested_parenthesis'] ) === false ) {
			$parenthesis = array_keys( $this->tokens[ $stackPtr ]['nested_parenthesis'] );
			$deepestOpen = array_pop( $parenthesis );
			if ( $deepestOpen > $ptr
				&& isset( $this->tokens[ $deepestOpen ]['parenthesis_owner'] ) === true
				&& $this->tokens[ $this->tokens[ $deepestOpen ]['parenthesis_owner'] ]['code'] === T_FUNCTION
			) {
				throw new RuntimeException( '$stackPtr is not a class member var' );
			}
		}

		$valid = array(
			T_STATIC   => T_STATIC,
			T_VAR      => T_VAR,
			T_READONLY => T_READONLY,
			T_FINAL    => T_FINAL,
		);

		$valid += Tokens::$scopeModifiers;
		$valid += Tokens::$emptyTokens;

		$scope          = 'public';
		$scopeSpecified = false;
		$setScope       = false;
		$isStatic       = false;
		$isReadonly     = false;
		$isFinal        = false;

		$startOfStatement = $this->findPrevious(
			array(
				T_SEMICOLON,
				T_OPEN_CURLY_BRACKET,
				T_CLOSE_CURLY_BRACKET,
				T_ATTRIBUTE_END,
			),
			( $stackPtr - 1 )
		);

		for ( $i = ( $startOfStatement + 1 ); $i < $stackPtr; $i++ ) {
			if ( isset( $valid[ $this->tokens[ $i ]['code'] ] ) === false ) {
				break;
			}

			switch ( $this->tokens[ $i ]['code'] ) {
				case T_PUBLIC:
					$scope          = 'public';
					$scopeSpecified = true;
					break;
				case T_PRIVATE:
					$scope          = 'private';
					$scopeSpecified = true;
					break;
				case T_PROTECTED:
					$scope          = 'protected';
					$scopeSpecified = true;
					break;
				case T_PUBLIC_SET:
					$setScope = 'public';
					break;
				case T_PROTECTED_SET:
					$setScope = 'protected';
					break;
				case T_PRIVATE_SET:
					$setScope = 'private';
					break;
				case T_STATIC:
					$isStatic = true;
					break;
				case T_READONLY:
					$isReadonly = true;
					break;
				case T_FINAL:
					$isFinal = true;
					break;
			}//end switch
		}//end for

		$type         = '';
		$typeToken    = false;
		$typeEndToken = false;
		$nullableType = false;

		if ( $i < $stackPtr ) {
			// We've found a type.
			$valid = array(
				T_STRING                 => T_STRING,
				T_CALLABLE               => T_CALLABLE,
				T_SELF                   => T_SELF,
				T_PARENT                 => T_PARENT,
				T_FALSE                  => T_FALSE,
				T_TRUE                   => T_TRUE,
				T_NULL                   => T_NULL,
				T_NAMESPACE              => T_NAMESPACE,
				T_NS_SEPARATOR           => T_NS_SEPARATOR,
				T_TYPE_UNION             => T_TYPE_UNION,
				T_TYPE_INTERSECTION      => T_TYPE_INTERSECTION,
				T_TYPE_OPEN_PARENTHESIS  => T_TYPE_OPEN_PARENTHESIS,
				T_TYPE_CLOSE_PARENTHESIS => T_TYPE_CLOSE_PARENTHESIS,
			);

			for ( $i; $i < $stackPtr; $i++ ) {
				if ( $this->tokens[ $i ]['code'] === T_VARIABLE ) {
					// Hit another variable in a group definition.
					break;
				}

				if ( $this->tokens[ $i ]['code'] === T_NULLABLE ) {
					$nullableType = true;
				}

				if ( isset( $valid[ $this->tokens[ $i ]['code'] ] ) === true ) {
					$typeEndToken = $i;
					if ( $typeToken === false ) {
						$typeToken = $i;
					}

					$type .= $this->tokens[ $i ]['content'];
				}
			}

			if ( $type !== '' && $nullableType === true ) {
				$type = '?' . $type;
			}
		}//end if

		return array(
			'scope'           => $scope,
			'scope_specified' => $scopeSpecified,
			'set_scope'       => $setScope,
			'is_static'       => $isStatic,
			'is_readonly'     => $isReadonly,
			'is_final'        => $isFinal,
			'type'            => $type,
			'type_token'      => $typeToken,
			'type_end_token'  => $typeEndToken,
			'nullable_type'   => $nullableType,
		);
	}//end getMemberProperties()


	/**
	 * Returns the visibility and implementation properties of a class.
	 *
	 * The format of the return value is:
	 * <code>
	 *   array(
	 *    'is_abstract' => boolean, // TRUE if the abstract keyword was found.
	 *    'is_final'    => boolean, // TRUE if the final keyword was found.
	 *    'is_readonly' => boolean, // TRUE if the readonly keyword was found.
	 *   );
	 * </code>
	 *
	 * @param int $stackPtr The position in the stack of the T_CLASS token to
	 *                      acquire the properties for.
	 *
	 * @return array
	 * @throws \PHP_CodeSniffer\Exceptions\RuntimeException If the specified position is not a
	 *                                                      T_CLASS token.
	 */
	public function getClassProperties( $stackPtr ) {
		if ( $this->tokens[ $stackPtr ]['code'] !== T_CLASS ) {
			throw new RuntimeException( '$stackPtr must be of type T_CLASS' );
		}

		$valid = array(
			T_FINAL       => T_FINAL,
			T_ABSTRACT    => T_ABSTRACT,
			T_READONLY    => T_READONLY,
			T_WHITESPACE  => T_WHITESPACE,
			T_COMMENT     => T_COMMENT,
			T_DOC_COMMENT => T_DOC_COMMENT,
		);

		$isAbstract = false;
		$isFinal    = false;
		$isReadonly = false;

		for ( $i = ( $stackPtr - 1 ); $i > 0; $i-- ) {
			if ( isset( $valid[ $this->tokens[ $i ]['code'] ] ) === false ) {
				break;
			}

			switch ( $this->tokens[ $i ]['code'] ) {
				case T_ABSTRACT:
					$isAbstract = true;
					break;

				case T_FINAL:
					$isFinal = true;
					break;

				case T_READONLY:
					$isReadonly = true;
					break;
			}
		}//end for

		return array(
			'is_abstract' => $isAbstract,
			'is_final'    => $isFinal,
			'is_readonly' => $isReadonly,
		);
	}//end getClassProperties()


	/**
	 * Determine if the passed token is a reference operator.
	 *
	 * Returns true if the specified token position represents a reference.
	 * Returns false if the token represents a bitwise operator.
	 *
	 * @param int $stackPtr The position of the T_BITWISE_AND token.
	 *
	 * @return boolean
	 */
	public function isReference( $stackPtr ) {
		if ( $this->tokens[ $stackPtr ]['code'] !== T_BITWISE_AND ) {
			return false;
		}

		$tokenBefore = $this->findPrevious(
			Tokens::$emptyTokens,
			( $stackPtr - 1 ),
			null,
			true
		);

		if ( $this->tokens[ $tokenBefore ]['code'] === T_FUNCTION
			|| $this->tokens[ $tokenBefore ]['code'] === T_CLOSURE
			|| $this->tokens[ $tokenBefore ]['code'] === T_FN
		) {
			// Function returns a reference.
			return true;
		}

		if ( $this->tokens[ $tokenBefore ]['code'] === T_DOUBLE_ARROW ) {
			// Inside a foreach loop or array assignment, this is a reference.
			return true;
		}

		if ( $this->tokens[ $tokenBefore ]['code'] === T_AS ) {
			// Inside a foreach loop, this is a reference.
			return true;
		}

		if ( isset( Tokens::$assignmentTokens[ $this->tokens[ $tokenBefore ]['code'] ] ) === true ) {
			// This is directly after an assignment. It's a reference. Even if
			// it is part of an operation, the other tests will handle it.
			return true;
		}

		$tokenAfter = $this->findNext(
			Tokens::$emptyTokens,
			( $stackPtr + 1 ),
			null,
			true
		);

		if ( $this->tokens[ $tokenAfter ]['code'] === T_NEW ) {
			return true;
		}

		if ( isset( $this->tokens[ $stackPtr ]['nested_parenthesis'] ) === true ) {
			$brackets    = $this->tokens[ $stackPtr ]['nested_parenthesis'];
			$lastBracket = array_pop( $brackets );
			if ( isset( $this->tokens[ $lastBracket ]['parenthesis_owner'] ) === true ) {
				$owner = $this->tokens[ $this->tokens[ $lastBracket ]['parenthesis_owner'] ];
				if ( $owner['code'] === T_FUNCTION
					|| $owner['code'] === T_CLOSURE
					|| $owner['code'] === T_FN
				) {
					$params = $this->getMethodParameters( $this->tokens[ $lastBracket ]['parenthesis_owner'] );
					foreach ( $params as $param ) {
						if ( $param['reference_token'] === $stackPtr ) {
							// Function parameter declared to be passed by reference.
							return true;
						}
					}
				}//end if
			} else {
				$prev = false;
				for ( $t = ( $this->tokens[ $lastBracket ]['parenthesis_opener'] - 1 ); $t >= 0; $t-- ) {
					if ( $this->tokens[ $t ]['code'] !== T_WHITESPACE ) {
						$prev = $t;
						break;
					}
				}

				if ( $prev !== false && $this->tokens[ $prev ]['code'] === T_USE ) {
					// Closure use by reference.
					return true;
				}
			}//end if
		}//end if

		// Pass by reference in function calls and assign by reference in arrays.
		if ( $this->tokens[ $tokenBefore ]['code'] === T_OPEN_PARENTHESIS
			|| $this->tokens[ $tokenBefore ]['code'] === T_COMMA
			|| $this->tokens[ $tokenBefore ]['code'] === T_OPEN_SHORT_ARRAY
		) {
			if ( $this->tokens[ $tokenAfter ]['code'] === T_VARIABLE ) {
				return true;
			} else {
				$skip   = Tokens::$emptyTokens;
				$skip[] = T_NS_SEPARATOR;
				$skip[] = T_SELF;
				$skip[] = T_PARENT;
				$skip[] = T_STATIC;
				$skip[] = T_STRING;
				$skip[] = T_NAMESPACE;
				$skip[] = T_DOUBLE_COLON;

				$nextSignificantAfter = $this->findNext(
					$skip,
					( $stackPtr + 1 ),
					null,
					true
				);
				if ( $this->tokens[ $nextSignificantAfter ]['code'] === T_VARIABLE ) {
					return true;
				}
			}//end if
		}//end if

		return false;
	}//end isReference()


	/**
	 * Returns the content of the tokens from the specified start position in
	 * the token stack for the specified length.
	 *
	 * @param int  $start       The position to start from in the token stack.
	 * @param int  $length      The length of tokens to traverse from the start pos.
	 * @param bool $origContent Whether the original content or the tab replaced
	 *                          content should be used.
	 *
	 * @return string The token contents.
	 * @throws \PHP_CodeSniffer\Exceptions\RuntimeException If the specified position does not exist.
	 */
	public function getTokensAsString( $start, $length, $origContent = false ) {
		if ( is_int( $start ) === false || isset( $this->tokens[ $start ] ) === false ) {
			throw new RuntimeException( 'The $start position for getTokensAsString() must exist in the token stack' );
		}

		if ( is_int( $length ) === false || $length <= 0 ) {
			return '';
		}

		$str = '';
		$end = ( $start + $length );
		if ( $end > $this->numTokens ) {
			$end = $this->numTokens;
		}

		for ( $i = $start; $i < $end; $i++ ) {
			// If tabs are being converted to spaces by the tokeniser, the
			// original content should be used instead of the converted content.
			if ( $origContent === true && isset( $this->tokens[ $i ]['orig_content'] ) === true ) {
				$str .= $this->tokens[ $i ]['orig_content'];
			} else {
				$str .= $this->tokens[ $i ]['content'];
			}
		}

		return $str;
	}//end getTokensAsString()


	/**
	 * Returns the position of the previous specified token(s).
	 *
	 * If a value is specified, the previous token of the specified type(s)
	 * containing the specified value will be returned.
	 *
	 * Returns false if no token can be found.
	 *
	 * @param int|string|array $types   The type(s) of tokens to search for.
	 * @param int              $start   The position to start searching from in the
	 *                                  token stack.
	 * @param int|null         $end     The end position to fail if no token is found.
	 *                                  if not specified or null, end will default to
	 *                                  the start of the token stack.
	 * @param bool             $exclude If true, find the previous token that is NOT of
	 *                                  the types specified in $types.
	 * @param string|null      $value   The value that the token(s) must be equal to.
	 *                                  If value is omitted, tokens with any value will
	 *                                  be returned.
	 * @param bool             $local   If true, tokens outside the current statement
	 *                                  will not be checked. IE. checking will stop
	 *                                  at the previous semicolon found.
	 *
	 * @return int|false
	 * @see    findNext()
	 */
	public function findPrevious(
		$types,
		$start,
		$end = null,
		$exclude = false,
		$value = null,
		$local = false
	) {
		$types = (array) $types;

		if ( $end === null ) {
			$end = 0;
		}

		for ( $i = $start; $i >= $end; $i-- ) {
			$found = (bool) $exclude;
			foreach ( $types as $type ) {
				if ( $this->tokens[ $i ]['code'] === $type ) {
					$found = ! $exclude;
					break;
				}
			}

			if ( $found === true ) {
				if ( $value === null ) {
					return $i;
				} elseif ( $this->tokens[ $i ]['content'] === $value ) {
					return $i;
				}
			}

			if ( $local === true ) {
				if ( isset( $this->tokens[ $i ]['scope_opener'] ) === true
					&& $i === $this->tokens[ $i ]['scope_closer']
				) {
					$i = $this->tokens[ $i ]['scope_opener'];
				} elseif ( isset( $this->tokens[ $i ]['bracket_opener'] ) === true
					&& $i === $this->tokens[ $i ]['bracket_closer']
				) {
					$i = $this->tokens[ $i ]['bracket_opener'];
				} elseif ( isset( $this->tokens[ $i ]['parenthesis_opener'] ) === true
					&& $i === $this->tokens[ $i ]['parenthesis_closer']
				) {
					$i = $this->tokens[ $i ]['parenthesis_opener'];
				} elseif ( $this->tokens[ $i ]['code'] === T_SEMICOLON ) {
					break;
				}
			}
		}//end for

		return false;
	}//end findPrevious()


	/**
	 * Returns the position of the next specified token(s).
	 *
	 * If a value is specified, the next token of the specified type(s)
	 * containing the specified value will be returned.
	 *
	 * Returns false if no token can be found.
	 *
	 * @param int|string|array $types   The type(s) of tokens to search for.
	 * @param int              $start   The position to start searching from in the
	 *                                  token stack.
	 * @param int|null         $end     The end position to fail if no token is found.
	 *                                  if not specified or null, end will default to
	 *                                  the end of the token stack.
	 * @param bool             $exclude If true, find the next token that is NOT of
	 *                                  a type specified in $types.
	 * @param string|null      $value   The value that the token(s) must be equal to.
	 *                                  If value is omitted, tokens with any value will
	 *                                  be returned.
	 * @param bool             $local   If true, tokens outside the current statement
	 *                                  will not be checked. i.e., checking will stop
	 *                                  at the next semicolon found.
	 *
	 * @return int|false
	 * @see    findPrevious()
	 */
	public function findNext(
		$types,
		$start,
		$end = null,
		$exclude = false,
		$value = null,
		$local = false
	) {
		$types = (array) $types;

		if ( $end === null || $end > $this->numTokens ) {
			$end = $this->numTokens;
		}

		for ( $i = $start; $i < $end; $i++ ) {
			$found = (bool) $exclude;
			foreach ( $types as $type ) {
				if ( $this->tokens[ $i ]['code'] === $type ) {
					$found = ! $exclude;
					break;
				}
			}

			if ( $found === true ) {
				if ( $value === null ) {
					return $i;
				} elseif ( $this->tokens[ $i ]['content'] === $value ) {
					return $i;
				}
			}

			if ( $local === true && $this->tokens[ $i ]['code'] === T_SEMICOLON ) {
				break;
			}
		}//end for

		return false;
	}//end findNext()


	/**
	 * Returns the position of the first non-whitespace token in a statement.
	 *
	 * @param int              $start  The position to start searching from in the token stack.
	 * @param int|string|array $ignore Token types that should not be considered stop points.
	 *
	 * @return int
	 */
	public function findStartOfStatement( $start, $ignore = null ) {
		$startTokens                         = Tokens::$blockOpeners;
		$startTokens[ T_OPEN_SHORT_ARRAY ]   = true;
		$startTokens[ T_OPEN_TAG ]           = true;
		$startTokens[ T_OPEN_TAG_WITH_ECHO ] = true;

		$endTokens = array(
			T_CLOSE_TAG    => true,
			T_COLON        => true,
			T_COMMA        => true,
			T_DOUBLE_ARROW => true,
			T_MATCH_ARROW  => true,
			T_SEMICOLON    => true,
		);

		if ( $ignore !== null ) {
			$ignore = (array) $ignore;
			foreach ( $ignore as $code ) {
				if ( isset( $startTokens[ $code ] ) === true ) {
					unset( $startTokens[ $code ] );
				}

				if ( isset( $endTokens[ $code ] ) === true ) {
					unset( $endTokens[ $code ] );
				}
			}
		}

		// If the start token is inside the case part of a match expression,
		// find the start of the condition. If it's in the statement part, find
		// the token that comes after the match arrow.
		if ( empty( $this->tokens[ $start ]['conditions'] ) === false ) {
			$conditions         = $this->tokens[ $start ]['conditions'];
			$lastConditionOwner = end( $conditions );
			$matchExpression    = key( $conditions );

			if ( $lastConditionOwner === T_MATCH
				// Check if the $start token is at the same parentheses nesting level as the match token.
				&& ( ( empty( $this->tokens[ $matchExpression ]['nested_parenthesis'] ) === true
				&& empty( $this->tokens[ $start ]['nested_parenthesis'] ) === true )
				|| ( ( empty( $this->tokens[ $matchExpression ]['nested_parenthesis'] ) === false
				&& empty( $this->tokens[ $start ]['nested_parenthesis'] ) === false )
				&& $this->tokens[ $matchExpression ]['nested_parenthesis'] === $this->tokens[ $start ]['nested_parenthesis'] ) )
			) {
				// Walk back to the previous match arrow (if it exists).
				$lastComma          = null;
				$inNestedExpression = false;
				for ( $prevMatch = $start; $prevMatch > $this->tokens[ $matchExpression ]['scope_opener']; $prevMatch-- ) {
					if ( $prevMatch !== $start && $this->tokens[ $prevMatch ]['code'] === T_MATCH_ARROW ) {
						break;
					}

					if ( $prevMatch !== $start && $this->tokens[ $prevMatch ]['code'] === T_COMMA ) {
						$lastComma = $prevMatch;
						continue;
					}

					// Skip nested statements.
					if ( isset( $this->tokens[ $prevMatch ]['bracket_opener'] ) === true
						&& $prevMatch === $this->tokens[ $prevMatch ]['bracket_closer']
					) {
						$prevMatch = $this->tokens[ $prevMatch ]['bracket_opener'];
						continue;
					}

					if ( isset( $this->tokens[ $prevMatch ]['parenthesis_opener'] ) === true
						&& $prevMatch === $this->tokens[ $prevMatch ]['parenthesis_closer']
					) {
						$prevMatch = $this->tokens[ $prevMatch ]['parenthesis_opener'];
						continue;
					}

					// Stop if we're _within_ a nested short array statement, which may contain comma's too.
					// No need to deal with parentheses, those are handled above via the `nested_parenthesis` checks.
					if ( isset( $this->tokens[ $prevMatch ]['bracket_opener'] ) === true
						&& $this->tokens[ $prevMatch ]['bracket_closer'] > $start
					) {
						$inNestedExpression = true;
						break;
					}
				}//end for

				if ( $inNestedExpression === false ) {
					// $prevMatch will now either be the scope opener or a match arrow.
					// If it is the scope opener, go the first non-empty token after. $start will have been part of the first condition.
					if ( $prevMatch <= $this->tokens[ $matchExpression ]['scope_opener'] ) {
						// We're before the arrow in the first case.
						$next = $this->findNext( Tokens::$emptyTokens, ( $this->tokens[ $matchExpression ]['scope_opener'] + 1 ), null, true );
						if ( $next === false ) {
							// Shouldn't be possible.
							return $start;
						}

						return $next;
					}

					// Okay, so we found a match arrow.
					// If $start was part of the "next" condition, the last comma will be set.
					// Otherwise, $start must have been part of a return expression.
					if ( isset( $lastComma ) === true && $lastComma > $prevMatch ) {
						$prevMatch = $lastComma;
					}

					// In both cases, go to the first non-empty token after.
					$next = $this->findNext( Tokens::$emptyTokens, ( $prevMatch + 1 ), null, true );
					if ( $next === false ) {
						// Shouldn't be possible.
						return $start;
					}

					return $next;
				}//end if
			}//end if
		}//end if

		$lastNotEmpty = $start;

		// If we are starting at a token that ends a scope block, skip to
		// the start and continue from there.
		// If we are starting at a token that ends a statement, skip this
		// token so we find the true start of the statement.
		while ( isset( $endTokens[ $this->tokens[ $start ]['code'] ] ) === true
			|| ( isset( $this->tokens[ $start ]['scope_condition'] ) === true
			&& $start === $this->tokens[ $start ]['scope_closer'] )
		) {
			if ( isset( $this->tokens[ $start ]['scope_condition'] ) === true ) {
				$start = $this->tokens[ $start ]['scope_condition'];
			} else {
				--$start;
			}
		}

		for ( $i = $start; $i >= 0; $i-- ) {
			if ( isset( $startTokens[ $this->tokens[ $i ]['code'] ] ) === true
				|| isset( $endTokens[ $this->tokens[ $i ]['code'] ] ) === true
			) {
				// Found the end of the previous statement.
				return $lastNotEmpty;
			}

			if ( isset( $this->tokens[ $i ]['scope_opener'] ) === true
				&& $i === $this->tokens[ $i ]['scope_closer']
				&& $this->tokens[ $i ]['code'] !== T_CLOSE_PARENTHESIS
				&& $this->tokens[ $i ]['code'] !== T_END_NOWDOC
				&& $this->tokens[ $i ]['code'] !== T_END_HEREDOC
				&& $this->tokens[ $i ]['code'] !== T_BREAK
				&& $this->tokens[ $i ]['code'] !== T_RETURN
				&& $this->tokens[ $i ]['code'] !== T_CONTINUE
				&& $this->tokens[ $i ]['code'] !== T_THROW
				&& $this->tokens[ $i ]['code'] !== T_EXIT
				&& $this->tokens[ $i ]['code'] !== T_GOTO
			) {
				// Found the end of the previous scope block.
				return $lastNotEmpty;
			}

			// Skip nested statements.
			if ( isset( $this->tokens[ $i ]['bracket_opener'] ) === true
				&& $i === $this->tokens[ $i ]['bracket_closer']
			) {
				$i = $this->tokens[ $i ]['bracket_opener'];
			} elseif ( isset( $this->tokens[ $i ]['parenthesis_opener'] ) === true
				&& $i === $this->tokens[ $i ]['parenthesis_closer']
			) {
				$i = $this->tokens[ $i ]['parenthesis_opener'];
			} elseif ( $this->tokens[ $i ]['code'] === T_CLOSE_USE_GROUP ) {
				$start = $this->findPrevious( T_OPEN_USE_GROUP, ( $i - 1 ) );
				if ( $start !== false ) {
					$i = $start;
				}
			}//end if

			if ( isset( Tokens::$emptyTokens[ $this->tokens[ $i ]['code'] ] ) === false ) {
				$lastNotEmpty = $i;
			}
		}//end for

		return 0;
	}//end findStartOfStatement()


	/**
	 * Returns the position of the last non-whitespace token in a statement.
	 *
	 * @param int              $start  The position to start searching from in the token stack.
	 * @param int|string|array $ignore Token types that should not be considered stop points.
	 *
	 * @return int
	 */
	public function findEndOfStatement( $start, $ignore = null ) {
		$endTokens = array(
			T_COLON                => true,
			T_COMMA                => true,
			T_DOUBLE_ARROW         => true,
			T_SEMICOLON            => true,
			T_CLOSE_PARENTHESIS    => true,
			T_CLOSE_SQUARE_BRACKET => true,
			T_CLOSE_CURLY_BRACKET  => true,
			T_CLOSE_SHORT_ARRAY    => true,
			T_OPEN_TAG             => true,
			T_CLOSE_TAG            => true,
		);

		if ( $ignore !== null ) {
			$ignore = (array) $ignore;
			foreach ( $ignore as $code ) {
				unset( $endTokens[ $code ] );
			}
		}

		// If the start token is inside the case part of a match expression,
		// advance to the match arrow and continue looking for the
		// end of the statement from there so that we skip over commas.
		if ( $this->tokens[ $start ]['code'] !== T_MATCH_ARROW ) {
			$matchExpression = $this->getCondition( $start, T_MATCH );
			if ( $matchExpression !== false ) {
				$beforeArrow    = true;
				$prevMatchArrow = $this->findPrevious( T_MATCH_ARROW, ( $start - 1 ), $this->tokens[ $matchExpression ]['scope_opener'] );
				if ( $prevMatchArrow !== false ) {
					$prevComma = $this->findNext( T_COMMA, ( $prevMatchArrow + 1 ), $start );
					if ( $prevComma === false ) {
						// No comma between this token and the last match arrow,
						// so this token exists after the arrow and we can continue
						// checking as normal.
						$beforeArrow = false;
					}
				}

				if ( $beforeArrow === true ) {
					$nextMatchArrow = $this->findNext( T_MATCH_ARROW, ( $start + 1 ), $this->tokens[ $matchExpression ]['scope_closer'] );
					if ( $nextMatchArrow !== false ) {
						$start = $nextMatchArrow;
					}
				}
			}//end if
		}//end if

		$lastNotEmpty = $start;
		for ( $i = $start; $i < $this->numTokens; $i++ ) {
			if ( $i !== $start && isset( $endTokens[ $this->tokens[ $i ]['code'] ] ) === true ) {
				// Found the end of the statement.
				if ( $this->tokens[ $i ]['code'] === T_CLOSE_PARENTHESIS
					|| $this->tokens[ $i ]['code'] === T_CLOSE_SQUARE_BRACKET
					|| $this->tokens[ $i ]['code'] === T_CLOSE_CURLY_BRACKET
					|| $this->tokens[ $i ]['code'] === T_CLOSE_SHORT_ARRAY
					|| $this->tokens[ $i ]['code'] === T_OPEN_TAG
					|| $this->tokens[ $i ]['code'] === T_CLOSE_TAG
				) {
					return $lastNotEmpty;
				}

				return $i;
			}

			// Skip nested statements.
			if ( isset( $this->tokens[ $i ]['scope_closer'] ) === true
				&& ( $i === $this->tokens[ $i ]['scope_opener']
				|| $i === $this->tokens[ $i ]['scope_condition'] )
			) {
				if ( $this->tokens[ $i ]['code'] === T_FN ) {
					$lastNotEmpty = $this->tokens[ $i ]['scope_closer'];
					$i            = ( $this->tokens[ $i ]['scope_closer'] - 1 );
					continue;
				}

				if ( $i === $start && isset( Tokens::$scopeOpeners[ $this->tokens[ $i ]['code'] ] ) === true ) {
					return $this->tokens[ $i ]['scope_closer'];
				}

				$i = $this->tokens[ $i ]['scope_closer'];
			} elseif ( isset( $this->tokens[ $i ]['bracket_closer'] ) === true
				&& $i === $this->tokens[ $i ]['bracket_opener']
			) {
				$i = $this->tokens[ $i ]['bracket_closer'];
			} elseif ( isset( $this->tokens[ $i ]['parenthesis_closer'] ) === true
				&& $i === $this->tokens[ $i ]['parenthesis_opener']
			) {
				$i = $this->tokens[ $i ]['parenthesis_closer'];
			} elseif ( $this->tokens[ $i ]['code'] === T_OPEN_USE_GROUP ) {
				$end = $this->findNext( T_CLOSE_USE_GROUP, ( $i + 1 ) );
				if ( $end !== false ) {
					$i = $end;
				}
			}//end if

			if ( isset( Tokens::$emptyTokens[ $this->tokens[ $i ]['code'] ] ) === false ) {
				$lastNotEmpty = $i;
			}
		}//end for

		return ( $this->numTokens - 1 );
	}//end findEndOfStatement()


	/**
	 * Returns the position of the first token on a line, matching given type.
	 *
	 * Returns false if no token can be found.
	 *
	 * @param int|string|array $types   The type(s) of tokens to search for.
	 * @param int              $start   The position to start searching from in the
	 *                                  token stack.
	 * @param bool             $exclude If true, find the token that is NOT of
	 *                                  the types specified in $types.
	 * @param string           $value   The value that the token must be equal to.
	 *                                  If value is omitted, tokens with any value will
	 *                                  be returned.
	 *
	 * @return int|false The first token which matches on the line containing the start
	 *                   token, between the start of the line and the start token.
	 *                   Note: The first token matching might be the start token.
	 *                   FALSE when no matching token could be found between the start of
	 *                   the line and the start token.
	 */
	public function findFirstOnLine( $types, $start, $exclude = false, $value = null ) {
		if ( is_array( $types ) === false ) {
			$types = array( $types );
		}

		$foundToken = false;

		for ( $i = $start; $i >= 0; $i-- ) {
			if ( $this->tokens[ $i ]['line'] < $this->tokens[ $start ]['line'] ) {
				break;
			}

			$found = $exclude;
			foreach ( $types as $type ) {
				if ( $exclude === false ) {
					if ( $this->tokens[ $i ]['code'] === $type ) {
						$found = true;
						break;
					}
				} elseif ( $this->tokens[ $i ]['code'] === $type ) {
						$found = false;
						break;
				}
			}

			if ( $found === true ) {
				if ( $value === null ) {
					$foundToken = $i;
				} elseif ( $this->tokens[ $i ]['content'] === $value ) {
					$foundToken = $i;
				}
			}
		}//end for

		return $foundToken;
	}//end findFirstOnLine()


	/**
	 * Determine if the passed token has a condition of one of the passed types.
	 *
	 * @param int              $stackPtr The position of the token we are checking.
	 * @param int|string|array $types    The type(s) of tokens to search for.
	 *
	 * @return boolean
	 */
	public function hasCondition( $stackPtr, $types ) {
		// Check for the existence of the token.
		if ( isset( $this->tokens[ $stackPtr ] ) === false ) {
			return false;
		}

		// Make sure the token has conditions.
		if ( empty( $this->tokens[ $stackPtr ]['conditions'] ) === true ) {
			return false;
		}

		$types      = (array) $types;
		$conditions = $this->tokens[ $stackPtr ]['conditions'];

		foreach ( $types as $type ) {
			if ( in_array( $type, $conditions, true ) === true ) {
				// We found a token with the required type.
				return true;
			}
		}

		return false;
	}//end hasCondition()


	/**
	 * Return the position of the condition for the passed token.
	 *
	 * Returns FALSE if the token does not have the condition.
	 *
	 * @param int        $stackPtr The position of the token we are checking.
	 * @param int|string $type     The type of token to search for.
	 * @param bool       $first    If TRUE, will return the matched condition
	 *                             furthest away from the passed token.
	 *                             If FALSE, will return the matched condition
	 *                             closest to the passed token.
	 *
	 * @return int|false
	 */
	public function getCondition( $stackPtr, $type, $first = true ) {
		// Check for the existence of the token.
		if ( isset( $this->tokens[ $stackPtr ] ) === false ) {
			return false;
		}

		// Make sure the token has conditions.
		if ( empty( $this->tokens[ $stackPtr ]['conditions'] ) === true ) {
			return false;
		}

		$conditions = $this->tokens[ $stackPtr ]['conditions'];
		if ( $first === false ) {
			$conditions = array_reverse( $conditions, true );
		}

		foreach ( $conditions as $token => $condition ) {
			if ( $condition === $type ) {
				return $token;
			}
		}

		return false;
	}//end getCondition()


	/**
	 * Returns the name of the class that the specified class extends.
	 * (works for classes, anonymous classes and interfaces)
	 *
	 * Returns FALSE on error or if there is no extended class name.
	 *
	 * @param int $stackPtr The stack position of the class.
	 *
	 * @return string|false
	 */
	public function findExtendedClassName( $stackPtr ) {
		// Check for the existence of the token.
		if ( isset( $this->tokens[ $stackPtr ] ) === false ) {
			return false;
		}

		if ( $this->tokens[ $stackPtr ]['code'] !== T_CLASS
			&& $this->tokens[ $stackPtr ]['code'] !== T_ANON_CLASS
			&& $this->tokens[ $stackPtr ]['code'] !== T_INTERFACE
		) {
			return false;
		}

		if ( isset( $this->tokens[ $stackPtr ]['scope_opener'] ) === false ) {
			return false;
		}

		$classOpenerIndex = $this->tokens[ $stackPtr ]['scope_opener'];
		$extendsIndex     = $this->findNext( T_EXTENDS, $stackPtr, $classOpenerIndex );
		if ( $extendsIndex === false ) {
			return false;
		}

		$find = array(
			T_NS_SEPARATOR,
			T_STRING,
			T_WHITESPACE,
		);

		$end  = $this->findNext( $find, ( $extendsIndex + 1 ), ( $classOpenerIndex + 1 ), true );
		$name = $this->getTokensAsString( ( $extendsIndex + 1 ), ( $end - $extendsIndex - 1 ) );
		$name = trim( $name );

		if ( $name === '' ) {
			return false;
		}

		return $name;
	}//end findExtendedClassName()


	/**
	 * Returns the names of the interfaces that the specified class or enum implements.
	 *
	 * Returns FALSE on error or if there are no implemented interface names.
	 *
	 * @param int $stackPtr The stack position of the class or enum token.
	 *
	 * @return array|false
	 */
	public function findImplementedInterfaceNames( $stackPtr ) {
		// Check for the existence of the token.
		if ( isset( $this->tokens[ $stackPtr ] ) === false ) {
			return false;
		}

		if ( $this->tokens[ $stackPtr ]['code'] !== T_CLASS
			&& $this->tokens[ $stackPtr ]['code'] !== T_ANON_CLASS
			&& $this->tokens[ $stackPtr ]['code'] !== T_ENUM
		) {
			return false;
		}

		if ( isset( $this->tokens[ $stackPtr ]['scope_closer'] ) === false ) {
			return false;
		}

		$classOpenerIndex = $this->tokens[ $stackPtr ]['scope_opener'];
		$implementsIndex  = $this->findNext( T_IMPLEMENTS, $stackPtr, $classOpenerIndex );
		if ( $implementsIndex === false ) {
			return false;
		}

		$find = array(
			T_NS_SEPARATOR,
			T_STRING,
			T_WHITESPACE,
			T_COMMA,
		);

		$end  = $this->findNext( $find, ( $implementsIndex + 1 ), ( $classOpenerIndex + 1 ), true );
		$name = $this->getTokensAsString( ( $implementsIndex + 1 ), ( $end - $implementsIndex - 1 ) );
		$name = trim( $name );

		if ( $name === '' ) {
			return false;
		} else {
			$names = explode( ',', $name );
			$names = array_map( 'trim', $names );
			return $names;
		}
	}//end findImplementedInterfaceNames()
}//end class
