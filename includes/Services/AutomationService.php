<?php
/**
 * Automation Service.
 *
 * @package DPLS\SchemaAI\Services
 * @author  Dev Pattern Labs
 * @license GPL-2.0-or-later
 */

namespace DPLS\SchemaAI\Services;

use DPLS\SchemaAI\Common\Constants;
use DPLS\SchemaAI\Interfaces\ServiceInterface;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles automated background scans and WP-Cron scheduling.
 */
class AutomationService implements ServiceInterface {

	public const CRON_HOOK = 'dpls_schema_ai_cron_scan';

	/**
	 * Settings Service.
	 *
	 * @var SettingsService
	 */
	protected SettingsService $settings;

	/**
	 * Scanner Service.
	 *
	 * @var ScannerService
	 */
	protected ScannerService $scanner;

	/**
	 * History Service.
	 *
	 * @var HistoryService
	 */
	protected HistoryService $history;

	/**
	 * Constructor.
	 *
	 * @param SettingsService $settings Settings service.
	 * @param ScannerService  $scanner Scanner service.
	 * @param HistoryService  $history History service.
	 */
	public function __construct( SettingsService $settings, ScannerService $scanner, HistoryService $history ) {
		$this->settings = $settings;
		$this->scanner  = $scanner;
		$this->history  = $history;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'cron_schedules', array( $this, 'add_cron_schedules' ) );
		add_action( self::CRON_HOOK, array( $this, 'run_automated_scan' ) );
	}

	/**
	 * Add custom cron schedules if needed (e.g. weekly).
	 *
	 * @param array $schedules Cron schedules.
	 * @return array
	 */
	public function add_cron_schedules( array $schedules ): array {
		if ( ! isset( $schedules['weekly'] ) ) {
			$schedules['weekly'] = array(
				'interval' => 604800,
				'display'  => __( 'Once Weekly', 'dpls-schema-ai' ),
			);
		}
		return $schedules;
	}

	/**
	 * Run the automated background scan.
	 *
	 * @return array Scan results.
	 */
	public function run_automated_scan(): array {
		$enabled_types = $this->settings->get( 'enabled_post_types', array( 'post', 'page' ) );

		$query = new \WP_Query(
			array(
				'post_type'      => $enabled_types,
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		$scanned_count = 0;
		if ( ! empty( $query->posts ) ) {
			foreach ( $query->posts as $post_id ) {
				$this->scanner->analyze_post( (int) $post_id );
				++$scanned_count;
			}
		}

		$now = time();
		update_option( Constants::OPTION_LAST_SCAN, $now );

		$this->history->log_event(
			'automation_scan',
			null,
			sprintf(
				/* translators: %d: Number of posts scanned */
				__( 'Automated scan completed for %d content items.', 'dpls-schema-ai' ),
				$scanned_count
			),
			array( 'scanned_count' => $scanned_count )
		);

		return array(
			'success'       => true,
			'scanned_count' => $scanned_count,
			'timestamp'     => $now,
		);
	}

	/**
	 * Schedule or reschedule cron event based on settings.
	 *
	 * @return void
	 */
	public function schedule_event(): void {
		$interval = $this->settings->get( 'auto_scan_interval', 'weekly' );
		if ( 'disabled' === $interval ) {
			$this->clear_schedule();
			return;
		}

		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( ! $timestamp ) {
			wp_schedule_event( time() + 3600, $interval, self::CRON_HOOK );
		}
	}

	/**
	 * Clear scheduled cron event.
	 *
	 * @return void
	 */
	public function clear_schedule(): void {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}
}
