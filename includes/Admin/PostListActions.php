<?php
/**
 * WordPress Post List Table Bulk Actions and Custom Columns.
 *
 * Integrates DPLS Schema AI directly into the core WordPress Posts, Pages,
 * and Custom Post Types list tables with bulk schema generation and schema status badges.
 *
 * @package DPLS\SchemaAI\Admin
 * @author  Dev Pattern Labs
 * @license GPL-2.0-or-later
 */

namespace DPLS\SchemaAI\Admin;

use DPLS\SchemaAI\Common\Constants;
use DPLS\SchemaAI\Common\Utils;
use DPLS\SchemaAI\Services\GeneratorService;
use DPLS\SchemaAI\Services\ScannerService;
use DPLS\SchemaAI\Services\SettingsService;
use DPLS\SchemaAI\Services\ValidatorService;

// Prevent direct script access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PostListActions.
 */
class PostListActions {

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
	 * Generator Service.
	 *
	 * @var GeneratorService
	 */
	protected GeneratorService $generator;

	/**
	 * Validator Service.
	 *
	 * @var ValidatorService
	 */
	protected ValidatorService $validator;

	/**
	 * Constructor.
	 *
	 * @param SettingsService  $settings Settings service.
	 * @param ScannerService   $scanner Scanner service.
	 * @param GeneratorService $generator Generator service.
	 * @param ValidatorService $validator Validator service.
	 */
	public function __construct(
		SettingsService $settings,
		ScannerService $scanner,
		GeneratorService $generator,
		ValidatorService $validator
	) {
		$this->settings  = $settings;
		$this->scanner   = $scanner;
		$this->generator = $generator;
		$this->validator = $validator;
	}

	/**
	 * Register hooks for all configured post types.
	 *
	 * @return void
	 */
	public function register(): void {
		if ( ! is_admin() ) {
			return;
		}

		$enabled_types = $this->settings->get( 'enabled_post_types', array( 'post', 'page' ) );

		foreach ( $enabled_types as $pt ) {
			// Register bulk actions.
			add_filter( "bulk_actions-edit-{$pt}", array( $this, 'register_bulk_actions' ) );
			add_filter( "handle_bulk_actions-edit-{$pt}", array( $this, 'handle_bulk_actions' ), 10, 3 );

			// Register custom schema status column.
			add_filter( "manage_{$pt}_posts_columns", array( $this, 'add_schema_column' ) );
			add_action( "manage_{$pt}_posts_custom_column", array( $this, 'render_schema_column' ), 10, 2 );
		}

		add_action( 'admin_notices', array( $this, 'render_bulk_notice' ) );
	}

	/**
	 * Add custom DPLS bulk actions to the post table dropdown.
	 *
	 * @param array<string, string> $actions Existing bulk actions.
	 * @return array<string, string>
	 */
	public function register_bulk_actions( array $actions ): array {
		if ( current_user_can( Constants::REQUIRED_CAPABILITY ) ) {
			$actions['dpls_bulk_generate'] = __( 'Generate Schema (DPLS AI)', 'dpls-schema-ai' );
			$actions['dpls_bulk_apply']    = __( 'Apply Schema Proposals (DPLS)', 'dpls-schema-ai' );
		}
		return $actions;
	}

	/**
	 * Handle execution of custom bulk actions.
	 *
	 * @param string   $redirect_to Redirect URL.
	 * @param string   $action Selected bulk action name.
	 * @param int[]    $post_ids Array of selected post IDs.
	 * @return string Redirect URL with query parameters.
	 */
	public function handle_bulk_actions( string $redirect_to, string $action, array $post_ids ): string {
		if ( ! in_array( $action, array( 'dpls_bulk_generate', 'dpls_bulk_apply' ), true ) ) {
			return $redirect_to;
		}

		if ( ! current_user_can( Constants::REQUIRED_CAPABILITY ) ) {
			return $redirect_to;
		}

		$generated_count = 0;
		$applied_count   = 0;

		foreach ( $post_ids as $post_id ) {
			$post_id = (int) $post_id;
			if ( $post_id <= 0 || ! current_user_can( 'edit_post', $post_id ) ) {
				continue;
			}

			if ( 'dpls_bulk_generate' === $action ) {
				// 1. Analyze and extract content.
				$analysis = $this->scanner->analyze_post( $post_id );
				if ( ! empty( $analysis ) && ! empty( $analysis['success'] ) ) {
					// 2. Generate proposal.
					$proposal = $this->generator->generate_proposal( $post_id );
					if ( ! empty( $proposal ) ) {
						$generated_count++;
					}
				}
			} elseif ( 'dpls_bulk_apply' === $action ) {
				// Apply pending proposal to active schema if valid.
				$proposal = get_post_meta( $post_id, Constants::META_SCHEMA_PROPOSAL, true );
				if ( is_array( $proposal ) && ! empty( $proposal['json_ld'] ) ) {
					$validation = $this->validator->validate( $proposal['json_ld'] );
					if ( $validation['is_valid'] ) {
						update_post_meta( $post_id, Constants::META_ACTIVE_SCHEMA, Utils::encode_schema( $proposal['json_ld'] ) );
						update_post_meta( $post_id, Constants::META_SCHEMA_STATUS, Constants::STATUS_APPROVED );
						update_post_meta( $post_id, Constants::META_APPLIED_TIME, current_time( 'mysql' ) );

						$current_version = (int) get_post_meta( $post_id, Constants::META_SCHEMA_VERSION, true );
						update_post_meta( $post_id, Constants::META_SCHEMA_VERSION, $current_version + 1 );
						$applied_count++;
					}
				}
			}
		}

		$redirect_to = remove_query_arg( array( 'dpls_bulk_generated', 'dpls_bulk_applied' ), $redirect_to );

		if ( $generated_count > 0 ) {
			$redirect_to = add_query_arg( 'dpls_bulk_generated', $generated_count, $redirect_to );
		}

		if ( $applied_count > 0 ) {
			$redirect_to = add_query_arg( 'dpls_bulk_applied', $applied_count, $redirect_to );
		}

		return $redirect_to;
	}

