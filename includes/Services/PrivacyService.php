<?php
/**
 * Privacy and Data Governance Service.
 *
 * Provides transparent disclosures for administrators and site visitors
 * regarding local data storage, external service policies, and WordPress
 * Privacy Policy Guide integration.
 *
 * @package DPLS\SchemaAI\Services
 * @author  Dev Pattern Labs
 * @license GPL-2.0-or-later
 */

namespace DPLS\SchemaAI\Services;

use DPLS\SchemaAI\Common\Constants;
use DPLS\SchemaAI\Interfaces\ServiceInterface;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles privacy policy integration and data disclosures.
 */
class PrivacyService implements ServiceInterface {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_init', array( $this, 'register_privacy_policy_content' ) );
	}

	/**
	 * Register suggested privacy policy content with WordPress Core.
	 *
	 * @return void
	 */
	public function register_privacy_policy_content(): void {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content = $this->get_privacy_policy_text();
		wp_add_privacy_policy_content( Constants::PLUGIN_NAME, wp_kses_post( wpautop( $content, false ) ) );
	}

	/**
	 * Get suggested privacy policy text.
	 *
	 * @return string
	 */
	public function get_privacy_policy_text(): string {
		return sprintf(
			'<h3>%1$s</h3>
<p>%2$s</p>
<h4>%3$s</h4>
<p>%4$s</p>
<ul>
	<li><strong>%5$s</strong>: %6$s</li>
	<li><strong>%7$s</strong>: %8$s</li>
	<li><strong>%9$s</strong>: %10$s</li>
</ul>
<h4>%11$s</h4>
<p>%12$s</p>',
			esc_html__( 'Structured Data and Schema.org Information', 'dpls-schema-ai' ),
			esc_html__( 'This website utilizes DPLS Schema AI (developed by Dev Pattern Labs) to manage Schema.org structured data in JSON-LD format. Structured data assists search engine crawlers in understanding the structure, context, and entities on this website.', 'dpls-schema-ai' ),
			esc_html__( 'What Personal Data We Store and Why', 'dpls-schema-ai' ),
			esc_html__( 'DPLS Schema AI stores configuration and structured content descriptors entirely within the local WordPress database. Specifically:', 'dpls-schema-ai' ),
			esc_html__( 'Public Author Attributes', 'dpls-schema-ai' ),
			esc_html__( 'Public display names and author profile URLs of content contributors are included in Article/BlogPosting schema as published on public posts.', 'dpls-schema-ai' ),
			esc_html__( 'Organization Identity', 'dpls-schema-ai' ),
			esc_html__( 'Verified organizational details (business name, legal entity type, official logo URL, and public contact endpoints) configured by the site administrator are output in public website schema.', 'dpls-schema-ai' ),
			esc_html__( 'Local Change Audit History', 'dpls-schema-ai' ),
			esc_html__( 'Timestamps, event descriptions, and version snapshots are logged locally in WordPress options to maintain audit history and allow schema rollbacks.', 'dpls-schema-ai' ),
			esc_html__( 'External Services and Artificial Intelligence', 'dpls-schema-ai' ),
			esc_html__( 'By default, DPLS Schema AI operates completely autonomously inside your local WordPress environment. It does not transmit page content, user data, or metadata to any external cloud service or commercial AI API. If an administrator optionally connects a local Ollama AI instance, all inference occurs strictly between your server and your local Ollama endpoint without transmitting data to third-party cloud AI vendors.', 'dpls-schema-ai' )
		);
	}
}
