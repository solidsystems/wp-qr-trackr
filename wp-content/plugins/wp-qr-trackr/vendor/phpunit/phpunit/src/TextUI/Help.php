<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\TextUI;

use const PHP_EOL;
use function count;
use function defined;
use function explode;
use function max;
use function preg_replace_callback;
use function str_pad;
use function str_repeat;
use function strlen;
use function wordwrap;
use PHPUnit\Util\Color;
use SebastianBergmann\Environment\Console;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 *
 * @internal This class is not covered by the backward compatibility promise for PHPUnit
 */
final class Help {

	private const LEFT_MARGIN              = '  ';
	private int $lengthOfLongestOptionName = 0;
	private readonly int $columnsAvailableForDescription;
	private ?bool $hasColor;

	public function __construct( ?int $width = null, ?bool $withColor = null ) {
		if ( $width === null ) {
			$width = ( new Console() )->getNumberOfColumns();
		}

		if ( $withColor === null ) {
			$this->hasColor = ( new Console() )->hasColorSupport();
		} else {
			$this->hasColor = $withColor;
		}

		foreach ( $this->elements() as $options ) {
			foreach ( $options as $option ) {
				if ( isset( $option['arg'] ) ) {
					$this->lengthOfLongestOptionName = max( $this->lengthOfLongestOptionName, strlen( $option['arg'] ) );
				}
			}
		}

		$this->columnsAvailableForDescription = $width - $this->lengthOfLongestOptionName - 4;
	}

	public function generate(): string {
		if ( $this->hasColor ) {
			return $this->writeWithColor();
		}

		return $this->writeWithoutColor();
	}

	private function writeWithoutColor(): string {
		$buffer = '';

		foreach ( $this->elements() as $section => $options ) {
			$buffer .= "{$section}:" . PHP_EOL;

			if ( $section !== 'Usage' ) {
				$buffer .= PHP_EOL;
			}

			foreach ( $options as $option ) {
				if ( isset( $option['spacer'] ) ) {
					$buffer .= PHP_EOL;
				}

				if ( isset( $option['text'] ) ) {
					$buffer .= self::LEFT_MARGIN . $option['text'] . PHP_EOL;
				}

				if ( isset( $option['arg'] ) ) {
					$arg = str_pad( $option['arg'], $this->lengthOfLongestOptionName );

					$buffer .= self::LEFT_MARGIN . $arg . ' ' . $option['desc'] . PHP_EOL;
				}
			}

			$buffer .= PHP_EOL;
		}

		return $buffer;
	}

	private function writeWithColor(): string {
		$buffer = '';

		foreach ( $this->elements() as $section => $options ) {
			$buffer .= Color::colorize( 'fg-yellow', "{$section}:" ) . PHP_EOL;

			if ( $section !== 'Usage' ) {
				$buffer .= PHP_EOL;
			}

			foreach ( $options as $option ) {
				if ( isset( $option['spacer'] ) ) {
					$buffer .= PHP_EOL;
				}

				if ( isset( $option['text'] ) ) {
					$buffer .= self::LEFT_MARGIN . $option['text'] . PHP_EOL;
				}

				if ( isset( $option['arg'] ) ) {
					$arg = Color::colorize( 'fg-green', str_pad( $option['arg'], $this->lengthOfLongestOptionName ) );
					$arg = preg_replace_callback(
						'/(<[^>]+>)/',
						static fn ( $matches ) => Color::colorize( 'fg-cyan', $matches[0] ),
						$arg,
					);

					$desc = explode( PHP_EOL, wordwrap( $option['desc'], $this->columnsAvailableForDescription, PHP_EOL ) );

					$buffer .= self::LEFT_MARGIN . $arg . ' ' . $desc[0] . PHP_EOL;

					for ( $i = 1; $i < count( $desc ); $i++ ) {
						$buffer .= str_repeat( ' ', $this->lengthOfLongestOptionName + 3 ) . $desc[ $i ] . PHP_EOL;
					}
				}
			}

			$buffer .= PHP_EOL;
		}

		return $buffer;
	}

