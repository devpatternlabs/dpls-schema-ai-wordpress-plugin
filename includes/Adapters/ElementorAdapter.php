<?php
/**
 * Elementor Page Builder Adapter.
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
 * Extracts plain content from Elementor JSON data structures.
 */
class ElementorAdapter implements PageBuilderAdapterInterface {

	/**
	 * Check if post uses Elementor.
	 *
	 * @param \WP_Post $post Post object.
	 * @return bool
	 */
	public function is_applicable( \WP_Post $post ): bool {
		$edit_mode = get_post_meta( $post->ID, '_elementor_edit_mode', true );
		return 'builder' === $edit_mode;
	}

	/**
	 * Recursively extract text from Elementor data json.
	 *
	 * @param \WP_Post $post Post object.
	 * @return string
	 */
	public function extract_content( \WP_Post $post ): string {
		$raw_data = get_post_meta( $post->ID, '_elementor_data', true );
		if ( empty( $raw_data ) ) {
			return $post->post_content;
		}

		$elements = is_array( $raw_data ) ? $raw_data : json_decode( $raw_data, true );
		if ( ! is_array( $elements ) ) {
			return $post->post_content;
		}

		$extracted_text = array();
		$this->walk_elements( $elements, $extracted_text );

		if ( empty( $extracted_text ) ) {
			return $post->post_content;
		}

		return implode( "\n\n", $extracted_text );
	}

	/**
	 * Recursively walk Elementor elements tree and harvest text strings.
	 *
	 * @param array $elements Element tree.
	 * @param array $extracted Collection reference.
	 * @return void
	 */
	protected function walk_elements( array $elements, array &$extracted ): void {
		foreach ( $elements as $el ) {
			if ( ! is_array( $el ) ) {
				continue;
			}

			// Widget settings text.
			if ( isset( $el['settings'] ) && is_array( $el['settings'] ) ) {
				$s = $el['settings'];
				foreach ( array( 'title', 'heading_title', 'editor', 'description', 'text', 'html' ) as $key ) {
					if ( ! empty( $s[ $key ] ) && is_string( $s[ $key ] ) ) {
						$extracted[] = $s[ $key ];
					}
				}
			}

			// Sub elements.
			if ( ! empty( $el['elements'] ) && is_array( $el['elements'] ) ) {
				$this->walk_elements( $el['elements'], $extracted );
			}
		}
	}
}
