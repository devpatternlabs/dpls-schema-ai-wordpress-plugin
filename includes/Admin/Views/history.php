<?php
/**
 * History & Audit Trail View.
 *
 * @package DPLS\SchemaAI\Admin\Views
 * @author  Dev Pattern Labs
 * @license GPL-2.0-or-later
 */

namespace DPLS\SchemaAI\Admin\Views;

use DPLS\SchemaAI\Common\Utils;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Variables passed from AdminMenu:
 *
 * @var array $events
 */
?>
<div class="wrap dpls-admin-wrap">
	<div class="dpls-header">
		<div class="dpls-header-title">
			<h1>
				<span class="dashicons dashicons-backup" aria-hidden="true"></span>
				<?php esc_html_e( 'DPL Schema AI &mdash; Activity History', 'dpls-schema-ai' ); ?>
			</h1>
			<p class="dpls-tagline">
				<?php esc_html_e( 'Comprehensive audit log of schema generation, updates, validation events, and user actions.', 'dpls-schema-ai' ); ?>
			</p>
		</div>
		<div class="dpls-header-actions">
			<button type="button" class="button button-secondary dpls-clear-all-history">
				<span class="dashicons dashicons-trash" aria-hidden="true"></span>
				<?php esc_html_e( 'Clear History Logs', 'dpls-schema-ai' ); ?>
			</button>
		</div>
	</div>

	<div class="dpls-card dpls-table-card">
		<?php if ( empty( $events ) ) : ?>
			<div class="dpls-no-items" style="padding:40px 20px; text-align:center;">
				<span class="dashicons dashicons-backup" style="font-size:48px; width:48px; height:48px; color:#c3c4c7;"></span>
				<p><strong><?php esc_html_e( 'No activity logged yet.', 'dpls-schema-ai' ); ?></strong></p>
				<p class="description"><?php esc_html_e( 'Actions such as analyzing content, generating schema, or applying JSON-LD will appear here.', 'dpls-schema-ai' ); ?></p>
			</div>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th scope="col" style="width:180px;"><?php esc_html_e( 'Date / Time', 'dpls-schema-ai' ); ?></th>
						<th scope="col" style="width:120px;"><?php esc_html_e( 'Action', 'dpls-schema-ai' ); ?></th>
						<th scope="col" style="width:140px;"><?php esc_html_e( 'Initiator', 'dpls-schema-ai' ); ?></th>
						<th scope="col" style="width:220px;"><?php esc_html_e( 'Target Content', 'dpls-schema-ai' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Message / Details', 'dpls-schema-ai' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $events as $dpls_ev ) : ?>
						<?php
						$dpls_post_title = '-';
						if ( ! empty( $dpls_ev['post_id'] ) ) {
							$dpls_post_obj   = get_post( $dpls_ev['post_id'] );
							$dpls_post_title = $dpls_post_obj ? $dpls_post_obj->post_title : '#' . $dpls_ev['post_id'];
						}
						?>
						<tr>
							<td><?php echo esc_html( Utils::format_date( $dpls_ev['timestamp'] ) ); ?></td>
							<td><code class="dpls-code-type"><?php echo esc_html( $dpls_ev['action'] ); ?></code></td>
							<td><?php echo esc_html( $dpls_ev['user_name'] ?? 'System' ); ?></td>
							<td>
								<?php if ( ! empty( $dpls_ev['post_id'] ) ) : ?>
									<a href="<?php echo esc_url( get_edit_post_link( $dpls_ev['post_id'] ) ); ?>">
										<?php echo esc_html( $dpls_post_title ); ?>
									</a>
								<?php else : ?>
									<em><?php esc_html_e( 'Global / Bulk', 'dpls-schema-ai' ); ?></em>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( $dpls_ev['message'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
</div>
