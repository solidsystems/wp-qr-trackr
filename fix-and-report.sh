#!/bin/sh

# Auto-fix all fixable PHPCS issues (excluding vendor/)
./wp-content/plugins/wp-qr-trackr/vendor/bin/phpcbf --standard=.phpcs.xml --extensions=php wp-content/plugins/wp-qr-trackr

# Show remaining PHPCS errors/warnings (excluding vendor/)
./wp-content/plugins/wp-qr-trackr/vendor/bin/phpcs --standard=.phpcs.xml --extensions=php wp-content/plugins/wp-qr-trackr 