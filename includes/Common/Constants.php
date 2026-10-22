<?php
/**
 * Plugin Constants and Configuration Keys.
 *
 * @package DPLS\SchemaAI\Common
 * @author  Dev Pattern Labs
 * @license GPL-2.0-or-later
 */

namespace DPLS\SchemaAI\Common;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Constants definition class.
 */
class Constants {

	// Plugin identification.
	public const PLUGIN_NAME = 'DPLS Schema AI';
	public const PLUGIN_SLUG = 'dpls-schema-ai';
	public const MENU_SLUG   = 'dpls-schema-ai';
	public const TEXT_DOMAIN = 'dpls-schema-ai';
	public const VERSION     = '2.0.0';

	// Permissions.
	public const REQUIRED_CAPABILITY = 'manage_options';

	// Post Meta Keys.
	public const META_SCHEMA_STATUS       = '_dpls_schema_status';
	public const META_SUGGESTED_SCHEMA    = '_dpls_suggested_schema';
	public const META_ACTIVE_SCHEMA       = '_dpls_active_schema';
	public const META_DRAFT_SCHEMA        = '_dpls_draft_schema';
	public const META_NORMALIZED_CONTENT  = '_dpls_normalized_content';
	public const META_SCHEMA_PROPOSAL     = '_dpls_schema_proposal';
	public const META_SCHEMA_VERSION      = '_dpls_schema_version';
	public const META_SCHEMA_HISTORY      = '_dpls_schema_history';
	public const META_LOCKED_PROPERTIES   = '_dpls_locked_properties';
	public const META_LAST_ANALYZED       = '_dpls_last_analyzed';
	public const META_APPLIED_TIME        = '_dpls_applied_time';
	public const META_AUTO_UPDATE         = '_dpls_auto_update';
	public const META_VALIDATION_ERRORS   = '_dpls_validation_errors';
	public const META_CONTENT_HASH        = '_dpls_content_hash';

	// Option Keys.
	public const OPTION_SETTINGS      = 'dpls_schema_ai_settings';
	public const OPTION_LAST_SCAN     = 'dpls_schema_ai_last_scan';
	public const OPTION_PRESERVE_DATA = 'dpls_schema_ai_preserve_data';
	public const OPTION_HISTORY       = 'dpls_schema_ai_history';
	public const OPTION_SCAN_JOB      = 'dpls_schema_ai_scan_job';
	public const OPTION_DEBUG_LOG     = 'dpls_schema_ai_debug_log';

	// Transients.
	public const TRANSIENT_SCAN_LOCK = 'dpls_schema_ai_scan_lock';

	// Nonce Actions.
	public const NONCE_ACTION_ADMIN   = 'dpls_schema_ai_admin_nonce';
	public const NONCE_ACTION_AJAX    = 'dpls_schema_ai_ajax_nonce';
	public const NONCE_ACTION_IMPORT  = 'dpls_schema_ai_import_nonce';
	public const NONCE_ACTION_EXPORT  = 'dpls_schema_ai_export_nonce';

	// Schema Status Enums.
	public const STATUS_NONE           = 'none';
	public const STATUS_PENDING_REVIEW = 'pending_review';
	public const STATUS_APPLIED        = 'applied';
	public const STATUS_WARNINGS       = 'warnings';
	public const STATUS_NEEDS_UPDATE   = 'needs_update';
	public const STATUS_DISABLED       = 'disabled';

	// Automation Policies.
	public const POLICY_OFF    = 'off';
	public const POLICY_SAFE   = 'safe';
	public const POLICY_REVIEW = 'review';

	// Supported Schema Types.
	public const SCHEMA_TYPES = array(
		'Article'        => 'Article',
		'BlogPosting'    => 'BlogPosting',
		'NewsArticle'    => 'NewsArticle',
		'WebPage'        => 'WebPage',
		'AboutPage'      => 'AboutPage',
		'ContactPage'    => 'ContactPage',
		'FAQPage'        => 'FAQPage',
		'Service'        => 'Service',
		'Product'        => 'Product',
		'Recipe'         => 'Recipe',
		'HowTo'          => 'HowTo',
		'LocalBusiness'  => 'LocalBusiness',
		'Organization'   => 'Organization',
		'BreadcrumbList' => 'BreadcrumbList',
		'Event'          => 'Event',
	);
}
