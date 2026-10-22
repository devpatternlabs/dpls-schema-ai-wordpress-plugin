<?php
/**
 * Output Interface.
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
 * Interface for rendering Schema.org JSON-LD in WordPress frontend.
 */
interface OutputInterface extends ServiceInterface {

	/**
	 * Output the JSON-LD script tag in the page <head>.
	 *
	 * @return void
	 */
	public function render_head_schema(): void;

	/**
	 * Get the active schema for the current queried object or specific post.
	 *
	 * @param int|null $post_id Optional post ID.
	 * @return string|null JSON-LD payload or null.
	 */
	public function get_schema_for_post( ?int $post_id = null ): ?string;
}
