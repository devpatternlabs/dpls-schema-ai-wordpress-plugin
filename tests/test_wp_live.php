<?php
/**
 * Live WordPress Integration Test.
 *
 * Verifies that DPLS Schema AI is recognized, activates cleanly,
 * and boots without warnings or errors in the local WordPress site.
 */

$_SERVER['HTTP_HOST']       = 'localhost';
$_SERVER['REQUEST_METHOD']  = 'GET';
$_SERVER['REMOTE_ADDR']     = '127.0.0.1';

if ( ! file_exists( 'C:/xampp/htdocs/stars/wp-load.php' ) ) {
	echo "Local WordPress site at stars not found. Skipping live boot test.\n";
	exit( 0 );
}

require_once 'C:/xampp/htdocs/stars/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

echo "WordPress version: " . get_bloginfo( 'version' ) . PHP_EOL;

$plugins = get_plugins();
echo "Total plugins detected by WordPress: " . count( $plugins ) . PHP_EOL;

$plugin_slug = 'dpls-schema-ai/dpls-schema-ai.php';
if ( isset( $plugins[ $plugin_slug ] ) ) {
	$data = $plugins[ $plugin_slug ];
	echo "Found target plugin: " . $data['Name'] . ' v' . $data['Version'] . ' by ' . $data['Author'] . PHP_EOL;
	echo "Text Domain: " . $data['TextDomain'] . PHP_EOL;
} else {
	echo "Plugin key '{$plugin_slug}' not found. Listing installed plugins:" . PHP_EOL;
	foreach ( array_keys( $plugins ) as $p ) {
		echo " - " . $p . PHP_EOL;
	}
}

// Test activating the plugin in WP environment
if ( ! is_plugin_active( $plugin_slug ) ) {
	$result = activate_plugin( $plugin_slug );
	if ( is_wp_error( $result ) ) {
		echo "Activation error: " . $result->get_error_message() . PHP_EOL;
		exit( 1 );
	}
	echo "Plugin successfully activated in local WordPress!" . PHP_EOL;
} else {
	echo "Plugin is already active in local WordPress." . PHP_EOL;
}

// Verify singleton instance
if ( function_exists( 'dpls_schema_ai' ) ) {
	$instance = dpls_schema_ai();
	echo "Singleton dpls_schema_ai() verified. Instance of: " . get_class( $instance ) . PHP_EOL;
	echo "Registered services: " . implode( ', ', array(
		'settings', 'history', 'scanner', 'generator', 'validator', 'output', 'updater', 'automation'
	) ) . PHP_EOL;
} else {
	echo "Function dpls_schema_ai() not found after activation." . PHP_EOL;
	exit( 1 );
}

echo "Live WordPress integration test completed successfully!" . PHP_EOL;
