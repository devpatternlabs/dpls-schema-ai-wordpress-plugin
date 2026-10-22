<?php
/**
 * AI Service Coordinator.
 *
 * @package DPLS\SchemaAI\AI
 * @author  Dev Pattern Labs
 * @license GPL-2.0-or-later
 */

namespace DPLS\SchemaAI\AI;

use DPLS\SchemaAI\AI\Providers\OllamaProvider;
use DPLS\SchemaAI\Common\Constants;
use DPLS\SchemaAI\Common\Utils;
use DPLS\SchemaAI\Generator\ContentClassifier;
use DPLS\SchemaAI\Generator\SchemaRegistry;
use DPLS\SchemaAI\Interfaces\ServiceInterface;
use DPLS\SchemaAI\Services\HistoryService;
use DPLS\SchemaAI\Services\SettingsService;

// Prevent direct script access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages modular AI providers, coordinates inference, and strictly validates structured proposals.
 */
class AIService implements ServiceInterface {

	/**
	 * Async update cron hook.
	 */
	public const ASYNC_UPDATE_HOOK = 'dpls_schema_ai_async_ai_update';

	/**
	 * Settings service.
	 *
	 * @var SettingsService
	 */
	protected SettingsService $settings;

	/**
	 * History service.
	 *
	 * @var HistoryService
	 */
	protected HistoryService $history;

	/**
	 * Content classifier for rule-based comparison.
	 *
	 * @var ContentClassifier
	 */
	protected ContentClassifier $classifier;

	/**
	 * Registered AI providers.
	 *
	 * @var array<string, AIProviderInterface>
	 */
	protected array $providers = array();

	/**
	 * Constructor.
	 *
	 * @param SettingsService        $settings Settings service.
	 * @param HistoryService         $history History service.
	 * @param ContentClassifier|null $classifier Optional classifier instance.
	 */
	public function __construct(
		SettingsService $settings,
		HistoryService $history,
		?ContentClassifier $classifier = null
	) {
		$this->settings   = $settings;
		$this->history    = $history;
		$this->classifier = $classifier ?? new ContentClassifier();

		$this->register_default_providers();
	}

	/**
	 * Register default providers.
	 *
	 * @return void
	 */
	protected function register_default_providers(): void {
		$ollama = new OllamaProvider( $this->settings );
		$this->register_provider( $ollama );
	}

	/**
	 * Register an AI provider.
	 *
	 * @param AIProviderInterface $provider Provider instance.
	 * @return void
	 */
	public function register_provider( AIProviderInterface $provider ): void {
		$this->providers[ $provider->get_id() ] = $provider;
	}

	/**
	 * Get registered provider by ID.
	 *
	 * @param string|null $id Provider ID or null for active provider.
	 * @return AIProviderInterface|null
	 */
	public function get_provider( ?string $id = null ): ?AIProviderInterface {
		if ( empty( $id ) ) {
			$id = (string) $this->settings->get( 'ai_provider', OllamaProvider::PROVIDER_ID );
		}
		return $this->providers[ $id ] ?? ( $this->providers[ OllamaProvider::PROVIDER_ID ] ?? null );
	}

	/**
	 * Get all registered providers.
	 *
	 * @return array<string, AIProviderInterface>
	 */
	public function get_providers(): array {
		return $this->providers;
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( self::ASYNC_UPDATE_HOOK, array( $this, 'process_async_update_job' ), 10, 2 );
	}

	/**
	 * Check whether AI analysis is enabled and consented by administrator.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		$enabled = (bool) $this->settings->get( 'ai_enabled', $this->settings->get( 'ollama_enabled', false ) );
		$consent = (bool) $this->settings->get( 'ai_consent', false );
		$prov    = $this->get_provider();

		return $enabled && $consent && ( null !== $prov ) && $prov->is_available();
	}

	/**
	 * Run connection test on active provider.
	 *
	 * @return array
	 */
	public function test_connection(): array {
		$provider = $this->get_provider();
		if ( ! $provider instanceof AIProviderInterface ) {
			return array(
				'success' => false,
				'message' => __( 'No active AI provider configured.', 'dpls-schema-ai' ),
			);
		}
		return $provider->test_connection();
	}

