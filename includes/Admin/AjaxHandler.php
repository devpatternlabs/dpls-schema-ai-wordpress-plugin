<?php
/**
 * Admin AJAX Handler.
 *
 * @package DPLS\SchemaAI\Admin
 * @author  Dev Pattern Labs
 * @license GPL-2.0-or-later
 */

namespace DPLS\SchemaAI\Admin;

use DPLS\SchemaAI\AI\AIService;
use DPLS\SchemaAI\Common\Constants;
use DPLS\SchemaAI\Common\Utils;
use DPLS\SchemaAI\Services\AutomationService;
use DPLS\SchemaAI\Services\GeneratorService;
use DPLS\SchemaAI\Services\HistoryService;
use DPLS\SchemaAI\Services\ImportExportService;
use DPLS\SchemaAI\Services\ScannerService;
use DPLS\SchemaAI\Services\ValidatorService;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles all dashboard and editor AJAX endpoints with capability and nonce verification.
 */
class AjaxHandler {

	/**
	 * Services container.
	 */
	protected ScannerService $scanner;
	protected GeneratorService $generator;
	protected ValidatorService $validator;
	protected HistoryService $history;
	protected AutomationService $automation;
	protected ?ImportExportService $import_export;
	protected ?AIService $ai;

	/**
	 * Constructor.
	 *
	 * @param ScannerService           $scanner       Scanner service.
	 * @param GeneratorService         $generator     Generator service.
	 * @param ValidatorService         $validator     Validator service.
	 * @param HistoryService           $history       History service.
	 * @param AutomationService        $automation    Automation service.
	 * @param ImportExportService|null $import_export Optional Import/Export service.
	 * @param AIService|null           $ai            Optional AI service coordinator.
	 */
	public function __construct(
		ScannerService $scanner,
		GeneratorService $generator,
		ValidatorService $validator,
		HistoryService $history,
		AutomationService $automation,
		?ImportExportService $import_export = null,
		?AIService $ai = null
	) {
		$this->scanner       = $scanner;
		$this->generator     = $generator;
		$this->validator     = $validator;
		$this->history       = $history;
		$this->automation    = $automation;
		$this->import_export = $import_export;
		$this->ai            = $ai;
	}

	/**
	 * Register AJAX hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		$actions = array(
			'analyze_post',
			'generate_schema',
			'preview_schema',
			'apply_schema',
			'validate_schema',
			'disable_schema',
			'view_history',
			'rollback_schema',
			'bulk_scan',
			'start_scan_job',
			'process_scan_batch',
			'get_scan_progress',
			'clear_history',
			'export_settings',
			'export_schemas',
			'preview_import',
			'execute_import',
			'test_ai_connection',
			'ai_analyze_post',
		);

		foreach ( $actions as $action ) {
			add_action( 'wp_ajax_dpls_' . $action, array( $this, 'handle_' . $action ) );
		}
	}

	/**
	 * Verify permissions and nonce.
	 *
	 * @param int|null $post_id Post ID for specific post editing check.
	 * @return void
	 */
	protected function verify_request( ?int $post_id = null ): void {
		check_ajax_referer( Constants::NONCE_ACTION_AJAX, 'nonce' );

		$has_access = false;
		if ( current_user_can( Constants::REQUIRED_CAPABILITY ) ) {
			$has_access = true;
		} elseif ( $post_id && current_user_can( 'edit_post', $post_id ) ) {
			$has_access = true;
		}

		if ( ! $has_access ) {
			wp_send_json_error(
				array( 'message' => __( 'Unauthorized access. Insufficient privileges.', 'dpls-schema-ai' ) ),
				403
			);
		}
	}

	/**
	 * Handle post analysis.
	 *
	 * @return void
	 */
	public function handle_analyze_post(): void {
		check_ajax_referer( Constants::NONCE_ACTION_AJAX, 'nonce' );
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$this->verify_request( $post_id );

		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post ID.', 'dpls-schema-ai' ) ) );
		}

