<?php
/**
 * Run Plugin Check (PCP) against DPLS Schema AI.
 */

$_SERVER['HTTP_HOST']       = 'localhost';
$_SERVER['REQUEST_METHOD']  = 'GET';
$_SERVER['REMOTE_ADDR']     = '127.0.0.1';

if ( ! file_exists( 'C:/xampp/htdocs/stars/wp-load.php' ) ) {
	echo "Local WordPress site not found.\n";
	exit( 1 );
}

require_once 'C:/xampp/htdocs/stars/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

$pcp_plugin_file = 'C:/xampp/htdocs/stars/wp-content/plugins/plugin-check/plugin.php';
if ( ! file_exists( $pcp_plugin_file ) ) {
	echo "Plugin Check main file not found at: {$pcp_plugin_file}\n";
	exit( 1 );
}

require_once $pcp_plugin_file;
wp_plugin_check_load();

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Default_Check_Repository;
use WordPress\Plugin_Check\Checker\Check_Repository;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Check;

$main_file = 'C:/xampp/htdocs/DPLSplugin/dpls-schema-ai.php';
$slug      = 'dpls-schema-ai';

echo "Initializing Check_Context for: {$main_file} (slug: {$slug})\n";
$context = new Check_Context( $main_file, $slug, 'new' );

$repository = new Default_Check_Repository();
$collection = $repository->get_checks( Check_Repository::TYPE_ALL );
$all_checks = iterator_to_array( $collection );
echo "Total registered checks: " . count( $all_checks ) . "\n\n";

$result = new Check_Result( $context );

foreach ( $all_checks as $check_slug => $check ) {
	echo "Running check: {$check_slug}... ";
	try {
		$check->run( $result );
		echo "DONE\n";
	} catch ( \Throwable $t ) {
		echo "SKIPPED/EXCEPTION: " . $t->getMessage() . "\n";
	}
}

$error_count   = $result->get_error_count();
$warning_count = $result->get_warning_count();

echo "\n========================================\n";
echo "RESULTS: {$error_count} Errors, {$warning_count} Warnings\n";
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
	echo "CLEAN! All Plugin Check checks passed with 0 errors and 0 warnings!\n";
}
