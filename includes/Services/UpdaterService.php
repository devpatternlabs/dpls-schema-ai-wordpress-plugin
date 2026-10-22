<?php
/**
 * Automatic Schema Maintenance Engine.
 *
 * @package DPLS\SchemaAI\Services
 * @author  Dev Pattern Labs
 * @license GPL-2.0-or-later
 */

namespace DPLS\SchemaAI\Services;

use DPLS\SchemaAI\AI\AIService;
use DPLS\SchemaAI\Common\Constants;
use DPLS\SchemaAI\Common\Utils;
use DPLS\SchemaAI\Generator\ContentClassifier;
use DPLS\SchemaAI\Interfaces\UpdaterInterface;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Monitors content changes, detects modifications via hashes, and enforces update policies.
 */
class UpdaterService implements UpdaterInterface {

	/**
	 * Settings Service.
	 *
	 * @var SettingsService
	 */
	protected SettingsService $settings;

	/**
	 * History Service.
	 *
	 * @var HistoryService
	 */
	protected HistoryService $history;

	/**
	 * Generator Service.
	 *
	 * @var GeneratorService
	 */
	protected GeneratorService $generator;

	/**
	 * Scanner Service.
	 *
	 * @var ScannerService
	 */
	protected ScannerService $scanner;

	/**
	 * Validator Service.
	 *
	 * @var ValidatorService
	 */
	protected ValidatorService $validator;

	/**
	 * Content Classifier.
	 *
	 * @var ContentClassifier
	 */
	protected ContentClassifier $classifier;

	/**
	 * AI Service Coordinator.
	 *
	 * @var AIService|null
	 */
	protected ?AIService $ai;

