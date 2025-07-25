<?php
/**
 * An interface that PHP_CodeSniffer reports must implement.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Reports;

use PHP_CodeSniffer\Files\File;

interface Report {



	/**
	 * Generate a partial report for a single processed file.
	 *
	 * Function should return TRUE if it printed or stored data about the file
	 * and FALSE if it ignored the file. Returning TRUE indicates that the file and
	 * its data should be counted in the grand totals.
	 *
	 * The format of the `$report` parameter the function receives is as follows:
	 * ```
	 * array(
	 *   'filename' => string The name of the current file.
	 *   'errors'   => int    The number of errors seen in the current file.
	 *   'warnings' => int    The number of warnings seen in the current file.
	 *   'fixable'  => int    The number of fixable issues seen in the current file.
	 *   'messages' => array(
	 *     int <Line number> => array(
	 *       int <Column number> => array(
	 *         int <Message index> => array(
	 *           'message'  => string The error/warning message.
	 *           'source'   => string The full error code for the message.
	 *           'severity' => int    The severity of the message.
	 *           'fixable'  => bool   Whether this error/warning is auto-fixable.
	 *           'type'     => string The type of message. Either 'ERROR' or 'WARNING'.
	 *         )
	 *       )
	 *     )
	 *   )
	 * )
	 * ```
	 *
	 * @param array<string, string|int|array> $report      Prepared report data.
	 * @param \PHP_CodeSniffer\Files\File     $phpcsFile   The file being reported on.
	 * @param bool                            $showSources Show sources?
	 * @param int                             $width       Maximum allowed line width.
	 *
	 * @return bool
	 */
	public function generateFileReport( $report, File $phpcsFile, $showSources = false, $width = 80 );


	/**
	 * Generate the actual report.
	 *
	 * @param string $cachedData    Any partial report data that was returned from
	 *                              generateFileReport during the run.
	 * @param int    $totalFiles    Total number of files processed during the run.
	 * @param int    $totalErrors   Total number of errors found during the run.
	 * @param int    $totalWarnings Total number of warnings found during the run.
	 * @param int    $totalFixable  Total number of problems that can be fixed.
	 * @param bool   $showSources   Show sources?
	 * @param int    $width         Maximum allowed line width.
	 * @param bool   $interactive   Are we running in interactive mode?
	 * @param bool   $toScreen      Is the report being printed to screen?
	 *
	 * @return void
	 */
	public function generate(
		$cachedData,
		$totalFiles,
		$totalErrors,
		$totalWarnings,
		$totalFixable,
		$showSources = false,
		$width = 80,
		$interactive = false,
		$toScreen = true
	);
}//end interface
