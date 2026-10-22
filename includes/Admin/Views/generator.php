<?php
/**
 * Schema Generator View.
 *
 * @package DPLS\SchemaAI\Admin\Views
 * @author  Dev Pattern Labs
 * @license GPL-2.0-or-later
 */

namespace DPLS\SchemaAI\Admin\Views;

use DPLS\SchemaAI\Common\Constants;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Variables passed from AdminMenu:
 *
 * @var \DPLS\SchemaAI\Services\SettingsService $settings
 */
$dpls_enabled_types = $settings->get( 'enabled_post_types', array( 'post', 'page' ) );
$dpls_recent_posts  = get_posts(
	array(
		'post_type'      => $dpls_enabled_types,
		'post_status'    => array( 'publish', 'draft' ),
		'posts_per_page' => 50,
		'orderby'        => 'title',
		'order'          => 'ASC',
	)
);
?>
<div class="wrap dpls-admin-wrap">
	<div class="dpls-header">
		<div class="dpls-header-title">
			<h1>
				<span class="dashicons dashicons-admin-generic" aria-hidden="true"></span>
				<?php esc_html_e( 'DPL Schema AI &mdash; Schema Generator', 'dpls-schema-ai' ); ?>
			</h1>
			<p class="dpls-tagline">
				<?php esc_html_e( 'Interactive schema composer and JSON-LD generator for pages and posts.', 'dpls-schema-ai' ); ?>
			</p>
		</div>
	</div>

	<div class="dpls-generator-layout">
		<!-- Left Form Column -->
		<div class="dpls-card dpls-form-card">
			<h2><?php esc_html_e( '1. Select Target Content', 'dpls-schema-ai' ); ?></h2>
			<div class="dpls-form-group">
				<label for="dpls-generator-post-select"><strong><?php esc_html_e( 'Choose Page or Post:', 'dpls-schema-ai' ); ?></strong></label>
				<select id="dpls-generator-post-select" class="regular-text">
					<option value=""><?php esc_html_e( '-- Select Content --', 'dpls-schema-ai' ); ?></option>
					<?php foreach ( $dpls_recent_posts as $dpls_p ) : ?>
						<option value="<?php echo esc_attr( $dpls_p->ID ); ?>">
							<?php echo esc_html( $dpls_p->post_title ? $dpls_p->post_title : '#' . $dpls_p->ID ); ?>
							(<?php echo esc_html( ucfirst( $dpls_p->post_type ) ); ?>)
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<h2><?php esc_html_e( '2. Schema Type Definition', 'dpls-schema-ai' ); ?></h2>
			<div class="dpls-form-group">
				<label for="dpls-generator-type-select"><strong><?php esc_html_e( 'Target Schema.org Type:', 'dpls-schema-ai' ); ?></strong></label>
				<select id="dpls-generator-type-select" class="regular-text">
					<?php foreach ( Constants::SCHEMA_TYPES as $dpls_val => $dpls_label ) : ?>
						<option value="<?php echo esc_attr( $dpls_val ); ?>"><?php echo esc_html( $dpls_label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="dpls-form-actions" style="display:flex; flex-direction:column; gap:8px;">
				<button type="button" id="dpls-generator-btn" class="button button-primary button-large" disabled>
					<span class="dashicons dashicons-admin-generic" aria-hidden="true"></span>
					<?php esc_html_e( 'Generate JSON-LD', 'dpls-schema-ai' ); ?>
				</button>
				<button type="button" id="dpls-btn-ai-analyze" class="button button-secondary" disabled>
					<span class="dashicons dashicons-lightbulb" aria-hidden="true"></span>
					<?php esc_html_e( 'AI Content Analysis & Comparison', 'dpls-schema-ai' ); ?>
				</button>
			</div>

			<div id="dpls-ai-analysis-card" class="dpls-card" style="display:none; margin-top:15px; background:#f0f6fc; border-left:4px solid #0073aa; padding:12px;">
				<h3 style="margin-top:0; font-size:14px;">
					<span class="dashicons dashicons-analytics" style="color:#0073aa;"></span>
					<?php esc_html_e( 'AI Recommendation & Comparison', 'dpls-schema-ai' ); ?>
				</h3>
				<div id="dpls-ai-analysis-content"></div>
			</div>
		</div>

		<!-- Right Preview Column -->
		<div class="dpls-card dpls-preview-card">
			<div class="dpls-code-viewer-bar">
				<h2><?php esc_html_e( 'Generated Schema Preview', 'dpls-schema-ai' ); ?></h2>
				<button type="button" class="button button-small dpls-copy-json" title="<?php esc_attr_e( 'Copy to clipboard', 'dpls-schema-ai' ); ?>">
					<span class="dashicons dashicons-clipboard" aria-hidden="true"></span>
					<?php esc_html_e( 'Copy JSON-LD', 'dpls-schema-ai' ); ?>
				</button>
			</div>

			<div id="dpls-gen-validation" class="dpls-validation-box" style="display:none;"></div>

			<textarea id="dpls-generator-output" class="dpls-code-editor" rows="18" placeholder="<?php esc_attr_e( 'Select content and click Generate to produce JSON-LD structured data...', 'dpls-schema-ai' ); ?>"></textarea>

			<div class="dpls-generator-footer" style="margin-top:15px; display:flex; gap:10px;">
				<button type="button" id="dpls-generator-apply-btn" class="button button-primary button-large" disabled>
					<span class="dashicons dashicons-saved" aria-hidden="true"></span>
					<?php esc_html_e( 'Apply Schema to Selected Content', 'dpls-schema-ai' ); ?>
				</button>
				<button type="button" id="dpls-generator-validate-btn" class="button button-secondary button-large" disabled>
					<span class="dashicons dashicons-yes" aria-hidden="true"></span>
					<?php esc_html_e( 'Validate Markup', 'dpls-schema-ai' ); ?>
				</button>
			</div>
		</div>
	</div>
</div>
