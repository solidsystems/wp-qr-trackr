<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInita12e5ac05425d52a4d3ce896bfc6ba7c {

	private static $loader;

	public static function loadClassLoader( $class ) {
		if ( 'Composer\Autoload\ClassLoader' === $class ) {
			require __DIR__ . '/ClassLoader.php';
		}
	}

	/**
	 * @return \Composer\Autoload\ClassLoader
	 */
	public static function getLoader() {
		if ( null !== self::$loader ) {
			return self::$loader;
		}

		require __DIR__ . '/platform_check.php';

		spl_autoload_register( array( 'ComposerAutoloaderInita12e5ac05425d52a4d3ce896bfc6ba7c', 'loadClassLoader' ), true, true );
		self::$loader = $loader = new \Composer\Autoload\ClassLoader( \dirname( __DIR__ ) );
		spl_autoload_unregister( array( 'ComposerAutoloaderInita12e5ac05425d52a4d3ce896bfc6ba7c', 'loadClassLoader' ) );

		require __DIR__ . '/autoload_static.php';
		call_user_func( \Composer\Autoload\ComposerStaticInita12e5ac05425d52a4d3ce896bfc6ba7c::getInitializer( $loader ) );

		$loader->register( true );

		$filesToLoad = \Composer\Autoload\ComposerStaticInita12e5ac05425d52a4d3ce896bfc6ba7c::$files;
		$requireFile = \Closure::bind(
			static function ( $fileIdentifier, $file ) {
				if ( empty( $GLOBALS['__composer_autoload_files'][ $fileIdentifier ] ) ) {
					$GLOBALS['__composer_autoload_files'][ $fileIdentifier ] = true;

					require $file;
				}
			},
			null,
			null
		);
		foreach ( $filesToLoad as $fileIdentifier => $file ) {
			$requireFile( $fileIdentifier, $file );
		}

		return $loader;
	}
}