	/**
	 * Constructor.
	 *
	 * @param SettingsService        $settings   Settings service.
	 * @param HistoryService         $history    History service.
	 * @param GeneratorService       $generator  Generator service.
	 * @param ScannerService         $scanner    Scanner service.
	 * @param ValidatorService       $validator  Validator service.
	 * @param ContentClassifier|null $classifier Classifier instance.
	 * @param AIService|null         $ai         Optional AI service coordinator.
	 */
	public function __construct(
		SettingsService $settings,
		HistoryService $history,
		GeneratorService $generator,
		ScannerService $scanner,
		ValidatorService $validator,
		?ContentClassifier $classifier = null,
		?AIService $ai = null
	) {
		$this->settings   = $settings;
		$this->history    = $history;
		$this->generator  = $generator;
		$this->scanner    = $scanner;
		$this->validator  = $validator;
		$this->classifier = $classifier ?? new ContentClassifier();
		$this->ai         = $ai;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'save_post', array( $this, 'on_post_save' ), 20, 3 );
	}

	/**
	 * Post save watcher and maintenance orchestrator.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post Post object.
	 * @param bool     $update Whether updating existing post.
	 * @return void
	 */
	public function on_post_save( int $post_id, \WP_Post $post, bool $update ): void {
		// 1. Guard against autosaves, revisions, trash, or non-managed post types.
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ( function_exists( 'wp_is_post_revision' ) && wp_is_post_revision( $post_id ) ) || ( function_exists( 'wp_is_post_autosave' ) && wp_is_post_autosave( $post_id ) ) ) {
			return;
		}

		if ( 'trash' === $post->post_status || ! $this->settings->is_post_type_enabled( $post->post_type ) ) {
			return;
		}

		// 2. Check if schema is managed for this content.
		$current_status = get_post_meta( $post_id, Constants::META_SCHEMA_STATUS, true );
		if ( empty( $current_status ) || Constants::STATUS_NONE === $current_status || Constants::STATUS_DISABLED === $current_status ) {
			return;
		}

		$stored_hash = get_post_meta( $post_id, Constants::META_CONTENT_HASH, true );

		// 3. Extract fresh normalized representation.
		$new_record = $this->scanner->extract_structured_content( $post, true );
		if ( ! is_array( $new_record ) ) {
			return;
		}

		$current_hash = $new_record['content_hash'];

		// 4. Performance: Skip immediately if content hash is unchanged.
		if ( ! empty( $stored_hash ) && $stored_hash === $current_hash ) {
			return;
		}

		// 5. Determine effective policy.
		$post_auto_toggle = get_post_meta( $post_id, Constants::META_AUTO_UPDATE, true );
		$global_policy    = $this->settings->get( 'automation_policy', Constants::POLICY_SAFE );

		if ( 'no' === $post_auto_toggle || Constants::POLICY_OFF === $global_policy ) {
			// Policy OFF: Flag for review and exit.
			update_post_meta( $post_id, Constants::META_SCHEMA_STATUS, Constants::STATUS_NEEDS_UPDATE );
			$this->history->log_event(
				'content_modified',
				$post_id,
				sprintf(
					/* translators: %s: Post title */
					__( 'Content modified on "%s"; auto-update is OFF so schema is flagged for review.', 'dpls-schema-ai' ),
					$post->post_title
				)
			);
			return;
		}

		// 6. Check if content changes imply a different schema type.
		$classification  = $this->classifier->classify( $new_record );
		$new_type        = $classification['type'];
		$existing_schema = get_post_meta( $post_id, Constants::META_ACTIVE_SCHEMA, true );
		$existing_type   = get_post_meta( $post_id, Constants::META_SUGGESTED_SCHEMA, true );

		if ( is_string( $existing_schema ) ) {
			$decoded_existing = json_decode( $existing_schema, true );
			if ( ! empty( $decoded_existing['@type'] ) ) {
				$existing_type = $decoded_existing['@type'];
			}
		}

		// Policy RULE: Do not automatically switch schema types merely because page text changed!
		if ( ( $new_type !== $existing_type ) || Constants::POLICY_REVIEW === $global_policy ) {
			update_post_meta( $post_id, Constants::META_SCHEMA_STATUS, Constants::STATUS_NEEDS_UPDATE );

			// Generate new draft proposal for review without publishing.
			$this->generator->generate_proposal( $post_id, $new_type );

			$this->history->log_event(
				'type_change_review_required',
				$post_id,
				sprintf(
					/* translators: 1: Post title, 2: Existing type, 3: New suggested type */
					__( 'Content change on "%1$s" suggests new schema type (%2$s &rarr; %3$s). Flagged for administrator review.', 'dpls-schema-ai' ),
					$post->post_title,
					$existing_type,
					$new_type
				)
			);
			return;
		}

		// 7. SAFE AUTO-UPDATE: Schema type is unchanged.
		// Retrieve locked properties to preserve.
		$locked = get_post_meta( $post_id, Constants::META_LOCKED_PROPERTIES, true );
		if ( ! is_array( $locked ) ) {
			$locked = array();
		}

		$built = $this->generator->build_schema_data( $existing_type, $new_record, $locked );
		$json_str = Utils::encode_schema( $built['data'] );

		// Validate before applying.
		$validation = $this->validator->validate( $json_str, $existing_type );

		if ( ! $validation['is_valid'] ) {
			// Regeneration failed validation: NEVER destroy approved schema! Retain existing active schema.
			update_post_meta( $post_id, Constants::META_SCHEMA_STATUS, Constants::STATUS_WARNINGS );
			$this->history->log_event(
				'auto_update_validation_failed',
				$post_id,
				sprintf(
					/* translators: %s: Post title */
					__( 'Automatic update on "%s" failed schema validation. Existing approved schema preserved.', 'dpls-schema-ai' ),
					$post->post_title
				),
				array( 'errors' => $validation['errors'] )
			);
			return;
		}

		// 8. Apply updated version safely.
		$current_version = (int) get_post_meta( $post_id, Constants::META_SCHEMA_VERSION, true );
		$new_version     = max( 1, $current_version + 1 );

		// Record in post schema history for rollback.
		$schema_history = get_post_meta( $post_id, Constants::META_SCHEMA_HISTORY, true );
		if ( ! is_array( $schema_history ) ) {
			$schema_history = array();
		}

		if ( ! empty( $existing_schema ) ) {
			array_unshift(
				$schema_history,
				array(
					'version'   => $current_version,
					'timestamp' => time(),
					'schema'    => $existing_schema,
					'status'    => $current_status,
				)
			);
			// Cap history versions to 10.
			$schema_history = array_slice( $schema_history, 0, 10 );
			update_post_meta( $post_id, Constants::META_SCHEMA_HISTORY, $schema_history );
		}

		$new_status = ! empty( $validation['warnings'] ) ? Constants::STATUS_WARNINGS : Constants::STATUS_APPLIED;

		update_post_meta( $post_id, Constants::META_ACTIVE_SCHEMA, $json_str );
		update_post_meta( $post_id, Constants::META_CONTENT_HASH, $current_hash );
		update_post_meta( $post_id, Constants::META_SCHEMA_STATUS, $new_status );
		update_post_meta( $post_id, Constants::META_SCHEMA_VERSION, $new_version );
		update_post_meta( $post_id, Constants::META_APPLIED_TIME, time() );
		update_post_meta( $post_id, Constants::META_VALIDATION_ERRORS, $validation['warnings'] );

		$this->history->log_event(
			'auto_update_applied',
			$post_id,
			sprintf(
				/* translators: 1: Post title, 2: Version number */
				__( 'Successfully auto-updated schema (v%2$d) for "%1$s".', 'dpls-schema-ai' ),
				$post->post_title,
				$new_version
			),
			array( 'version' => $new_version )
		);
	}

	/**
	 * Process content update programmatically or via background cron worker.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post Post object.
	 * @return void
	 */
	public function process_content_update( int $post_id, \WP_Post $post ): void {
		$this->on_post_save( $post_id, $post, true );
	}
}