	/**
	 * Analyzes a post's content with AI and produces validated schema recommendations.
	 *
	 * Compares the AI recommendation against deterministic rule classification.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $content_record Normalized content representation.
	 * @param array $options Optional override options.
	 * @return array Validated recommendation payload or fallback array.
	 */
	public function analyze_post( int $post_id, array $content_record, array $options = array() ): array {
		if ( ! $this->is_enabled() ) {
			return array(
				'success'     => false,
				'fallback'    => true,
				'message'     => __( 'AI content analysis is disabled or consent has not been granted.', 'dpls-schema-ai' ),
				'rule_result' => $this->classifier->classify( $content_record ),
			);
		}

		$provider = $this->get_provider();
		if ( ! $provider instanceof AIProviderInterface ) {
			return array(
				'success'     => false,
				'fallback'    => true,
				'message'     => __( 'No valid AI provider found.', 'dpls-schema-ai' ),
				'rule_result' => $this->classifier->classify( $content_record ),
			);
		}

		// 1. Call AI provider for inference.
		$raw_result = $provider->analyze_content( $content_record, $options );

		if ( empty( $raw_result['success'] ) || empty( $raw_result['data'] ) ) {
			// Log audit event for failure.
			$this->history->log_event(
				'ai_analysis_failed',
				$post_id,
				sprintf(
					/* translators: 1: Provider label, 2: Error message */
					__( 'AI analysis via %1$s failed: %2$s. Gracefully falling back to rule-based engine.', 'dpls-schema-ai' ),
					$provider->get_label(),
					$raw_result['message'] ?? __( 'Unknown provider error', 'dpls-schema-ai' )
				)
			);

			return array(
				'success'     => false,
				'fallback'    => true,
				'message'     => $raw_result['message'] ?? __( 'AI analysis failed.', 'dpls-schema-ai' ),
				'rule_result' => $this->classifier->classify( $content_record ),
			);
		}

		// 2. Validate response strictly with PHP schema rules.
		$validated = $this->validate_ai_response( $raw_result['data'], $content_record );

		// 3. Compare with deterministic rule-based classifier.
		$rule_classification = $this->classifier->classify( $content_record );
		$validated['rule_comparison'] = array(
			'rule_type'       => $rule_classification['type'],
			'rule_confidence' => $rule_classification['confidence'] ?? 0.8,
			'agrees'          => ( $rule_classification['type'] === $validated['suggested_type'] ),
		);

		$validated['duration_ms'] = $raw_result['duration_ms'] ?? 0;
		$validated['model_used']  = $raw_result['model_used'] ?? '';
		$validated['provider_id'] = $provider->get_id();

		// If AI and rule-based systems disagree, flag for human review.
		if ( ! $validated['rule_comparison']['agrees'] ) {
			$validated['review_required'] = true;
			$validated['warnings'][]      = sprintf(
				/* translators: 1: AI suggested type, 2: Rule-based suggested type */
				__( 'AI suggested %1$s, while deterministic rules classified content as %2$s. Administrator review recommended.', 'dpls-schema-ai' ),
				$validated['suggested_type'],
				$rule_classification['type']
			);
		}

		// 4. Log successful audit event.
		$this->history->log_event(
			'ai_analysis_completed',
			$post_id,
			sprintf(
				/* translators: 1: Suggested type, 2: Confidence score percentage */
				__( 'AI content analysis suggested "%1$s" with %2$d%% confidence.', 'dpls-schema-ai' ),
				$validated['suggested_type'],
				(int) round( $validated['confidence'] * 100 )
			),
			array(
				'suggested_type' => $validated['suggested_type'],
				'confidence'     => $validated['confidence'],
				'provider'       => $provider->get_id(),
			)
		);

		return $validated;
	}

