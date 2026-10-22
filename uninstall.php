<?php
/**
 * Plugin Uninstall Handler.
 *
 * @package DPLS\SchemaAI
 * @author  Dev Pattern Labs
 * @license GPL-2.0-or-later
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Check data preservation preference. Preserves user data by default (1).
$dpls_preserve_data = get_option( 'dpls_schema_ai_preserve_data', 1 );

if ( ! empty( $dpls_preserve_data ) ) {
	// Administrator chose to preserve all data; do nothing.
	return;
}

// Explicit cleanup opted in by administrator:
// 1. Delete all plugin options.
delete_option( 'dpls_schema_ai_settings' );
delete_option( 'dpls_schema_ai_last_scan' );
delete_option( 'dpls_schema_ai_history' );
delete_option( 'dpls_schema_ai_preserve_data' );
delete_option( 'dpls_schema_ai_scan_job' );
delete_option( 'dpls_schema_ai_debug_log' );

// 2. Clear scheduled cron hooks.
$dpls_timestamp = wp_next_scheduled( 'dpls_schema_ai_cron_scan' );
if ( $dpls_timestamp ) {
	wp_unschedule_event( $dpls_timestamp, 'dpls_schema_ai_cron_scan' );
}

// 3. Delete only plugin-owned post metadata safely using WordPress delete_metadata API.
// Does NOT touch post metadata managed by WordPress core, WooCommerce, Yoast, or Rank Math.
$dpls_meta_keys = array(
	'_dpls_schema_status',
	'_dpls_suggested_schema',
	'_dpls_active_schema',
	'_dpls_draft_schema',
	'_dpls_normalized_content',
	'_dpls_schema_proposal',
	'_dpls_schema_version',
	'_dpls_schema_history',
	'_dpls_locked_properties',
	'_dpls_last_analyzed',
	'_dpls_applied_time',
	'_dpls_auto_update',
	'_dpls_validation_errors',
	'_dpls_content_hash',
);

foreach ( $dpls_meta_keys as $dpls_key ) {
	delete_metadata( 'post', 0, $dpls_key, '', true );
}
