<?php
/**
 * Validation Hub View.
 *
 * @package DPLS\SchemaAI\Admin\Views
 * @author  Dev Pattern Labs
 * @license GPL-2.0-or-later
 */

namespace DPLS\SchemaAI\Admin\Views;

use DPLS\SchemaAI\Common\Constants;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Variables passed from AdminMenu:
 *
 * @var array $stats
 */

// Query items with warnings.
$dpls_warning_posts = get_posts(
	array(
		'post_type'      => array( 'post', 'page' ),
		'posts_per_page' => 20,
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Filter by validation warnings required.
		'meta_query'     => array(
			array(
				'key'     => Constants::META_SCHEMA_STATUS,
				'value'   => Constants::STATUS_WARNINGS,
				'compare' => '=',
			),
		),
	)
);
?>
<div class="wrap dpls-admin-wrap">
	<div class="dpls-header">
		<div class="dpls-header-title">
			<h1>
				<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
				<?php esc_html_e( 'DPL Schema AI &mdash; Validation Hub', 'dpls-schema-ai' ); ?>
			</h1>
			<p class="dpls-tagline">
				<?php esc_html_e( 'Validate Schema.org structure, check required attributes, and fix Google rich snippet warnings.', 'dpls-schema-ai' ); ?>
			</p>
		</div>
	</div>

	<!-- Health Metrics -->
	<div class="dpls-stats-grid">
		<div class="dpls-stat-card dpls-card-accent">
			<div class="dpls-stat-icon dpls-icon-managed">
				<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
			</div>
			<div class="dpls-stat-content">
				<span class="dpls-stat-number"><?php echo esc_html( number_format_i18n( $stats['managed_count'] ) ); ?></span>
				<span class="dpls-stat-label"><?php esc_html_e( 'Valid Active Schemas', 'dpls-schema-ai' ); ?></span>
			</div>
		</div>

		<div class="dpls-stat-card <?php echo $stats['validation_warnings'] > 0 ? 'dpls-card-alert' : ''; ?>">
			<div class="dpls-stat-icon dpls-icon-warnings">
				<span class="dashicons dashicons-warning" aria-hidden="true"></span>
			</div>
			<div class="dpls-stat-content">
				<span class="dpls-stat-number"><?php echo esc_html( number_format_i18n( $stats['validation_warnings'] ) ); ?></span>
				<span class="dpls-stat-label"><?php esc_html_e( 'Items with Warnings', 'dpls-schema-ai' ); ?></span>
			</div>
		</div>

		<div class="dpls-stat-card">
			<div class="dpls-stat-icon dpls-icon-pending">
				<span class="dashicons dashicons-clock" aria-hidden="true"></span>
			</div>
			<div class="dpls-stat-content">
				<span class="dpls-stat-number"><?php echo esc_html( number_format_i18n( $stats['pending_review'] ) ); ?></span>
				<span class="dpls-stat-label"><?php esc_html_e( 'Pending Review', 'dpls-schema-ai' ); ?></span>
			</div>
		</div>
	</div>

	<!-- Items with Warnings Table -->
	<div class="dpls-card" style="margin-top:20px;">
		<h2><?php esc_html_e( 'Pages & Posts Requiring Validation Attention', 'dpls-schema-ai' ); ?></h2>
		<?php if ( empty( $dpls_warning_posts ) ) : ?>
			<div class="dpls-notice-success-box">
				<span class="dashicons dashicons-yes" aria-hidden="true"></span>
				<div>
					<strong><?php esc_html_e( 'Excellent! No validation warnings found.', 'dpls-schema-ai' ); ?></strong>
					<p><?php esc_html_e( 'All active schemas conform to Schema.org requirements and recommended properties.', 'dpls-schema-ai' ); ?></p>
				</div>
			</div>
		<?php else : ?>
			<table class="wp-list-table widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Content Title', 'dpls-schema-ai' ); ?></th>
						<th><?php esc_html_e( 'Type', 'dpls-schema-ai' ); ?></th>
						<th><?php esc_html_e( 'Detected Issues', 'dpls-schema-ai' ); ?></th>
						<th><?php esc_html_e( 'Action', 'dpls-schema-ai' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $dpls_warning_posts as $dpls_wp_item ) : ?>
						<?php
						$dpls_errs = get_post_meta( $dpls_wp_item->ID, Constants::META_VALIDATION_ERRORS, true );
						if ( ! is_array( $dpls_errs ) ) {
							$dpls_errs = array( __( 'General schema warning.', 'dpls-schema-ai' ) );
						}
						?>
						<tr>
							<td><strong><?php echo esc_html( $dpls_wp_item->post_title ); ?></strong></td>
							<td><?php echo esc_html( ucfirst( $dpls_wp_item->post_type ) ); ?></td>
							<td>
								<ul class="dpls-warning-list">
									<?php foreach ( $dpls_errs as $dpls_err ) : ?>
										<li><span class="dashicons dashicons-warning" aria-hidden="true"></span> <?php echo esc_html( $dpls_err ); ?></li>
									<?php endforeach; ?>
								</ul>
							</td>
							<td>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=dpls-schema-ai-posts&s=' . urlencode( $dpls_wp_item->post_title ) ) ); ?>" class="button button-small">
									<?php esc_html_e( 'Fix in Manager', 'dpls-schema-ai' ); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
</div>
