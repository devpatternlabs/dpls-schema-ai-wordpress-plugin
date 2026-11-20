<?php
/**
 * WordPress.org SVN Preparation Script.
 *
 * Copies clean production plugin files into the checked-out SVN working copy:
 * - trunk/
 * - tags/2.0.0/
 *
 * @package DPLS\SchemaAI\Tools
 * @author  Dev Pattern Labs
 * @license GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || ( 'cli' === php_sapi_name() ) || exit;

$root_dir   = dirname( __DIR__ );
$svn_dir    = isset( $argv[1] ) ? rtrim( $argv[1], '\\/' ) : $root_dir . '/dist/svn';
$version    = '2.0.0';

echo "========================================\n";
echo " DPLS Schema AI - SVN Release Staging   \n";
echo " Version: {$version}                    \n";
echo " Target SVN Path: {$svn_dir}            \n";
echo "========================================\n\n";

if ( ! is_dir( $svn_dir ) ) {
	echo "Error: Target SVN directory '{$svn_dir}' does not exist.\n";
	echo "Please check out the SVN repository first using:\n";
	echo "  svn checkout https://plugins.svn.wordpress.org/dpls-schema-ai {$svn_dir}\n";
	exit( 1 );
}

$trunk_dir = $svn_dir . '/trunk';
$tag_dir   = $svn_dir . '/tags/' . $version;

if ( ! is_dir( $trunk_dir ) ) {
	mkdir( $trunk_dir, 0755, true );
}
if ( ! is_dir( $tag_dir ) ) {
	mkdir( $tag_dir, 0755, true );
}

$excluded_dirs = array(
	'.git',
	'.github',
	'.vscode',
	'dist',
	'scratch',
	'tests',
	'tools',
);

$excluded_files = array(
	'.gitignore',
	'.gitattributes',
	'LICENSE',
	'README.md',
	'package-release.php',
	'package-release.ps1',
);

function copy_recursive( $src, $dst, $excluded_dirs, $excluded_files ) {
	$dir = opendir( $src );
	if ( ! is_dir( $dst ) ) {
		mkdir( $dst, 0755, true );
	}

	while ( false !== ( $file = readdir( $dir ) ) ) {
		if ( ( '.' === $file ) || ( '..' === $file ) ) {
			continue;
		}

		if ( in_array( $file, $excluded_files, true ) ) {
			continue;
		}

		$src_path = $src . '/' . $file;
		$dst_path = $dst . '/' . $file;

		if ( is_dir( $src_path ) ) {
			if ( in_array( $file, $excluded_dirs, true ) ) {
				continue;
			}
			copy_recursive( $src_path, $dst_path, $excluded_dirs, $excluded_files );
		} else {
			copy( $src_path, $dst_path );
		}
	}
	closedir( $dir );
}

echo "1. Copying production files to trunk/ ...\n";
copy_recursive( $root_dir, $trunk_dir, $excluded_dirs, $excluded_files );
echo "   Done.\n";

echo "2. Copying production files to tags/{$version}/ ...\n";
copy_recursive( $root_dir, $tag_dir, $excluded_dirs, $excluded_files );
echo "   Done.\n\n";

echo "Success! Files staged for SVN commit.\n";
echo "Next steps in '{$svn_dir}':\n";
echo "  cd {$svn_dir}\n";
echo "  svn add --force trunk/* tags/{$version}/*\n";
echo "  svn status\n";
echo "  svn commit -m \"Initial release of DPLS Schema AI v{$version}\"\n";
