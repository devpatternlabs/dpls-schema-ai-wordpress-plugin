<?php
/**
 * Test Plugin Check against the clean release package.
 */

// Prevent direct web access.
defined( 'ABSPATH' ) || ( 'cli' === php_sapi_name() ) || exit;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

register_shutdown_function(function() {
	$err = error_get_last();
	if ($err) {
		echo "\nLAST ERROR: " . print_r($err, true) . "\n";
	}
});

$_SERVER['HTTP_HOST']       = 'localhost';
$_SERVER['REQUEST_METHOD']  = 'GET';
$_SERVER['REMOTE_ADDR']     = '127.0.0.1';

if ( ! file_exists( 'C:/xampp/htdocs/stars/wp-load.php' ) ) {
	echo "Local WordPress site not found.\n";
	exit( 1 );
}

require_once 'C:/xampp/htdocs/stars/wp-load.php';
add_filter('wp_die_handler', function() {
	return function($message, $title = '', $args = array()) {
		echo "WP_DIE: " . (is_wp_error($message) ? $message->get_error_message() : (is_scalar($message) ? $message : print_r($message, true))) . "\n";
		exit(1);
	};
});
require_once ABSPATH . 'wp-admin/includes/plugin.php';

$zip_path = 'C:/xampp/htdocs/DPLSplugin/dist/dpls-schema-ai.zip';
$extract_dir = 'C:/xampp/htdocs/stars/wp-content/plugins/dpls-test-package';

if ( is_dir( $extract_dir ) ) {
	$files = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $extract_dir, RecursiveDirectoryIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::CHILD_FIRST
	);
	foreach ( $files as $fileinfo ) {
		$todo = ( $fileinfo->isDir() ? 'rmdir' : 'unlink' );
		$todo( $fileinfo->getRealPath() );
	}
	rmdir( $extract_dir );
}

mkdir( $extract_dir, 0755, true );

$zip = new ZipArchive();
if ( $zip->open( $zip_path ) === true ) {
	$zip->extractTo( $extract_dir );
	$zip->close();
	echo "Extracted release zip to: {$extract_dir}\n";
} else {
	echo "Failed to open zip: {$zip_path}\n";
	exit( 1 );
}

$pcp_plugin_file = 'C:/xampp/htdocs/stars/wp-content/plugins/plugin-check/plugin.php';
require_once $pcp_plugin_file;
wp_plugin_check_load();

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Default_Check_Repository;
use WordPress\Plugin_Check\Checker\Check_Repository;
use WordPress\Plugin_Check\Checker\Check_Result;

$target_main_file = $extract_dir . '/dpls-schema-ai/dpls-schema-ai.php';
$slug             = 'dpls-schema-ai';

echo "Initializing Check_Context for package: {$target_main_file}\n";
$context = new Check_Context( $target_main_file, $slug, 'new' );

$repository = new Default_Check_Repository();
$collection = $repository->get_checks( Check_Repository::TYPE_STATIC );
$all_checks = iterator_to_array( $collection );

echo "Running " . count( $all_checks ) . " static & repository checks from Plugin Check...\n\n";

$result = new Check_Result( $context );

foreach ( $all_checks as $check_slug => $check ) {
	try {
		$check->run( $result );
		echo " [PASS] Check: {$check_slug}\n";
	} catch ( \Throwable $t ) {
		echo " [FAIL/EXCEPTION] Check: {$check_slug} - " . $t->getMessage() . "\n";
		echo $t->getTraceAsString() . "\n";
	}
}

$error_count   = $result->get_error_count();
$warning_count = $result->get_warning_count();

echo "\n========================================\n";
echo "RELEASE PACKAGE AUDIT RESULTS: {$error_count} Errors, {$warning_count} Warnings\n";
echo "========================================\n\n";

if ( $error_count > 0 ) {
	echo "--- ERRORS ---\n";
	foreach ( $result->get_errors() as $file => $lines ) {
		foreach ( $lines as $line => $cols ) {
			foreach ( $cols as $col => $msgs ) {
				foreach ( $msgs as $m ) {
					echo sprintf( "[ERROR] %s:%d:%d [%s] %s\n", $file, $line, $col, $m['code'] ?? '', $m['message'] );
				}
			}
		}
	}
	echo "\n";
}

if ( $warning_count > 0 ) {
	echo "--- WARNINGS ---\n";
	foreach ( $result->get_warnings() as $file => $lines ) {
		foreach ( $lines as $line => $cols ) {
			foreach ( $cols as $col => $msgs ) {
				foreach ( $msgs as $m ) {
					echo sprintf( "[WARNING] %s:%d:%d [%s] %s\n", $file, $line, $col, $m['code'] ?? '', $m['message'] );
				}
			}
		}
	}
	echo "\n";
}

if ( 0 === $error_count && 0 === $warning_count ) {
	echo "SUCCESS: 100% CLEAN! Zero errors, zero warnings in the release ZIP package!\n";
}

// Clean up test extract directory.
if ( is_dir( $extract_dir ) ) {
	$cleanup_files = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $extract_dir, RecursiveDirectoryIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::CHILD_FIRST
	);
	foreach ( $cleanup_files as $f ) {
		$fn = ( $f->isDir() ? 'rmdir' : 'unlink' );
		$fn( $f->getRealPath() );
	}
	rmdir( $extract_dir );
}
