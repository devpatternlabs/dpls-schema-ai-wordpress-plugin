<?php
/**
 * Validator Interface.
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
 * Interface for validating JSON-LD schema strings.
 */
interface ValidatorInterface extends ServiceInterface {

	/**
	 * Validate a JSON-LD schema string or array.
	 *
	 * @param string|array $schema JSON-LD payload.
	 * @param string|null  $expected_type Expected schema @type.
	 * @return array Validation result containing 'is_valid', 'errors', 'warnings'.
	 */
	public function validate( $schema, ?string $expected_type = null ): array;
}
