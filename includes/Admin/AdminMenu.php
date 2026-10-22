<?php
/**
 * Admin Menu Registration.
 *
 * @package DPLS\SchemaAI\Admin
 * @author  Dev Pattern Labs
 * @license GPL-2.0-or-later
 */

namespace DPLS\SchemaAI\Admin;

use DPLS\SchemaAI\AI\AIService;
use DPLS\SchemaAI\Common\Constants;
use DPLS\SchemaAI\Integrations\WooCommerceIntegration;
use DPLS\SchemaAI\Services\AutomationService;
use DPLS\SchemaAI\Services\GeneratorService;
use DPLS\SchemaAI\Services\HistoryService;
use DPLS\SchemaAI\Services\ImportExportService;
use DPLS\SchemaAI\Services\ScannerService;
use DPLS\SchemaAI\Services\SettingsService;
use DPLS\SchemaAI\Services\ValidatorService;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers all admin menus, submenus, and renders views.
 */
class AdminMenu {

	/**
	 * Service instances.
	 */
	protected SettingsService $settings;
	protected ScannerService $scanner;
	protected GeneratorService $generator;
	protected ValidatorService $validator;
	protected HistoryService $history;
	protected AutomationService $automation;
	protected ContentTable $content_table;
	protected ?WooCommerceIntegration $woocommerce;
	protected ?ImportExportService $import_export;
	protected ?AIService $ai;

