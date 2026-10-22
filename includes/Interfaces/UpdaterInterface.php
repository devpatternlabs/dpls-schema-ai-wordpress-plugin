<?php
/**
 * Updater Interface.
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
 * Interface for maintaining schema freshness when content changes.
 */
interface UpdaterInterface extends ServiceInterface {

	/**
	 * Handle post save and assess freshness or trigger automatic re-generation.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post Post object.
	 * @param bool     $update Whether this is an existing post being updated.
	 * @return void
	 */
	public function on_post_save( int $post_id, \WP_Post $post, bool $update ): void;
}