		$force  = ! empty( $_POST['force'] );
		$result = $this->scanner->analyze_post( $post_id, $force );
		if ( empty( $result['success'] ) ) {
			wp_send_json_error( $result );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Handle schema proposal generation.
	 *
	 * @return void
	 */
	public function handle_generate_schema(): void {
		check_ajax_referer( Constants::NONCE_ACTION_AJAX, 'nonce' );
		$post_id     = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$schema_type = isset( $_POST['schema_type'] ) ? sanitize_text_field( wp_unslash( $_POST['schema_type'] ) ) : null;

		$this->verify_request( $post_id );

		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post ID.', 'dpls-schema-ai' ) ) );
		}

		$proposal = $this->generator->generate_proposal( $post_id, $schema_type );
		if ( empty( $proposal['success'] ) ) {
			wp_send_json_error( $proposal );
		}

		$status = get_post_meta( $post_id, Constants::META_SCHEMA_STATUS, true );

		$response = array(
			'post_id'             => $post_id,
			'schema_type'         => $proposal['proposed_type'],
			'schema_json'         => $proposal['json_ld_string'],
			'proposal'            => $proposal,
			'status'              => $status,
			'status_badge'        => Utils::get_status_badge( $status ),
			'missing_recommended' => $proposal['missing_recommended'],
			'warnings'            => $proposal['warnings'],
			'message'             => __( 'Schema proposal generated successfully (not yet published).', 'dpls-schema-ai' ),
		);

		wp_send_json_success( $response );
	}

