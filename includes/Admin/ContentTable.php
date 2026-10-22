<?php
/**
 * Content Table Renderer for Pages and Posts.
 *
 * @package DPLS\SchemaAI\Admin
 * @author  Dev Pattern Labs
 * @license GPL-2.0-or-later
 */

namespace DPLS\SchemaAI\Admin;

use DPLS\SchemaAI\Common\Constants;
use DPLS\SchemaAI\Common\Utils;
use DPLS\SchemaAI\Services\SettingsService;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles querying, pagination, filtering and rendering the content list table.
 */
class ContentTable {

	/**
	 * Settings Service.
	 *
	 * @var SettingsService
	 */
	protected SettingsService $settings;

	/**
	 * Current page number.
	 *
	 * @var int
	 */
	protected int $current_page = 1;

	/**
	 * Items per page.
	 *
	 * @var int
	 */
	protected int $per_page = 15;

	/**
	 * Total items found.
	 *
	 * @var int
	 */
	protected int $total_items = 0;

	/**
	 * Total pages calculated.
	 *
	 * @var int
	 */
	protected int $total_pages = 1;

	/**
	 * Retrieved posts list.
	 *
	 * @var array
	 */
	protected array $items = array();

	/**
	 * Constructor.
	 *
	 * @param SettingsService $settings Settings service.
	 */
	public function __construct( SettingsService $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Prepare query and items based on request filters.
	 *
	 * @param array $request Request variables ($_GET).
	 * @return void
	 */
	public function prepare_items( array $request ): void {
		// Capability check.
		if ( ! current_user_can( Constants::REQUIRED_CAPABILITY ) ) {
			return;
		}

		$this->current_page = isset( $request['paged'] ) ? max( 1, absint( $request['paged'] ) ) : 1;
		$search_query       = isset( $request['s'] ) ? sanitize_text_field( wp_unslash( $request['s'] ) ) : '';
		$type_filter        = isset( $request['post_type_filter'] ) ? sanitize_key( $request['post_type_filter'] ) : '';
		$status_filter      = isset( $request['schema_status_filter'] ) ? sanitize_key( $request['schema_status_filter'] ) : '';

		$enabled_types = $this->settings->get( 'enabled_post_types', array( 'post', 'page' ) );

		if ( ! empty( $type_filter ) && in_array( $type_filter, $enabled_types, true ) ) {
			$query_types = array( $type_filter );
		} else {
			$query_types = $enabled_types;
		}

		$query_args = array(
			'post_type'      => $query_types,
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'paged'          => $this->current_page,
			'posts_per_page' => $this->per_page,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		if ( ! empty( $search_query ) ) {
			$query_args['s'] = $search_query;
		}

		if ( ! empty( $status_filter ) ) {
			if ( Constants::STATUS_NONE === $status_filter ) {
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Filter by schema status meta required.
				$query_args['meta_query'] = array(
					'relation' => 'OR',
					array(
						'key'     => Constants::META_SCHEMA_STATUS,
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => Constants::META_SCHEMA_STATUS,
						'value'   => Constants::STATUS_NONE,
						'compare' => '=',
					),
				);
			} else {
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Filter by schema status meta required.
				$query_args['meta_query'] = array(
					array(
						'key'     => Constants::META_SCHEMA_STATUS,
						'value'   => $status_filter,
						'compare' => '=',
					),
				);
			}
		}

		$query = new \WP_Query( $query_args );

		$this->items       = $query->posts;
		$this->total_items = (int) $query->found_posts;
		$this->total_pages = (int) $query->max_num_pages;
	}

	/**
	 * Get current items.
	 *
	 * @return array
	 */
	public function get_items(): array {
		return $this->items;
	}

	/**
	 * Render the filters and search form.
	 *
	 * @param array $request Current GET parameters.
	 * @return void
	 */
	public function render_filters( array $request ): void {
		$search_query   = isset( $request['s'] ) ? sanitize_text_field( wp_unslash( $request['s'] ) ) : '';
		$type_filter    = isset( $request['post_type_filter'] ) ? sanitize_key( $request['post_type_filter'] ) : '';
		$status_filter  = isset( $request['schema_status_filter'] ) ? sanitize_key( $request['schema_status_filter'] ) : '';
		$enabled_types  = $this->settings->get( 'enabled_post_types', array( 'post', 'page' ) );
		?>
		<div class="dpls-table-controls">
			<form method="get" class="dpls-filter-form" action="">
				<input type="hidden" name="page" value="dpls-schema-ai-posts" />

				<div class="alignleft actions bulkactions">
					<label for="post-type-filter" class="screen-reader-text"><?php esc_html_e( 'Filter by post type', 'dpls-schema-ai' ); ?></label>
					<select name="post_type_filter" id="post-type-filter">
						<option value=""><?php esc_html_e( 'All Content Types', 'dpls-schema-ai' ); ?></option>
						<?php foreach ( $enabled_types as $pt ) : ?>
							<?php $obj = get_post_type_object( $pt ); ?>
							<option value="<?php echo esc_attr( $pt ); ?>" <?php selected( $type_filter, $pt ); ?>>
								<?php echo esc_html( $obj ? $obj->labels->singular_name : ucfirst( $pt ) ); ?>
							</option>
						<?php endforeach; ?>
					</select>

					<label for="schema-status-filter" class="screen-reader-text"><?php esc_html_e( 'Filter by schema status', 'dpls-schema-ai' ); ?></label>
					<select name="schema_status_filter" id="schema-status-filter">
						<option value=""><?php esc_html_e( 'All Schema Statuses', 'dpls-schema-ai' ); ?></option>
						<option value="<?php echo esc_attr( Constants::STATUS_APPLIED ); ?>" <?php selected( $status_filter, Constants::STATUS_APPLIED ); ?>>
							<?php esc_html_e( 'Active', 'dpls-schema-ai' ); ?>
						</option>
						<option value="<?php echo esc_attr( Constants::STATUS_PENDING_REVIEW ); ?>" <?php selected( $status_filter, Constants::STATUS_PENDING_REVIEW ); ?>>
							<?php esc_html_e( 'Review Needed', 'dpls-schema-ai' ); ?>
						</option>
						<option value="<?php echo esc_attr( Constants::STATUS_NEEDS_UPDATE ); ?>" <?php selected( $status_filter, Constants::STATUS_NEEDS_UPDATE ); ?>>
							<?php esc_html_e( 'Needs Update', 'dpls-schema-ai' ); ?>
						</option>
						<option value="<?php echo esc_attr( Constants::STATUS_WARNINGS ); ?>" <?php selected( $status_filter, Constants::STATUS_WARNINGS ); ?>>
							<?php esc_html_e( 'Warnings', 'dpls-schema-ai' ); ?>
						</option>
						<option value="<?php echo esc_attr( Constants::STATUS_DISABLED ); ?>" <?php selected( $status_filter, Constants::STATUS_DISABLED ); ?>>
							<?php esc_html_e( 'Disabled', 'dpls-schema-ai' ); ?>
						</option>
						<option value="<?php echo esc_attr( Constants::STATUS_NONE ); ?>" <?php selected( $status_filter, Constants::STATUS_NONE ); ?>>
							<?php esc_html_e( 'Not Analyzed', 'dpls-schema-ai' ); ?>
						</option>
					</select>

					<input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'dpls-schema-ai' ); ?>">

					<?php if ( ! empty( $type_filter ) || ! empty( $status_filter ) || ! empty( $search_query ) ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=dpls-schema-ai-posts' ) ); ?>" class="button button-secondary">
							<?php esc_html_e( 'Reset Filters', 'dpls-schema-ai' ); ?>
						</a>
					<?php endif; ?>
				</div>

				<p class="search-box">
					<label class="screen-reader-text" for="post-search-input"><?php esc_html_e( 'Search Content:', 'dpls-schema-ai' ); ?></label>
					<input type="search" id="post-search-input" name="s" value="<?php echo esc_attr( $search_query ); ?>" placeholder="<?php esc_attr_e( 'Search by title...', 'dpls-schema-ai' ); ?>" />
					<input type="submit" id="search-submit" class="button" value="<?php esc_attr_e( 'Search', 'dpls-schema-ai' ); ?>" />
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Render pagination controls.
	 *
	 * @return void
	 */
	public function render_pagination(): void {
		if ( $this->total_pages <= 1 ) {
			return;
		}

		$page_links = paginate_links(
			array(
				'base'      => add_query_arg( 'paged', '%#%' ),
				'format'    => '',
				'prev_text' => __( '&laquo; Prev', 'dpls-schema-ai' ),
				'next_text' => __( 'Next &raquo;', 'dpls-schema-ai' ),
				'total'     => $this->total_pages,
				'current'   => $this->current_page,
				'type'      => 'plain',
			)
		);

		if ( $page_links ) {
			?>
			<div class="tablenav-pages">
				<span class="displaying-num">
					<?php
					printf(
						/* translators: %s: Number of items */
						esc_html( _n( '%s item', '%s items', $this->total_items, 'dpls-schema-ai' ) ),
						esc_html( number_format_i18n( $this->total_items ) )
					);
					?>
				</span>
				<span class="pagination-links">
					<?php echo wp_kses_post( $page_links ); ?>
				</span>
			</div>
			<?php
		}
	}

	/**
	 * Render the full table.
	 *
	 * @return void
	 */
	public function render_table(): void {
		?>
		<table class="wp-list-table widefat fixed striped table-view-list dpls-content-table">
			<thead>
				<tr>
					<th scope="col" class="manage-column column-title column-primary"><?php esc_html_e( 'Content Title', 'dpls-schema-ai' ); ?></th>
					<th scope="col" class="manage-column column-type"><?php esc_html_e( 'Content Type', 'dpls-schema-ai' ); ?></th>
					<th scope="col" class="manage-column column-status"><?php esc_html_e( 'Publication Status', 'dpls-schema-ai' ); ?></th>
					<th scope="col" class="manage-column column-suggested"><?php esc_html_e( 'Suggested Schema', 'dpls-schema-ai' ); ?></th>
					<th scope="col" class="manage-column column-schema-status"><?php esc_html_e( 'Schema Status', 'dpls-schema-ai' ); ?></th>
					<th scope="col" class="manage-column column-last-analyzed"><?php esc_html_e( 'Last Analyzed', 'dpls-schema-ai' ); ?></th>
					<th scope="col" class="manage-column column-auto-update"><?php esc_html_e( 'Automatic Update', 'dpls-schema-ai' ); ?></th>
					<th scope="col" class="manage-column column-actions"><?php esc_html_e( 'Actions', 'dpls-schema-ai' ); ?></th>
				</tr>
			</thead>
			<tbody id="the-list">
				<?php if ( empty( $this->items ) ) : ?>
					<tr>
						<td colspan="8" class="dpls-no-items">
							<?php esc_html_e( 'No content matching the criteria found.', 'dpls-schema-ai' ); ?>
						</td>
					</tr>
				<?php else : ?>
					<?php foreach ( $this->items as $post ) : ?>
						<?php $this->render_row( $post ); ?>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
			<tfoot>
				<tr>
					<th scope="col" class="manage-column column-title column-primary"><?php esc_html_e( 'Content Title', 'dpls-schema-ai' ); ?></th>
					<th scope="col" class="manage-column column-type"><?php esc_html_e( 'Content Type', 'dpls-schema-ai' ); ?></th>
					<th scope="col" class="manage-column column-status"><?php esc_html_e( 'Publication Status', 'dpls-schema-ai' ); ?></th>
					<th scope="col" class="manage-column column-suggested"><?php esc_html_e( 'Suggested Schema', 'dpls-schema-ai' ); ?></th>
					<th scope="col" class="manage-column column-schema-status"><?php esc_html_e( 'Schema Status', 'dpls-schema-ai' ); ?></th>
					<th scope="col" class="manage-column column-last-analyzed"><?php esc_html_e( 'Last Analyzed', 'dpls-schema-ai' ); ?></th>
					<th scope="col" class="manage-column column-auto-update"><?php esc_html_e( 'Automatic Update', 'dpls-schema-ai' ); ?></th>
					<th scope="col" class="manage-column column-actions"><?php esc_html_e( 'Actions', 'dpls-schema-ai' ); ?></th>
				</tr>
			</tfoot>
		</table>
		<?php
	}

	/**
	 * Render single table row.
	 *
	 * @param \WP_Post $post Post object.
	 * @return void
	 */
	protected function render_row( \WP_Post $post ): void {
		$post_id          = $post->ID;
		$title            = ! empty( $post->post_title ) ? $post->post_title : __( '(no title)', 'dpls-schema-ai' );
		$edit_link        = get_edit_post_link( $post_id );
		$type_obj         = get_post_type_object( $post->post_type );
		$type_label       = $type_obj ? $type_obj->labels->singular_name : ucfirst( $post->post_type );
		$status_obj       = get_post_status_object( $post->post_status );
		$status_label     = $status_obj ? $status_obj->label : ucfirst( $post->post_status );
		$suggested_schema = get_post_meta( $post_id, Constants::META_SUGGESTED_SCHEMA, true );
		$schema_status    = get_post_meta( $post_id, Constants::META_SCHEMA_STATUS, true );
		if ( empty( $schema_status ) ) {
			$schema_status = Constants::STATUS_NONE;
		}

		$last_analyzed = get_post_meta( $post_id, Constants::META_LAST_ANALYZED, true );
		$auto_update   = get_post_meta( $post_id, Constants::META_AUTO_UPDATE, true );
		$has_active    = (bool) get_post_meta( $post_id, Constants::META_ACTIVE_SCHEMA, true );
		$has_draft     = (bool) get_post_meta( $post_id, Constants::META_DRAFT_SCHEMA, true );

		if ( '' === $auto_update ) {
			$global_auto = (bool) $this->settings->get( 'auto_update', true );
			$auto_label  = $global_auto ? __( 'Enabled (Global)', 'dpls-schema-ai' ) : __( 'Disabled (Global)', 'dpls-schema-ai' );
			$auto_class  = $global_auto ? 'dpls-text-success' : 'dpls-text-muted';
		} else {
			$is_enabled = ( 'yes' === $auto_update );
			$auto_label = $is_enabled ? __( 'Enabled', 'dpls-schema-ai' ) : __( 'Disabled', 'dpls-schema-ai' );
			$auto_class = $is_enabled ? 'dpls-text-success' : 'dpls-text-muted';
		}
		?>
		<tr id="dpls-post-row-<?php echo esc_attr( $post_id ); ?>" data-post-id="<?php echo esc_attr( $post_id ); ?>">
			<td class="title column-title has-row-actions column-primary">
				<strong>
					<?php if ( $edit_link ) : ?>
						<a class="row-title" href="<?php echo esc_url( $edit_link ); ?>">
							<?php echo esc_html( $title ); ?>
						</a>
					<?php else : ?>
						<?php echo esc_html( $title ); ?>
					<?php endif; ?>
				</strong>
				<div class="row-actions">
					<span class="view">
						<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'View', 'dpls-schema-ai' ); ?>
						</a> |
					</span>
					<span class="edit">
						<a href="<?php echo esc_url( $edit_link ); ?>">
							<?php esc_html_e( 'Edit', 'dpls-schema-ai' ); ?>
						</a>
					</span>
				</div>
			</td>
			<td class="column-type">
				<span class="dpls-type-tag"><?php echo esc_html( $type_label ); ?></span>
			</td>
			<td class="column-status">
				<span class="dpls-pub-status dpls-status-<?php echo esc_attr( $post->post_status ); ?>">
					<?php echo esc_html( $status_label ); ?>
				</span>
			</td>
			<td class="column-suggested">
				<?php if ( ! empty( $suggested_schema ) ) : ?>
					<code class="dpls-code-type"><?php echo esc_html( $suggested_schema ); ?></code>
				<?php else : ?>
					<span class="dpls-text-muted"><?php esc_html_e( 'None', 'dpls-schema-ai' ); ?></span>
				<?php endif; ?>
			</td>
			<td class="column-schema-status">
				<?php echo Utils::get_status_badge( $schema_status ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</td>
			<td class="column-last-analyzed">
				<?php echo esc_html( Utils::format_date( $last_analyzed ) ); ?>
			</td>
			<td class="column-auto-update">
				<span class="<?php echo esc_attr( $auto_class ); ?>">
					<?php echo esc_html( $auto_label ); ?>
				</span>
			</td>
			<td class="column-actions">
				<div class="dpls-action-buttons">
					<button type="button" class="button button-small dpls-action-analyze" data-post-id="<?php echo esc_attr( $post_id ); ?>" title="<?php esc_attr_e( 'Analyze content and suggest schema', 'dpls-schema-ai' ); ?>">
						<span class="dashicons dashicons-search" aria-hidden="true"></span> <?php esc_html_e( 'Analyze', 'dpls-schema-ai' ); ?>
					</button>

					<button type="button" class="button button-small dpls-action-generate" data-post-id="<?php echo esc_attr( $post_id ); ?>" title="<?php esc_attr_e( 'Generate draft schema', 'dpls-schema-ai' ); ?>">
						<span class="dashicons dashicons-admin-generic" aria-hidden="true"></span> <?php esc_html_e( 'Generate', 'dpls-schema-ai' ); ?>
					</button>

					<button type="button" class="button button-small dpls-action-preview" data-post-id="<?php echo esc_attr( $post_id ); ?>" <?php disabled( ! $has_active && ! $has_draft ); ?> title="<?php esc_attr_e( 'Preview generated JSON-LD', 'dpls-schema-ai' ); ?>">
						<span class="dashicons dashicons-visibility" aria-hidden="true"></span> <?php esc_html_e( 'Preview', 'dpls-schema-ai' ); ?>
					</button>

					<button type="button" class="button button-small button-primary dpls-action-apply" data-post-id="<?php echo esc_attr( $post_id ); ?>" <?php disabled( ! $has_draft && ! $has_active ); ?> title="<?php esc_attr_e( 'Apply schema to frontend', 'dpls-schema-ai' ); ?>">
						<span class="dashicons dashicons-saved" aria-hidden="true"></span> <?php esc_html_e( 'Apply', 'dpls-schema-ai' ); ?>
					</button>

					<button type="button" class="button button-small dpls-action-validate" data-post-id="<?php echo esc_attr( $post_id ); ?>" <?php disabled( ! $has_active && ! $has_draft ); ?> title="<?php esc_attr_e( 'Validate schema syntax and completeness', 'dpls-schema-ai' ); ?>">
						<span class="dashicons dashicons-yes" aria-hidden="true"></span> <?php esc_html_e( 'Validate', 'dpls-schema-ai' ); ?>
					</button>

					<button type="button" class="button button-small dpls-action-disable" data-post-id="<?php echo esc_attr( $post_id ); ?>" <?php disabled( Constants::STATUS_DISABLED === $schema_status || Constants::STATUS_NONE === $schema_status ); ?> title="<?php esc_attr_e( 'Disable schema for this content', 'dpls-schema-ai' ); ?>">
						<span class="dashicons dashicons-hidden" aria-hidden="true"></span> <?php esc_html_e( 'Disable', 'dpls-schema-ai' ); ?>
					</button>

					<button type="button" class="button button-small dpls-action-history" data-post-id="<?php echo esc_attr( $post_id ); ?>" title="<?php esc_attr_e( 'View activity history for this content', 'dpls-schema-ai' ); ?>">
						<span class="dashicons dashicons-backup" aria-hidden="true"></span> <?php esc_html_e( 'History', 'dpls-schema-ai' ); ?>
					</button>
				</div>
			</td>
		</tr>
		<?php
	}
}