	/**
	 * Strictly validates and normalizes an AI model's JSON response in PHP.
	 *
	 * Enforces Schema.org type validity, bounds confidence, strips hazardous properties,
	 * and checks evidence to prevent hallucinations.
	 *
	 * @param array $ai_data Decoded JSON response from AI model.
	 * @param array $content_record Source normalized content record.
	 * @return array Validated response array.
	 */
	public function validate_ai_response( array $ai_data, array $content_record ): array {
		$warnings = array();

		// A. Validate Suggested Schema Type.
		$raw_type = isset( $ai_data['suggested_type'] ) && is_string( $ai_data['suggested_type'] )
			? sanitize_text_field( trim( $ai_data['suggested_type'] ) )
			: '';

		if ( empty( $raw_type ) || ! SchemaRegistry::is_supported( $raw_type ) ) {
			$default_type = ( ( $content_record['post_type'] ?? 'post' ) === 'page' ) ? 'WebPage' : 'BlogPosting';
			$warnings[]   = sprintf(
				/* translators: 1: Invalid type returned, 2: Safe fallback type */
				__( 'AI returned unsupported or unknown schema type "%1$s". Fallback applied: %2$s.', 'dpls-schema-ai' ),
				$raw_type,
				$default_type
			);
			$raw_type = $default_type;
		}

		// B. Bounded Confidence Score (0.0 to 1.0).
		$confidence = 0.70;
		if ( isset( $ai_data['confidence'] ) && is_numeric( $ai_data['confidence'] ) ) {
			$confidence = max( 0.10, min( 0.99, (float) $ai_data['confidence'] ) );
		}

		// C. Validate Reasons.
		$reasons = array();
		if ( ! empty( $ai_data['reasons'] ) && is_array( $ai_data['reasons'] ) ) {
			foreach ( $ai_data['reasons'] as $r ) {
				if ( is_string( $r ) && ! empty( trim( $r ) ) ) {
					$reasons[] = sanitize_text_field( wp_strip_all_tags( $r ) );
				}
			}
		}
		if ( empty( $reasons ) ) {
			$reasons[] = sprintf(
				/* translators: %s: Schema type */
				__( 'AI identified qualifying structural patterns for %s.', 'dpls-schema-ai' ),
				$raw_type
			);
		}

		// D. Sanitize and Validate Proposed Properties.
		$properties      = array();
		$source_evidence = array();
		$plain_text      = mb_strtolower( (string) ( $content_record['plain_content'] ?? '' ) );
		$title_text      = mb_strtolower( (string) ( $content_record['title'] ?? '' ) );

		if ( ! empty( $ai_data['properties'] ) && is_array( $ai_data['properties'] ) ) {
			foreach ( $ai_data['properties'] as $key => $val ) {
				$clean_key = sanitize_key( $key );
				if ( empty( $clean_key ) ) {
					continue;
				}

				// Check for nested strings or basic scalar values.
				if ( is_string( $val ) ) {
					$clean_val = sanitize_text_field( wp_strip_all_tags( $val ) );

					// Anti-hallucination check: ensure key facts actually exist in content.
					if ( in_array( $clean_key, array( 'price', 'telephone', 'email', 'sku', 'ratingValue' ), true ) ) {
						if ( ! str_contains( $plain_text, mb_strtolower( $clean_val ) ) && ! str_contains( $title_text, mb_strtolower( $clean_val ) ) ) {
							$warnings[] = sprintf(
								/* translators: 1: Property key, 2: Value */
								__( 'AI proposed property "%1$s" ("%2$s") was not verified in source text. Discarded to prevent hallucination.', 'dpls-schema-ai' ),
								$clean_key,
								$clean_val
							);
							continue;
						}
					}

					// Disallow executable content or dangerous code strings.
					if ( preg_match( '/(<script|javascript:|eval\(|base64_decode)/i', $clean_val ) ) {
						continue;
					}

					$properties[ $clean_key ] = $clean_val;
				} elseif ( is_numeric( $val ) || is_bool( $val ) ) {
					$properties[ $clean_key ] = $val;
				} elseif ( is_array( $val ) ) {
					// Safe scalar array extraction (e.g. recipeIngredient or steps).
					$clean_sub = array();
					foreach ( $val as $sub_item ) {
						if ( is_string( $sub_item ) ) {
							$clean_sub[] = sanitize_text_field( wp_strip_all_tags( $sub_item ) );
						}
					}
					if ( ! empty( $clean_sub ) ) {
						$properties[ $clean_key ] = $clean_sub;
					}
				}
			}
		}

		// E. Source Evidence Mapping.
		if ( ! empty( $ai_data['source_evidence'] ) && is_array( $ai_data['source_evidence'] ) ) {
			foreach ( $ai_data['source_evidence'] as $ekey => $eval ) {
				if ( is_string( $eval ) ) {
					$source_evidence[ sanitize_key( $ekey ) ] = sanitize_text_field( wp_strip_all_tags( $eval ) );
				}
			}
		}

		// F. Missing Data & Warnings.
		$missing_data = array();
		if ( ! empty( $ai_data['missing_data'] ) && is_array( $ai_data['missing_data'] ) ) {
			foreach ( $ai_data['missing_data'] as $m ) {
				if ( is_string( $m ) && ! empty( trim( $m ) ) ) {
					$missing_data[] = sanitize_text_field( wp_strip_all_tags( $m ) );
				}
			}
		}

		if ( ! empty( $ai_data['warnings'] ) && is_array( $ai_data['warnings'] ) ) {
			foreach ( $ai_data['warnings'] as $w ) {
				if ( is_string( $w ) && ! empty( trim( $w ) ) ) {
					$warnings[] = sanitize_text_field( wp_strip_all_tags( $w ) );
				}
			}
		}

		$review_required = ! empty( $ai_data['review_required'] ) || ( $confidence < 0.75 ) || ! empty( $warnings );

		return array(
			'success'         => true,
			'suggested_type'  => $raw_type,
			'confidence'      => $confidence,
			'reasons'         => $reasons,
			'properties'      => $properties,
			'source_evidence' => $source_evidence,
			'missing_data'    => $missing_data,
			'warnings'        => $warnings,
			'review_required' => $review_required,
		);
	}

	/**
	 * Schedules an asynchronous background AI update job for a post.
	 *
	 * Prevents blocking synchronous save_post executions.
	 *
	 * @param int $post_id Post ID.
	 * @return bool True if scheduled.
	 */
	public function schedule_async_update( int $post_id ): bool {
		// Prevent scheduling duplicate events for same post.
		if ( wp_next_scheduled( self::ASYNC_UPDATE_HOOK, array( $post_id, false ) ) ) {
			return false;
		}

		return (bool) wp_schedule_single_event(
			time() + 2, // 2-second buffer.
			self::ASYNC_UPDATE_HOOK,
			array( $post_id, false )
		);
	}

	/**
	 * Processes asynchronous AI update cron job.
	 *
	 * @param int  $post_id Post ID.
	 * @param bool $force Force update flag.
	 * @return void
	 */
	public function process_async_update_job( int $post_id, bool $force = false ): void {
		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post || 'trash' === $post->post_status ) {
			return;
		}

		if ( function_exists( '\\dpls_schema_ai' ) ) {
			$plugin = \dpls_schema_ai();
			if ( isset( $plugin->updater ) ) {
				$plugin->updater->process_content_update( $post_id, $post );
			}
		}
	}
}
