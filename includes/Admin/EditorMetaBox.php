<?php
/**
 * Editor Meta Box Integration.
 *
 * @package DPLS\SchemaAI\Admin
 * @author  Dev Pattern Labs
 * @license GPL-2.0-or-later
 */

namespace DPLS\SchemaAI\Admin;

use DPLS\SchemaAI\Common\Constants;
use DPLS\SchemaAI\Common\Utils;
use DPLS\SchemaAI\Generator\SchemaRegistry;
use DPLS\SchemaAI\Services\GeneratorService;
use DPLS\SchemaAI\Services\ScannerService;
use DPLS\SchemaAI\Services\SettingsService;
use DPLS\SchemaAI\Services\ValidatorService;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds an interactive Schema management panel to the WordPress post editor.
 */
class EditorMetaBox {

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
	public function __construct( SettingsService $settings, ScannerService $scanner, GeneratorService $generator, ValidatorService $validator ) {
		$this->settings  = $settings;
		$this->scanner   = $scanner;
		$this->generator = $generator;
		$this->validator = $validator;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_meta_box_data' ), 15, 2 );
	}

	/**
	 * Register meta box on all configured post types.
	 *
	 * @return void
	 */
	public function add_meta_box(): void {
		$enabled_types = $this->settings->get( 'enabled_post_types', array( 'post', 'page' ) );

		foreach ( $enabled_types as $pt ) {
			add_meta_box(
				'dpls_schema_ai_box',
				__( 'DPL Schema AI &mdash; Structured Data', 'dpls-schema-ai' ),
				array( $this, 'render_meta_box' ),
				$pt,
				'normal',
				'high'
			);
		}
	}

