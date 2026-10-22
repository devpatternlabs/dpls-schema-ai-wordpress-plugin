<?php
/**
 * Rule-Based Schema Generator Service.
 *
 * @package DPLS\SchemaAI\Services
 * @author  Dev Pattern Labs
 * @license GPL-2.0-or-later
 */

namespace DPLS\SchemaAI\Services;

use DPLS\SchemaAI\AI\AIService;
use DPLS\SchemaAI\Common\Constants;
use DPLS\SchemaAI\Common\Utils;
use DPLS\SchemaAI\Generator\ContentClassifier;
use DPLS\SchemaAI\Generator\SchemaRegistry;
use DPLS\SchemaAI\Interfaces\GeneratorInterface;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates Schema.org JSON-LD structured data from normalized WordPress content.
 * Completely autonomous, rule-based, and requiring no external paid APIs.
 */
class GeneratorService implements GeneratorInterface {

	/**
	 * Settings Service.
	 *
	 * @var SettingsService
	 */
	protected SettingsService $settings;

	/**
	 * History Service.
	 *
	 * @var HistoryService
	 */
	protected HistoryService $history;

	/**
	 * Content Classifier.
	 *
	 * @var ContentClassifier
	 */
	protected ContentClassifier $classifier;

	/**
	 * Scanner Service.
	 *
	 * @var ScannerService|null
	 */
	protected ?ScannerService $scanner;

	/**
	 * AI Service Coordinator.
	 *
	 * @var AIService|null
	 */
	protected ?AIService $ai;

	/**
	 * Constructor.
	 *
	 * @param SettingsService        $settings   Settings service.
	 * @param HistoryService         $history    History service.
	 * @param ContentClassifier|null $classifier Classifier instance.
	 * @param ScannerService|null    $scanner    Scanner service.
	 * @param AIService|null         $ai         Optional AI service coordinator.
	 */
	public function __construct(
		SettingsService $settings,
		HistoryService $history,
		?ContentClassifier $classifier = null,
		?ScannerService $scanner = null,
		?AIService $ai = null
	) {
		$this->settings   = $settings;
		$this->history    = $history;
		$this->classifier = $classifier ?? new ContentClassifier();
		$this->scanner    = $scanner;
		$this->ai         = $ai;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		// Nothing hooked globally.
	}

	/**
	 * Generate a structured schema proposal without automatically publishing it.
	 *
	 * @param int         $post_id Post ID.
	 * @param string|null $schema_type Optional override type.
	 * @return array Structured proposal.
	 */
	public function generate_proposal( int $post_id, ?string $schema_type = null ): array {
		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post ) {
			return array(
				'success' => false,
				'message' => __( 'Post not found.', 'dpls-schema-ai' ),
			);
		}

		// Retrieve or extract normalized content record.
		$record = get_post_meta( $post_id, Constants::META_NORMALIZED_CONTENT, true );
		if ( ! is_array( $record ) || empty( $record ) ) {
			$scanner = $this->scanner ?? ( function_exists( '\\dpls_schema_ai' ) && isset( \dpls_schema_ai()->scanner ) ? \dpls_schema_ai()->scanner : new ScannerService( $this->settings, $this->history ) );
			$record  = $scanner->extract_structured_content( $post );
		}

		if ( ! is_array( $record ) ) {
			return array(
				'success' => false,
				'message' => __( 'Unable to process content. Ensure post is published and enabled.', 'dpls-schema-ai' ),
			);
		}

		// Determine target type.
		$source_engine = 'rule_based';
		$ai_analysis   = null;

