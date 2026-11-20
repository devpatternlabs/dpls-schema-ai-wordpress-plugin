<?php
/**
 * Third-Party SEO Plugin Conflict Detector.
 *
 * Detects active SEO plugins (Yoast SEO, Rank Math, AIOSEO, SEOPress)
 * that output competing Schema.org markup, displays proactive guidance,
 * and provides one-click suppression to prevent Google duplicate schema warnings.
 *
 * @package DPLS\SchemaAI\Integrations
 * @author  Dev Pattern Labs
 * @license GPL-2.0-or-later
 */

namespace DPLS\SchemaAI\Integrations;

use DPLS\SchemaAI\Common\Constants;
use DPLS\SchemaAI\Services\SettingsService;

// Prevent direct script access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SeoConflictDetector.
 */
class SeoConflictDetector {

	/**
	 * Settings Service.
	 *
	 * @var SettingsService
	 */
	protected SettingsService $settings;

	/**
	 * Dismissed transient prefix.
	 */
	protected const DISMISS_TRANSIENT_KEY = 'dpls_dismiss_seo_conflict_';

	/**
	 * Constructor.
	 *
	 * @param SettingsService $settings Settings service instance.
	 */
	public function __construct( SettingsService $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		if ( is_admin() ) {
			add_action( 'admin_notices', array( $this, 'render_admin_notice' ) );
			add_action( 'wp_ajax_dpls_dismiss_seo_conflict', array( $this, 'ajax_dismiss_notice' ) );
			add_action( 'wp_ajax_dpls_toggle_seo_suppression', array( $this, 'ajax_toggle_suppression' ) );
		}

		// Apply suppression filters if enabled by administrator.
		if ( $this->is_suppression_enabled() ) {
			$this->apply_suppression_filters();
		}
	}

	/**
	 * Determine if third-party SEO schema suppression is enabled.
	 *
	 * @return bool
	 */
	public function is_suppression_enabled(): bool {
		return (bool) $this->settings->get( 'suppress_third_party_seo_schema', false );
	}

	/**
	 * Detect active conflicting SEO plugins.
	 *
	 * @return array<string, array<string, string>> Array of detected plugins.
	 */
	public function get_detected_plugins(): array {
		$detected = array();

		// 1. Yoast SEO
		if ( defined( 'WPSEO_VERSION' ) || class_exists( 'WPSEO_Options', false ) ) {
			$version = defined( 'WPSEO_VERSION' ) ? constant( 'WPSEO_VERSION' ) : '';
			$detected['yoast'] = array(
				'name'    => 'Yoast SEO',
				'version' => $version,
				'slug'    => 'wordpress-seo',
			);
		}

		// 2. Rank Math
		if ( defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath', false ) ) {
			$version = defined( 'RANK_MATH_VERSION' ) ? constant( 'RANK_MATH_VERSION' ) : '';
			$detected['rank_math'] = array(
				'name'    => 'Rank Math SEO',
				'version' => $version,
				'slug'    => 'seo-by-rank-math',
			);
		}

		// 3. All in One SEO (AIOSEO)
		if ( defined( 'AIOSEO_VERSION' ) || class_exists( 'AIOSEO\\Plugin\\AIOSEO', false ) ) {
			$version = defined( 'AIOSEO_VERSION' ) ? constant( 'AIOSEO_VERSION' ) : '';
			$detected['aioseo'] = array(
				'name'    => 'All in One SEO',
				'version' => $version,
				'slug'    => 'all-in-one-seo-pack',
			);
		}

		// 4. SEOPress
		if ( defined( 'SEOPRESS_VERSION' ) || function_exists( 'seopress_init' ) ) {
			$version = defined( 'SEOPRESS_VERSION' ) ? constant( 'SEOPRESS_VERSION' ) : '';
			$detected['seopress'] = array(
				'name'    => 'SEOPress',
				'version' => $version,
				'slug'    => 'wp-seopress',
			);
		}

		// 5. Schema & Structured Data for WP
		if ( defined( 'SASWP_VERSION' ) || class_exists( 'SASWP', false ) ) {
			$version = defined( 'SASWP_VERSION' ) ? constant( 'SASWP_VERSION' ) : '';
			$detected['saswp'] = array(
				'name'    => 'Schema & Structured Data for WP',
				'version' => $version,
				'slug'    => 'schema-and-structured-data-for-wp',
			);
		}

		return $detected;
	}

	/**
	 * Apply suppression filters to prevent duplicate frontend schema output.
	 *
	 * @return void
	 */
	public function apply_suppression_filters(): void {
		// Yoast SEO Schema JSON-LD suppression.
		add_filter( 'wpseo_json_ld_output', '__return_empty_array', 99 );

		// Rank Math Schema JSON-LD suppression.
		add_filter( 'rank_math/json_ld', '__return_empty_array', 99 );

		// All in One SEO Schema output suppression.
		add_filter( 'aioseo_schema_output', '__return_empty_array', 99 );

		// SEOPress Schema output suppression.
		add_filter( 'seopress_schemas_enable', '__return_false', 99 );
	}

