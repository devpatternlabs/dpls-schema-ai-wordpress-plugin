<?php
/**
 * AI Provider Interface.
 *
 * @package DPLS\SchemaAI\AI
 * @author  Dev Pattern Labs
 * @license GPL-2.0-or-later
 */

namespace DPLS\SchemaAI\AI;

// Prevent direct script access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Contract for modular AI content analysis providers.
 */
interface AIProviderInterface {

	/**
	 * Unique provider identifier (e.g. 'ollama').
	 *
	 * @return string
	 */
	public function get_id(): string;

	/**
	 * Human-readable provider label.
	 *
	 * @return string
	 */
	public function get_label(): string;

	/**
	 * Checks if the provider is currently configured and enabled.
	 *
	 * @return bool
	 */
	public function is_available(): bool;

	/**
	 * Tests connection to the configured AI service endpoint.
	 *
	 * @return array Array with 'success' (bool), 'message' (string), 'latency_ms' (int), 'models' (array).
	 */
	public function test_connection(): array;

	/**
	 * Analyzes a normalized content record and generates structured schema recommendations.
	 *
	 * @param array $content_record Normalized WordPress content record.
	 * @param array $options Optional provider parameters (model, max_tokens, etc.).
	 * @return array Structured analysis result or error array.
	 */
	public function analyze_content( array $content_record, array $options = array() ): array;

	/**
	 * Gets the provider settings definition and defaults.
	 *
	 * @return array
	 */
	public function get_settings(): array;
}
