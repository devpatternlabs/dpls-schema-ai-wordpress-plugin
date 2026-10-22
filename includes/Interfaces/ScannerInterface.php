<?php
/**
 * Scanner Interface.
 *
 * @package DPLS\SchemaAI\Interfaces
 * @author  Dev Pattern Labs
 * @license GPL-2.0-or-later
 */

namespace DPLS\SchemaAI\Interfaces;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface for analyzing content, extracting structured representations, and batch scanning.
 */
interface ScannerInterface extends ServiceInterface {

	/**
	 * Extract normalized structured content from a post without executing shortcodes/PHP.
	 *
	 * @param \WP_Post $post Post object.
	 * @param bool     $force Whether to bypass content hash cache check.
	 * @return array|null Normalized content record or null if ineligible.
	 */
	public function extract_structured_content( \WP_Post $post, bool $force = false ): ?array;

	/**
	 * Analyze a single post and return analysis summary.
	 *
	 * @param int  $post_id Post ID to inspect.
	 * @param bool $force Force re-scan.
	 * @return array Analysis results with suggested schema, confidence, and detected signals.
	 */
	public function analyze_post( int $post_id, bool $force = false ): array;

	/**
	 * Start a new resumable batch scanning job.
	 *
	 * @param int $batch_size Number of items per batch chunk.
	 * @return array Initialized job state.
	 */
	public function start_batch_scan( int $batch_size = 20 ): array;

	/**
	 * Process the next batch chunk of the active scanning job.
	 *
	 * @param int $batch_size Number of items in chunk.
	 * @return array Progress and results.
	 */
	public function process_scan_batch( int $batch_size = 20 ): array;

	/**
	 * Get current batch scan job progress.
	 *
	 * @return array Job status array.
	 */
	public function get_scan_progress(): array;

	/**
	 * Cancel or reset the current batch scan job.
	 *
	 * @return bool
	 */
	public function cancel_scan_job(): bool;

	/**
	 * Get overview metrics and real statistics.
	 *
	 * @return array Calculated metrics array.
	 */
	public function get_overview_statistics(): array;

	/**
	 * Recommend schema type based on post characteristics.
	 *
	 * @param \WP_Post $post Post object.
	 * @return string Recommended schema type identifier.
	 */
	public function recommend_schema_type( \WP_Post $post ): string;
}