	/**
	 * @psalm-return array<non-empty-string, non-empty-list<array{text: non-empty-string}|array{arg: non-empty-string, desc: non-empty-string}|array{spacer: ''}>>
	 */
	private function elements(): array {
		$elements = array(
			'Usage'         => array(
				array( 'text' => 'phpunit [options] <directory|file> ...' ),
			),

			'Configuration' => array(
				array(
					'arg'  => '--bootstrap <file>',
					'desc' => 'A PHP script that is included before the tests run',
				),
				array(
					'arg'  => '-c|--configuration <file>',
					'desc' => 'Read configuration from XML file',
				),
				array(
					'arg'  => '--no-configuration',
					'desc' => 'Ignore default configuration file (phpunit.xml)',
				),
				array(
					'arg'  => '--no-extensions',
					'desc' => 'Do not load PHPUnit extensions',
				),
				array(
					'arg'  => '--include-path <path(s)>',
					'desc' => 'Prepend PHP\'s include_path with given path(s)',
				),
				array(
					'arg'  => '-d <key[=value]>',
					'desc' => 'Sets a php.ini value',
				),
				array(
					'arg'  => '--cache-directory <dir>',
					'desc' => 'Specify cache directory',
				),
				array(
					'arg'  => '--generate-configuration',
					'desc' => 'Generate configuration file with suggested settings',
				),
				array(
					'arg'  => '--migrate-configuration',
					'desc' => 'Migrate configuration file to current format',
				),
				array(
					'arg'  => '--generate-baseline <file>',
					'desc' => 'Generate baseline for issues',
				),
				array(
					'arg'  => '--use-baseline <file>',
					'desc' => 'Use baseline to ignore issues',
				),
				array(
					'arg'  => '--ignore-baseline',
					'desc' => 'Do not use baseline to ignore issues',
				),
			),

			'Selection'     => array(
				array(
					'arg'  => '--list-suites',
					'desc' => 'List available test suites',
				),
				array(
					'arg'  => '--testsuite <name>',
					'desc' => 'Only run tests from the specified test suite(s)',
				),
				array(
					'arg'  => '--exclude-testsuite <name>',
					'desc' => 'Exclude tests from the specified test suite(s)',
				),
				array(
					'arg'  => '--list-groups',
					'desc' => 'List available test groups',
				),
				array(
					'arg'  => '--group <name>',
					'desc' => 'Only run tests from the specified group(s)',
				),
				array(
					'arg'  => '--exclude-group <name>',
					'desc' => 'Exclude tests from the specified group(s)',
				),
				array(
					'arg'  => '--covers <name>',
					'desc' => 'Only run tests that intend to cover <name>',
				),
				array(
					'arg'  => '--uses <name>',
					'desc' => 'Only run tests that intend to use <name>',
				),
				array(
					'arg'  => '--list-tests',
					'desc' => 'List available tests',
				),
				array(
					'arg'  => '--list-tests-xml <file>',
					'desc' => 'List available tests in XML format',
				),
				array(
					'arg'  => '--filter <pattern>',
					'desc' => 'Filter which tests to run',
				),
				array(
					'arg'  => '--test-suffix <suffixes>',
					'desc' => 'Only search for test in files with specified suffix(es). Default: Test.php,.phpt',
				),
			),

			'Execution'     => array(
				array(
					'arg'  => '--process-isolation',
					'desc' => 'Run each test in a separate PHP process',
				),
				array(
					'arg'  => '--globals-backup',
					'desc' => 'Backup and restore $GLOBALS for each test',
				),
				array(
					'arg'  => '--static-backup',
					'desc' => 'Backup and restore static properties for each test',
				),
				array( 'spacer' => '' ),

				array(
					'arg'  => '--strict-coverage',
					'desc' => 'Be strict about code coverage metadata',
				),
				array(
					'arg'  => '--strict-global-state',
					'desc' => 'Be strict about changes to global state',
				),
				array(
					'arg'  => '--disallow-test-output',
					'desc' => 'Be strict about output during tests',
				),
				array(
					'arg'  => '--enforce-time-limit',
					'desc' => 'Enforce time limit based on test size',
				),
				array(
					'arg'  => '--default-time-limit <sec>',
					'desc' => 'Timeout in seconds for tests that have no declared size',
				),
				array(
					'arg'  => '--dont-report-useless-tests',
					'desc' => 'Do not report tests that do not test anything',
				),
				array( 'spacer' => '' ),

				array(
					'arg'  => '--stop-on-defect',
					'desc' => 'Stop after first error, failure, warning, or risky test',
				),
				array(
					'arg'  => '--stop-on-error',
					'desc' => 'Stop after first error',
				),
				array(
					'arg'  => '--stop-on-failure',
					'desc' => 'Stop after first failure',
				),
				array(
					'arg'  => '--stop-on-warning',
					'desc' => 'Stop after first warning',
				),
				array(
					'arg'  => '--stop-on-risky',
					'desc' => 'Stop after first risky test',
				),
				array(
					'arg'  => '--stop-on-deprecation',
					'desc' => 'Stop after first test that triggered a deprecation',
				),
				array(
					'arg'  => '--stop-on-notice',
					'desc' => 'Stop after first test that triggered a notice',
				),
				array(
					'arg'  => '--stop-on-skipped',
					'desc' => 'Stop after first skipped test',
				),
				array(
					'arg'  => '--stop-on-incomplete',
					'desc' => 'Stop after first incomplete test',
				),
				array( 'spacer' => '' ),

				array(
					'arg'  => '--fail-on-empty-test-suite',
					'desc' => 'Signal failure using shell exit code when no tests were run',
				),
				array(
					'arg'  => '--fail-on-warning',
					'desc' => 'Signal failure using shell exit code when a warning was triggered',
				),
				array(
					'arg'  => '--fail-on-risky',
					'desc' => 'Signal failure using shell exit code when a test was considered risky',
				),
				array(
					'arg'  => '--fail-on-deprecation',
					'desc' => 'Signal failure using shell exit code when a deprecation was triggered',
				),
				array(
					'arg'  => '--fail-on-phpunit-deprecation',
					'desc' => 'Signal failure using shell exit code when a PHPUnit deprecation was triggered',
				),
				array(
					'arg'  => '--fail-on-notice',
					'desc' => 'Signal failure using shell exit code when a notice was triggered',
				),
				array(
					'arg'  => '--fail-on-skipped',
					'desc' => 'Signal failure using shell exit code when a test was skipped',
				),
				array(
					'arg'  => '--fail-on-incomplete',
					'desc' => 'Signal failure using shell exit code when a test was marked incomplete',
				),
				array(
					'arg'  => '--fail-on-all-issues',
					'desc' => 'Signal failure using shell exit code when an issue is triggered',
				),
				array( 'spacer' => '' ),

				array(
					'arg'  => '--cache-result',
					'desc' => 'Write test results to cache file',
				),
				array(
					'arg'  => '--do-not-cache-result',
					'desc' => 'Do not write test results to cache file',
				),
				array( 'spacer' => '' ),

				array(
					'arg'  => '--order-by <order>',
					'desc' => 'Run tests in order: default|defects|depends|duration|no-depends|random|reverse|size',
				),
				array(
					'arg'  => '--random-order-seed <N>',
					'desc' => 'Use the specified random seed when running tests in random order',
				),
			),

			'Reporting'     => array(
				array(
					'arg'  => '--colors <flag>',
					'desc' => 'Use colors in output ("never", "auto" or "always")',
				),
				array(
					'arg'  => '--columns <n>',
					'desc' => 'Number of columns to use for progress output',
				),
				array(
					'arg'  => '--columns max',
					'desc' => 'Use maximum number of columns for progress output',
				),
				array(
					'arg'  => '--stderr',
					'desc' => 'Write to STDERR instead of STDOUT',
				),
				array( 'spacer' => '' ),

				array(
					'arg'  => '--no-progress',
					'desc' => 'Disable output of test execution progress',
				),
				array(
					'arg'  => '--no-results',
					'desc' => 'Disable output of test results',
				),
				array(
					'arg'  => '--no-output',
					'desc' => 'Disable all output',
				),
				array( 'spacer' => '' ),

				array(
					'arg'  => '--display-incomplete',
					'desc' => 'Display details for incomplete tests',
				),
				array(
					'arg'  => '--display-skipped',
					'desc' => 'Display details for skipped tests',
				),
				array(
					'arg'  => '--display-deprecations',
					'desc' => 'Display details for deprecations triggered by tests',
				),
				array(
					'arg'  => '--display-phpunit-deprecations',
					'desc' => 'Display details for PHPUnit deprecations',
				),
				array(
					'arg'  => '--display-errors',
					'desc' => 'Display details for errors triggered by tests',
				),
				array(
					'arg'  => '--display-notices',
					'desc' => 'Display details for notices triggered by tests',
				),
				array(
					'arg'  => '--display-warnings',
					'desc' => 'Display details for warnings triggered by tests',
				),
				array(
					'arg'  => '--display-all-issues',
					'desc' => 'Display details for all issues that are triggered',
				),
				array(
					'arg'  => '--reverse-list',
					'desc' => 'Print defects in reverse order',
				),
				array( 'spacer' => '' ),

				array(
					'arg'  => '--teamcity',
					'desc' => 'Replace default progress and result output with TeamCity format',
				),
				array(
					'arg'  => '--testdox',
					'desc' => 'Replace default result output with TestDox format',
				),
				array( 'spacer' => '' ),

				array(
					'arg'  => '--debug',
					'desc' => 'Replace default progress and result output with debugging information',
				),
			),

			'Logging'       => array(
				array(
					'arg'  => '--log-junit <file>',
					'desc' => 'Write test results in JUnit XML format to file',
				),
				array(
					'arg'  => '--log-teamcity <file>',
					'desc' => 'Write test results in TeamCity format to file',
				),
				array(
					'arg'  => '--testdox-html <file>',
					'desc' => 'Write test results in TestDox format (HTML) to file',
				),
				array(
					'arg'  => '--testdox-text <file>',
					'desc' => 'Write test results in TestDox format (plain text) to file',
				),
				array(
					'arg'  => '--log-events-text <file>',
					'desc' => 'Stream events as plain text to file',
				),
				array(
					'arg'  => '--log-events-verbose-text <file>',
					'desc' => 'Stream events as plain text with extended information to file',
				),
				array(
					'arg'  => '--no-logging',
					'desc' => 'Ignore logging configured in the XML configuration file',
				),
			),

			'Code Coverage' => array(
				array(
					'arg'  => '--coverage-clover <file>',
					'desc' => 'Write code coverage report in Clover XML format to file',
				),
				array(
					'arg'  => '--coverage-cobertura <file>',
					'desc' => 'Write code coverage report in Cobertura XML format to file',
				),
				array(
					'arg'  => '--coverage-crap4j <file>',
					'desc' => 'Write code coverage report in Crap4J XML format to file',
				),
				array(
					'arg'  => '--coverage-html <dir>',
					'desc' => 'Write code coverage report in HTML format to directory',
				),
				array(
					'arg'  => '--coverage-php <file>',
					'desc' => 'Write serialized code coverage data to file',
				),
				array(
					'arg'  => '--coverage-text=<file>',
					'desc' => 'Write code coverage report in text format to file [default: standard output]',
				),
				array(
					'arg'  => '--only-summary-for-coverage-text',
					'desc' => 'Option for code coverage report in text format: only show summary',
				),
				array(
					'arg'  => '--show-uncovered-for-coverage-text',
					'desc' => 'Option for code coverage report in text format: show uncovered files',
				),
				array(
					'arg'  => '--coverage-xml <dir>',
					'desc' => 'Write code coverage report in XML format to directory',
				),
				array(
					'arg'  => '--warm-coverage-cache',
					'desc' => 'Warm static analysis cache',
				),
				array(
					'arg'  => '--coverage-filter <dir>',
					'desc' => 'Include <dir> in code coverage reporting',
				),
				array(
					'arg'  => '--path-coverage',
					'desc' => 'Report path coverage in addition to line coverage',
				),
				array(
					'arg'  => '--disable-coverage-ignore',
					'desc' => 'Disable metadata for ignoring code coverage',
				),
				array(
					'arg'  => '--no-coverage',
					'desc' => 'Ignore code coverage reporting configured in the XML configuration file',
				),
			),
		);

		if ( defined( '__PHPUNIT_PHAR__' ) ) {
			$elements['PHAR'] = array(
				array(
					'arg'  => '--manifest',
					'desc' => 'Print Software Bill of Materials (SBOM) in plain-text format',
				),
				array(
					'arg'  => '--sbom',
					'desc' => 'Print Software Bill of Materials (SBOM) in CycloneDX XML format',
				),
				array(
					'arg'  => '--composer-lock',
					'desc' => 'Print composer.lock file used to build the PHAR',
				),
			);
		}

		$elements['Miscellaneous'] = array(
			array(
				'arg'  => '-h|--help',
				'desc' => 'Prints this usage information',
			),
			array(
				'arg'  => '--version',
				'desc' => 'Prints the version and exits',
			),
			array(
				'arg'  => '--atleast-version <min>',
				'desc' => 'Checks that version is greater than <min> and exits',
			),
			array(
				'arg'  => '--check-version',
				'desc' => 'Checks whether PHPUnit is the latest version and exits',
			),
		);

		return $elements;
	}
}
