<?php
// Test WordPress header parsing.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', 'C:/xampp/htdocs/stars/' );
}
if ( ! defined( 'WPINC' ) ) {
	define( 'WPINC', 'wp-includes' );
}

require_once 'C:/xampp/htdocs/stars/wp-includes/plugin.php';
require_once 'C:/xampp/htdocs/stars/wp-includes/load.php';
require_once 'C:/xampp/htdocs/stars/wp-includes/default-constants.php';
wp_initial_constants();
require_once 'C:/xampp/htdocs/stars/wp-includes/functions.php';
require_once 'C:/xampp/htdocs/stars/wp-admin/includes/plugin.php';

$headers = array(
	'Name'        => 'Plugin Name',
	'PluginURI'   => 'Plugin URI',
	'Version'     => 'Version',
	'Description' => 'Description',
	'Author'      => 'Author',
	'AuthorURI'   => 'Author URI',
	'TextDomain'  => 'Text Domain',
	'RequiresWP'  => 'Requires at least',
	'RequiresPHP' => 'Requires PHP',
	'License'     => 'License',
	'LicenseURI'  => 'License URI',
);

$data = get_file_data( 'C:/xampp/htdocs/DPLSplugin/dpls-schema-ai.php', $headers, 'plugin' );

echo "Name: " . $data['Name'] . PHP_EOL;
echo "PluginURI: " . $data['PluginURI'] . PHP_EOL;
echo "Version: " . $data['Version'] . PHP_EOL;
echo "Description: " . $data['Description'] . PHP_EOL;
echo "Author: " . $data['Author'] . PHP_EOL;
echo "AuthorURI: " . $data['AuthorURI'] . PHP_EOL;
echo "TextDomain: " . $data['TextDomain'] . PHP_EOL;
echo "RequiresWP: " . $data['RequiresWP'] . PHP_EOL;
echo "RequiresPHP: " . $data['RequiresPHP'] . PHP_EOL;
echo "License: " . $data['License'] . PHP_EOL;
echo "LicenseURI: " . $data['LicenseURI'] . PHP_EOL;
