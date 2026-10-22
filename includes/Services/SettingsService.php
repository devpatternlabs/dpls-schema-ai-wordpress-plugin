<?php
/**
 * Settings Service.
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
 * Manages plugin settings and configuration.
 */
class SettingsService implements ServiceInterface {

	/**
	 * Default settings.
	 *
	 * @var array
	 */
	protected array $defaults = array(
		'enabled_post_types'           => array( 'post', 'page' ),
		'default_schema_type'          => 'WebPage',
		'auto_update'                  => true,
		'automation_policy'            => Constants::POLICY_SAFE,
		'auto_scan_interval'           => 'weekly',
		'default_publisher'            => '',
		'default_logo_url'             => '',
		'output_breadcrumbs'           => true,
		'output_minify_json'           => false,
		'woocommerce_enabled'          => true,
		'suppress_wc_core_schema'      => false,
		'elementor_enabled'            => true,
		'history_retention_days'       => 30,
		'history_max_versions'         => 10,
		'debug_logging'                => false,
		'preserve_data'                => true,
		'delete_all_data_on_uninstall' => false,
		'ollama_enabled'               => false,
		'ollama_endpoint'              => 'http://localhost:11434',
		'ollama_model'                 => 'llama3:latest',
		'ai_consent'                   => false,
		'organization'                 => array(
			'name'         => '',
			'url'          => '',
			'logo_url'     => '',
			'type'         => 'Organization',
			'email'        => '',
			'phone'        => '',
			'contact_type' => 'customer support',
			'street'       => '',
			'city'         => '',
			'region'       => '',
			'postal_code'  => '',
			'country'      => '',
			'same_as'      => array(),
		),
	);

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register settings with WordPress Settings API.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			Constants::OPTION_SETTINGS,
			Constants::OPTION_SETTINGS,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => $this->defaults,
			)
		);
	}

	/**
	 * Get all settings merged with defaults.
	 *
	 * @return array
	 */
	public function get_all(): array {
		$saved = get_option( Constants::OPTION_SETTINGS, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		$merged = wp_parse_args( $saved, $this->defaults );
		if ( isset( $saved['organization'] ) && is_array( $saved['organization'] ) ) {
			$merged['organization'] = wp_parse_args( $saved['organization'], $this->defaults['organization'] );
		}

		return $merged;
	}

	/**
	 * Get a single setting by key.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $default Fallback value.
	 * @return mixed
	 */
	public function get( string $key, $default = null ) {
		$all = $this->get_all();
		return $all[ $key ] ?? $default;
	}

	/**
	 * Retrieve verified organization data.
	 *
	 * @return array
	 */
	public function get_organization_data(): array {
		$all = $this->get_all();
		$org = $all['organization'] ?? array();

		$site_name = get_bloginfo( 'name' );
		$site_url  = function_exists( 'home_url' ) ? home_url() : 'https://example.com';

		return array(
			'name'         => ! empty( $org['name'] ) ? $org['name'] : ( ! empty( $all['default_publisher'] ) ? $all['default_publisher'] : $site_name ),
			'url'          => ! empty( $org['url'] ) ? $org['url'] : $site_url,
			'logo_url'     => ! empty( $org['logo_url'] ) ? $org['logo_url'] : ( $all['default_logo_url'] ?? '' ),
			'type'         => ! empty( $org['type'] ) ? $org['type'] : 'Organization',
			'email'        => ! empty( $org['email'] ) ? $org['email'] : get_option( 'admin_email', '' ),
			'phone'        => ! empty( $org['phone'] ) ? $org['phone'] : '',
			'contact_type' => ! empty( $org['contact_type'] ) ? $org['contact_type'] : 'customer support',
			'street'       => $org['street'] ?? '',
			'city'         => $org['city'] ?? '',
			'region'       => $org['region'] ?? '',
			'postal_code'  => $org['postal_code'] ?? '',
			'country'      => $org['country'] ?? '',
			'same_as'      => is_array( $org['same_as'] ?? null ) ? $org['same_as'] : array(),
		);
	}

	/**
	 * Update settings.
	 *
	 * @param array $settings New settings array.
	 * @return bool
	 */
	public function update( array $settings ): bool {
		if ( ! current_user_can( Constants::REQUIRED_CAPABILITY ) ) {
			return false;
		}

		// Merge with existing settings so partial updates preserve unmentioned keys.
		$existing  = $this->get_all();
		$merged    = array_merge( $existing, $settings );
		if ( isset( $settings['organization'] ) && is_array( $settings['organization'] ) ) {
			$merged['organization'] = array_merge( $existing['organization'], $settings['organization'] );
		}

		$sanitized = $this->sanitize_settings( $merged );
		return update_option( Constants::OPTION_SETTINGS, $sanitized );
	}

	/**
	 * Sanitize settings array.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public function sanitize_settings( $input ): array {
		if ( ! is_array( $input ) ) {
			return $this->defaults;
		}

		$output = array();

		// Post types.
		$output['enabled_post_types'] = array();
		if ( isset( $input['enabled_post_types'] ) && is_array( $input['enabled_post_types'] ) ) {
			foreach ( $input['enabled_post_types'] as $pt ) {
				$output['enabled_post_types'][] = sanitize_key( $pt );
			}
		}
		if ( empty( $output['enabled_post_types'] ) ) {
			$output['enabled_post_types'] = array( 'post', 'page' );
		}

		// Booleans & Options.
		$output['auto_update']                = ! empty( $input['auto_update'] );
		$output['output_breadcrumbs']         = ! empty( $input['output_breadcrumbs'] );
		$output['output_minify_json']         = ! empty( $input['output_minify_json'] );
		$output['woocommerce_enabled']        = ! empty( $input['woocommerce_enabled'] );
		$output['suppress_wc_core_schema']    = ! empty( $input['suppress_wc_core_schema'] );
		$output['elementor_enabled']          = ! empty( $input['elementor_enabled'] );
		$output['ollama_enabled']             = ! empty( $input['ollama_enabled'] );
		$output['ai_consent']                 = ! empty( $input['ai_consent'] );
		$output['debug_logging']              = ! empty( $input['debug_logging'] );

		// Uninstall & Data Preservation Strategy.
		$delete_on_uninstall                  = ! empty( $input['delete_all_data_on_uninstall'] );
		$output['delete_all_data_on_uninstall'] = $delete_on_uninstall;
		$output['preserve_data']              = ! $delete_on_uninstall && ( ! isset( $input['preserve_data'] ) || ! empty( $input['preserve_data'] ) );

		// Sync standalone preservation option.
		update_option( Constants::OPTION_PRESERVE_DATA, $output['preserve_data'] ? 1 : 0 );

		// Automation policy.
		$policy = sanitize_key( $input['automation_policy'] ?? Constants::POLICY_SAFE );
		if ( ! in_array( $policy, array( Constants::POLICY_OFF, Constants::POLICY_SAFE, Constants::POLICY_REVIEW ), true ) ) {
			$policy = Constants::POLICY_SAFE;
		}
		$output['automation_policy'] = $policy;

		// Integers.
		$output['history_retention_days'] = max( 1, (int) ( $input['history_retention_days'] ?? 30 ) );
		$output['history_max_versions']   = max( 1, min( 50, (int) ( $input['history_max_versions'] ?? 10 ) ) );

		// Strings.
		$output['default_schema_type'] = sanitize_text_field( $input['default_schema_type'] ?? 'WebPage' );
		$output['auto_scan_interval']  = sanitize_text_field( $input['auto_scan_interval'] ?? 'weekly' );
		$output['default_publisher']   = sanitize_text_field( $input['default_publisher'] ?? get_bloginfo( 'name' ) );
		$output['default_logo_url']    = esc_url_raw( $input['default_logo_url'] ?? '' );
		$output['ollama_endpoint']     = esc_url_raw( $input['ollama_endpoint'] ?? 'http://localhost:11434' );
		$output['ollama_model']        = sanitize_text_field( $input['ollama_model'] ?? 'llama3:latest' );

		// Organization details.
		$org_in = isset( $input['organization'] ) && is_array( $input['organization'] ) ? $input['organization'] : array();
		$same_as_raw = $org_in['same_as'] ?? array();
		if ( is_string( $same_as_raw ) ) {
			$same_as_raw = array_filter( array_map( 'trim', explode( "\n", $same_as_raw ) ) );
		}
		$same_as_clean = array();
		if ( is_array( $same_as_raw ) ) {
			foreach ( $same_as_raw as $s_url ) {
				$clean_url = esc_url_raw( $s_url );
				if ( ! empty( $clean_url ) ) {
					$same_as_clean[] = $clean_url;
				}
			}
		}

		$output['organization'] = array(
			'name'         => sanitize_text_field( $org_in['name'] ?? $output['default_publisher'] ),
			'url'          => esc_url_raw( $org_in['url'] ?? ( function_exists( 'home_url' ) ? home_url() : '' ) ),
			'logo_url'     => esc_url_raw( $org_in['logo_url'] ?? $output['default_logo_url'] ),
			'type'         => sanitize_text_field( $org_in['type'] ?? 'Organization' ),
			'email'        => sanitize_email( $org_in['email'] ?? '' ),
			'phone'        => sanitize_text_field( $org_in['phone'] ?? '' ),
			'contact_type' => sanitize_text_field( $org_in['contact_type'] ?? 'customer support' ),
			'street'       => sanitize_text_field( $org_in['street'] ?? '' ),
			'city'         => sanitize_text_field( $org_in['city'] ?? '' ),
			'region'       => sanitize_text_field( $org_in['region'] ?? '' ),
			'postal_code'  => sanitize_text_field( $org_in['postal_code'] ?? '' ),
			'country'      => sanitize_text_field( $org_in['country'] ?? '' ),
			'same_as'      => $same_as_clean,
		);

		return $output;
	}

	/**
	 * Check if a post type is enabled for schema processing.
	 *
	 * @param string $post_type Post type name.
	 * @return bool
	 */
	public function is_post_type_enabled( string $post_type ): bool {
		$enabled = $this->get( 'enabled_post_types', array( 'post', 'page' ) );
		return in_array( $post_type, (array) $enabled, true );
	}
}
