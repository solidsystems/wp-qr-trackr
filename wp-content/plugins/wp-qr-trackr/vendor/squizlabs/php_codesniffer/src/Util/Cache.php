<?php
/**
 * Function for caching between runs.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Util;

use FilesystemIterator;
use PHP_CodeSniffer\Autoload;
use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Ruleset;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Cache {


	/**
	 * The filesystem location of the cache file.
	 *
	 * @var string
	 */
	private static $path = '';

	/**
	 * The cached data.
	 *
	 * @var array<string, mixed>
	 */
	private static $cache = array();


	/**
	 * Loads existing cache data for the run, if any.
	 *
	 * @param \PHP_CodeSniffer\Ruleset $ruleset The ruleset used for the run.
	 * @param \PHP_CodeSniffer\Config  $config  The config data for the run.
	 *
	 * @return void
	 */
	public static function load( Ruleset $ruleset, Config $config ) {
		// Look at every loaded sniff class so far and use their file contents
		// to generate a hash for the code used during the run.
		// At this point, the loaded class list contains the core PHPCS code
		// and all sniffs that have been loaded as part of the run.
		if ( PHP_CODESNIFFER_VERBOSITY > 1 ) {
			echo PHP_EOL . "\tGenerating loaded file list for code hash" . PHP_EOL;
		}

		$codeHashFiles = array();

		$classes = array_keys( Autoload::getLoadedClasses() );
		sort( $classes );

		$installDir     = dirname( __DIR__ );
		$installDirLen  = strlen( $installDir );
		$standardDir    = $installDir . DIRECTORY_SEPARATOR . 'Standards';
		$standardDirLen = strlen( $standardDir );
		foreach ( $classes as $file ) {
			if ( substr( $file, 0, $standardDirLen ) !== $standardDir ) {
				if ( substr( $file, 0, $installDirLen ) === $installDir ) {
					// We are only interested in sniffs here.
					continue;
				}

				if ( PHP_CODESNIFFER_VERBOSITY > 1 ) {
					echo "\t\t=> external file: $file" . PHP_EOL;
				}
			} elseif ( PHP_CODESNIFFER_VERBOSITY > 1 ) {
				echo "\t\t=> internal sniff: $file" . PHP_EOL;
			}

			$codeHashFiles[] = $file;
		}

		// Add the content of the used rulesets to the hash so that sniff setting
		// changes in the ruleset invalidate the cache.
		$rulesets = $ruleset->paths;
		sort( $rulesets );
		foreach ( $rulesets as $file ) {
			if ( substr( $file, 0, $standardDirLen ) !== $standardDir ) {
				if ( PHP_CODESNIFFER_VERBOSITY > 1 ) {
					echo "\t\t=> external ruleset: $file" . PHP_EOL;
				}
			} elseif ( PHP_CODESNIFFER_VERBOSITY > 1 ) {
				echo "\t\t=> internal ruleset: $file" . PHP_EOL;
			}

			$codeHashFiles[] = $file;
		}

		// Go through the core PHPCS code and add those files to the file
		// hash. This ensures that core PHPCS changes will also invalidate the cache.
		// Note that we ignore sniffs here, and any files that don't affect
		// the outcome of the run.
		$di     = new RecursiveDirectoryIterator(
			$installDir,
			( FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::SKIP_DOTS )
		);
		$filter = new RecursiveCallbackFilterIterator(
			$di,
			function ( $file, $key, $iterator ) {
				// Skip non-php files.
				$filename = $file->getFilename();
				if ( $file->isFile() === true && substr( $filename, -4 ) !== '.php' ) {
					return false;
				}

				$filePath = Common::realpath( $key );
				if ( $filePath === false ) {
					return false;
				}

				if ( $iterator->hasChildren() === true
					&& ( $filename === 'Standards'
					|| $filename === 'Exceptions'
					|| $filename === 'Reports'
					|| $filename === 'Generators' )
				) {
					return false;
				}

				return true;
			}
		);

		$iterator = new RecursiveIteratorIterator( $filter );
		foreach ( $iterator as $file ) {
			if ( PHP_CODESNIFFER_VERBOSITY > 1 ) {
				echo "\t\t=> core file: $file" . PHP_EOL;
			}

			$codeHashFiles[] = $file->getPathname();
		}

		$codeHash = '';
		sort( $codeHashFiles );
		foreach ( $codeHashFiles as $file ) {
			$codeHash .= md5_file( $file );
		}

		$codeHash = md5( $codeHash );

		// Along with the code hash, use various settings that can affect
		// the results of a run to create a new hash. This hash will be used
		// in the cache file name.
		$rulesetHash       = md5( var_export( $ruleset->ignorePatterns, true ) . var_export( $ruleset->includePatterns, true ) );
		$phpExtensionsHash = md5( var_export( get_loaded_extensions(), true ) );
		$configData        = array(
			'phpVersion'    => PHP_VERSION_ID,
			'phpExtensions' => $phpExtensionsHash,
			'tabWidth'      => $config->tabWidth,
			'encoding'      => $config->encoding,
			'recordErrors'  => $config->recordErrors,
			'annotations'   => $config->annotations,
			'configData'    => Config::getAllConfigData(),
			'codeHash'      => $codeHash,
			'rulesetHash'   => $rulesetHash,
		);

		$configString = var_export( $configData, true );
		$cacheHash    = substr( sha1( $configString ), 0, 12 );

		if ( PHP_CODESNIFFER_VERBOSITY > 1 ) {
			echo "\tGenerating cache key data" . PHP_EOL;
			foreach ( $configData as $key => $value ) {
				if ( is_array( $value ) === true ) {
					echo "\t\t=> $key:" . PHP_EOL;
					foreach ( $value as $subKey => $subValue ) {
						echo "\t\t\t=> $subKey: $subValue" . PHP_EOL;
					}

					continue;
				}

				if ( $value === true || $value === false ) {
					$value = (int) $value;
				}

				echo "\t\t=> $key: $value" . PHP_EOL;
			}

			echo "\t\t=> cacheHash: $cacheHash" . PHP_EOL;
		}//end if

		if ( $config->cacheFile !== null ) {
			$cacheFile = $config->cacheFile;
		} else {
			// Determine the common paths for all files being checked.
			// We can use this to locate an existing cache file, or to
			// determine where to create a new one.
			if ( PHP_CODESNIFFER_VERBOSITY > 1 ) {
				echo "\tChecking possible cache file paths" . PHP_EOL;
			}

			$paths = array();
			foreach ( $config->files as $file ) {
				$file = Common::realpath( $file );
				while ( $file !== DIRECTORY_SEPARATOR ) {
					if ( isset( $paths[ $file ] ) === false ) {
						$paths[ $file ] = 1;
					} else {
						++$paths[ $file ];
					}

					$lastFile = $file;
					$file     = dirname( $file );
					if ( $file === $lastFile ) {
						// Just in case something went wrong,
						// we don't want to end up in an infinite loop.
						break;
					}
				}
			}

			ksort( $paths );
			$paths = array_reverse( $paths );

			$numFiles = count( $config->files );

			$cacheFile = null;
			$cacheDir  = getenv( 'XDG_CACHE_HOME' );
			if ( $cacheDir === false || is_dir( $cacheDir ) === false ) {
				$cacheDir = sys_get_temp_dir();
			}

			foreach ( $paths as $file => $count ) {
				if ( $count !== $numFiles ) {
					unset( $paths[ $file ] );
					continue;
				}

				$fileHash = substr( sha1( $file ), 0, 12 );
				$testFile = $cacheDir . DIRECTORY_SEPARATOR . "phpcs.$fileHash.$cacheHash.cache";
				if ( $cacheFile === null ) {
					// This will be our default location if we can't find
					// an existing file.
					$cacheFile = $testFile;
				}

				if ( PHP_CODESNIFFER_VERBOSITY > 1 ) {
					echo "\t\t=> $testFile" . PHP_EOL;
					echo "\t\t\t * based on shared location: $file *" . PHP_EOL;
				}

				if ( file_exists( $testFile ) === true ) {
					$cacheFile = $testFile;
					break;
				}
			}//end foreach

			if ( $cacheFile === null ) {
				// Unlikely, but just in case $paths is empty for some reason.
				$cacheFile = $cacheDir . DIRECTORY_SEPARATOR . "phpcs.$cacheHash.cache";
			}
		}//end if

		self::$path = $cacheFile;
		if ( PHP_CODESNIFFER_VERBOSITY > 1 ) {
			echo "\t=> Using cache file: " . self::$path . PHP_EOL;
		}

		if ( file_exists( self::$path ) === true ) {
			self::$cache = json_decode( file_get_contents( self::$path ), true );

			// Verify the contents of the cache file.
			if ( self::$cache['config'] !== $configData ) {
				self::$cache = array();
				if ( PHP_CODESNIFFER_VERBOSITY > 1 ) {
					echo "\t* cache was invalid and has been cleared *" . PHP_EOL;
				}
			}
		} elseif ( PHP_CODESNIFFER_VERBOSITY > 1 ) {
			echo "\t* cache file does not exist *" . PHP_EOL;
		}

		self::$cache['config'] = $configData;
	}//end load()


	/**
	 * Saves the current cache to the filesystem.
	 *
	 * @return void
	 */
	public static function save() {
		file_put_contents( self::$path, json_encode( self::$cache ) );
	}//end save()


	/**
	 * Retrieves a single entry from the cache.
	 *
	 * @param string $key The key of the data to get. If NULL,
	 *                    everything in the cache is returned.
	 *
	 * @return mixed
	 */
	public static function get( $key = null ) {
		if ( $key === null ) {
			return self::$cache;
		}

		if ( isset( self::$cache[ $key ] ) === true ) {
			return self::$cache[ $key ];
		}

		return false;
	}//end get()


	/**
	 * Retrieves a single entry from the cache.
	 *
	 * @param string $key   The key of the data to set. If NULL,
	 *                      sets the entire cache.
	 * @param mixed  $value The value to set.
	 *
	 * @return void
	 */
	public static function set( $key, $value ) {
		if ( $key === null ) {
			self::$cache = $value;
		} else {
			self::$cache[ $key ] = $value;
		}
	}//end set()


	/**
	 * Retrieves the number of cache entries.
	 *
	 * @return int
	 */
	public static function getSize() {
		return ( count( self::$cache ) - 1 );
	}//end getSize()
}//end class
