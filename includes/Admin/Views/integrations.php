<?php
/**
 * Integrations View.
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
 * @var \DPLS\SchemaAI\Integrations\WooCommerceIntegration $woocommerce
 */
$dpls_wc_available   = $woocommerce->is_available();
$dpls_wc_version     = $woocommerce->get_version();
$dpls_wc_enabled     = (bool) $settings->get( 'woocommerce_enabled', true );
$dpls_wc_suppress    = (bool) $settings->get( 'suppress_wc_core_schema', false );

$dpls_elementor_active  = did_action( 'elementor/loaded' ) || defined( 'ELEMENTOR_VERSION' );
$dpls_elementor_version = defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : null;
$dpls_elementor_enabled = (bool) $settings->get( 'elementor_enabled', true );

$dpls_ollama_enabled  = (bool) $settings->get( 'ollama_enabled', false );
$dpls_ollama_endpoint = (string) $settings->get( 'ollama_endpoint', 'http://localhost:11434' );
$dpls_ollama_model    = (string) $settings->get( 'ollama_model', 'llama3:latest' );
$dpls_ai_consent      = (bool) $settings->get( 'ai_consent', false );
?>
<div class="wrap dpls-admin-wrap">
	<div class="dpls-header">
		<div class="dpls-header-title">
			<h1>
				<span class="dashicons dashicons-networking" aria-hidden="true"></span>
				<?php esc_html_e( 'DPLS Schema AI &mdash; Integrations & Compatibility', 'dpls-schema-ai' ); ?>
			</h1>
			<p class="dpls-tagline">
				<?php esc_html_e( 'Configure seamless integrations with WooCommerce, Page Builders, Local AI models, and SEO plugins.', 'dpls-schema-ai' ); ?>
			</p>
		</div>
	</div>

	<div class="dpls-dashboard-panels">
		<!-- Main Integrations Column -->
		<div class="dpls-panel" style="flex:2;">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=dpls-schema-ai-settings' ) ); ?>">
				<?php wp_nonce_field( Constants::NONCE_ACTION_ADMIN, 'dpls_nonce' ); ?>

				<!-- 1. WooCommerce Integration -->
				<div class="dpls-card" style="margin-bottom:20px; border-left:4px solid #7f54b3;">
					<div style="display:flex; justify-content:space-between; align-items:center;">
						<h2 style="margin:0;">
							<span class="dashicons dashicons-cart" style="color:#7f54b3;"></span>
							<?php esc_html_e( 'WooCommerce E-Commerce Integration', 'dpls-schema-ai' ); ?>
						</h2>
						<?php if ( $dpls_wc_available ) : ?>
							<span class="dpls-badge dpls-badge-applied">
								<?php
								/* translators: %s: WooCommerce version number */
								$dpls_wc_badge = sprintf( __( 'Active (v%s)', 'dpls-schema-ai' ), $dpls_wc_version ?: '6.0+' );
								echo esc_html( $dpls_wc_badge );
								?>
							</span>
						<?php else : ?>
							<span class="dpls-badge dpls-badge-disabled">
								<?php esc_html_e( 'Not Installed / Inactive', 'dpls-schema-ai' ); ?>
							</span>
						<?php endif; ?>
					</div>

					<p class="description" style="margin-top:10px;">
						<?php esc_html_e( 'Extracts official WooCommerce product attributes (SKU, real prices, stock availability, sale schedules, and genuine verified reviews) into clean Schema.org Product entities. Never invents ratings, reviews, or prices.', 'dpls-schema-ai' ); ?>
					</p>

					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable WooCommerce Integration:', 'dpls-schema-ai' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="dpls_settings[woocommerce_enabled]" value="1" <?php checked( $dpls_wc_enabled ); ?> />
									<strong><?php esc_html_e( 'Process WooCommerce products for Product schema', 'dpls-schema-ai' ); ?></strong>
								</label>
								<p class="description"><?php esc_html_e( 'Supported versions: WooCommerce 6.0 through 9.x+.', 'dpls-schema-ai' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Conflict Prevention:', 'dpls-schema-ai' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="dpls_settings[suppress_wc_core_schema]" value="1" <?php checked( $dpls_wc_suppress ); ?> />
									<strong><?php esc_html_e( 'Suppress WooCommerce core structured data output', 'dpls-schema-ai' ); ?></strong>
								</label>
								<p class="description"><?php esc_html_e( 'Prevents duplicate @type: Product blocks in search engines by letting DPLS Schema AI manage the single source of truth.', 'dpls-schema-ai' ); ?></p>
							</td>
						</tr>
					</table>
				</div>

				<!-- 2. Page Builders (Elementor Adapter) -->
				<div class="dpls-card" style="margin-bottom:20px; border-left:4px solid #92003b;">
					<div style="display:flex; justify-content:space-between; align-items:center;">
						<h2 style="margin:0;">
							<span class="dashicons dashicons-layout" style="color:#92003b;"></span>
							<?php esc_html_e( 'Elementor Page Builder Adapter', 'dpls-schema-ai' ); ?>
						</h2>
						<?php if ( $dpls_elementor_active ) : ?>
							<span class="dpls-badge dpls-badge-applied">
								<?php
								/* translators: %s: Elementor version number */
								$dpls_elem_badge = sprintf( __( 'Active (v%s)', 'dpls-schema-ai' ), $dpls_elementor_version ?: '3.0+' );
								echo esc_html( $dpls_elem_badge );
								?>
							</span>
						<?php else : ?>
							<span class="dpls-badge dpls-badge-disabled">
								<?php esc_html_e( 'Inactive (Fallback Adapter Active)', 'dpls-schema-ai' ); ?>
							</span>
						<?php endif; ?>
					</div>

					<p class="description" style="margin-top:10px;">
						<?php esc_html_e( 'Parses custom Elementor JSON widget trees (_elementor_data) to extract plain text and heading hierarchies when post_content is empty. Gracefully handles deactivated status without errors.', 'dpls-schema-ai' ); ?>
					</p>

					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable Elementor Adapter:', 'dpls-schema-ai' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="dpls_settings[elementor_enabled]" value="1" <?php checked( $dpls_elementor_enabled ); ?> />
									<strong><?php esc_html_e( 'Enable native Elementor content tree scanner', 'dpls-schema-ai' ); ?></strong>
								</label>
								<p class="description"><?php esc_html_e( 'Tested and verified on Elementor 3.0 through 3.25+.', 'dpls-schema-ai' ); ?></p>
							</td>
						</tr>
					</table>
				</div>

				<!-- 3. Local AI (Ollama) -->
				<div class="dpls-card" style="margin-bottom:20px; border-left:4px solid #0073aa;">
					<div style="display:flex; justify-content:space-between; align-items:center;">
						<h2 style="margin:0;">
							<span class="dashicons dashicons-admin-generic" style="color:#0073aa;"></span>
							<?php esc_html_e( 'Optional Local AI Model (Ollama)', 'dpls-schema-ai' ); ?>
						</h2>
						<span class="dpls-badge <?php echo $dpls_ollama_enabled ? 'dpls-badge-applied' : 'dpls-badge-disabled'; ?>">
							<?php echo $dpls_ollama_enabled ? esc_html__( 'Enabled', 'dpls-schema-ai' ) : esc_html__( 'Disabled', 'dpls-schema-ai' ); ?>
						</span>
					</div>

					<p class="description" style="margin-top:10px;">
						<?php esc_html_e( 'DPLS Schema AI is 100% self-contained and does NOT require paid AI APIs. If you run Ollama locally on your server or development workstation, you can connect it for supplementary text analysis.', 'dpls-schema-ai' ); ?>
					</p>

					<div class="notice notice-info inline" style="margin:10px 0;">
						<p>
							<strong><?php esc_html_e( 'Privacy Notice:', 'dpls-schema-ai' ); ?></strong>
							<?php esc_html_e( 'No page content is ever transmitted to external AI providers or commercial cloud vendors. Local inference strictly queries your configured local Ollama endpoint only upon explicit consent.', 'dpls-schema-ai' ); ?>
						</p>
					</div>

					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable Local Ollama AI:', 'dpls-schema-ai' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="dpls_settings[ollama_enabled]" value="1" <?php checked( $dpls_ollama_enabled ); ?> />
									<strong><?php esc_html_e( 'Enable local inference assistant', 'dpls-schema-ai' ); ?></strong>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="dpls-ai-consent"><?php esc_html_e( 'Administrator Consent:', 'dpls-schema-ai' ); ?></label></th>
							<td>
								<label>
									<input type="checkbox" id="dpls-ai-consent" name="dpls_settings[ai_consent]" value="1" <?php checked( $dpls_ai_consent ); ?> />
									<?php esc_html_e( 'I understand and authorize DPLS Schema AI to send page text to my configured local Ollama endpoint for entity classification.', 'dpls-schema-ai' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="ollama-endpoint"><?php esc_html_e( 'Ollama Endpoint:', 'dpls-schema-ai' ); ?></label></th>
							<td>
								<input type="url" id="ollama-endpoint" name="dpls_settings[ollama_endpoint]" value="<?php echo esc_attr( $dpls_ollama_endpoint ); ?>" class="regular-text" />
								<p class="description"><?php esc_html_e( 'Default is http://localhost:11434', 'dpls-schema-ai' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="ollama-model"><?php esc_html_e( 'Model Tag:', 'dpls-schema-ai' ); ?></label></th>
							<td>
								<input type="text" id="ollama-model" name="dpls_settings[ollama_model]" value="<?php echo esc_attr( $dpls_ollama_model ); ?>" class="regular-text" />
								<p class="description"><?php esc_html_e( 'e.g. llama3:latest, mistral:latest, qwen2.5:latest', 'dpls-schema-ai' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Test Connection:', 'dpls-schema-ai' ); ?></th>
							<td>
								<button type="button" id="dpls-btn-test-ai" class="button button-secondary">
									<span class="dashicons dashicons-update" style="vertical-align:middle; display:none;" id="dpls-ai-test-spinner"></span>
									<?php esc_html_e( 'Test Ollama Connection', 'dpls-schema-ai' ); ?>
								</button>
								<span id="dpls-ai-test-status" style="margin-left:12px; font-weight:600;"></span>
								<p class="description"><?php esc_html_e( 'Pings your local endpoint and lists available installed models.', 'dpls-schema-ai' ); ?></p>
							</td>
						</tr>
					</table>
				</div>

				<p class="submit">
					<input type="submit" name="dpls_settings_submit" class="button button-primary button-large" value="<?php esc_attr_e( 'Save Integration Settings', 'dpls-schema-ai' ); ?>" />
				</p>
			</form>
		</div>

		<!-- SEO Ecosystem Sidebar -->
		<div class="dpls-panel dpls-panel-sidebar" style="flex:1;">
			<div class="dpls-card">
				<h2><?php esc_html_e( 'SEO Ecosystem Compatibility', 'dpls-schema-ai' ); ?></h2>
				<ul class="dpls-compat-list" style="margin-top:15px;">
					<li style="margin-bottom:15px;">
						<span class="dashicons dashicons-yes-alt dpls-text-success" style="font-size:24px; vertical-align:middle;"></span>
						<div>
							<strong><?php esc_html_e( 'Yoast SEO', 'dpls-schema-ai' ); ?></strong>
							<p style="margin:4px 0 0; color:#50575e;"><?php esc_html_e( 'Harmonious coexistence: output scripts tagged with specific class identifiers to prevent schema collisions.', 'dpls-schema-ai' ); ?></p>
						</div>
					</li>
					<li style="margin-bottom:15px;">
						<span class="dashicons dashicons-yes-alt dpls-text-success" style="font-size:24px; vertical-align:middle;"></span>
						<div>
							<strong><?php esc_html_e( 'Rank Math', 'dpls-schema-ai' ); ?></strong>
							<p style="margin:4px 0 0; color:#50575e;"><?php esc_html_e( 'Clean JSON-LD rendering ensures no duplicate property errors in Google Search Console.', 'dpls-schema-ai' ); ?></p>
						</div>
					</li>
					<li style="margin-bottom:15px;">
						<span class="dashicons dashicons-yes-alt dpls-text-success" style="font-size:24px; vertical-align:middle;"></span>
						<div>
							<strong><?php esc_html_e( 'All in One SEO (AIOSEO)', 'dpls-schema-ai' ); ?></strong>
							<p style="margin:4px 0 0; color:#50575e;"><?php esc_html_e( 'Compatible frontend script tags without conflicting header rewrites.', 'dpls-schema-ai' ); ?></p>
						</div>
					</li>
				</ul>
			</div>

			<div class="dpls-card" style="margin-top:20px;">
				<h2><?php esc_html_e( 'Data Safety Guarantee', 'dpls-schema-ai' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'DPLS Schema AI adheres to strict data minimization principles. All operations run within your PHP environment, and no credentials or proprietary data are transmitted.', 'dpls-schema-ai' ); ?>
				</p>
			</div>
		</div>
	</div>
</div>
