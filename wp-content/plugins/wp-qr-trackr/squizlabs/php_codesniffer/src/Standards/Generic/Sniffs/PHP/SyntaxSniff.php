<?php
/**
 * Ensures PHP believes the syntax is clean.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Blaine Schmeisser <blainesch@gmail.com>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Generic\Sniffs\PHP;

use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Common;

class SyntaxSniff implements Sniff {


	/**
	 * The path to the PHP version we are checking with.
	 *
	 * @var string
	 */
	private $phpPath = null;


	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array<int|string>
	 */
	public function register() {
		return array(
			T_OPEN_TAG,
			T_OPEN_TAG_WITH_ECHO,
		);
	}//end register()


	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
	 * @param int                         $stackPtr  The position of the current token in
	 *                                               the stack passed in $tokens.
	 *
	 * @return int
	 */
	public function process( File $phpcsFile, $stackPtr ) {
		if ( $this->phpPath === null ) {
			$this->phpPath = Config::getExecutablePath( 'php' );
		}

		$fileName = escapeshellarg( $phpcsFile->getFilename() );
		$cmd      = Common::escapeshellcmd( $this->phpPath ) . " -l -d display_errors=1 -d error_prepend_string='' $fileName 2>&1";
		$output   = shell_exec( $cmd );
		$matches  = array();
		if ( preg_match( '/^.*error:(.*) in .* on line ([0-9]+)/m', trim( $output ), $matches ) === 1 ) {
			$error = trim( $matches[1] );
			$line  = (int) $matches[2];
			$phpcsFile->addErrorOnLine( "PHP syntax error: $error", $line, 'PHPSyntax' );
		}

		// Ignore the rest of the file.
		return $phpcsFile->numTokens;
	}//end process()
}//end class
