<?php
/**
 * WP-CLI command for QR Trackr plugin tests.
 *
 * Usage:
 *   wp qr-trackr test
 *
 * Runs the plugin's PHPUnit test suite (if available in the environment).
 */
if (defined('WP_CLI') && WP_CLI) {
    /**
     * Run QR Trackr PHPUnit tests via WP-CLI.
     */
    class QR_Trackr_CLI_Command {
        /**
         * Runs the plugin's PHPUnit test suite.
         *
         * ## EXAMPLES
         *     wp qr-trackr test
         */
        public function test($args, $assoc_args) {
            $phpunit = __DIR__ . '/../../../vendor/bin/phpunit';
            if (!file_exists($phpunit)) {
                WP_CLI::error('PHPUnit not found. Please run `composer install` first.');
                return;
            }
            WP_CLI::log('Running QR Trackr PHPUnit tests...');
            passthru(escapeshellcmd($phpunit), $exit_code);
            if ($exit_code === 0) {
                WP_CLI::success('All tests passed!');
            } else {
                WP_CLI::error('Some tests failed. See output above.');
            }
        }
    }
    WP_CLI::add_command('qr-trackr test', 'QR_Trackr_CLI_Command');
} 