	/**
	 * Render the administrative warning banner if conflicting SEO plugins are detected.
	 *
	 * @return void
	 */
	public function render_admin_notice(): void {
		if ( ! current_user_can( Constants::REQUIRED_CAPABILITY ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen ) {
			return;
		}

		// Only display on DPLS Schema AI screens or Post edit screens.
		$is_dpls_screen = ( false !== strpos( $screen->id, 'dpls-schema-ai' ) );
		$is_edit_screen = ( 'post' === $screen->base || 'edit' === $screen->base );

		if ( ! $is_dpls_screen && ! $is_edit_screen ) {
			return;
		}

		$detected = $this->get_detected_plugins();
		if ( empty( $detected ) ) {
			return;
		}

		$current_user_id = get_current_user_id();
		$dismissed       = get_user_meta( $current_user_id, self::DISMISS_TRANSIENT_KEY . 'banner', true );

		if ( $dismissed && ! $is_dpls_screen ) {
			return;
		}

		$names              = wp_list_pluck( $detected, 'name' );
		$names_list         = implode( ', ', $names );
		$suppression_active = $this->is_suppression_enabled();
		?>
		<div class="notice notice-warning is-dismissible dpls-seo-conflict-notice" style="border-left-color: #f59e0b; padding: 12px 16px;">
			<div style="display:flex; align-items:flex-start; gap:12px;">
				<span class="dashicons dashicons-shield" style="font-size:24px; width:24px; height:24px; color:#f59e0b; margin-top:2px;"></span>
				<div style="flex:1;">
					<h4 style="margin:0 0 6px; font-size:14px; color:#1e293b;">
						<?php
						/* translators: %s: Comma-separated list of active SEO plugin names */
						printf( esc_html__( 'DPLS Schema AI — SEO Plugin Compatibility Notice (%s)', 'dpls-schema-ai' ), esc_html( $names_list ) );
						?>
					</h4>
					<p style="margin:0 0 10px; color:#475569; font-size:13px; line-height:1.5;">
						<?php
						/* translators: %s: Comma-separated list of active SEO plugin names */
						printf( esc_html__( 'We detected %s active on your website. When multiple plugins output Schema.org JSON-LD simultaneously, search engines like Google may flag duplicate or conflicting entity definitions.', 'dpls-schema-ai' ), '<strong>' . esc_html( $names_list ) . '</strong>' );
						?>
					</p>

					<div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
						<?php if ( ! $suppression_active ) : ?>
							<button type="button" class="button button-primary dpls-btn-enable-seo-suppression" data-nonce="<?php echo esc_attr( wp_create_nonce( Constants::NONCE_ACTION_ADMIN ) ); ?>">
								<span class="dashicons dashicons-admin-generic" style="vertical-align:middle; margin-top:-2px;"></span>
								<?php esc_html_e( 'Auto-Suppress Third-Party Schema (Recommended)', 'dpls-schema-ai' ); ?>
							</button>
						<?php else : ?>
							<span style="display:inline-flex; align-items:center; gap:4px; color:#059669; font-weight:600; font-size:13px;">
								<span class="dashicons dashicons-yes-alt"></span>
								<?php esc_html_e( 'Third-party duplicate schema suppression is ACTIVE.', 'dpls-schema-ai' ); ?>
							</span>
						<?php endif; ?>

						<a href="<?php echo esc_url( admin_url( 'admin.php?page=dpls-schema-ai-integrations' ) ); ?>" class="button button-secondary">
							<?php esc_html_e( 'Manage Integration Settings', 'dpls-schema-ai' ); ?>
						</a>

						<button type="button" class="button button-link dpls-btn-dismiss-conflict" style="color:#64748b; margin-left:8px;" data-nonce="<?php echo esc_attr( wp_create_nonce( Constants::NONCE_ACTION_ADMIN ) ); ?>">
							<?php esc_html_e( 'Dismiss this notice', 'dpls-schema-ai' ); ?>
						</button>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX handler for dismissing the conflict notice.
	 *
	 * @return void
	 */
	public function ajax_dismiss_notice(): void {
		check_ajax_referer( Constants::NONCE_ACTION_ADMIN, 'nonce' );

		if ( ! current_user_can( Constants::REQUIRED_CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'dpls-schema-ai' ) ) );
		}

		$user_id = get_current_user_id();
		update_user_meta( $user_id, self::DISMISS_TRANSIENT_KEY . 'banner', 1 );

		wp_send_json_success( array( 'message' => __( 'Notice dismissed.', 'dpls-schema-ai' ) ) );
	}

	/**
	 * AJAX handler for toggling third-party SEO schema suppression.
	 *
	 * @return void
	 */
	public function ajax_toggle_suppression(): void {
		check_ajax_referer( Constants::NONCE_ACTION_ADMIN, 'nonce' );

		if ( ! current_user_can( Constants::REQUIRED_CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'dpls-schema-ai' ) ) );
		}

		$this->settings->set( 'suppress_third_party_seo_schema', true );

		wp_send_json_success(
			array(
				'message' => __( 'Third-party SEO schema suppression activated successfully.', 'dpls-schema-ai' ),
			)
		);
	}
}