	/**
	 * Handle preview schema.
	 *
	 * @return void
	 */
	public function handle_preview_schema(): void {
		check_ajax_referer( Constants::NONCE_ACTION_AJAX, 'nonce' );
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$this->verify_request( $post_id );

		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post ID.', 'dpls-schema-ai' ) ) );
		}

		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post ) {
			wp_send_json_error( array( 'message' => __( 'Post not found.', 'dpls-schema-ai' ) ) );
		}

		$active_schema = get_post_meta( $post_id, Constants::META_ACTIVE_SCHEMA, true );
		$draft_schema  = get_post_meta( $post_id, Constants::META_DRAFT_SCHEMA, true );
		$proposal      = get_post_meta( $post_id, Constants::META_SCHEMA_PROPOSAL, true );
		$schema_to_use = ! empty( $draft_schema ) ? $draft_schema : $active_schema;
		$status        = get_post_meta( $post_id, Constants::META_SCHEMA_STATUS, true );

		if ( empty( $schema_to_use ) ) {
			wp_send_json_error( array( 'message' => __( 'No schema has been generated or applied yet.', 'dpls-schema-ai' ) ) );
		}

		$validation = $this->validator->validate( $schema_to_use );

		wp_send_json_success(
			array(
				'post_id'             => $post_id,
				'post_title'          => $post->post_title,
				'schema_json'         => Utils::encode_schema( $schema_to_use ),
				'has_active'          => ! empty( $active_schema ),
				'has_draft'           => ! empty( $draft_schema ),
				'status'              => $status,
				'status_badge'        => Utils::get_status_badge( $status ),
				'validation'          => $validation,
				'missing_recommended' => is_array( $proposal ) ? ( $proposal['missing_recommended'] ?? array() ) : array(),
				'warnings'            => is_array( $proposal ) ? ( $proposal['warnings'] ?? array() ) : array(),
			)
		);
	}

	/**
	 * Handle applying schema to live site.
	 *
	 * @return void
	 */
	public function handle_apply_schema(): void {
		check_ajax_referer( Constants::NONCE_ACTION_AJAX, 'nonce' );
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$this->verify_request( $post_id );

		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post ID.', 'dpls-schema-ai' ) ) );
		}

		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post ) {
			wp_send_json_error( array( 'message' => __( 'Post not found.', 'dpls-schema-ai' ) ) );
		}

		// Security: Check capabilities specifically for this post.
		if ( ! current_user_can( 'edit_post', $post_id ) && ! current_user_can( Constants::REQUIRED_CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'You cannot edit this content item.', 'dpls-schema-ai' ) ), 403 );
		}

		// Custom schema string from code editor or fallback to draft proposal.
		$custom_json = isset( $_POST['schema_json'] ) ? wp_unslash( $_POST['schema_json'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! empty( $custom_json ) ) {
			$schema_to_apply = $custom_json;
		} else {
			$schema_to_apply = get_post_meta( $post_id, Constants::META_DRAFT_SCHEMA, true );
			if ( empty( $schema_to_apply ) ) {
				$schema_to_apply = get_post_meta( $post_id, Constants::META_ACTIVE_SCHEMA, true );
			}
		}

		if ( empty( $schema_to_apply ) ) {
			wp_send_json_error( array( 'message' => __( 'No schema available to apply.', 'dpls-schema-ai' ) ) );
		}

		// Validate.
		$validation = $this->validator->validate( $schema_to_apply );
		if ( ! $validation['is_valid'] ) {
			wp_send_json_error(
				array(
					'message'    => __( 'Cannot apply schema due to validation errors.', 'dpls-schema-ai' ),
					'errors'     => $validation['errors'],
					'validation' => $validation,
				)
			);
		}

		$encoded_clean = Utils::encode_schema( $schema_to_apply );

		// Versioning and rollback archive.
		$old_schema  = get_post_meta( $post_id, Constants::META_ACTIVE_SCHEMA, true );
		$old_version = (int) get_post_meta( $post_id, Constants::META_SCHEMA_VERSION, true );
		$new_version = max( 1, $old_version + 1 );

		if ( ! empty( $old_schema ) ) {
			$history = get_post_meta( $post_id, Constants::META_SCHEMA_HISTORY, true );
			if ( ! is_array( $history ) ) {
				$history = array();
			}
			array_unshift(
				$history,
				array(
					'version'   => $old_version,
					'timestamp' => time(),
					'schema'    => $old_schema,
				)
			);
			update_post_meta( $post_id, Constants::META_SCHEMA_HISTORY, array_slice( $history, 0, 10 ) );
		}

		$new_status = ! empty( $validation['warnings'] ) ? Constants::STATUS_WARNINGS : Constants::STATUS_APPLIED;

		update_post_meta( $post_id, Constants::META_ACTIVE_SCHEMA, $encoded_clean );
		update_post_meta( $post_id, Constants::META_SCHEMA_STATUS, $new_status );
		update_post_meta( $post_id, Constants::META_SCHEMA_VERSION, $new_version );
		update_post_meta( $post_id, Constants::META_APPLIED_TIME, time() );
		update_post_meta( $post_id, Constants::META_CONTENT_HASH, Utils::compute_content_hash( $post ) );
		update_post_meta( $post_id, Constants::META_VALIDATION_ERRORS, $validation['warnings'] );

		// Clear draft schema once applied.
		delete_post_meta( $post_id, Constants::META_DRAFT_SCHEMA );

		$this->history->log_event(
			'apply',
			$post_id,
			sprintf(
				/* translators: 1: Post title, 2: Version */
				__( 'Applied active JSON-LD schema (v%2$d) for "%1$s".', 'dpls-schema-ai' ),
				$post->post_title,
				$new_version
			),
			array( 'version' => $new_version, 'status' => $new_status )
		);

		wp_send_json_success(
			array(
				'post_id'      => $post_id,
				'version'      => $new_version,
				'status'       => $new_status,
				'status_badge' => Utils::get_status_badge( $new_status ),
				'validation'   => $validation,
				/* translators: %d: Schema version number */
				'message'      => sprintf( __( 'Schema v%d successfully applied to this content.', 'dpls-schema-ai' ), $new_version ),
			)
		);
	}

	/**
	 * Handle rolling back to a previous schema version.
	 *
	 * @return void
	 */
	public function handle_rollback_schema(): void {
		check_ajax_referer( Constants::NONCE_ACTION_AJAX, 'nonce' );
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$target_version = isset( $_POST['version'] ) ? absint( $_POST['version'] ) : 0;

		$this->verify_request( $post_id );

		if ( ! $post_id || ! $target_version ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post or version.', 'dpls-schema-ai' ) ) );
		}

		$history = get_post_meta( $post_id, Constants::META_SCHEMA_HISTORY, true );
		if ( ! is_array( $history ) || empty( $history ) ) {
			wp_send_json_error( array( 'message' => __( 'No history available for rollback.', 'dpls-schema-ai' ) ) );
		}

		$found_schema = null;
		foreach ( $history as $h ) {
			if ( (int) $h['version'] === $target_version ) {
				$found_schema = $h['schema'];
				break;
			}
		}

		if ( empty( $found_schema ) ) {
			wp_send_json_error( array( 'message' => __( 'Target version not found in history.', 'dpls-schema-ai' ) ) );
		}

		// Apply restored version.
		update_post_meta( $post_id, Constants::META_ACTIVE_SCHEMA, $found_schema );
		update_post_meta( $post_id, Constants::META_SCHEMA_STATUS, Constants::STATUS_APPLIED );
		update_post_meta( $post_id, Constants::META_SCHEMA_VERSION, $target_version );

		$this->history->log_event(
			'rollback',
			$post_id,
			sprintf(
				/* translators: 1: Post ID, 2: Version */
				__( 'Rolled back schema on post #%1$d to version %2$d.', 'dpls-schema-ai' ),
				$post_id,
				$target_version
			)
		);

		wp_send_json_success(
			array(
				'post_id'      => $post_id,
				'version'      => $target_version,
				'status'       => Constants::STATUS_APPLIED,
				'status_badge' => Utils::get_status_badge( Constants::STATUS_APPLIED ),
				/* translators: %d: Target version number */
				'message'      => sprintf( __( 'Successfully rolled back to version %d.', 'dpls-schema-ai' ), $target_version ),
			)
		);
	}

	/**
	 * Handle schema validation.
	 *
	 * @return void
	 */
	public function handle_validate_schema(): void {
		check_ajax_referer( Constants::NONCE_ACTION_AJAX, 'nonce' );
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$this->verify_request( $post_id );

		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post ID.', 'dpls-schema-ai' ) ) );
		}

		$active_schema = get_post_meta( $post_id, Constants::META_ACTIVE_SCHEMA, true );
		$draft_schema  = get_post_meta( $post_id, Constants::META_DRAFT_SCHEMA, true );
		$schema        = ! empty( $active_schema ) ? $active_schema : $draft_schema;

		if ( empty( $schema ) ) {
			wp_send_json_error( array( 'message' => __( 'No schema found to validate.', 'dpls-schema-ai' ) ) );
		}

		$result = $this->validator->validate( $schema );

		$current_status = get_post_meta( $post_id, Constants::META_SCHEMA_STATUS, true );
		if ( Constants::STATUS_APPLIED === $current_status || Constants::STATUS_WARNINGS === $current_status ) {
			$new_status = ( ! empty( $result['warnings'] ) || ! $result['is_valid'] ) ? Constants::STATUS_WARNINGS : Constants::STATUS_APPLIED;
			update_post_meta( $post_id, Constants::META_SCHEMA_STATUS, $new_status );
			update_post_meta( $post_id, Constants::META_VALIDATION_ERRORS, $result['warnings'] );
			$result['status_badge'] = Utils::get_status_badge( $new_status );
		} else {
			$result['status_badge'] = Utils::get_status_badge( $current_status );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Handle disabling schema.
	 *
	 * @return void
	 */
	public function handle_disable_schema(): void {
		check_ajax_referer( Constants::NONCE_ACTION_AJAX, 'nonce' );
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$this->verify_request( $post_id );

		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post ID.', 'dpls-schema-ai' ) ) );
		}

		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post ) {
			wp_send_json_error( array( 'message' => __( 'Post not found.', 'dpls-schema-ai' ) ) );
		}

		update_post_meta( $post_id, Constants::META_SCHEMA_STATUS, Constants::STATUS_DISABLED );

		$this->history->log_event(
			'disable',
			$post_id,
			sprintf(
				/* translators: %s: Post title */
				__( 'Disabled schema output for "%s".', 'dpls-schema-ai' ),
				$post->post_title
			)
		);

		wp_send_json_success(
			array(
				'post_id'      => $post_id,
				'status'       => Constants::STATUS_DISABLED,
				'status_badge' => Utils::get_status_badge( Constants::STATUS_DISABLED ),
				'message'      => __( 'Schema disabled for this content.', 'dpls-schema-ai' ),
			)
		);
	}

	/**
	 * Handle retrieving activity history.
	 *
	 * @return void
	 */
	public function handle_view_history(): void {
		check_ajax_referer( Constants::NONCE_ACTION_AJAX, 'nonce' );
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : null;
		$this->verify_request( $post_id );

		$events = $this->history->get_events( 30, $post_id );

		$formatted = array();
		foreach ( $events as $event ) {
			$formatted[] = array(
				'id'      => $event['id'],
				'date'    => Utils::format_date( $event['timestamp'] ),
				'action'  => $event['action'],
				'user'    => $event['user_name'],
				'message' => $event['message'],
			);
		}

		// Also fetch post-specific schema version history if post_id is provided.
		$versions = array();
		if ( $post_id ) {
			$raw_versions = get_post_meta( $post_id, Constants::META_SCHEMA_HISTORY, true );
			if ( is_array( $raw_versions ) ) {
				foreach ( $raw_versions as $rv ) {
					$versions[] = array(
						'version'   => $rv['version'],
						'date'      => Utils::format_date( $rv['timestamp'] ),
						'timestamp' => $rv['timestamp'],
					);
				}
			}
		}

		wp_send_json_success(
			array(
				'events'   => $formatted,
				'versions' => $versions,
			)
		);
	}

	/**
	 * Start a new background scan job.
	 *
	 * @return void
	 */
	public function handle_start_scan_job(): void {
		check_ajax_referer( Constants::NONCE_ACTION_AJAX, 'nonce' );
		$this->verify_request();
		$batch_size = isset( $_POST['batch_size'] ) ? absint( $_POST['batch_size'] ) : 20;
		$res        = $this->scanner->start_batch_scan( $batch_size );
		wp_send_json_success( $res );
	}

	/**
	 * Process a batch chunk.
	 *
	 * @return void
	 */
	public function handle_process_scan_batch(): void {
		check_ajax_referer( Constants::NONCE_ACTION_AJAX, 'nonce' );
		$this->verify_request();
		$batch_size = isset( $_POST['batch_size'] ) ? absint( $_POST['batch_size'] ) : 20;
		$res        = $this->scanner->process_scan_batch( $batch_size );
		wp_send_json_success( $res );
	}

	/**
	 * Get scan progress.
	 *
	 * @return void
	 */
	public function handle_get_scan_progress(): void {
		check_ajax_referer( Constants::NONCE_ACTION_AJAX, 'nonce' );
		$this->verify_request();
		$progress = $this->scanner->get_scan_progress();
		wp_send_json_success( $progress );
	}

	/**
	 * Handle legacy bulk scan.
	 *
	 * @return void
	 */
	public function handle_bulk_scan(): void {
		check_ajax_referer( Constants::NONCE_ACTION_AJAX, 'nonce' );
		$this->verify_request();
		$result = $this->automation->run_automated_scan();
		wp_send_json_success( $result );
	}

	/**
	 * Handle clearing history log.
	 *
	 * @return void
	 */
	public function handle_clear_history(): void {
		check_ajax_referer( Constants::NONCE_ACTION_AJAX, 'nonce' );
		$this->verify_request();
		$cleared = $this->history->clear_history();
		if ( $cleared ) {
			wp_send_json_success( array( 'message' => __( 'History logs successfully cleared.', 'dpls-schema-ai' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to clear history logs.', 'dpls-schema-ai' ) ) );
		}
	}

	/**
	 * Handle exporting settings.
	 *
	 * @return void
	 */
	public function handle_export_settings(): void {
		check_ajax_referer( Constants::NONCE_ACTION_AJAX, 'nonce' );
		$this->verify_request();
		if ( ! $this->import_export ) {
			wp_send_json_error( array( 'message' => __( 'Import/Export service not available.', 'dpls-schema-ai' ) ) );
		}
		$data = $this->import_export->export_settings();
		wp_send_json_success(
			array(
				'filename' => 'dpls-schema-settings-' . gmdate( 'Y-m-d' ) . '.json',
				'data'     => $data,
			)
		);
	}

	/**
	 * Handle exporting schemas.
	 *
	 * @return void
	 */
	public function handle_export_schemas(): void {
		check_ajax_referer( Constants::NONCE_ACTION_AJAX, 'nonce' );
		$this->verify_request();
		if ( ! $this->import_export ) {
			wp_send_json_error( array( 'message' => __( 'Import/Export service not available.', 'dpls-schema-ai' ) ) );
		}
		$post_ids = isset( $_POST['post_ids'] ) && is_array( $_POST['post_ids'] ) ? array_map( 'absint', $_POST['post_ids'] ) : null;
		$data     = $this->import_export->export_schemas( $post_ids );
		wp_send_json_success(
			array(
				'filename' => 'dpls-schema-records-' . gmdate( 'Y-m-d' ) . '.json',
				'data'     => $data,
			)
		);
	}

	/**
	 * Handle validating and previewing import payload.
	 *
	 * @return void
	 */
	public function handle_preview_import(): void {
		check_ajax_referer( Constants::NONCE_ACTION_AJAX, 'nonce' );
		$this->verify_request();
		if ( ! $this->import_export ) {
			wp_send_json_error( array( 'message' => __( 'Import/Export service not available.', 'dpls-schema-ai' ) ) );
		}
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Raw JSON payload validated by json_decode and schema validator.
		$raw     = isset( $_POST['import_json'] ) ? wp_unslash( $_POST['import_json'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$decoded = json_decode( $raw, true );
		if ( ! is_array( $decoded ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid JSON string provided.', 'dpls-schema-ai' ) ) );
		}
		$report = $this->import_export->validate_import( $decoded );
		wp_send_json_success( $report );
	}

	/**
	 * Handle executing confirmed import.
	 *
	 * @return void
	 */
	public function handle_execute_import(): void {
		check_ajax_referer( Constants::NONCE_ACTION_AJAX, 'nonce' );
		$this->verify_request();
		if ( ! $this->import_export ) {
			wp_send_json_error( array( 'message' => __( 'Import/Export service not available.', 'dpls-schema-ai' ) ) );
		}
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Raw JSON payload validated by json_decode and schema validator.
		$raw       = isset( $_POST['import_json'] ) ? wp_unslash( $_POST['import_json'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$confirmed = ! empty( $_POST['confirmed'] );
		$overwrite = ! empty( $_POST['overwrite'] );
		$decoded   = json_decode( $raw, true );
		if ( ! is_array( $decoded ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid JSON string provided.', 'dpls-schema-ai' ) ) );
		}
		$type = $decoded['type'] ?? '';
		if ( 'settings' === $type ) {
			$res = $this->import_export->import_settings( $decoded, $confirmed );
		} elseif ( 'schemas' === $type ) {
			$res = $this->import_export->import_schemas( $decoded, $confirmed, $overwrite );
		} else {
			wp_send_json_error( array( 'message' => __( 'Unsupported import payload type.', 'dpls-schema-ai' ) ) );
		}

		if ( ! empty( $res['success'] ) ) {
			wp_send_json_success( $res );
		} else {
			wp_send_json_error( $res );
		}
	}

	/**
	 * Handle testing active AI provider connection (e.g. Ollama).
	 *
	 * @return void
	 */
	public function handle_test_ai_connection(): void {
		check_ajax_referer( Constants::NONCE_ACTION_AJAX, 'nonce' );

		if ( ! current_user_can( Constants::REQUIRED_CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'dpls-schema-ai' ) ) );
		}

		if ( ! $this->ai instanceof AIService ) {
			wp_send_json_error( array( 'message' => __( 'AI service not available.', 'dpls-schema-ai' ) ) );
		}

		$result = $this->ai->test_connection();
		if ( empty( $result['success'] ) ) {
			wp_send_json_error( $result );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Handle on-demand AI content analysis for a post.
	 *
	 * @return void
	 */
	public function handle_ai_analyze_post(): void {
		check_ajax_referer( Constants::NONCE_ACTION_AJAX, 'nonce' );
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$this->verify_request( $post_id );

		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post ID.', 'dpls-schema-ai' ) ) );
		}

		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post ) {
			wp_send_json_error( array( 'message' => __( 'Post not found.', 'dpls-schema-ai' ) ) );
		}

		if ( ! $this->ai instanceof AIService ) {
			wp_send_json_error( array( 'message' => __( 'AI service not configured.', 'dpls-schema-ai' ) ) );
		}

		$record = get_post_meta( $post_id, Constants::META_NORMALIZED_CONTENT, true );
		if ( ! is_array( $record ) || empty( $record ) ) {
			$record = $this->scanner->extract_structured_content( $post );
		}

		if ( ! is_array( $record ) ) {
			wp_send_json_error( array( 'message' => __( 'Could not extract content for analysis.', 'dpls-schema-ai' ) ) );
		}

		$analysis = $this->ai->analyze_post( $post_id, $record );

		wp_send_json_success(
			array(
				'post_id'  => $post_id,
				'analysis' => $analysis,
			)
		);
	}
}
