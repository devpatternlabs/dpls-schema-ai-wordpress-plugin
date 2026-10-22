<?php
/**
 * Production Release Packaging Script.
 *
 * Creates a clean, distributable ZIP archive of DPLS Schema AI
 * excluding all unit tests, development scripts, and version control files.
 *
 * @package DPLS\SchemaAI\Tools
 * @author  Dev Pattern Labs
 * @license GPL-2.0-or-later
 */

// Prevent direct web access.
defined( 'ABSPATH' ) || ( 'cli' === php_sapi_name() ) || exit;

$root_dir = dirname( __DIR__ );
$dist_dir = $root_dir . '/dist';
$zip_file = $dist_dir . '/dpls-schema-ai.zip';

if ( ! is_dir( $dist_dir ) ) {
	mkdir( $dist_dir, 0755, true );
}

if ( file_exists( $zip_file ) ) {
	unlink( $zip_file );
}

$zip = new ZipArchive();
if ( true !== $zip->open( $zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
	echo "Failed to create ZIP archive at: {$zip_file}\n";
	exit( 1 );
}

$iterator = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator( $root_dir, RecursiveDirectoryIterator::SKIP_DOTS ),
	RecursiveIteratorIterator::SELF_FIRST
);

$excluded_dirs = array(
	'tests',
	'dist',
	'tools',
	'.git',
	'.github',
	'.vscode',
	'scratch',
);

$excluded_files = array(
	'.gitignore',
	'.gitattributes',
	'package-release.php',
);

$file_count = 0;

foreach ( $iterator as $item ) {
	$sub_path = str_replace( '\\', '/', substr( $item->getPathname(), strlen( $root_dir ) + 1 ) );
	$parts    = explode( '/', $sub_path );

	// Check if file is in an excluded directory
	if ( in_array( $parts[0], $excluded_dirs, true ) ) {
		continue;
	}

	// Check if file name is excluded or is a zip archive
	if ( in_array( $item->getFilename(), $excluded_files, true ) || str_ends_with( $item->getFilename(), '.zip' ) ) {
		continue;
	}

	$zip_path = 'dpls-schema-ai/' . $sub_path;

	if ( $item->isDir() ) {
		$zip->addEmptyDir( $zip_path );
	} elseif ( $item->isFile() ) {
		$zip->addFile( $item->getPathname(), $zip_path );
		$file_count++;
	}
}

$zip->close();

echo "Successfully built production release package!\n";
echo "Location: {$zip_file}\n";
echo "Archive size: " . number_format( filesize( $zip_file ) / 1024, 2 ) . " KB\n";
echo "Total production files packaged: {$file_count}\n";
