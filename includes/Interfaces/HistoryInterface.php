<?php
/**
 * History Interface.
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
 * Interface for tracking schema activity, changes, and audit trail.
 */
interface HistoryInterface extends ServiceInterface {

	/**
	 * Log an event.
	 *
	 * @param string   $action Action identifier (e.g. 'analyze', 'generate', 'apply', 'disable').
	 * @param int|null $post_id Related post ID or null for global actions.
	 * @param string   $message Human readable summary.
	 * @param array    $context Additional metadata.
	 * @return bool True if logged.
	 */
	public function log_event( string $action, ?int $post_id, string $message, array $context = array() ): bool;

	/**
	 * Retrieve logged history events.
	 *
	 * @param int      $limit Number of records to return.
	 * @param int|null $post_id Filter by post ID.
	 * @return array List of history entries.
	 */
	public function get_events( int $limit = 50, ?int $post_id = null ): array;

	/**
	 * Clear all history logs.
	 *
	 * @return bool True on success.
	 */
	public function clear_history(): bool;
}