		if ( empty( $schema_type ) ) {
			$classification = $this->classifier->classify( $record );
			$target_type    = $classification['type'];
			$confidence     = $classification['confidence'] ?? 0.8;
			$reasons        = $classification['reasons'] ?? array();
			$review_req     = $classification['review_required'] ?? false;

			// If AI is enabled and consented, run supplementary AI analysis.
			if ( $this->ai instanceof AIService && $this->ai->is_enabled() ) {
				$ai_response = $this->ai->analyze_post( $post_id, $record );
				if ( ! empty( $ai_response['success'] ) ) {
					$ai_analysis   = $ai_response;
					$source_engine = 'ai_assisted';
					// Favor specific schema type from AI if confidence is sufficient.
					if ( ! empty( $ai_response['suggested_type'] ) && ( $ai_response['confidence'] >= 0.70 ) ) {
						$target_type = $ai_response['suggested_type'];
						$confidence  = $ai_response['confidence'];
					}
					if ( ! empty( $ai_response['reasons'] ) ) {
						$reasons = array_merge( $ai_response['reasons'], $reasons );
					}
					if ( ! empty( $ai_response['review_required'] ) ) {
						$review_req = true;
					}
				}
			}
		} else {
			$target_type = sanitize_text_field( $schema_type );
			if ( ! SchemaRegistry::is_supported( $target_type ) ) {
				$target_type = ( 'page' === $post->post_type ) ? 'WebPage' : 'BlogPosting';
			}
			$confidence = 1.0;
			$reasons    = array( __( 'Administrator explicitly selected this schema type.', 'dpls-schema-ai' ) );
			$review_req = false;
		}

		// Check for locked properties.
		$locked = get_post_meta( $post_id, Constants::META_LOCKED_PROPERTIES, true );
		if ( ! is_array( $locked ) ) {
			$locked = array();
		}

		// Build JSON-LD structured data.
		$built = $this->build_schema_data( $target_type, $record, $locked );

		$json_encoded = Utils::encode_schema( $built['data'] );

		// Review status: if confidence is low or review required, flag it.
		$status = $review_req || ( $confidence < 0.75 ) ? Constants::STATUS_PENDING_REVIEW : Constants::STATUS_PENDING_REVIEW;

		$proposal = array(
			'success'             => true,
			'post_id'             => $post_id,
			'proposed_type'       => $target_type,
			'confidence'          => $confidence,
			'engine'              => $source_engine,
			'reasons'             => $reasons,
			'json_ld_data'        => $built['data'],
			'json_ld_string'      => $json_encoded,
			'source_fields_used'  => $built['sources'],
			'missing_required'    => $built['missing_required'],
			'missing_recommended' => $built['missing_recommended'],
			'warnings'            => $built['warnings'],
			'review_status'       => $status,
			'is_published'        => false, // Stored as proposal only!
			'generated_at'        => time(),
		);

		if ( ! empty( $ai_analysis ) ) {
			$proposal['ai_analysis'] = $ai_analysis;
		}

		// Store draft and proposal in post meta without publishing live!
		update_post_meta( $post_id, Constants::META_DRAFT_SCHEMA, $json_encoded );
		update_post_meta( $post_id, Constants::META_SCHEMA_PROPOSAL, $proposal );

		$current_status = get_post_meta( $post_id, Constants::META_SCHEMA_STATUS, true );
		if ( empty( $current_status ) || Constants::STATUS_NONE === $current_status ) {
			update_post_meta( $post_id, Constants::META_SCHEMA_STATUS, Constants::STATUS_PENDING_REVIEW );
		}

		$this->history->log_event(
			'generate_proposal',
			$post_id,
			sprintf(
				/* translators: 1: Schema type, 2: Post title, 3: Confidence */
				__( 'Generated %1$s schema proposal for "%2$s" (%3$d%% confidence).', 'dpls-schema-ai' ),
				$target_type,
				$post->post_title,
				(int) ( $confidence * 100 )
			),
			array(
				'type'       => $target_type,
				'confidence' => $confidence,
			)
		);

