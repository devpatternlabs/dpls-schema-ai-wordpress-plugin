<?php
/**
 * Admin Assets Handler.
 *
 * @package DPLS\SchemaAI\Admin
 * @author  Dev Pattern Labs
 * @license GPL-2.0-or-later
 */

namespace DPLS\SchemaAI\Admin;

use DPLS\SchemaAI\Common\Constants;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles enqueuing styles and scripts on plugin admin screens.
 */
class AdminAssets {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue assets only on plugin admin pages.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		// Only load on our plugin pages.
		if ( false === strpos( $hook_suffix, 'dpls-schema-ai' ) ) {
			return;
		}

		// Enqueue WordPress dashicons and core components.
		wp_enqueue_style( 'dashicons' );

		// Plugin Admin CSS.
		wp_enqueue_style(
			'dpls-schema-ai-admin',
			plugins_url( 'assets/css/admin.css', DPLS_SCHEMA_AI_FILE ),
			array( 'dashicons' ),
			Constants::VERSION
		);

		// Plugin Admin JS.
		wp_enqueue_script(
			'dpls-schema-ai-admin',
			plugins_url( 'assets/js/admin.js', DPLS_SCHEMA_AI_FILE ),
			array( 'jquery' ),
			Constants::VERSION,
			true
		);

		// Localize script data.
		wp_localize_script(
			'dpls-schema-ai-admin',
			'dplsSchemaAi',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( Constants::NONCE_ACTION_AJAX ),
				'i18n'    => array(
					'confirmDisable'   => __( 'Are you sure you want to disable schema for this item? Structured data will no longer appear on the frontend.', 'dpls-schema-ai' ),
					'confirmClearLogs' => __( 'Are you sure you want to clear all activity history logs?', 'dpls-schema-ai' ),
					'confirmScan'      => __( 'Start bulk scan now? This will analyze content items in batches.', 'dpls-schema-ai' ),
					'analyzing'        => __( 'Analyzing...', 'dpls-schema-ai' ),
					'generating'       => __( 'Generating Schema...', 'dpls-schema-ai' ),
					'applying'         => __( 'Applying...', 'dpls-schema-ai' ),
					'validating'       => __( 'Validating...', 'dpls-schema-ai' ),
					'saving'           => __( 'Saving...', 'dpls-schema-ai' ),
					'success'          => __( 'Operation completed successfully.', 'dpls-schema-ai' ),
					'error'            => __( 'An error occurred. Please try again.', 'dpls-schema-ai' ),
					'copied'           => __( 'Copied to clipboard!', 'dpls-schema-ai' ),
				),
			)
		);
	}
}
