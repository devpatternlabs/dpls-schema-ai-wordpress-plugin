<?php
/**
 * Schema Generator Interface.
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
 * Interface for generating Schema.org JSON-LD proposals and structures.
 */
interface GeneratorInterface extends ServiceInterface {

	/**
	 * Generate a structured schema proposal without auto-publishing.
	 *
	 * @param int         $post_id Post ID.
	 * @param string|null $schema_type Optional override type.
	 * @return array Structured proposal object.
	 */
	public function generate_proposal( int $post_id, ?string $schema_type = null ): array;

	/**
	 * Generate schema for a given post (convenience wrapper).
	 *
	 * @param int         $post_id Post ID.
	 * @param string|null $schema_type Optional override schema type.
	 * @return array Array containing generated JSON-LD schema or error.
	 */
	public function generate_for_post( int $post_id, ?string $schema_type = null ): array;

	/**
	 * Build JSON-LD structured data array from normalized content record.
	 *
	 * @param string $schema_type Schema type.
	 * @param array  $record Normalized content record.
	 * @param array  $locked_properties Locked properties to preserve.
	 * @return array
	 */
	public function build_schema_data( string $schema_type, array $record, array $locked_properties = array() ): array;
}
