<?php
/**
 * Overview Dashboard View.
 *
 * @package DPLS\SchemaAI\Admin\Views
 * @author  Dev Pattern Labs
 * @license GPL-2.0-or-later
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Variables passed from AdminMenu:
 *
 * @var array $stats Array containing calculated statistics.
 */
?>
<div class="wrap dpls-admin-wrap">
	<div class="dpls-header">
		<div class="dpls-header-title">
			<h1>
				<span class="dashicons dashicons-share-alt2" aria-hidden="true"></span>
				<?php esc_html_e( 'DPL Schema AI &mdash; Overview', 'dpls-schema-ai' ); ?>
			</h1>
			<p class="dpls-tagline">
				<?php esc_html_e( 'Automated Schema.org structured data analysis, JSON-LD generation, and health maintenance.', 'dpls-schema-ai' ); ?>
			</p>
		</div>
		<div class="dpls-header-actions">
			<button type="button" class="button button-primary dpls-trigger-bulk-scan">
				<span class="dashicons dashicons-update" aria-hidden="true"></span>
				<?php esc_html_e( 'Run Content Scan Now', 'dpls-schema-ai' ); ?>
			</button>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=dpls-schema-ai-posts' ) ); ?>" class="button button-secondary">
				<span class="dashicons dashicons-list-view" aria-hidden="true"></span>
				<?php esc_html_e( 'Manage Content', 'dpls-schema-ai' ); ?>
			</a>
		</div>
	</div>

	<!-- Scan Metadata Banner -->
	<div class="dpls-scan-banner">
		<div class="dpls-scan-info">
			<span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span>
			<strong><?php esc_html_e( 'Last Content Scan:', 'dpls-schema-ai' ); ?></strong>
			<span id="dpls-last-scan-display"><?php echo esc_html( $stats['last_scan'] ); ?></span>
		</div>
		<div class="dpls-scan-tip">
			<span class="dashicons dashicons-info" aria-hidden="true"></span>
			<span><?php esc_html_e( 'Statistics reflect actual records from your WordPress content and schema metadata.', 'dpls-schema-ai' ); ?></span>
		</div>
	</div>

	<!-- Metric Stat Cards Grid -->
	<div class="dpls-stats-grid">
		<!-- Total Published Pages -->
		<div class="dpls-stat-card">
			<div class="dpls-stat-icon dpls-icon-pages">
				<span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
			</div>
			<div class="dpls-stat-content">
				<span class="dpls-stat-number"><?php echo esc_html( number_format_i18n( $stats['total_pages'] ) ); ?></span>
				<span class="dpls-stat-label"><?php esc_html_e( 'Total Published Pages', 'dpls-schema-ai' ); ?></span>
			</div>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=dpls-schema-ai-posts&post_type_filter=page' ) ); ?>" class="dpls-stat-link">
				<?php esc_html_e( 'View pages', 'dpls-schema-ai' ); ?> &rarr;
			</a>
		</div>

		<!-- Total Published Posts -->
		<div class="dpls-stat-card">
			<div class="dpls-stat-icon dpls-icon-posts">
				<span class="dashicons dashicons-admin-post" aria-hidden="true"></span>
			</div>
			<div class="dpls-stat-content">
				<span class="dpls-stat-number"><?php echo esc_html( number_format_i18n( $stats['total_posts'] ) ); ?></span>
				<span class="dpls-stat-label"><?php esc_html_e( 'Total Published Posts', 'dpls-schema-ai' ); ?></span>
			</div>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=dpls-schema-ai-posts&post_type_filter=post' ) ); ?>" class="dpls-stat-link">
				<?php esc_html_e( 'View posts', 'dpls-schema-ai' ); ?> &rarr;
			</a>
		</div>

		<!-- Managed Schema -->
		<div class="dpls-stat-card dpls-card-accent">
			<div class="dpls-stat-icon dpls-icon-managed">
				<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
			</div>
			<div class="dpls-stat-content">
				<span class="dpls-stat-number"><?php echo esc_html( number_format_i18n( $stats['managed_count'] ) ); ?></span>
				<span class="dpls-stat-label"><?php esc_html_e( 'Active Managed Schema', 'dpls-schema-ai' ); ?></span>
			</div>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=dpls-schema-ai-posts&schema_status_filter=applied' ) ); ?>" class="dpls-stat-link">
				<?php esc_html_e( 'View active', 'dpls-schema-ai' ); ?> &rarr;
			</a>
		</div>

		<!-- Awaiting Review -->
		<div class="dpls-stat-card">
			<div class="dpls-stat-icon dpls-icon-pending">
				<span class="dashicons dashicons-clock" aria-hidden="true"></span>
			</div>
			<div class="dpls-stat-content">
				<span class="dpls-stat-number"><?php echo esc_html( number_format_i18n( $stats['pending_review'] ) ); ?></span>
				<span class="dpls-stat-label"><?php esc_html_e( 'Awaiting Schema Review', 'dpls-schema-ai' ); ?></span>
			</div>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=dpls-schema-ai-posts&schema_status_filter=pending_review' ) ); ?>" class="dpls-stat-link">
				<?php esc_html_e( 'Review items', 'dpls-schema-ai' ); ?> &rarr;
			</a>
		</div>

		<!-- Validation Warnings -->
		<div class="dpls-stat-card <?php echo $stats['validation_warnings'] > 0 ? 'dpls-card-alert' : ''; ?>">
			<div class="dpls-stat-icon dpls-icon-warnings">
				<span class="dashicons dashicons-warning" aria-hidden="true"></span>
			</div>
			<div class="dpls-stat-content">
				<span class="dpls-stat-number"><?php echo esc_html( number_format_i18n( $stats['validation_warnings'] ) ); ?></span>
				<span class="dpls-stat-label"><?php esc_html_e( 'Validation Warnings', 'dpls-schema-ai' ); ?></span>
			</div>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=dpls-schema-ai-posts&schema_status_filter=warnings' ) ); ?>" class="dpls-stat-link">
				<?php esc_html_e( 'Fix warnings', 'dpls-schema-ai' ); ?> &rarr;
			</a>
		</div>

		<!-- Needs Updating -->
		<div class="dpls-stat-card <?php echo $stats['needs_update'] > 0 ? 'dpls-card-notice' : ''; ?>">
			<div class="dpls-stat-icon dpls-icon-update">
				<span class="dashicons dashicons-update" aria-hidden="true"></span>
			</div>
			<div class="dpls-stat-content">
				<span class="dpls-stat-number"><?php echo esc_html( number_format_i18n( $stats['needs_update'] ) ); ?></span>
				<span class="dpls-stat-label"><?php esc_html_e( 'Schema Needs Updating', 'dpls-schema-ai' ); ?></span>
			</div>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=dpls-schema-ai-posts&schema_status_filter=needs_update' ) ); ?>" class="dpls-stat-link">
				<?php esc_html_e( 'Update schema', 'dpls-schema-ai' ); ?> &rarr;
			</a>
		</div>
	</div>

	<!-- Operations & Quick Guidance Row -->
	<div class="dpls-dashboard-panels">
		<div class="dpls-panel">
			<h2><?php esc_html_e( 'Schema Health & Workflow', 'dpls-schema-ai' ); ?></h2>
			<div class="dpls-workflow-steps">
				<div class="dpls-workflow-step">
					<div class="dpls-step-badge">1</div>
					<div class="dpls-step-body">
						<h3><?php esc_html_e( 'Content Inspection', 'dpls-schema-ai' ); ?></h3>
						<p><?php esc_html_e( 'DPL Schema AI scans content structure, headings, taxonomies, and entities to determine the ideal Schema.org type.', 'dpls-schema-ai' ); ?></p>
					</div>
				</div>
				<div class="dpls-workflow-step">
					<div class="dpls-step-badge">2</div>
					<div class="dpls-step-body">
						<h3><?php esc_html_e( 'JSON-LD Generation', 'dpls-schema-ai' ); ?></h3>
						<p><?php esc_html_e( 'Generate clean, standards-compliant JSON-LD markup with authors, dates, publishers, and rich snippet signals.', 'dpls-schema-ai' ); ?></p>
					</div>
				</div>
				<div class="dpls-workflow-step">
					<div class="dpls-step-badge">3</div>
					<div class="dpls-step-body">
						<h3><?php esc_html_e( 'Validation & Application', 'dpls-schema-ai' ); ?></h3>
						<p><?php esc_html_e( 'Validate syntax and requirements, preview markup, and publish schema safely directly into your site <head>.', 'dpls-schema-ai' ); ?></p>
					</div>
				</div>
				<div class="dpls-workflow-step">
					<div class="dpls-step-badge">4</div>
					<div class="dpls-step-body">
						<h3><?php esc_html_e( 'Change Maintenance', 'dpls-schema-ai' ); ?></h3>
						<p><?php esc_html_e( 'When editors edit content, schema is automatically maintained or flagged as needing synchronization.', 'dpls-schema-ai' ); ?></p>
					</div>
				</div>
			</div>
		</div>

		<div class="dpls-panel dpls-panel-sidebar">
			<h2><?php esc_html_e( 'Quick Shortcuts', 'dpls-schema-ai' ); ?></h2>
			<ul class="dpls-action-list">
				<li>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=dpls-schema-ai-posts' ) ); ?>" class="dpls-action-link">
						<span class="dashicons dashicons-admin-post" aria-hidden="true"></span>
						<strong><?php esc_html_e( 'Pages & Posts Manager', 'dpls-schema-ai' ); ?></strong>
						<span class="description"><?php esc_html_e( 'Analyze, generate, preview, and apply schema.', 'dpls-schema-ai' ); ?></span>
					</a>
				</li>
				<li>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=dpls-schema-ai-validation' ) ); ?>" class="dpls-action-link">
						<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
						<strong><?php esc_html_e( 'Schema Validation Hub', 'dpls-schema-ai' ); ?></strong>
						<span class="description"><?php esc_html_e( 'Audit active schema for Schema.org warnings.', 'dpls-schema-ai' ); ?></span>
					</a>
				</li>
				<li>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=dpls-schema-ai-automation' ) ); ?>" class="dpls-action-link">
						<span class="dashicons dashicons-clock" aria-hidden="true"></span>
						<strong><?php esc_html_e( 'Automation & Cron', 'dpls-schema-ai' ); ?></strong>
						<span class="description"><?php esc_html_e( 'Manage scheduled background scans and rules.', 'dpls-schema-ai' ); ?></span>
					</a>
				</li>
				<li>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=dpls-schema-ai-settings' ) ); ?>" class="dpls-action-link">
						<span class="dashicons dashicons-admin-settings" aria-hidden="true"></span>
						<strong><?php esc_html_e( 'Global Settings', 'dpls-schema-ai' ); ?></strong>
						<span class="description"><?php esc_html_e( 'Configure post types, defaults, and data preservation.', 'dpls-schema-ai' ); ?></span>
					</a>
				</li>
			</ul>
		</div>
	</div>
</div>
