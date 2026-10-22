<?php
/**
 * Service Interface Contract.
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
 * Common interface for all pluggable services.
 */
interface ServiceInterface {

	/**
	 * Register hooks and initialize the service.
	 *
	 * @return void
	 */
	public function register(): void;
}
