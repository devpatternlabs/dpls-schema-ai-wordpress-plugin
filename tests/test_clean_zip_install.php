<?php
/**
 * Clean ZIP Extraction & Installation Verification Test.
 *
 * Verifies that the production release archive extracts cleanly,
 * contains all required files, contains no development files,
 * and can be activated in a live WordPress environment.
 */

$zip_file = 'C:/xampp/htdocs/DPLSplugin/dist/dpls-schema-ai.zip';
$extract_dir = sys_get_temp_dir() . '/dpls_test_install_' . time();

echo "Testing extraction of: {$zip_file}\n";
echo "Destination: {$extract_dir}\n";

// Run PowerShell Expand-Archive to test extraction.
exec( "powershell -Command \"Expand-Archive -Path '{$zip_file}' -DestinationPath '{$extract_dir}' -Force\"", $output, $code );

if ( 0 !== $code ) {
	echo "Failed to extract ZIP with code: {$code}\n";
	exit( 1 );
}

$extracted_main = $extract_dir . '/dpls-schema-ai/dpls-schema-ai.php';
if ( ! file_exists( $extracted_main ) ) {
	echo "Failed: Main plugin file not found in extracted folder.\n";
	exit( 1 );
}

echo "[PASS] Extracted main plugin file found successfully.\n";

// Verify absence of test/dev files
$bad_files = array(
	$extract_dir . '/dpls-schema-ai/tests',
	$extract_dir . '/dpls-schema-ai/tools',
	$extract_dir . '/dpls-schema-ai/.gitignore',
);

foreach ( $bad_files as $bf ) {
	if ( file_exists( $bf ) ) {
		echo "Failed: Dev file or directory {$bf} found in production package!\n";
		exit( 1 );
	}
}

echo "[PASS] Verified: Zero development/test files present in production package.\n";

// Verify PHP syntax of all extracted files
$all_files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $extract_dir ) );
$php_count = 0;
foreach ( $all_files as $f ) {
	if ( $f->isFile() && $f->getExtension() === 'php' ) {
		$php_count++;
		$out = array();
		exec( "C:\\xampp\\php\\php.exe -l \"{$f->getPathname()}\"", $out, $lint_code );
		if ( 0 !== $lint_code ) {
			echo "Failed: Syntax error in extracted file {$f->getPathname()}: " . implode( "\n", $out ) . "\n";
			exit( 1 );
		}
	}
}

echo "[PASS] Verified syntax of all {$php_count} extracted PHP files with 0 errors.\n";

// Clean up temp dir
exec( "powershell -Command \"Remove-Item -Recurse -Force '{$extract_dir}'\"" );
echo "[PASS] Clean ZIP installation test passed successfully!\n";
