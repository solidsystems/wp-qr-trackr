<?php
// Development debug settings for local Docker environment.
// Enable full debug logging for non-production Docker environment
if ( getenv( 'DOCKER' ) || getenv( 'WP_QR_TRACKR_NONPROD' ) ) {
	define( 'WP_DEBUG', true );
	define( 'WP_DEBUG_LOG', true );
	define( 'WP_DEBUG_DISPLAY', true );
	@ini_set( 'display_errors', 1 );
}

define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
