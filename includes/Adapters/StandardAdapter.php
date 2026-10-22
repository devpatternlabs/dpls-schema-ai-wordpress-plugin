<?php
/**
 * Standard WordPress Content Adapter.
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
 * Standard adapter extracting content directly from post_content.
 */
class StandardAdapter implements PageBuilderAdapterInterface {

	/**
	 * Always applicable as baseline fallback.
	 *
	 * @param \WP_Post $post Post object.
	 * @return bool
	 */
	public function is_applicable( \WP_Post $post ): bool {
		return true;
	}

	/**
	 * Extract post content.
	 *
	 * @param \WP_Post $post Post object.
	 * @return string
	 */
	public function extract_content( \WP_Post $post ): string {
		return (string) $post->post_content;
	}
}
