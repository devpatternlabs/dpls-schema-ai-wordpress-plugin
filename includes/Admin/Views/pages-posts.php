<?php
/**
 * Pages and Posts Management View.
 *
 * @package DPLS\SchemaAI\Admin\Views
 * @author  Dev Pattern Labs
 * @license GPL-2.0-or-later
 */

use DPLS\SchemaAI\Generator\SchemaRegistry;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Variables passed from AdminMenu:
 *
 * @var \DPLS\SchemaAI\Admin\ContentTable $table
 */
$dpls_all_types = SchemaRegistry::get_all();
?>
<div class="wrap dpls-admin-wrap">
	<div class="dpls-header">
		<div class="dpls-header-title">
			<h1>
				<span class="dashicons dashicons-admin-post" aria-hidden="true"></span>
				<?php esc_html_e( 'DPL Schema AI &mdash; Pages & Posts', 'dpls-schema-ai' ); ?>
			</h1>
			<p class="dpls-tagline">
				<?php esc_html_e( 'Review, generate, validate, and manage Schema.org structured data across all content.', 'dpls-schema-ai' ); ?>
			</p>
		</div>
		<div class="dpls-header-actions">
			<button type="button" class="button button-primary dpls-trigger-batch-scan">
				<span class="dashicons dashicons-update" aria-hidden="true"></span>
				<?php esc_html_e( 'Scan All Content', 'dpls-schema-ai' ); ?>
			</button>
		</div>
	</div>

	<!-- Background Scan Progress Banner -->
	<div id="dpls-scan-progress-wrap" class="dpls-card" style="display:none; margin-bottom:20px; padding:15px;">
		<div style="display:flex; justify-content:space-between; margin-bottom:8px;">
			<span id="dpls-scan-progress-label"><strong><?php esc_html_e( 'Scanning content in background chunks...', 'dpls-schema-ai' ); ?></strong></span>
			<span id="dpls-scan-progress-percent">0%</span>
		</div>
		<div style="background:#e0e0e0; height:12px; border-radius:6px; overflow:hidden;">
			<div id="dpls-scan-progress-bar" style="background:#2271b1; height:100%; width:0%; transition:width 0.3s ease;"></div>
		</div>
	</div>

	<!-- Notice Container for AJAX notifications -->
	<div id="dpls-ajax-notice" class="notice notice-info is-dismissible" style="display:none;">
		<p id="dpls-ajax-notice-message"></p>
	</div>

	<!-- Content Table Card -->
	<div class="dpls-card dpls-table-card">
		<?php $table->render_filters( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		
		<?php $table->render_table(); ?>

		<div class="tablenav bottom">
			<?php $table->render_pagination(); ?>
		</div>
	</div>
</div>

<!-- Schema Preview & Review Modal Dialog -->
<div id="dpls-preview-modal" class="dpls-modal-backdrop" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="dpls-modal-title">
	<div class="dpls-modal dpls-modal-lg">
		<div class="dpls-modal-header">
			<h2 id="dpls-modal-title"><?php esc_html_e( 'Schema Review & JSON-LD Inspector', 'dpls-schema-ai' ); ?></h2>
			<button type="button" class="dpls-modal-close" aria-label="<?php esc_attr_e( 'Close', 'dpls-schema-ai' ); ?>">&times;</button>
		</div>
		<div class="dpls-modal-body">
			<div class="dpls-modal-meta" style="flex-wrap:wrap; gap:15px; align-items:center;">
				<p style="margin:0;">
					<strong><?php esc_html_e( 'Content:', 'dpls-schema-ai' ); ?></strong>
					<span id="dpls-modal-post-title"></span>
				</p>
				<p style="margin:0;">
					<strong><?php esc_html_e( 'Current Status:', 'dpls-schema-ai' ); ?></strong>
					<span id="dpls-modal-status-badge"></span>
				</p>
				<div style="margin-left:auto; display:flex; align-items:center; gap:8px;">
					<label for="dpls-modal-change-type"><strong><?php esc_html_e( 'Schema Type:', 'dpls-schema-ai' ); ?></strong></label>
					<select id="dpls-modal-change-type" style="font-size:12px;">
						<?php foreach ( $dpls_all_types as $dpls_t_val => $dpls_t_info ) : ?>
							<option value="<?php echo esc_attr( $dpls_t_val ); ?>"><?php echo esc_html( $dpls_t_info['label'] ); ?></option>
						<?php endforeach; ?>
					</select>
					<button type="button" class="button button-small" id="dpls-modal-regenerate-btn"><?php esc_html_e( 'Regenerate', 'dpls-schema-ai' ); ?></button>
				</div>
			</div>

			<!-- Warnings and recommendations panel -->
			<div id="dpls-modal-missing-info" class="notice notice-warning inline" style="display:none; margin:0 0 15px 0;">
				<p><strong><?php esc_html_e( 'Recommendations / Missing Fields:', 'dpls-schema-ai' ); ?></strong></p>
				<ul id="dpls-modal-missing-list" style="margin:4px 0 8px 18px; list-style:disc;"></ul>
			</div>

			<div id="dpls-modal-validation-summary" class="dpls-validation-box" style="display:none;"></div>

			<div class="dpls-code-viewer-wrap">
				<div class="dpls-code-viewer-bar">
					<span><strong>JSON-LD (Schema.org)</strong></span>
					<button type="button" class="button button-small dpls-copy-json" title="<?php esc_attr_e( 'Copy to clipboard', 'dpls-schema-ai' ); ?>">
						<span class="dashicons dashicons-clipboard" aria-hidden="true"></span>
						<?php esc_html_e( 'Copy', 'dpls-schema-ai' ); ?>
					</button>
				</div>
				<textarea id="dpls-modal-schema-code" class="dpls-code-editor" rows="14" spellcheck="false"></textarea>
			</div>

			<!-- Rollback section if versions exist -->
			<div id="dpls-modal-rollback-wrap" style="margin-top:15px; padding:10px; background:#f6f7f7; border:1px solid #dcdcde; border-radius:4px; display:none;">
				<strong><?php esc_html_e( 'Restore Previous Version (Rollback):', 'dpls-schema-ai' ); ?></strong>
				<div style="display:flex; gap:10px; margin-top:6px; align-items:center;">
					<select id="dpls-modal-rollback-version" style="font-size:12px;"></select>
					<button type="button" class="button button-small" id="dpls-modal-rollback-btn"><?php esc_html_e( 'Rollback to Selected Version', 'dpls-schema-ai' ); ?></button>
				</div>
			</div>
		</div>
		<div class="dpls-modal-footer">
			<input type="hidden" id="dpls-modal-post-id" value="" />
			<button type="button" class="button button-secondary dpls-modal-close-btn"><?php esc_html_e( 'Close', 'dpls-schema-ai' ); ?></button>
			<button type="button" class="button button-primary dpls-modal-apply-btn">
				<span class="dashicons dashicons-saved" aria-hidden="true"></span>
				<?php esc_html_e( 'Apply Schema to Site', 'dpls-schema-ai' ); ?>
			</button>
		</div>
	</div>
</div>

<!-- History Modal Dialog -->
<div id="dpls-history-modal" class="dpls-modal-backdrop" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="dpls-history-modal-title">
	<div class="dpls-modal dpls-modal-lg">
		<div class="dpls-modal-header">
			<h2 id="dpls-history-modal-title"><?php esc_html_e( 'Content Schema History & Audit Log', 'dpls-schema-ai' ); ?></h2>
			<button type="button" class="dpls-modal-close" aria-label="<?php esc_attr_e( 'Close', 'dpls-schema-ai' ); ?>">&times;</button>
		</div>
		<div class="dpls-modal-body">
			<table class="wp-list-table widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Date & Time', 'dpls-schema-ai' ); ?></th>
						<th><?php esc_html_e( 'Action', 'dpls-schema-ai' ); ?></th>
						<th><?php esc_html_e( 'Actor', 'dpls-schema-ai' ); ?></th>
						<th><?php esc_html_e( 'Details', 'dpls-schema-ai' ); ?></th>
					</tr>
				</thead>
				<tbody id="dpls-history-list">
					<tr>
						<td colspan="4"><?php esc_html_e( 'Loading history...', 'dpls-schema-ai' ); ?></td>
					</tr>
				</tbody>
			</table>
		</div>
		<div class="dpls-modal-footer">
			<button type="button" class="button button-secondary dpls-modal-close-btn"><?php esc_html_e( 'Close', 'dpls-schema-ai' ); ?></button>
		</div>
	</div>
</div>