		return $proposal;
	}

	/**
	 * Convenience wrapper implementing GeneratorInterface.
	 *
	 * @param int         $post_id Post ID.
	 * @param string|null $schema_type Optional override type.
	 * @return array
	 */
	public function generate_for_post( int $post_id, ?string $schema_type = null ): array {
		$proposal = $this->generate_proposal( $post_id, $schema_type );
		if ( empty( $proposal['success'] ) ) {
			return $proposal;
		}

		return array(
			'success'     => true,
			'post_id'     => $post_id,
			'schema_type' => $proposal['proposed_type'],
			'schema_json' => $proposal['json_ld_string'],
			'proposal'    => $proposal,
			'message'     => __( 'Draft schema generated successfully.', 'dpls-schema-ai' ),
		);
	}

	/**
	 * Build JSON-LD structured data array from normalized content record.
	 *
	 * @param string $type Target Schema.org type.
	 * @param array  $record Normalized content record.
	 * @param array  $locked Locked properties to preserve.
	 * @return array
	 */
	public function build_schema_data( string $type, array $record, array $locked = array() ): array {
		$sources             = array( 'post_title', 'permalink' );
		$missing_required    = array();
		$missing_recommended = array();
		$warnings            = array();

		$permalink = $record['permalink'] ?? 'https://example.com';
		$title     = $record['title'] ?? '';
		$excerpt   = $record['excerpt'] ?? '';

		$org_data = $this->settings->get_organization_data();

		// Stable entity IDs.
		$webpage_id      = $permalink . '#webpage';
		$primary_id      = $permalink . '#' . strtolower( $type );
		$organization_id = rtrim( $org_data['url'], '/' ) . '#organization';

		// Build Publisher Organization entity.
		$publisher_entity = array(
			'@type' => $org_data['type'],
			'@id'   => $organization_id,
			'name'  => $org_data['name'],
			'url'   => $org_data['url'],
		);

		if ( ! empty( $org_data['logo_url'] ) ) {
			$publisher_entity['logo'] = array(
				'@type' => 'ImageObject',
				'url'   => $org_data['logo_url'],
			);
			$sources[] = 'organization_logo';
		} else {
			$missing_recommended[] = __( 'Organization logo URL is missing in Settings.', 'dpls-schema-ai' );
		}

		// Verified Contact details.
		if ( ! empty( $org_data['phone'] ) || ! empty( $org_data['email'] ) ) {
			$contact_point = array(
				'@type'       => 'ContactPoint',
				'contactType' => $org_data['contact_type'],
			);
			if ( ! empty( $org_data['phone'] ) ) {
				$contact_point['telephone'] = $org_data['phone'];
			}
			if ( ! empty( $org_data['email'] ) ) {
				$contact_point['email'] = $org_data['email'];
			}
			$publisher_entity['contactPoint'] = $contact_point;
			$sources[] = 'organization_contact';
		}

		if ( ! empty( $org_data['same_as'] ) ) {
			$publisher_entity['sameAs'] = $org_data['same_as'];
			$sources[] = 'organization_social_profiles';
		}

		// Author Entity (Do NOT invent author).
		$author_entity = null;
		if ( ! empty( $record['author']['name'] ) ) {
			$author_entity = array(
				'@type' => 'Person',
				'name'  => $record['author']['name'],
			);
			if ( ! empty( $record['author']['url'] ) ) {
				$author_entity['url'] = $record['author']['url'];
			}
			$sources[] = 'author';
		} else {
			$missing_recommended[] = __( 'Author details missing; falling back to publisher.', 'dpls-schema-ai' );
			$author_entity         = $publisher_entity;
		}

		// Image Object.
		$image_entity = null;
		if ( ! empty( $record['image_url'] ) ) {
			$image_entity = array(
				'@type' => 'ImageObject',
				'url'   => $record['image_url'],
			);
			if ( ! empty( $record['image_alt'] ) ) {
				$image_entity['caption'] = $record['image_alt'];
			}
			$sources[] = 'featured_image';
		} else {
			$missing_recommended[] = __( 'Featured image missing. Rich results benefit from a high-resolution image.', 'dpls-schema-ai' );
		}

		// BreadcrumbList.
		$breadcrumb_entity = $this->build_breadcrumb( $record );

		// Base WebPage wrapper.
		$webpage_entity = array(
			'@type'      => 'WebPage',
			'@id'        => $webpage_id,
			'url'        => $permalink,
			'name'       => $title,
			'isPartOf'   => array(
				'@type' => 'WebSite',
				'@id'   => rtrim( $org_data['url'], '/' ) . '#website',
				'name'  => $org_data['name'],
				'url'   => $org_data['url'],
			),
			'breadcrumb' => $breadcrumb_entity,
		);

		if ( ! empty( $excerpt ) ) {
			$webpage_entity['description'] = $excerpt;
			$sources[] = 'excerpt';
		}

		// Construct primary schema based on selected type.
		$data = array();

		switch ( $type ) {
			case 'Article':
			case 'BlogPosting':
			case 'NewsArticle':
				$data = array(
					'@context'         => 'https://schema.org',
					'@type'            => $type,
					'@id'              => $primary_id,
					'isPartOf'         => array( '@id' => $webpage_id ),
					'mainEntityOfPage' => $webpage_id,
					'headline'         => $title,
					'url'              => $permalink,
					'datePublished'    => $record['date_published'] ?? '',
					'dateModified'     => $record['date_modified'] ?? '',
					'author'           => $author_entity,
					'publisher'        => $publisher_entity,
				);

				if ( ! empty( $excerpt ) ) {
					$data['description'] = $excerpt;
				}
				if ( $image_entity ) {
					$data['image'] = $image_entity;
				}

				$sources[] = 'date_published';
				$sources[] = 'date_modified';
				break;

			case 'FAQPage':
				$classifier = $this->classifier;
				$qa_items   = $classifier->extract_qa_items( $record['headings'] ?? array(), $record['raw_content'] ?? '' );

				if ( empty( $qa_items ) ) {
					$missing_required[] = __( 'FAQPage requires at least one Question and Answer pair.', 'dpls-schema-ai' );
					$warnings[]         = __( 'No Q&A pairs detected; generated empty FAQ container.', 'dpls-schema-ai' );
				}

				$main_entity = array();
				foreach ( $qa_items as $qa ) {
					$main_entity[] = array(
						'@type'          => 'Question',
						'name'           => $qa['question'],
						'acceptedAnswer' => array(
							'@type' => 'Answer',
							'text'  => $qa['answer'],
						),
					);
				}

				$data = array(
					'@context'   => 'https://schema.org',
					'@type'      => 'FAQPage',
					'@id'        => $primary_id,
					'url'        => $permalink,
					'name'       => $title,
					'mainEntity' => $main_entity,
				);
				$sources[] = 'faq_questions_and_answers';
				break;

			case 'AboutPage':
				$data = array(
					'@context'    => 'https://schema.org',
					'@type'       => 'AboutPage',
					'@id'         => $primary_id,
					'url'         => $permalink,
					'name'        => $title,
					'description' => $excerpt,
					'mainEntity'  => $publisher_entity,
				);
				$sources[] = 'organization_details';
				break;

			case 'ContactPage':
				$data = array(
					'@context'    => 'https://schema.org',
					'@type'       => 'ContactPage',
					'@id'         => $primary_id,
					'url'         => $permalink,
					'name'        => $title,
					'description' => $excerpt,
					'mainEntity'  => $publisher_entity,
				);
				$sources[] = 'organization_contact';
				break;

			case 'Product':
				$meta = $record['metadata'] ?? array();
				$wc   = $record['woocommerce'] ?? array();
				$sku  = ! empty( $wc['sku'] ) ? (string) $wc['sku'] : ( ! empty( $meta['_sku'] ) ? (string) $meta['_sku'] : '' );

				$data = array(
					'@context'    => 'https://schema.org',
					'@type'       => 'Product',
					'@id'         => $primary_id,
					'name'        => ! empty( $wc['name'] ) ? $wc['name'] : $title,
					'url'         => $permalink,
					'description' => ! empty( $wc['description'] ) ? $wc['description'] : $excerpt,
				);

				if ( $image_entity ) {
					$data['image'] = $image_entity;
				}

				if ( ! empty( $sku ) ) {
					$data['sku'] = $sku;
					$sources[]   = 'sku_metadata';
				}

				// Assign brand from verified organization identity if available.
				if ( ! empty( $org_data['name'] ) ) {
					$data['brand'] = array(
						'@type' => 'Brand',
						'name'  => $org_data['name'],
					);
					$sources[] = 'organization_brand';
				}

				// Only add offers if verifiable price exists! (Do NOT invent prices).
				$price = ! empty( $wc['price'] ) ? (string) $wc['price'] : ( ! empty( $meta['_price'] ) ? (string) $meta['_price'] : ( ! empty( $meta['_regular_price'] ) ? (string) $meta['_regular_price'] : '' ) );
				if ( ! empty( $price ) && is_numeric( $price ) ) {
					$currency = ! empty( $wc['currency'] ) ? $wc['currency'] : ( function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'USD' );
					$stock    = ! empty( $wc['availability'] ) ? $wc['availability'] : ( ! empty( $meta['_stock_status'] ) && 'outofstock' === $meta['_stock_status']
						? 'https://schema.org/OutOfStock'
						: 'https://schema.org/InStock' );

					$offer = array(
						'@type'         => 'Offer',
						'url'           => $permalink,
						'price'         => number_format( (float) $price, 2, '.', '' ),
						'priceCurrency' => $currency,
						'availability'  => $stock,
						'itemCondition' => 'https://schema.org/NewCondition',
					);

					if ( ! empty( $wc['price_valid_until'] ) ) {
						$offer['priceValidUntil'] = $wc['price_valid_until'];
					}

					$data['offers'] = $offer;
					$sources[]      = 'price_metadata';
				} else {
					$missing_recommended[] = __( 'No verified price found; omitted offers block rather than inventing a price.', 'dpls-schema-ai' );
				}

				// Real ratings and reviews: strictly mapped from approved store reviews, NEVER invented.
				if ( ! empty( $wc['review_count'] ) && $wc['review_count'] > 0 && ! empty( $wc['average_rating'] ) && $wc['average_rating'] > 0 ) {
					$data['aggregateRating'] = array(
						'@type'       => 'AggregateRating',
						'ratingValue' => number_format( (float) $wc['average_rating'], 2, '.', '' ),
						'reviewCount' => (int) $wc['review_count'],
					);
					$sources[] = 'woocommerce_ratings';
				}

				if ( ! empty( $wc['catalog_visibility'] ) && 'hidden' === $wc['catalog_visibility'] ) {
					$warnings[] = __( 'Product is set to hidden in WooCommerce catalog.', 'dpls-schema-ai' );
				}
				break;

			case 'Service':
				$data = array(
					'@context'    => 'https://schema.org',
					'@type'       => 'Service',
					'@id'         => $primary_id,
					'name'        => $title,
					'url'         => $permalink,
					'description' => $excerpt,
					'provider'    => $publisher_entity,
				);
				$sources[] = 'provider_organization';
				break;

			case 'Recipe':
				$data = array(
					'@context'         => 'https://schema.org',
					'@type'            => 'Recipe',
					'@id'              => $primary_id,
					'name'             => $title,
					'url'              => $permalink,
					'description'      => $excerpt,
					'author'           => $author_entity,
					'recipeIngredient' => array(),
				);

				if ( $image_entity ) {
					$data['image'] = $image_entity;
				}

				// Look for ingredient lines.
				$plain_text = $record['plain_text'] ?? '';
				if ( preg_match( '/ingredients[:\s]+(.*?)(?:instructions|directions|prep time|$)/is', $plain_text, $ing_m ) ) {
					$lines = array_filter( array_map( 'trim', explode( "\n", $ing_m[1] ) ) );
					$data['recipeIngredient'] = array_slice( $lines, 0, 15 );
					$sources[] = 'recipe_ingredients';
				} else {
					$missing_required[] = __( 'Recipe requires a "recipeIngredient" list.', 'dpls-schema-ai' );
				}
				break;

			case 'HowTo':
				$data = array(
					'@context'    => 'https://schema.org',
					'@type'       => 'HowTo',
					'@id'         => $primary_id,
					'name'        => $title,
					'url'         => $permalink,
					'description' => $excerpt,
					'step'        => array(),
				);

				if ( $image_entity ) {
					$data['image'] = $image_entity;
				}

				// Extract steps from numbered lists.
				$plain_text = $record['plain_text'] ?? '';
				if ( preg_match_all( '/(?:step\s*\d+[:.\s]+|\d+\.\s+)(.*?)(?=(?:step\s*\d+|\d+\.|$))/is', $plain_text, $steps_m ) ) {
					$steps_data = array();
					$pos = 1;
					foreach ( $steps_m[1] as $s_text ) {
						$clean_s = trim( $s_text );
						if ( strlen( $clean_s ) > 5 ) {
							$steps_data[] = array(
								'@type'    => 'HowToStep',
								'position' => $pos++,
								'text'     => $clean_s,
							);
						}
					}
					$data['step'] = array_slice( $steps_data, 0, 10 );
					$sources[]    = 'howto_steps';
				} else {
					$missing_required[] = __( 'HowTo requires a "step" sequence.', 'dpls-schema-ai' );
				}
				break;

			case 'WebPage':
			default:
				$data = $webpage_entity;
				$data['@context'] = 'https://schema.org';
				break;
		}

		// Ensure BreadcrumbList navigation is attached.
		if ( ! isset( $data['breadcrumb'] ) && ! empty( $breadcrumb_entity ) ) {
			$data['breadcrumb'] = $breadcrumb_entity;
		}

		// Apply manual locked property preservation if defined.
		if ( ! empty( $locked ) ) {
			foreach ( $locked as $lock_prop => $lock_val ) {
				$data[ $lock_prop ] = $lock_val;
				$sources[]          = 'locked_property_' . $lock_prop;
			}
		}

		// Filter for extensible custom schemas.
		$data = apply_filters( 'dpls_schema_ai_built_schema', $data, $type, $record );

		return array(
			'data'                => $data,
			'sources'             => array_unique( $sources ),
			'missing_required'    => $missing_required,
			'missing_recommended' => $missing_recommended,
			'warnings'            => $warnings,
		);
	}

	/**
	 * Build BreadcrumbList navigation entity.
	 *
	 * @param array $record Normalized content record.
	 * @return array
	 */
	protected function build_breadcrumb( array $record ): array {
		$items = array();

		// 1. Home
		$site_url = function_exists( 'home_url' ) ? home_url() : 'https://example.com';
		$items[]  = array(
			'@type'    => 'ListItem',
			'position' => 1,
			'name'     => __( 'Home', 'dpls-schema-ai' ),
			'item'     => $site_url,
		);

		// 2. Category if post has categories
		$pos = 2;
		if ( ! empty( $record['categories'][0]['name'] ) ) {
			$cat_name = $record['categories'][0]['name'];
			$cat_id   = $record['categories'][0]['id'] ?? 0;
			$cat_link = function_exists( 'get_category_link' ) && $cat_id ? get_category_link( $cat_id ) : $site_url;

			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $pos++,
				'name'     => $cat_name,
				'item'     => $cat_link,
			);
		}

		// 3. Current Post
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => $pos,
			'name'     => $record['title'] ?? '',
			'item'     => $record['permalink'] ?? '',
		);

		return array(
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $items,
		);
	}

	/**
	 * Get injected AI service instance.
	 *
	 * @return AIService|null
	 */
	public function get_ai(): ?AIService {
		return $this->ai;
	}
}
