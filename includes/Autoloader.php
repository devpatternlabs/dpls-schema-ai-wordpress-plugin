<?php
/**
 * Autoloader for DPLS Schema AI.
 *
 * @package DPLS\SchemaAI
 * @author  Dev Pattern Labs
 * @license GPL-2.0-or-later
 */

namespace DPLS\SchemaAI;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PSR-4 Autoloader implementation.
 */
class Autoloader {

	/**
	 * Namespace prefix to match.
	 *
	 * @var string
	 */
	protected const PREFIX = 'DPLS\\SchemaAI\\';

	/**
	 * Base directory for the namespace.
	 *
	 * @var string
	 */
	protected static string $base_dir = '';

	/**
	 * Register the autoloader.
	 *
	 * @param string $base_dir Directory path containing class files.
	 * @return void
	 */
	public static function register( string $base_dir ): void {
		self::$base_dir = rtrim( $base_dir, '/\\' ) . DIRECTORY_SEPARATOR;

		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Loads the class file for a given class name.
	 *
	 * @param string $class Fully-qualified class name.
	 * @return bool True if loaded, false otherwise.
	 */
	public static function autoload( string $class ): bool {
		// Does the class use the namespace prefix?
		$len = strlen( self::PREFIX );
		if ( strncmp( self::PREFIX, $class, $len ) !== 0 ) {
			return false;
		}

		// Get the relative class name.
		$relative_class = substr( $class, $len );

		// Replace the namespace prefix with the base directory, replace namespace
		// separators with directory separators in the relative class name, append
		// with .php.
		$file = self::$base_dir . str_replace( '\\', DIRECTORY_SEPARATOR, $relative_class ) . '.php';

		// If the file exists, require it.
		if ( file_exists( $file ) ) {
			require_once $file;
			return true;
		}

		return false;
	}
}
