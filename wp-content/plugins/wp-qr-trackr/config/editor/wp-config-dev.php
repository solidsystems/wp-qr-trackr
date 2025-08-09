<?php
/**
 * Development debug settings for local Docker environment.
 *
 * Enable full debug logging for non-production Docker environment.
 *
 * @package WP_QR_Trackr
 * @since 1.0.0
 */

// Development debug settings for local Docker environment.
// Enable full debug logging for non-production Docker environment.
if ( getenv( 'DOCKER' ) || getenv( 'WP_QR_TRACKR_NONPROD' ) ) {
	define( 'WP_DEBUG', true );
	define( 'WP_DEBUG_LOG', true );
	define( 'WP_DEBUG_DISPLAY', true );
}

define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
