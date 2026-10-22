<?php
/**
 * WooCommerce Integration.
 *
 * Provides safe, robust integration with WooCommerce APIs to detect eligible
 * products, read genuine product attributes, and prevent conflicts with
 * WooCommerce's default core schema output.
 *
 * @package DPLS\SchemaAI\Integrations
 * @author  Dev Pattern Labs
 * @license GPL-2.0-or-later
 */

namespace DPLS\SchemaAI\Integrations;

use DPLS\SchemaAI\Common\Constants;
use DPLS\SchemaAI\Services\SettingsService;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles WooCommerce schema mapping and conflict prevention.
 */
class WooCommerceIntegration {

	/**
	 * Settings Service.
	 *
	 * @var SettingsService
	 */
	protected SettingsService $settings;

	/**
	 * Constructor.
	 *
	 * @param SettingsService $settings Settings service instance.
	 */
	public function __construct( SettingsService $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Check if WooCommerce is installed and active.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return class_exists( 'WooCommerce' ) || function_exists( 'WC' ) || function_exists( 'wc_get_product' );
	}

	/**
	 * Get detected WooCommerce version.
	 *
	 * @return string|null
	 */
	public function get_version(): ?string {
		if ( defined( 'WC_VERSION' ) ) {
			return WC_VERSION;
		}
		if ( function_exists( 'WC' ) && isset( WC()->version ) ) {
			return WC()->version;
		}
		return null;
	}

	/**
	 * Check if WooCommerce integration is enabled in plugin settings.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return (bool) $this->settings->get( 'woocommerce_enabled', true );
	}

	/**
	 * Check if core WooCommerce schema should be suppressed to avoid duplicate output.
	 *
	 * @return bool
	 */
	public function should_suppress_core_schema(): bool {
		return $this->is_enabled() && (bool) $this->settings->get( 'suppress_wc_core_schema', false );
	}

	/**
	 * Register runtime hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		if ( ! $this->is_available() || ! $this->is_enabled() ) {
			return;
		}

		if ( $this->should_suppress_core_schema() ) {
			// Suppress WooCommerce core default product structured data to eliminate duplication.
			add_filter( 'woocommerce_structured_data_product', '__return_empty_array', 20 );

			// Also remove WC structured data generator if hooked into wp_footer.
			add_action( 'wp_loaded', array( $this, 'remove_wc_footer_schema' ) );
		}
	}

	/**
	 * Remove WooCommerce structured data output from footer when configured.
	 *
	 * @return void
	 */
	public function remove_wc_footer_schema(): void {
		if ( function_exists( 'WC' ) && isset( WC()->structured_data ) ) {
			remove_action( 'wp_footer', array( WC()->structured_data, 'output_structured_data' ), 10 );
		}
	}

	/**
	 * Check if a given post is an eligible WooCommerce product.
	 *
	 * @param \WP_Post $post Post object.
	 * @return bool
	 */
	public function is_product( \WP_Post $post ): bool {
		return 'product' === $post->post_type;
	}

	/**
	 * Safely extract rich product data through WooCommerce APIs.
	 *
	 * Uses WC_Product methods when WooCommerce is active, and falls back
	 * gracefully to standard WordPress post meta when WooCommerce is unavailable.
	 *
	 * @param \WP_Post $post Post object.
	 * @return array
	 */
	public function extract_product_data( \WP_Post $post ): array {
		if ( ! $this->is_product( $post ) ) {
			return array();
		}

		// Fallback data when WC API is unavailable.
		$fallback_price  = get_post_meta( $post->ID, '_price', true );
		$fallback_reg    = get_post_meta( $post->ID, '_regular_price', true );
		$fallback_sale   = get_post_meta( $post->ID, '_sale_price', true );
		$fallback_sku    = get_post_meta( $post->ID, '_sku', true );
		$fallback_stock  = get_post_meta( $post->ID, '_stock_status', true );
		$fallback_manage = get_post_meta( $post->ID, '_manage_stock', true );

		$currency = function_exists( 'get_woocommerce_currency' )
			? get_woocommerce_currency()
			: 'USD';

		if ( ! $this->is_available() || ! function_exists( 'wc_get_product' ) ) {
			return array(
				'is_wc_active'       => false,
				'name'               => $post->post_title,
				'description'        => $post->post_excerpt ?: wp_strip_all_tags( $post->post_content ),
				'sku'                => (string) $fallback_sku,
				'price'              => is_numeric( $fallback_price ) ? (string) $fallback_price : '',
				'regular_price'      => is_numeric( $fallback_reg ) ? (string) $fallback_reg : '',
				'sale_price'         => is_numeric( $fallback_sale ) ? (string) $fallback_sale : '',
				'currency'           => $currency,
				'stock_status'       => $fallback_stock ?: 'instock',
				'availability'       => 'outofstock' === $fallback_stock ? 'https://schema.org/OutOfStock' : 'https://schema.org/InStock',
				'catalog_visibility' => 'visible',
				'rating_count'       => 0,
				'average_rating'     => 0.0,
				'review_count'       => 0,
				'gallery_image_urls' => array(),
				'price_valid_until'  => null,
			);
		}

		$product = wc_get_product( $post->ID );
		if ( ! $product ) {
			return array();
		}

		// Read genuine product data through WooCommerce official methods.
		$sku          = (string) $product->get_sku();
		$price        = (string) $product->get_price();
		$reg_price    = (string) $product->get_regular_price();
		$sale_price   = (string) $product->get_sale_price();
		$stock_status = (string) $product->get_stock_status();
		$visibility   = (string) $product->get_catalog_visibility();

		// Availability mapping.
		$availability_url = match ( $stock_status ) {
			'outofstock'   => 'https://schema.org/OutOfStock',
			'onbackorder'  => 'https://schema.org/BackOrder',
			default        => 'https://schema.org/InStock',
		};

		// Sale end date if on sale.
		$date_on_sale_to = $product->get_date_on_sale_to();
		$price_valid_to  = null;
		if ( $date_on_sale_to instanceof \WC_DateTime ) {
			$price_valid_to = $date_on_sale_to->date( 'Y-m-d' );
		}

		// Ratings and reviews: strictly real data, NEVER invented.
		$avg_rating   = (float) $product->get_average_rating();
		$review_count = (int) $product->get_review_count();
		$rating_count = (int) $product->get_rating_count();

		// Gallery image URLs.
		$gallery_ids  = $product->get_gallery_image_ids();
		$gallery_urls = array();
		if ( is_array( $gallery_ids ) ) {
			foreach ( $gallery_ids as $gid ) {
				$url = wp_get_attachment_image_url( (int) $gid, 'full' );
				if ( ! empty( $url ) ) {
					$gallery_urls[] = esc_url_raw( $url );
				}
			}
		}

		return array(
			'is_wc_active'       => true,
			'name'               => $product->get_name(),
			'description'        => $product->get_short_description() ?: wp_strip_all_tags( $product->get_description() ),
			'sku'                => $sku,
			'price'              => is_numeric( $price ) ? $price : '',
			'regular_price'      => is_numeric( $reg_price ) ? $reg_price : '',
			'sale_price'         => is_numeric( $sale_price ) ? $sale_price : '',
			'currency'           => $currency,
			'stock_status'       => $stock_status,
			'availability'       => $availability_url,
			'catalog_visibility' => $visibility,
			'rating_count'       => $rating_count,
			'average_rating'     => $avg_rating,
			'review_count'       => $review_count,
			'gallery_image_urls' => $gallery_urls,
			'price_valid_until'  => $price_valid_to,
		);
	}
}