	/**
	 * Render the editor meta box view.
	 *
	 * @param \WP_Post $post Current post.
	 * @return void
	 */
	public function render_meta_box( \WP_Post $post ): void {
		wp_nonce_field( Constants::NONCE_ACTION_ADMIN, 'dpls_metabox_nonce' );

		$post_id          = $post->ID;
		$status           = get_post_meta( $post_id, Constants::META_SCHEMA_STATUS, true );
		$suggested_type   = get_post_meta( $post_id, Constants::META_SUGGESTED_SCHEMA, true );
		$active_schema    = get_post_meta( $post_id, Constants::META_ACTIVE_SCHEMA, true );
		$draft_schema     = get_post_meta( $post_id, Constants::META_DRAFT_SCHEMA, true );
		$applied_time     = get_post_meta( $post_id, Constants::META_APPLIED_TIME, true );
		$version          = (int) get_post_meta( $post_id, Constants::META_SCHEMA_VERSION, true );
		$proposal         = get_post_meta( $post_id, Constants::META_SCHEMA_PROPOSAL, true );

		if ( empty( $status ) ) {
			$status = Constants::STATUS_NONE;
		}

		$current_schema_code = ! empty( $active_schema ) ? $active_schema : $draft_schema;
		$all_types           = SchemaRegistry::get_all();
		?>
		<div class="dpls-metabox-container" data-post-id="<?php echo esc_attr( $post_id ); ?>">
			<div class="dpls-metabox-row" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; padding-bottom:12px; border-bottom:1px solid #dcdcde;">
				<div class="dpls-metabox-status">
					<strong><?php esc_html_e( 'Schema Status:', 'dpls-schema-ai' ); ?></strong>
					<span id="dpls-mb-status-badge"><?php echo Utils::get_status_badge( $status ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<?php if ( $version > 0 ) : ?>
						<?php
						/* translators: %d: Schema version number */
						$dpls_version_str = sprintf( __( 'v%d', 'dpls-schema-ai' ), $version );
						?>
						<span class="dpls-code-type" style="margin-left:8px;"><?php echo esc_html( $dpls_version_str ); ?></span>
					<?php endif; ?>
				</div>

				<div class="dpls-metabox-quick-actions" style="display:flex; gap:8px;">
					<button type="button" class="button button-small dpls-action-analyze" data-post-id="<?php echo esc_attr( $post_id ); ?>">
						<span class="dashicons dashicons-search"></span> <?php esc_html_e( 'Analyze Content', 'dpls-schema-ai' ); ?>
					</button>
					<button type="button" class="button button-small dpls-action-generate" data-post-id="<?php echo esc_attr( $post_id ); ?>">
						<span class="dashicons dashicons-admin-generic"></span> <?php esc_html_e( 'Generate Proposal', 'dpls-schema-ai' ); ?>
					</button>
					<button type="button" class="button button-small dpls-action-validate" data-post-id="<?php echo esc_attr( $post_id ); ?>" <?php disabled( empty( $current_schema_code ) ); ?>>
						<span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Validate', 'dpls-schema-ai' ); ?>
					</button>
				</div>
			</div>

			<?php if ( is_array( $proposal ) && ! empty( $proposal['missing_recommended'] ) ) : ?>
				<div class="notice notice-warning inline" style="margin:0 0 15px 0;">
					<p><strong><?php esc_html_e( 'Recommendations:', 'dpls-schema-ai' ); ?></strong></p>
					<ul style="margin:4px 0 8px 18px; list-style:disc;">
						<?php foreach ( $proposal['missing_recommended'] as $rec ) : ?>
							<li><?php echo esc_html( $rec ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<div class="dpls-metabox-controls" style="margin-bottom:12px; display:flex; gap:15px; align-items:center;">
				<label for="dpls-mb-schema-type"><strong><?php esc_html_e( 'Assigned Schema Type:', 'dpls-schema-ai' ); ?></strong></label>
				<select name="dpls_assigned_schema_type" id="dpls-mb-schema-type" style="min-width:200px;">
					<?php foreach ( $all_types as $type_key => $type_info ) : ?>
						<option value="<?php echo esc_attr( $type_key ); ?>" <?php selected( $suggested_type, $type_key ); ?>>
							<?php echo esc_html( $type_info['label'] ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="dpls-metabox-editor-wrap">
				<label for="dpls-mb-schema-code"><strong><?php esc_html_e( 'Schema.org JSON-LD Markup:', 'dpls-schema-ai' ); ?></strong></label>
				<p class="description" style="margin:2px 0 6px;">
					<?php esc_html_e( 'Edit or inspect the structured data payload below. Click Apply to activate on your live site.', 'dpls-schema-ai' ); ?>
				</p>
				<textarea name="dpls_editor_schema_code" id="dpls-mb-schema-code" rows="10" class="large-text code" style="font-family:monospace; background:#1e1e1e; color:#d4d4d4; padding:10px;" placeholder="<?php esc_attr_e( 'No schema generated yet. Click Generate Proposal to build schema.', 'dpls-schema-ai' ); ?>"><?php echo esc_textarea( $current_schema_code ); ?></textarea>
			</div>

			<div class="dpls-metabox-footer" style="margin-top:12px; display:flex; justify-content:space-between; align-items:center;">
				<div class="dpls-metabox-timestamps" style="font-size:12px; color:#646970;">
					<?php if ( $applied_time ) : ?>
						<?php
						/* translators: %s: Formatted date string */
						$dpls_applied_str = sprintf( __( 'Applied: %s', 'dpls-schema-ai' ), Utils::format_date( $applied_time ) );
						?>
						<span><?php echo esc_html( $dpls_applied_str ); ?></span>
					<?php endif; ?>
				</div>

				<div class="dpls-metabox-actions" style="display:flex; gap:8px;">
					<button type="button" class="button dpls-action-disable" data-post-id="<?php echo esc_attr( $post_id ); ?>" <?php disabled( Constants::STATUS_DISABLED === $status || Constants::STATUS_NONE === $status ); ?>>
						<?php esc_html_e( 'Disable Schema', 'dpls-schema-ai' ); ?>
					</button>
					<button type="button" class="button button-primary dpls-action-apply" data-post-id="<?php echo esc_attr( $post_id ); ?>">
						<span class="dashicons dashicons-saved"></span> <?php esc_html_e( 'Apply Schema', 'dpls-schema-ai' ); ?>
					</button>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Save meta box edits upon standard post save.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post Post object.
	 * @return void
	 */
	public function save_meta_box_data( int $post_id, \WP_Post $post ): void {
		// Nonce check.
		if ( ! isset( $_POST['dpls_metabox_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dpls_metabox_nonce'] ) ), Constants::NONCE_ACTION_ADMIN ) ) {
			return;
		}

		// Capability check.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Check schema type change.
		if ( isset( $_POST['dpls_assigned_schema_type'] ) ) {
			$new_type = sanitize_text_field( wp_unslash( $_POST['dpls_assigned_schema_type'] ) );
			if ( SchemaRegistry::is_supported( $new_type ) ) {
				update_post_meta( $post_id, Constants::META_SUGGESTED_SCHEMA, $new_type );
			}
		}

		// Check manual schema code save.
		if ( isset( $_POST['dpls_editor_schema_code'] ) ) {
			$raw_code = trim( wp_unslash( $_POST['dpls_editor_schema_code'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( ! empty( $raw_code ) ) {
				$val = $this->validator->validate( $raw_code );
				if ( $val['is_valid'] ) {
					update_post_meta( $post_id, Constants::META_ACTIVE_SCHEMA, Utils::encode_schema( $raw_code ) );
				} else {
					update_post_meta( $post_id, Constants::META_DRAFT_SCHEMA, $raw_code );
				}
			}
		}
	}
}
