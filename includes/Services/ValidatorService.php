<?php
/**
 * Validator Service.
 *
 * @package DPLS\SchemaAI\Services
 * @author  Dev Pattern Labs
 * @license GPL-2.0-or-later
 */

namespace DPLS\SchemaAI\Services;

use DPLS\SchemaAI\Interfaces\ValidatorInterface;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validates Schema.org JSON-LD against syntax and schema requirements.
 */
class ValidatorService implements ValidatorInterface {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		// Nothing hooked globally.
	}

	/**
	 * Validate a JSON-LD payload.
	 *
	 * @param string|array $schema JSON-LD string or associative array.
	 * @param string|null  $expected_type Optional expected Schema @type.
	 * @return array Validation report.
	 */
	public function validate( $schema, ?string $expected_type = null ): array {
		$errors   = array();
		$warnings = array();

		// Check emptiness.
		if ( empty( $schema ) ) {
			return array(
				'is_valid' => false,
				'errors'   => array( __( 'Schema payload is empty.', 'dpls-schema-ai' ) ),
				'warnings' => array(),
			);
		}

		// Decode if string.
		if ( is_string( $schema ) ) {
			$data = json_decode( $schema, true );
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				return array(
					'is_valid' => false,
					/* translators: %s: JSON syntax error message */
					'errors'   => array( sprintf( __( 'Invalid JSON syntax: %s', 'dpls-schema-ai' ), json_last_error_msg() ) ),
					'warnings' => array(),
				);
			}
		} elseif ( is_array( $schema ) ) {
			$data = $schema;
		} else {
			return array(
				'is_valid' => false,
				'errors'   => array( __( 'Invalid schema data format supplied.', 'dpls-schema-ai' ) ),
				'warnings' => array(),
			);
		}

		// Verify @context.
		if ( empty( $data['@context'] ) ) {
			$errors[] = __( 'Missing required "@context" property.', 'dpls-schema-ai' );
		} else {
			$context = (string) $data['@context'];
			if ( ! in_array( rtrim( $context, '/' ), array( 'https://schema.org', 'http://schema.org' ), true ) ) {
				/* translators: %s: Given schema context URL */
				$warnings[] = sprintf( __( 'Standard @context is "https://schema.org", received: "%s".', 'dpls-schema-ai' ), $context );
			}
		}

		// Verify @type.
		if ( empty( $data['@type'] ) ) {
			$errors[] = __( 'Missing required "@type" property.', 'dpls-schema-ai' );
		} else {
			$type = (string) $data['@type'];
			if ( $expected_type && strcasecmp( $type, $expected_type ) !== 0 ) {
				$warnings[] = sprintf(
					/* translators: 1: Current schema type, 2: Expected schema type */
					__( 'Schema type is "%1$s", but "%2$s" was expected.', 'dpls-schema-ai' ),
					$type,
					$expected_type
				);
			}

			// Validate type-specific properties.
			$this->validate_type_specific( $type, $data, $errors, $warnings );
		}

		return array(
			'is_valid' => empty( $errors ),
			'errors'   => $errors,
			'warnings' => $warnings,
		);
	}

	/**
	 * Run checks on specific Schema types.
	 *
	 * @param string $type Schema type.
	 * @param array  $data Payload array.
	 * @param array  $errors Errors reference.
	 * @param array  $warnings Warnings reference.
	 * @return void
	 */
	protected function validate_type_specific( string $type, array $data, array &$errors, array &$warnings ): void {
		switch ( $type ) {
			case 'Article':
			case 'BlogPosting':
			case 'NewsArticle':
				if ( empty( $data['headline'] ) ) {
					$errors[] = __( 'Articles require a "headline" property.', 'dpls-schema-ai' );
				}
				if ( empty( $data['author'] ) ) {
					$warnings[] = __( 'Article missing recommended "author" property.', 'dpls-schema-ai' );
				}
				if ( empty( $data['datePublished'] ) ) {
					$warnings[] = __( 'Article missing recommended "datePublished" property.', 'dpls-schema-ai' );
				}
				if ( empty( $data['image'] ) ) {
					$warnings[] = __( 'Article missing recommended "image" property for rich snippets.', 'dpls-schema-ai' );
				}
				break;

			case 'FAQPage':
				if ( empty( $data['mainEntity'] ) || ! is_array( $data['mainEntity'] ) ) {
					$errors[] = __( 'FAQPage requires a "mainEntity" array of Question entities.', 'dpls-schema-ai' );
				}
				break;

			case 'Product':
				if ( empty( $data['name'] ) ) {
					$errors[] = __( 'Product requires a "name" property.', 'dpls-schema-ai' );
				}
				if ( empty( $data['offers'] ) ) {
					$warnings[] = __( 'Product missing recommended "offers" property.', 'dpls-schema-ai' );
				}
				break;

			case 'WebPage':
			case 'AboutPage':
			case 'ContactPage':
				if ( empty( $data['name'] ) && empty( $data['headline'] ) ) {
					$warnings[] = __( 'WebPage should include a "name" or "headline".', 'dpls-schema-ai' );
				}
				break;
		}
	}
}
