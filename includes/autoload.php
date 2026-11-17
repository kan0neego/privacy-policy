<?php
/**
 * PSR-4 autoload for CookieConsent\ mapped to includes/.
 *
 * @package CookieConsent
 */

defined( 'ABSPATH' ) || exit;

spl_autoload_register(
	static function ( string $module_class ): void {
		$prefix = 'CookieConsent\\';
		$base   = __DIR__;

		if ( strncmp( $module_class, $prefix, strlen( $prefix ) ) !== 0 ) {
			return;
		}

		$relative = substr( $module_class, strlen( $prefix ) );
		$path     = $base . '/' . str_replace( '\\', '/', $relative ) . '.php';

		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
);
