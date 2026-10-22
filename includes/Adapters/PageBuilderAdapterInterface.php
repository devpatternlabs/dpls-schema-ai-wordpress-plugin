<?php
/**
 * Page Builder Adapter Interface.
 *
 * @package DPLS\SchemaAI\Adapters
 * @author  Dev Pattern Labs
 * @license GPL-2.0-or-later
 */

namespace DPLS\SchemaAI\Adapters;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface for extracting structured content from page builders or custom storage.
 */
interface PageBuilderAdapterInterface {

	/**
	 * Check if this adapter supports the given post.
	 *
	 * @param \WP_Post $post Post object.
	 * @return bool
	 */
	public function is_applicable( \WP_Post $post ): bool;

	/**
	 * Extract raw content string or elements from custom storage.
	 *
	 * @param \WP_Post $post Post object.
	 * @return string Extracted raw content.
	 */
	public function extract_content( \WP_Post $post ): string;
}
