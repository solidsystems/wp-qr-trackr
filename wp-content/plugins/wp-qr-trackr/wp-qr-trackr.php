// Register WP-CLI command for running tests
if (defined('WP_CLI') && WP_CLI) {
    require_once __DIR__ . '/includes/class-qr-trackr-cli.php';
} 