	/**
	 * Render bulk action completion admin notice.
	 *
	 * @return void
	 */
	public function render_bulk_notice(): void {
		if ( isset( $_GET['dpls_bulk_generated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$count = (int) $_GET['dpls_bulk_generated']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			?>
			<div class="notice notice-success is-dismissible">
				<p>
					<strong><?php esc_html_e( 'DPLS Schema AI:', 'dpls-schema-ai' ); ?></strong>
					<?php
					/* translators: %d: Number of posts processed */
					printf( esc_html( _n( 'Successfully analyzed content and generated Schema proposal for %d post.', 'Successfully analyzed content and generated Schema proposals for %d posts.', $count, 'dpls-schema-ai' ) ), $count );
					?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=dpls-schema-ai-pages-posts' ) ); ?>">
						<?php esc_html_e( 'Review all proposals in DPLS Hub &rarr;', 'dpls-schema-ai' ); ?>
					</a>
				</p>
			</div>
			<?php
		}

		if ( isset( $_GET['dpls_bulk_applied'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$count = (int) $_GET['dpls_bulk_applied']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			?>
			<div class="notice notice-success is-dismissible">
				<p>
					<strong><?php esc_html_e( 'DPLS Schema AI:', 'dpls-schema-ai' ); ?></strong>
					<?php
					/* translators: %d: Number of schemas applied */
					printf( esc_html( _n( 'Successfully applied and activated Schema for %d post.', 'Successfully applied and activated Schema for %d posts.', $count, 'dpls-schema-ai' ) ), $count );
					?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Append custom Schema status column to the post table.
	 *
	 * @param array<string, string> $columns Existing column headers.
	 * @return array<string, string>
	 */
	public function add_schema_column( array $columns ): array {
		$new_columns = array();

		foreach ( $columns as $key => $title ) {
			$new_columns[ $key ] = $title;
			if ( 'title' === $key ) {
				$new_columns['dpls_schema'] = __( 'Schema (DPLS)', 'dpls-schema-ai' );
			}
		}

		if ( ! isset( $new_columns['dpls_schema'] ) ) {
			$new_columns['dpls_schema'] = __( 'Schema (DPLS)', 'dpls-schema-ai' );
		}

		return $new_columns;
	}

	/**
	 * Render the content of the Schema status column.
	 *
	 * @param string $column Column name.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public function render_schema_column( string $column, int $post_id ): void {
		if ( 'dpls_schema' !== $column ) {
			return;
		}

		$status         = get_post_meta( $post_id, Constants::META_SCHEMA_STATUS, true );
		$suggested_type = get_post_meta( $post_id, Constants::META_SUGGESTED_SCHEMA, true );
		$active_schema  = get_post_meta( $post_id, Constants::META_ACTIVE_SCHEMA, true );
		$proposal       = get_post_meta( $post_id, Constants::META_SCHEMA_PROPOSAL, true );

		if ( empty( $status ) ) {
			$status = Constants::STATUS_NONE;
		}

		$type_label = ! empty( $suggested_type ) ? $suggested_type : 'WebPage';

		if ( ! empty( $active_schema ) && Constants::STATUS_APPROVED === $status ) {
			echo '<span class="dpls-badge dpls-badge-active" style="background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; padding:2px 8px; border-radius:12px; font-size:11px; font-weight:600; display:inline-flex; align-items:center; gap:4px;">';
			echo '<span class="dashicons dashicons-yes-alt" style="font-size:14px; width:14px; height:14px;"></span> ' . esc_html( $type_label );
			echo '</span>';
		} elseif ( ! empty( $proposal ) || Constants::STATUS_NEEDS_REVIEW === $status ) {
			echo '<span class="dpls-badge dpls-badge-review" style="background:#fffbeb; color:#92400e; border:1px solid #fde68a; padding:2px 8px; border-radius:12px; font-size:11px; font-weight:600; display:inline-flex; align-items:center; gap:4px;">';
			echo '<span class="dashicons dashicons-clock" style="font-size:14px; width:14px; height:14px;"></span> ' . esc_html( $type_label ) . ' ' . esc_html__( '(Proposal)', 'dpls-schema-ai' );
			echo '</span>';
		} elseif ( Constants::STATUS_DISABLED === $status ) {
			echo '<span class="dpls-badge dpls-badge-disabled" style="background:#f1f5f9; color:#64748b; border:1px solid #cbd5e1; padding:2px 8px; border-radius:12px; font-size:11px;">';
			echo esc_html__( 'Disabled', 'dpls-schema-ai' );
			echo '</span>';
		} else {
			echo '<span class="dpls-badge dpls-badge-none" style="color:#94a3b8; font-size:11px; font-style:italic;">';
			echo esc_html__( 'Not Generated', 'dpls-schema-ai' );
			echo '</span>';
		}
	}
}
