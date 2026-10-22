<?php
/**
 * Plugin Name: DPLS Schema AI
 * Plugin URI: https://devpatternlabs.com/plugins/dpls-schema-ai
 * Description: Automatically analyzes WordPress page and post content, recommends structured data, generates Schema.org JSON-LD, and maintains it when content changes.
 * Version: 2.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: Dev Pattern Labs
 * Author URI: https://devpatternlabs.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: dpls-schema-ai
 * Domain Path: /languages
 *
 * @package DPLS\SchemaAI
 * @author  Dev Pattern Labs
 * @license GPL-2.0-or-later
 */

// Prevent direct script access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants safely.
if ( ! defined( 'DPLS_SCHEMA_AI_VERSION' ) ) {
	define( 'DPLS_SCHEMA_AI_VERSION', '2.0.0' );
}

if ( ! defined( 'DPLS_SCHEMA_AI_FILE' ) ) {
	define( 'DPLS_SCHEMA_AI_FILE', __FILE__ );
}

if ( ! defined( 'DPLS_SCHEMA_AI_PATH' ) ) {
	define( 'DPLS_SCHEMA_AI_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'DPLS_SCHEMA_AI_URL' ) ) {
	define( 'DPLS_SCHEMA_AI_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'DPLS_SCHEMA_AI_BASENAME' ) ) {
	define( 'DPLS_SCHEMA_AI_BASENAME', plugin_basename( __FILE__ ) );
}

// Require and register PSR-4 Autoloader.
require_once DPLS_SCHEMA_AI_PATH . 'includes/Autoloader.php';
\DPLS\SchemaAI\Autoloader::register( DPLS_SCHEMA_AI_PATH . 'includes' );

// Register Activation and Deactivation hooks.
register_activation_hook( __FILE__, array( '\DPLS\SchemaAI\Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( '\DPLS\SchemaAI\Plugin', 'deactivate' ) );

/**
 * Main plugin bootstrap function.
 *
 * @return \DPLS\SchemaAI\Plugin
 */
function dpls_schema_ai(): \DPLS\SchemaAI\Plugin {
	return \DPLS\SchemaAI\Plugin::get_instance();
}

// Initialize on plugins_loaded.
add_action( 'plugins_loaded', 'dpls_schema_ai' );
