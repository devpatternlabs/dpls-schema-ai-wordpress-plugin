<?php
/**
 * Automation Settings & Status View.
 *
 * @package DPLS\SchemaAI\Admin\Views
 * @author  Dev Pattern Labs
 * @license GPL-2.0-or-later
 */

namespace DPLS\SchemaAI\Admin\Views;

use DPLS\SchemaAI\Services\AutomationService;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Variables passed from AdminMenu:
 *
 * @var \DPLS\SchemaAI\Services\SettingsService $settings
 * @var array $stats
 */

$dpls_next_cron     = wp_next_scheduled( AutomationService::CRON_HOOK );
$dpls_next_cron_fmt = $dpls_next_cron ? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $dpls_next_cron ) : __( 'Not scheduled', 'dpls-schema-ai' );
$dpls_interval      = $settings->get( 'auto_scan_interval', 'weekly' );
$dpls_auto_update   = $settings->get( 'auto_update', true );
?>
<div class="wrap dpls-admin-wrap">
	<div class="dpls-header">
		<div class="dpls-header-title">
			<h1>
				<span class="dashicons dashicons-clock" aria-hidden="true"></span>
				<?php esc_html_e( 'DPL Schema AI &mdash; Automation & Cron', 'dpls-schema-ai' ); ?>
			</h1>
			<p class="dpls-tagline">
				<?php esc_html_e( 'Manage automated scanning schedules, real-time content modification watchers, and background tasks.', 'dpls-schema-ai' ); ?>
			</p>
		</div>
		<div class="dpls-header-actions">
			<button type="button" class="button button-primary dpls-trigger-bulk-scan">
				<span class="dashicons dashicons-update" aria-hidden="true"></span>
				<?php esc_html_e( 'Execute Scan Now', 'dpls-schema-ai' ); ?>
			</button>
		</div>
	</div>

	<div class="dpls-dashboard-panels">
		<div class="dpls-panel">
			<h2><?php esc_html_e( 'Automation Architecture Status', 'dpls-schema-ai' ); ?></h2>
			<table class="dpls-info-table">
				<tr>
					<th><?php esc_html_e( 'WP-Cron Hook:', 'dpls-schema-ai' ); ?></th>
					<td><code><?php echo esc_html( AutomationService::CRON_HOOK ); ?></code></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Scan Frequency:', 'dpls-schema-ai' ); ?></th>
					<td><strong><?php echo esc_html( ucfirst( $dpls_interval ) ); ?></strong></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Next Scheduled Scan:', 'dpls-schema-ai' ); ?></th>
					<td><?php echo esc_html( $dpls_next_cron_fmt ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Last Completed Scan:', 'dpls-schema-ai' ); ?></th>
					<td><?php echo esc_html( $stats['last_scan'] ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Real-time Save Watcher:', 'dpls-schema-ai' ); ?></th>
					<td>
						<?php if ( $dpls_auto_update ) : ?>
							<span class="dpls-badge dpls-badge-applied"><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Active (Auto-regenerates on save)', 'dpls-schema-ai' ); ?></span>
						<?php else : ?>
							<span class="dpls-badge dpls-badge-pending"><span class="dashicons dashicons-clock"></span> <?php esc_html_e( 'Flags for manual review on save', 'dpls-schema-ai' ); ?></span>
						<?php endif; ?>
					</td>
				</tr>
			</table>
		</div>

		<div class="dpls-panel dpls-panel-sidebar">
			<h2><?php esc_html_e( 'Automated Maintenance Workflow', 'dpls-schema-ai' ); ?></h2>
			<div class="dpls-info-box">
				<p>
					<strong><?php esc_html_e( 'How change detection works:', 'dpls-schema-ai' ); ?></strong>
				</p>
				<p>
					<?php esc_html_e( 'When any author edits a post, an MD5 hash of title, excerpt, and body is compared against the stored generation hash.', 'dpls-schema-ai' ); ?>
				</p>
				<p>
					<?php esc_html_e( 'If automatic update is enabled, fresh schema is generated and applied silently without downtime. If disabled, the dashboard alerts you with a "Needs Update" badge.', 'dpls-schema-ai' ); ?>
				</p>
			</div>
		</div>
	</div>
</div>