	/**
	 * Constructor.
	 *
	 * @param SettingsService             $settings      Settings service.
	 * @param ScannerService              $scanner       Scanner service.
	 * @param GeneratorService            $generator     Generator service.
	 * @param ValidatorService            $validator     Validator service.
	 * @param HistoryService              $history       History service.
	 * @param AutomationService           $automation    Automation service.
	 * @param ContentTable                $content_table Content table renderer.
	 * @param WooCommerceIntegration|null $woocommerce   Optional WooCommerce integration.
	 * @param ImportExportService|null    $import_export Optional Import/Export service.
	 * @param AIService|null              $ai            Optional AI service coordinator.
	 */
	public function __construct(
		SettingsService $settings,
		ScannerService $scanner,
		GeneratorService $generator,
		ValidatorService $validator,
		HistoryService $history,
		AutomationService $automation,
		ContentTable $content_table,
		?WooCommerceIntegration $woocommerce = null,
		?ImportExportService $import_export = null,
		?AIService $ai = null
	) {
		$this->settings      = $settings;
		$this->scanner       = $scanner;
		$this->generator     = $generator;
		$this->validator     = $validator;
		$this->history       = $history;
		$this->automation    = $automation;
		$this->content_table = $content_table;
		$this->woocommerce   = $woocommerce;
		$this->import_export = $import_export;
		$this->ai            = $ai;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'register_menus' ) );
	}

	/**
	 * Register top-level menu and 8 submenus.
	 *
	 * @return void
	 */
	public function register_menus(): void {
		$cap = Constants::REQUIRED_CAPABILITY;

		// Top-level menu.
		add_menu_page(
			__( 'DPL Schema AI', 'dpls-schema-ai' ),
			__( 'DPL Schema AI', 'dpls-schema-ai' ),
			$cap,
			Constants::MENU_SLUG,
			array( $this, 'render_overview_page' ),
			'dashicons-share-alt2',
			30
		);

		// 1. Overview (replaces default redundant first item).
		add_submenu_page(
			Constants::MENU_SLUG,
			__( 'Overview &lsaquo; DPL Schema AI', 'dpls-schema-ai' ),
			__( 'Overview', 'dpls-schema-ai' ),
			$cap,
			Constants::MENU_SLUG,
			array( $this, 'render_overview_page' )
		);

		// 2. Pages & Posts.
		add_submenu_page(
			Constants::MENU_SLUG,
			__( 'Pages & Posts &lsaquo; DPL Schema AI', 'dpls-schema-ai' ),
			__( 'Pages & Posts', 'dpls-schema-ai' ),
			$cap,
			'dpls-schema-ai-posts',
			array( $this, 'render_posts_page' )
		);

		// 3. Schema Generator.
		add_submenu_page(
			Constants::MENU_SLUG,
			__( 'Schema Generator &lsaquo; DPL Schema AI', 'dpls-schema-ai' ),
			__( 'Schema Generator', 'dpls-schema-ai' ),
			$cap,
			'dpls-schema-ai-generator',
			array( $this, 'render_generator_page' )
		);

		// 4. Validation.
		add_submenu_page(
			Constants::MENU_SLUG,
			__( 'Validation &lsaquo; DPL Schema AI', 'dpls-schema-ai' ),
			__( 'Validation', 'dpls-schema-ai' ),
			$cap,
			'dpls-schema-ai-validation',
			array( $this, 'render_validation_page' )
		);

		// 5. Automation.
		add_submenu_page(
			Constants::MENU_SLUG,
			__( 'Automation &lsaquo; DPL Schema AI', 'dpls-schema-ai' ),
			__( 'Automation', 'dpls-schema-ai' ),
			$cap,
			'dpls-schema-ai-automation',
			array( $this, 'render_automation_page' )
		);

		// 6. History.
		add_submenu_page(
			Constants::MENU_SLUG,
			__( 'History &lsaquo; DPL Schema AI', 'dpls-schema-ai' ),
			__( 'History', 'dpls-schema-ai' ),
			$cap,
			'dpls-schema-ai-history',
			array( $this, 'render_history_page' )
		);

		// 7. Integrations.
		add_submenu_page(
			Constants::MENU_SLUG,
			__( 'Integrations &lsaquo; DPL Schema AI', 'dpls-schema-ai' ),
			__( 'Integrations', 'dpls-schema-ai' ),
			$cap,
			'dpls-schema-ai-integrations',
			array( $this, 'render_integrations_page' )
		);

		// 8. Settings.
		add_submenu_page(
			Constants::MENU_SLUG,
			__( 'Settings &lsaquo; DPL Schema AI', 'dpls-schema-ai' ),
			__( 'Settings', 'dpls-schema-ai' ),
			$cap,
			'dpls-schema-ai-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Verify permissions before rendering.
	 *
	 * @return void
	 */
	protected function check_permissions(): void {
		if ( ! current_user_can( Constants::REQUIRED_CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'dpls-schema-ai' ) );
		}
	}

	/**
	 * Render 1. Overview Page.
	 *
	 * @return void
	 */
	public function render_overview_page(): void {
		$this->check_permissions();
		$stats = $this->scanner->get_overview_statistics();
		require_once DPLS_SCHEMA_AI_PATH . 'includes/Admin/Views/overview.php';
	}

	/**
	 * Render 2. Pages & Posts Page.
	 *
	 * @return void
	 */
	public function render_posts_page(): void {
		$this->check_permissions();
		$this->content_table->prepare_items( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$table = $this->content_table;
		require_once DPLS_SCHEMA_AI_PATH . 'includes/Admin/Views/pages-posts.php';
	}

	/**
	 * Render 3. Schema Generator Page.
	 *
	 * @return void
	 */
	public function render_generator_page(): void {
		$this->check_permissions();
		$settings = $this->settings;
		$ai       = $this->ai;
		require_once DPLS_SCHEMA_AI_PATH . 'includes/Admin/Views/generator.php';
	}

	/**
	 * Render 4. Validation Page.
	 *
	 * @return void
	 */
	public function render_validation_page(): void {
		$this->check_permissions();
		$stats = $this->scanner->get_overview_statistics();
		require_once DPLS_SCHEMA_AI_PATH . 'includes/Admin/Views/validation.php';
	}

	/**
	 * Render 5. Automation Page.
	 *
	 * @return void
	 */
	public function render_automation_page(): void {
		$this->check_permissions();
		$settings = $this->settings;
		$stats    = $this->scanner->get_overview_statistics();
		require_once DPLS_SCHEMA_AI_PATH . 'includes/Admin/Views/automation.php';
	}

	/**
	 * Render 6. History Page.
	 *
	 * @return void
	 */
	public function render_history_page(): void {
		$this->check_permissions();
		$events = $this->history->get_events( 50 );
		require_once DPLS_SCHEMA_AI_PATH . 'includes/Admin/Views/history.php';
	}

	/**
	 * Render 7. Integrations Page.
	 *
	 * @return void
	 */
	public function render_integrations_page(): void {
		$this->check_permissions();
		$settings    = $this->settings;
		$woocommerce = $this->woocommerce ?? new WooCommerceIntegration( $this->settings );
		$ai          = $this->ai;
		require_once DPLS_SCHEMA_AI_PATH . 'includes/Admin/Views/integrations.php';
	}

	/**
	 * Render 8. Settings Page.
	 *
	 * @return void
	 */
	public function render_settings_page(): void {
		$this->check_permissions();

		// Handle settings save if posted directly.
		if ( isset( $_POST['dpls_settings_submit'] ) ) {
			check_admin_referer( Constants::NONCE_ACTION_ADMIN, 'dpls_nonce' );

			$raw_settings = isset( $_POST['dpls_settings'] ) && is_array( $_POST['dpls_settings'] ) ? wp_unslash( $_POST['dpls_settings'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Validated and sanitized deeply in SettingsService::sanitize().
			$this->settings->update( $raw_settings );

			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings successfully saved.', 'dpls-schema-ai' ) . '</p></div>';
		}

		$settings      = $this->settings->get_all();
		$import_export = $this->import_export ?? new ImportExportService( $this->settings, $this->validator, $this->history );
		require_once DPLS_SCHEMA_AI_PATH . 'includes/Admin/Views/settings.php';
	}
}
