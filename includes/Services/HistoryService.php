<?php
/**
 * History Service.
 *
 * @package DPLS\SchemaAI\Services
 * @author  Dev Pattern Labs
 * @license GPL-2.0-or-later
 */

namespace DPLS\SchemaAI\Services;

use DPLS\SchemaAI\Common\Constants;
use DPLS\SchemaAI\Interfaces\HistoryInterface;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Audit log and activity history tracker.
 */
class HistoryService implements HistoryInterface {

	/**
	 * Maximum number of logs to retain.
	 */
	protected const MAX_ENTRIES = 100;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		// Nothing hooked globally; called by services on state changes.
	}

	/**
	 * Log an event.
	 *
	 * @param string   $action Action key.
	 * @param int|null $post_id Associated post ID.
	 * @param string   $message Description.
	 * @param array    $context Extra payload.
	 * @return bool
	 */
	public function log_event( string $action, ?int $post_id, string $message, array $context = array() ): bool {
		$events = get_option( Constants::OPTION_HISTORY, array() );
		if ( ! is_array( $events ) ) {
			$events = array();
		}

		$user_id   = get_current_user_id();
		$user_name = $user_id ? ( get_userdata( $user_id )->display_name ?? 'User' ) : 'System';

		$entry = array(
			'id'        => uniqid( 'dpls_', true ),
			'timestamp' => time(),
			'action'    => sanitize_key( $action ),
			'post_id'   => $post_id ? absint( $post_id ) : null,
			'user_id'   => $user_id ? absint( $user_id ) : 0,
			'user_name' => sanitize_text_field( $user_name ),
			'message'   => sanitize_text_field( $message ),
			'context'   => $context,
		);

		array_unshift( $events, $entry );

		// Cap max entries to preserve storage efficiency.
		if ( count( $events ) > self::MAX_ENTRIES ) {
			$events = array_slice( $events, 0, self::MAX_ENTRIES );
		}

		return update_option( Constants::OPTION_HISTORY, $events, false );
	}

	/**
	 * Retrieve logged history events.
	 *
	 * @param int      $limit Maximum records to return.
	 * @param int|null $post_id Filter by post ID.
	 * @return array
	 */
	public function get_events( int $limit = 50, ?int $post_id = null ): array {
		$events = get_option( Constants::OPTION_HISTORY, array() );
		if ( ! is_array( $events ) ) {
			return array();
		}

		if ( $post_id !== null ) {
			$events = array_filter(
				$events,
				static function ( $event ) use ( $post_id ) {
					return isset( $event['post_id'] ) && (int) $event['post_id'] === $post_id;
				}
			);
		}

		return array_slice( array_values( $events ), 0, $limit );
	}

	/**
	 * Clear all history logs.
	 *
	 * @return bool
	 */
	public function clear_history(): bool {
		if ( ! current_user_can( Constants::REQUIRED_CAPABILITY ) ) {
			return false;
		}

		return update_option( Constants::OPTION_HISTORY, array(), false );
	}
}
