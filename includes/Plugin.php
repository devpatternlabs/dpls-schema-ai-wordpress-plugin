<?php
/**
 * Main Plugin Orchestrator.
 *
 * @package DPLS\SchemaAI
 * @author  Dev Pattern Labs
 * @license GPL-2.0-or-later
 */

namespace DPLS\SchemaAI;

use DPLS\SchemaAI\Admin\AdminAssets;
use DPLS\SchemaAI\Admin\AdminMenu;
use DPLS\SchemaAI\Admin\AjaxHandler;
use DPLS\SchemaAI\Admin\ContentTable;
use DPLS\SchemaAI\Admin\EditorMetaBox;
use DPLS\SchemaAI\AI\AIService;
use DPLS\SchemaAI\Common\Constants;
use DPLS\SchemaAI\Generator\ContentClassifier;
use DPLS\SchemaAI\Integrations\WooCommerceIntegration;
use DPLS\SchemaAI\Services\AutomationService;
use DPLS\SchemaAI\Services\GeneratorService;
use DPLS\SchemaAI\Services\HistoryService;
use DPLS\SchemaAI\Services\ImportExportService;
use DPLS\SchemaAI\Services\OutputService;
use DPLS\SchemaAI\Services\PrivacyService;
use DPLS\SchemaAI\Services\ScannerService;
use DPLS\SchemaAI\Services\SettingsService;
use DPLS\SchemaAI\Services\UpdaterService;
use DPLS\SchemaAI\Services\ValidatorService;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin singleton bootstrap class.
 */
class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	protected static ?Plugin $instance = null;

	/**
	 * Service instances.
	 */
	public SettingsService $settings;
	public HistoryService $history;
	public ContentClassifier $classifier;
	public ScannerService $scanner;
	public AIService $ai;
	public GeneratorService $generator;
	public ValidatorService $validator;
	public OutputService $output;
	public UpdaterService $updater;
	public AutomationService $automation;
	public ContentTable $content_table;
	public AdminMenu $admin_menu;
	public AdminAssets $admin_assets;
	public AjaxHandler $ajax_handler;
	public EditorMetaBox $editor_metabox;
	public WooCommerceIntegration $woocommerce;
	public ImportExportService $import_export;
	public PrivacyService $privacy;

	/**
	 * Get singleton instance.
	 *
	 * @return Plugin
	 */
	public static function get_instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor. Initializes services and registers hooks.
	 */
	protected function __construct() {
		$this->init_services();
		$this->register_hooks();
	}

	/**
	 * Instantiate all services with proper dependencies.
	 *
	 * @return void
	 */
	protected function init_services(): void {
		$this->settings       = new SettingsService();
		$this->history        = new HistoryService();
		$this->classifier     = new ContentClassifier();
		$this->woocommerce    = new WooCommerceIntegration( $this->settings );
		$this->scanner        = new ScannerService( $this->settings, $this->history, $this->classifier, $this->woocommerce );
		$this->ai             = new AIService( $this->settings, $this->history, $this->classifier );
		$this->generator      = new GeneratorService( $this->settings, $this->history, $this->classifier, $this->scanner, $this->ai );
		$this->validator      = new ValidatorService();
		$this->output         = new OutputService( $this->settings );
		$this->updater        = new UpdaterService(
			$this->settings,
			$this->history,
			$this->generator,
			$this->scanner,
			$this->validator,
			$this->classifier,
			$this->ai
		);
		$this->automation     = new AutomationService( $this->settings, $this->scanner, $this->history );
		$this->import_export  = new ImportExportService( $this->settings, $this->validator, $this->history );
		$this->privacy        = new PrivacyService();
		$this->content_table  = new ContentTable( $this->settings );
		$this->admin_menu     = new AdminMenu(
			$this->settings,
			$this->scanner,
			$this->generator,
			$this->validator,
			$this->history,
			$this->automation,
			$this->content_table,
			$this->woocommerce,
			$this->import_export,
			$this->ai
		);
		$this->admin_assets   = new AdminAssets();
		$this->ajax_handler   = new AjaxHandler(
			$this->scanner,
			$this->generator,
			$this->validator,
			$this->history,
			$this->automation,
			$this->import_export,
			$this->ai
		);
		$this->editor_metabox = new EditorMetaBox(
			$this->settings,
			$this->scanner,
			$this->generator,
			$this->validator
		);
	}

	/**
	 * Register hooks across all services.
	 *
	 * @return void
	 */
	protected function register_hooks(): void {
		$this->settings->register();
		$this->history->register();
		$this->scanner->register();
		$this->ai->register();
		$this->generator->register();
		$this->validator->register();
		$this->output->register();
		$this->updater->register();
		$this->automation->register();
		$this->woocommerce->register();
		$this->privacy->register();

		if ( is_admin() ) {
			$this->admin_menu->register();
			$this->admin_assets->register();
			$this->editor_metabox->register();
		}

		if ( wp_doing_ajax() ) {
			$this->ajax_handler->register();
		}
	}

	/**
	 * Activation callback.
	 *
	 * @return void
	 */
	public static function activate(): void {
		// Set default settings if not already present.
		$current_settings = get_option( Constants::OPTION_SETTINGS );
		if ( empty( $current_settings ) ) {
			$defaults = array(
				'enabled_post_types' => array( 'post', 'page' ),
				'auto_update'        => true,
				'automation_policy'  => Constants::POLICY_SAFE,
				'auto_scan_interval' => 'weekly',
				'default_publisher'  => get_bloginfo( 'name' ),
				'default_logo_url'   => '',
				'preserve_data'      => true,
				'ollama_enabled'     => false,
				'ollama_endpoint'    => 'http://localhost:11434',
				'ollama_model'       => 'llama3:latest',
				'organization'       => array(
					'name'         => get_bloginfo( 'name' ),
					'url'          => function_exists( 'home_url' ) ? home_url() : '',
					'logo_url'     => '',
					'type'         => 'Organization',
					'email'        => get_option( 'admin_email', '' ),
					'phone'        => '',
					'contact_type' => 'customer support',
				),
			);
			update_option( Constants::OPTION_SETTINGS, $defaults );
		}

		// Ensure preservation option defaults to 1.
		if ( false === get_option( Constants::OPTION_PRESERVE_DATA ) ) {
			update_option( Constants::OPTION_PRESERVE_DATA, 1 );
		}

		// Schedule cron job.
		$instance = self::get_instance();
		$instance->automation->schedule_event();

		// Log activation event.
		$instance->history->log_event(
			'activate',
			null,
			__( 'DPLS Schema AI activated.', 'dpls-schema-ai' )
		);
	}

	/**
	 * Deactivation callback.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		$instance = self::get_instance();
		$instance->automation->clear_schedule();

		// Log deactivation event.
		$instance->history->log_event(
			'deactivate',
			null,
			__( 'DPLS Schema AI deactivated.', 'dpls-schema-ai' )
		);
	}
}
