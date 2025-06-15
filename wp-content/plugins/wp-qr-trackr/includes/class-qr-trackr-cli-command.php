<?php
/**
 * WP-CLI command for QR Trackr plugin tests.
 *
 * Usage:
 *   wp qr-trackr test
 *
 * Runs the plugin's PHPUnit test suite (if available in the environment).
 *
 * @package QR_Trackr
 */

// NOTE: For full WordPress Coding Standards compliance, this file should be renamed to class-qr-trackr-cli-command.php to match the class name.

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Run QR Trackr PHPUnit tests via WP-CLI.
	 */
	class QR_Trackr_CLI_Command {
		/**
		 * Runs the plugin's PHPUnit test suite.
		 *
		 * ## OPTIONS
		 *
		 * [--filter=<test-name>]
		 * : Filter which tests to run.
		 *
		 * [--group=<group-name>]
		 * : Filter which test groups to run.
		 *
		 * ## EXAMPLES
		 *
		 *     # Run all tests
		 *     $ wp qr-trackr test
		 *
		 *     # Run specific test
		 *     $ wp qr-trackr test --filter=test_qr_code_generation
		 *
		 *     # Run specific test group
		 *     $ wp qr-trackr test --group=qr-code
		 *
		 * @param array $args       Positional arguments (unused).
		 * @param array $assoc_args Associative arguments.
		 * @return void
		 */
		public function test( $args, $assoc_args ) {
			$phpunit = __DIR__ . '/../../../vendor/bin/phpunit';
			if ( ! file_exists( $phpunit ) ) {
				WP_CLI::error( 'PHPUnit not found. Please run `composer install` first.' );
				return;
			}

			$command = 'php ' . escapeshellarg( $phpunit ) . ' --configuration=' . escapeshellarg( __DIR__ . '/../../../phpunit.xml' );
			if ( ! empty( $assoc_args['filter'] ) ) {
				$command .= ' --filter=' . escapeshellarg( $assoc_args['filter'] );
			}
			if ( ! empty( $assoc_args['group'] ) ) {
				$command .= ' --group=' . escapeshellarg( $assoc_args['group'] );
			}

			WP_CLI::log( 'Running QR Trackr PHPUnit tests...' );
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_passthru
			passthru( $command, $exit_code );
			if ( 0 === $exit_code ) {
				WP_CLI::success( 'All tests passed!' );
			} else {
				WP_CLI::error( 'Some tests failed. See output above.' );
			}
		}
	}
	WP_CLI::add_command( 'qr-trackr test', 'QR_Trackr_CLI_Command' );
}
