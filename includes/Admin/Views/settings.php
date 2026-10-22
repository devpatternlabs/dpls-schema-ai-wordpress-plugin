<?php
/**
 * Settings View.
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
 * @var array $settings Current settings array.
 * @var \DPLS\SchemaAI\Services\ImportExportService $import_export
 */
$dpls_public_types = get_post_types( array( 'public' => true ), 'objects' );
$dpls_org          = $settings['organization'] ?? array();
$dpls_same_as_text = is_array( $dpls_org['same_as'] ?? null ) ? implode( "\n", $dpls_org['same_as'] ) : '';
?>
<div class="wrap dpls-admin-wrap">
	<div class="dpls-header">
		<div class="dpls-header-title">
			<h1>
				<span class="dashicons dashicons-admin-settings" aria-hidden="true"></span>
				<?php esc_html_e( 'DPLS Schema AI &mdash; Settings & Data Management', 'dpls-schema-ai' ); ?>
			</h1>
			<p class="dpls-tagline">
				<?php esc_html_e( 'Configure verified organization identity, content post types, automated maintenance policies, backup import/export, and storage preservation.', 'dpls-schema-ai' ); ?>
			</p>
		</div>
	</div>

	<div class="dpls-card dpls-form-card" style="max-width:960px;">
		<form method="post" action="">
			<?php wp_nonce_field( Constants::NONCE_ACTION_ADMIN, 'dpls_nonce' ); ?>

			<!-- Section 1: Verified Organization Details -->
			<h2>
				<span class="dashicons dashicons-building" style="color:#2271b1; vertical-align:text-bottom;"></span>
				<?php esc_html_e( 'Verified Organization Identity', 'dpls-schema-ai' ); ?>
			</h2>
			<p class="description">
				<?php esc_html_e( 'Accurate Organization metadata outputs on your homepage, articles, and rich snippets. Do not assume the WordPress site name alone provides complete structured organization data.', 'dpls-schema-ai' ); ?>
			</p>

			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><label for="dpls-org-name"><?php esc_html_e( 'Organization Name', 'dpls-schema-ai' ); ?></label></th>
						<td>
							<input type="text" id="dpls-org-name" name="dpls_settings[organization][name]" value="<?php echo esc_attr( $dpls_org['name'] ?? '' ); ?>" class="regular-text" required />
							<p class="description"><?php esc_html_e( 'Official legal name or recognized trading name.', 'dpls-schema-ai' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="dpls-org-url"><?php esc_html_e( 'Canonical Website URL', 'dpls-schema-ai' ); ?></label></th>
						<td>
							<input type="url" id="dpls-org-url" name="dpls_settings[organization][url]" value="<?php echo esc_attr( $dpls_org['url'] ?? '' ); ?>" class="regular-text" />
							<p class="description"><?php esc_html_e( 'e.g. https://example.com', 'dpls-schema-ai' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="dpls-org-logo"><?php esc_html_e( 'Logo Image URL', 'dpls-schema-ai' ); ?></label></th>
						<td>
							<input type="url" id="dpls-org-logo" name="dpls_settings[organization][logo_url]" value="<?php echo esc_attr( $dpls_org['logo_url'] ?? '' ); ?>" class="regular-text" placeholder="https://..." />
							<p class="description"><?php esc_html_e( 'High-resolution square or rectangle logo image (SVG, PNG, or WebP).', 'dpls-schema-ai' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="dpls-org-type"><?php esc_html_e( 'Organization Entity Type', 'dpls-schema-ai' ); ?></label></th>
						<td>
							<select id="dpls-org-type" name="dpls_settings[organization][type]">
								<option value="Organization" <?php selected( $dpls_org['type'] ?? '', 'Organization' ); ?>><?php esc_html_e( 'General Organization', 'dpls-schema-ai' ); ?></option>
								<option value="Corporation" <?php selected( $dpls_org['type'] ?? '', 'Corporation' ); ?>><?php esc_html_e( 'Corporation / Commercial Business', 'dpls-schema-ai' ); ?></option>
								<option value="LocalBusiness" <?php selected( $dpls_org['type'] ?? '', 'LocalBusiness' ); ?>><?php esc_html_e( 'Local Business / Physical Store', 'dpls-schema-ai' ); ?></option>
								<option value="NGO" <?php selected( $dpls_org['type'] ?? '', 'NGO' ); ?>><?php esc_html_e( 'Non-Governmental Organization (NGO)', 'dpls-schema-ai' ); ?></option>
								<option value="EducationalOrganization" <?php selected( $dpls_org['type'] ?? '', 'EducationalOrganization' ); ?>><?php esc_html_e( 'Educational Institution', 'dpls-schema-ai' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="dpls-org-email"><?php esc_html_e( 'Public Contact Email', 'dpls-schema-ai' ); ?></label></th>
						<td>
							<input type="email" id="dpls-org-email" name="dpls_settings[organization][email]" value="<?php echo esc_attr( $dpls_org['email'] ?? '' ); ?>" class="regular-text" />
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="dpls-org-phone"><?php esc_html_e( 'Public Contact Phone', 'dpls-schema-ai' ); ?></label></th>
						<td>
							<input type="text" id="dpls-org-phone" name="dpls_settings[organization][phone]" value="<?php echo esc_attr( $dpls_org['phone'] ?? '' ); ?>" class="regular-text" placeholder="+1-800-555-0199" />
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="dpls-org-sameas"><?php esc_html_e( 'Social Profiles (sameAs URLs)', 'dpls-schema-ai' ); ?></label></th>
						<td>
							<textarea id="dpls-org-sameas" name="dpls_settings[organization][same_as]" rows="4" class="large-text" placeholder="https://www.linkedin.com/company/...&#10;https://twitter.com/...&#10;https://facebook.com/..."><?php echo esc_textarea( $dpls_same_as_text ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Enter one official verified profile URL per line.', 'dpls-schema-ai' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>

			<hr style="margin:25px 0; border:0; border-top:1px solid #dcdcde;" />

			<!-- Section 2: Automation Policies -->
			<h2>
				<span class="dashicons dashicons-update" style="color:#2271b1; vertical-align:text-bottom;"></span>
				<?php esc_html_e( 'Automatic Maintenance Policies', 'dpls-schema-ai' ); ?>
			</h2>
			<p class="description">
				<?php esc_html_e( 'Control how approved schema adapts when authors edit WordPress content.', 'dpls-schema-ai' ); ?>
			</p>

			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'Content Change Policy', 'dpls-schema-ai' ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><?php esc_html_e( 'Content Change Policy', 'dpls-schema-ai' ); ?></legend>
								<label style="display:block; margin-bottom:10px;">
									<input type="radio" name="dpls_settings[automation_policy]" value="<?php echo esc_attr( Constants::POLICY_SAFE ); ?>" <?php checked( ( $settings['automation_policy'] ?? Constants::POLICY_SAFE ), Constants::POLICY_SAFE ); ?> />
									<strong><?php esc_html_e( 'SAFE AUTO-UPDATE (Recommended)', 'dpls-schema-ai' ); ?></strong>
									<span class="description" style="display:block; margin-left:22px;"><?php esc_html_e( 'Automatically updates headlines, descriptions, modified dates, and images when the Schema type remains unchanged. Locks preserved custom edits.', 'dpls-schema-ai' ); ?></span>
								</label>
								<label style="display:block; margin-bottom:10px;">
									<input type="radio" name="dpls_settings[automation_policy]" value="<?php echo esc_attr( Constants::POLICY_REVIEW ); ?>" <?php checked( ( $settings['automation_policy'] ?? '' ), Constants::POLICY_REVIEW ); ?> />
									<strong><?php esc_html_e( 'REVIEW REQUIRED', 'dpls-schema-ai' ); ?></strong>
									<span class="description" style="display:block; margin-left:22px;"><?php esc_html_e( 'Queues all modified content for manual administrator review before updating live schema.', 'dpls-schema-ai' ); ?></span>
								</label>
								<label style="display:block;">
									<input type="radio" name="dpls_settings[automation_policy]" value="<?php echo esc_attr( Constants::POLICY_OFF ); ?>" <?php checked( ( $settings['automation_policy'] ?? '' ), Constants::POLICY_OFF ); ?> />
									<strong><?php esc_html_e( 'OFF', 'dpls-schema-ai' ); ?></strong>
									<span class="description" style="display:block; margin-left:22px;"><?php esc_html_e( 'Never automatically touch approved schema upon post save.', 'dpls-schema-ai' ); ?></span>
								</label>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="dpls-scan-interval"><?php esc_html_e( 'Background Scan Frequency', 'dpls-schema-ai' ); ?></label></th>
						<td>
							<select id="dpls-scan-interval" name="dpls_settings[auto_scan_interval]">
								<option value="daily" <?php selected( $settings['auto_scan_interval'], 'daily' ); ?>><?php esc_html_e( 'Daily', 'dpls-schema-ai' ); ?></option>
								<option value="weekly" <?php selected( $settings['auto_scan_interval'], 'weekly' ); ?>><?php esc_html_e( 'Weekly', 'dpls-schema-ai' ); ?></option>
								<option value="disabled" <?php selected( $settings['auto_scan_interval'], 'disabled' ); ?>><?php esc_html_e( 'Disabled (Manual scans only)', 'dpls-schema-ai' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'How frequently WP-Cron scans for newly published content.', 'dpls-schema-ai' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>

			<hr style="margin:25px 0; border:0; border-top:1px solid #dcdcde;" />

			<!-- Section 3: Content Types & Default Behavior -->
			<h2>
				<span class="dashicons dashicons-admin-post" style="color:#2271b1; vertical-align:text-bottom;"></span>
				<?php esc_html_e( 'Supported Content & Default Behavior', 'dpls-schema-ai' ); ?>
			</h2>

			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'Enabled Post Types', 'dpls-schema-ai' ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><?php esc_html_e( 'Enabled Post Types', 'dpls-schema-ai' ); ?></legend>
								<?php foreach ( $dpls_public_types as $dpls_pt_name => $dpls_pt_obj ) : ?>
									<?php if ( 'attachment' === $dpls_pt_name ) continue; ?>
									<label style="display:block; margin-bottom:8px;">
										<input type="checkbox" name="dpls_settings[enabled_post_types][]" value="<?php echo esc_attr( $dpls_pt_name ); ?>" <?php checked( in_array( $dpls_pt_name, (array) $settings['enabled_post_types'], true ) ); ?> />
										<strong><?php echo esc_html( $dpls_pt_obj->labels->name ); ?></strong>
										<code>(<?php echo esc_html( $dpls_pt_name ); ?>)</code>
									</label>
								<?php endforeach; ?>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="dpls-default-schema-type"><?php esc_html_e( 'Default Fallback Schema', 'dpls-schema-ai' ); ?></label></th>
						<td>
							<select id="dpls-default-schema-type" name="dpls_settings[default_schema_type]">
								<option value="WebPage" <?php selected( $settings['default_schema_type'] ?? 'WebPage', 'WebPage' ); ?>><?php esc_html_e( 'WebPage (Standard Fallback)', 'dpls-schema-ai' ); ?></option>
								<option value="Article" <?php selected( $settings['default_schema_type'] ?? '', 'Article' ); ?>><?php esc_html_e( 'Article', 'dpls-schema-ai' ); ?></option>
								<option value="BlogPosting" <?php selected( $settings['default_schema_type'] ?? '', 'BlogPosting' ); ?>><?php esc_html_e( 'BlogPosting', 'dpls-schema-ai' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'Applied when no specialized schema signals qualify for content.', 'dpls-schema-ai' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Frontend Output Options', 'dpls-schema-ai' ); ?></th>
						<td>
							<label style="display:block; margin-bottom:8px;">
								<input type="checkbox" name="dpls_settings[output_breadcrumbs]" value="1" <?php checked( ! empty( $settings['output_breadcrumbs'] ) ); ?> />
								<strong><?php esc_html_e( 'Include BreadcrumbList navigation entities in schema graph', 'dpls-schema-ai' ); ?></strong>
							</label>
							<label style="display:block;">
								<input type="checkbox" name="dpls_settings[output_minify_json]" value="1" <?php checked( ! empty( $settings['output_minify_json'] ) ); ?> />
								<strong><?php esc_html_e( 'Minify JSON-LD output in HTML head', 'dpls-schema-ai' ); ?></strong>
							</label>
						</td>
					</tr>
				</tbody>
			</table>

			<hr style="margin:25px 0; border:0; border-top:1px solid #dcdcde;" />

			<!-- Section 4: History & Diagnostics -->
			<h2>
				<span class="dashicons dashicons-backup" style="color:#2271b1; vertical-align:text-bottom;"></span>
				<?php esc_html_e( 'History Retention & Diagnostics', 'dpls-schema-ai' ); ?>
			</h2>

			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><label for="dpls-history-days"><?php esc_html_e( 'History Retention (Days)', 'dpls-schema-ai' ); ?></label></th>
						<td>
							<input type="number" id="dpls-history-days" name="dpls_settings[history_retention_days]" value="<?php echo esc_attr( $settings['history_retention_days'] ?? 30 ); ?>" min="1" max="365" class="small-text" />
							<p class="description"><?php esc_html_e( 'Days to retain audit events in change history log.', 'dpls-schema-ai' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="dpls-history-versions"><?php esc_html_e( 'Max Versions Per Post', 'dpls-schema-ai' ); ?></label></th>
						<td>
							<input type="number" id="dpls-history-versions" name="dpls_settings[history_max_versions]" value="<?php echo esc_attr( $settings['history_max_versions'] ?? 10 ); ?>" min="1" max="50" class="small-text" />
							<p class="description"><?php esc_html_e( 'Number of previous schema snapshots kept per post for rollback.', 'dpls-schema-ai' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Debug Logging', 'dpls-schema-ai' ); ?></th>
						<td>
							<label for="dpls-debug-logging">
								<input type="checkbox" id="dpls-debug-logging" name="dpls_settings[debug_logging]" value="1" <?php checked( ! empty( $settings['debug_logging'] ) ); ?> />
								<strong><?php esc_html_e( 'Enable developer diagnostic logging', 'dpls-schema-ai' ); ?></strong>
							</label>
							<p class="description"><?php esc_html_e( 'Logs schema generation milestones to WP_DEBUG_LOG when WP_DEBUG is enabled.', 'dpls-schema-ai' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>

			<hr style="margin:25px 0; border:0; border-top:1px solid #dcdcde;" />

			<!-- Section 5: Data Preservation & Uninstall -->
			<h2>
				<span class="dashicons dashicons-trash" style="color:#d63638; vertical-align:text-bottom;"></span>
				<?php esc_html_e( 'Data Preservation & Uninstall Strategy', 'dpls-schema-ai' ); ?>
			</h2>
			<p class="description">
				<?php esc_html_e( 'By default, DPLS Schema AI preserves all user data, applied schemas, and version histories upon uninstall so you never lose structured data unintentionally.', 'dpls-schema-ai' ); ?>
			</p>

			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'Preserve User Data', 'dpls-schema-ai' ); ?></th>
						<td>
							<label for="dpls-preserve-data">
								<input type="checkbox" id="dpls-preserve-data" name="dpls_settings[preserve_data]" value="1" <?php checked( ! empty( $settings['preserve_data'] ) ); ?> />
								<strong><?php esc_html_e( 'Preserve user data and generated schemas on uninstall (Default)', 'dpls-schema-ai' ); ?></strong>
							</label>
							<p class="description">
								<?php esc_html_e( 'Recommended. Leaves post metadata and options intact if the plugin is temporarily deactivated or upgraded.', 'dpls-schema-ai' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row" style="color:#d63638;"><?php esc_html_e( 'Permanent Data Removal', 'dpls-schema-ai' ); ?></th>
						<td>
							<label for="dpls-delete-on-uninstall" style="color:#d63638;">
								<input type="checkbox" id="dpls-delete-on-uninstall" name="dpls_settings[delete_all_data_on_uninstall]" value="1" <?php checked( ! empty( $settings['delete_all_data_on_uninstall'] ) ); ?> />
								<strong><?php esc_html_e( 'Explicitly delete ALL plugin data upon uninstall', 'dpls-schema-ai' ); ?></strong>
							</label>
							<p class="description" style="color:#d63638;">
								<?php esc_html_e( 'DANGER: Checking this box overrides data preservation. When the plugin is deleted via the WordPress Plugins screen, all DPLS options, history logs, and post schema metadata will be permanently deleted. Does NOT delete schemas managed by third-party plugins.', 'dpls-schema-ai' ); ?>
							</p>
						</td>
					</tr>
				</tbody>
			</table>

			<p class="submit">
				<input type="submit" name="dpls_settings_submit" id="submit" class="button button-primary button-large" value="<?php esc_attr_e( 'Save All Settings', 'dpls-schema-ai' ); ?>" />
			</p>
		</form>

		<hr style="margin:35px 0; border:0; border-top:2px solid #c3c4c7;" />

		<!-- Section 6: Backup, Export & Import Management -->
		<h2>
			<span class="dashicons dashicons-database-export" style="color:#2271b1; vertical-align:text-bottom;"></span>
			<?php esc_html_e( 'Configuration & Schema Portability (Import / Export)', 'dpls-schema-ai' ); ?>
		</h2>
		<p class="description">
			<?php esc_html_e( 'Safely export or import plugin configurations and structured schema records as standard JSON files.', 'dpls-schema-ai' ); ?>
		</p>

		<div style="display:flex; gap:20px; flex-wrap:wrap; margin-top:20px;">
			<!-- Export Box -->
			<div class="dpls-card" style="flex:1; min-width:300px; background:#f6f7f7;">
				<h3><?php esc_html_e( 'Export Backup', 'dpls-schema-ai' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Download current settings or all published Schema.org records.', 'dpls-schema-ai' ); ?></p>
				<p style="margin-top:15px;">
					<button type="button" class="button button-secondary" id="dpls-export-settings-btn">
						<span class="dashicons dashicons-download" style="vertical-align:text-bottom;"></span>
						<?php esc_html_e( 'Export Settings JSON', 'dpls-schema-ai' ); ?>
					</button>
					<button type="button" class="button button-secondary" id="dpls-export-schemas-btn" style="margin-left:8px;">
						<span class="dashicons dashicons-media-code" style="vertical-align:text-bottom;"></span>
						<?php esc_html_e( 'Export Schema Records JSON', 'dpls-schema-ai' ); ?>
					</button>
				</p>
			</div>

			<!-- Import Box -->
			<div class="dpls-card" style="flex:1; min-width:300px; background:#f6f7f7;">
				<h3><?php esc_html_e( 'Import Configuration or Records', 'dpls-schema-ai' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Select a valid DPLS Schema AI export JSON file. An interactive validation preview is displayed before any changes are written.', 'dpls-schema-ai' ); ?></p>
				<p style="margin-top:15px;">
					<input type="file" id="dpls-import-file" accept=".json" style="margin-bottom:10px; display:block;" />
					<button type="button" class="button button-primary" id="dpls-preview-import-btn" disabled>
						<span class="dashicons dashicons-visibility" style="vertical-align:text-bottom;"></span>
						<?php esc_html_e( 'Validate & Preview Import', 'dpls-schema-ai' ); ?>
					</button>
				</p>
			</div>
		</div>

		<!-- Interactive Import Preview Box (hidden by default) -->
		<div id="dpls-import-preview-box" class="dpls-card" style="display:none; margin-top:20px; border-left:4px solid #2271b1;">
			<h3><?php esc_html_e( 'Import Validation & Confirmation Preview', 'dpls-schema-ai' ); ?></h3>
			<div id="dpls-import-preview-content"></div>
			<div style="margin-top:15px; display:flex; gap:10px; align-items:center;">
				<label id="dpls-overwrite-label" style="display:none;">
					<input type="checkbox" id="dpls-import-overwrite" value="1" />
					<strong><?php esc_html_e( 'Overwrite existing approved schema on matched posts', 'dpls-schema-ai' ); ?></strong>
				</label>
				<button type="button" class="button button-primary button-large" id="dpls-confirm-import-btn">
					<?php esc_html_e( 'Confirm & Apply Import', 'dpls-schema-ai' ); ?>
				</button>
				<button type="button" class="button button-secondary" id="dpls-cancel-import-btn">
					<?php esc_html_e( 'Cancel', 'dpls-schema-ai' ); ?>
				</button>
			</div>
		</div>

		<hr style="margin:35px 0; border:0; border-top:1px solid #dcdcde;" />

		<!-- Section 7: Privacy Policy Transparency -->
		<h2>
			<span class="dashicons dashicons-shield" style="color:#2271b1; vertical-align:text-bottom;"></span>
			<?php esc_html_e( 'Privacy, Transparency & Data Protection', 'dpls-schema-ai' ); ?>
		</h2>
		<p class="description">
			<?php esc_html_e( 'DPLS Schema AI adheres to strict data minimization. Review what is stored in your database:', 'dpls-schema-ai' ); ?>
		</p>
		<ul style="list-style:disc; margin-left:20px; color:#50575e; line-height:1.6;">
			<li><strong><?php esc_html_e( 'Local Execution:', 'dpls-schema-ai' ); ?></strong> <?php esc_html_e( 'All scanning, classification, validation, and JSON-LD generation runs locally on your server.', 'dpls-schema-ai' ); ?></li>
			<li><strong><?php esc_html_e( 'Zero Third-Party Cloud Transmissions:', 'dpls-schema-ai' ); ?></strong> <?php esc_html_e( 'No page content, URLs, or metadata are transmitted to external AI cloud vendors.', 'dpls-schema-ai' ); ?></li>
			<li><strong><?php esc_html_e( 'WordPress Core Privacy Guide:', 'dpls-schema-ai' ); ?></strong> <?php esc_html_e( 'Suggested privacy policy content has been registered with your WordPress Privacy Policy Guide under Settings &rarr; Privacy.', 'dpls-schema-ai' ); ?></li>
		</ul>
	</div>
</div>
