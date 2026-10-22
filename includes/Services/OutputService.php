<?php
/**
 * Frontend JSON-LD Output Service.
 *
 * @package DPLS\SchemaAI\Services
 * @author  Dev Pattern Labs
 * @license GPL-2.0-or-later
 */

namespace DPLS\SchemaAI\Services;

use DPLS\SchemaAI\Common\Constants;
use DPLS\SchemaAI\Common\Utils;
use DPLS\SchemaAI\Interfaces\OutputInterface;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles rendering of approved JSON-LD in the public frontend <head>.
 */
class OutputService implements OutputInterface {

	/**
	 * Settings Service.
	 *
	 * @var SettingsService
	 */
	protected SettingsService $settings;

	/**
	 * Entity IDs already rendered on current request to avoid duplicates.
	 *
	 * @var array
	 */
	protected array $rendered_entities = array();

	/**
	 * Constructor.
	 *
	 * @param SettingsService $settings Settings service.
	 */
	public function __construct( SettingsService $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp_head', array( $this, 'render_head_schema' ), 20 );
	}

	/**
	 * Output approved JSON-LD script in <head>.
	 *
	 * @return void
	 */
	public function render_head_schema(): void {
		// Only run on frontend single posts / pages.
		if ( is_admin() || ! is_singular() ) {
			return;
		}

		// Resolve current queried content ID accurately.
		$post_id = (int) get_queried_object_id();
		if ( ! $post_id ) {
			$post_id = (int) get_the_ID();
		}

		if ( ! $post_id ) {
			return;
		}

		// Privacy & publication checks: do not output on drafts, private, or password-protected posts.
		if ( 'publish' !== get_post_status( $post_id ) ) {
			return;
		}

		if ( function_exists( 'post_password_required' ) && post_password_required( $post_id ) ) {
			return;
		}

		$post_type = get_post_type( $post_id );
		if ( ! $this->settings->is_post_type_enabled( $post_type ) ) {
			return;
		}

		// Check if entity already rendered for this post in this request (deduplication).
		if ( in_array( $post_id, $this->rendered_entities, true ) ) {
			return;
		}

		// Check filter allowing third-party adapters / integrations to suppress if needed.
		$should_render = apply_filters( 'dpls_schema_ai_should_output_schema', true, $post_id );
		if ( ! $should_render ) {
			return;
		}

		$schema = $this->get_schema_for_post( $post_id );
		if ( empty( $schema ) ) {
			return;
		}

		// Clean and normalize JSON.
		$encoded_clean = Utils::encode_schema( $schema );
		if ( empty( $encoded_clean ) ) {
			return;
		}

		$this->rendered_entities[] = $post_id;

		// Security: Output safely in script tag without executing as PHP.
		echo "\n<!-- DPLS Schema AI JSON-LD Begin -->\n";
		echo '<script type="application/ld+json" class="dpls-schema-ai-jsonld">' . "\n";
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Valid JSON-LD structured data encoded with wp_json_encode and stripped of all HTML tags.
		echo wp_strip_all_tags( $encoded_clean ) . "\n";
		echo "</script>\n";
		echo "<!-- DPLS Schema AI JSON-LD End -->\n\n";
	}

	/**
	 * Retrieve approved, active schema string for a post.
	 *
	 * @param int|null $post_id Post ID.
	 * @return string|null
	 */
	public function get_schema_for_post( ?int $post_id = null ): ?string {
		if ( null === $post_id ) {
			$post_id = (int) get_queried_object_id();
			if ( ! $post_id ) {
				$post_id = (int) get_the_ID();
			}
		}

		if ( ! $post_id ) {
			return null;
		}

		// Check status: ONLY output applied or warnings. Never draft, pending_review, or disabled!
		$status = get_post_meta( $post_id, Constants::META_SCHEMA_STATUS, true );
		if ( ! in_array( $status, array( Constants::STATUS_APPLIED, Constants::STATUS_WARNINGS ), true ) ) {
			return null;
		}

		$schema = get_post_meta( $post_id, Constants::META_ACTIVE_SCHEMA, true );
		if ( empty( $schema ) ) {
			return null;
		}

		// Strip any internal metadata if array.
		if ( is_string( $schema ) ) {
			$decoded = json_decode( $schema, true );
			if ( is_array( $decoded ) ) {
				unset( $decoded['_internal_notes'], $decoded['_dpls_debug'] );
				$schema = (string) wp_json_encode( $decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			}
		}

		return apply_filters( 'dpls_schema_ai_output_schema', $schema, $post_id );
	}
}
