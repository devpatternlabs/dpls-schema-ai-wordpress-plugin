<?php
/**
 * Schema Type Registry.
 *
 * @package DPLS\SchemaAI\Generator
 * @author  Dev Pattern Labs
 * @license GPL-2.0-or-later
 */

namespace DPLS\SchemaAI\Generator;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registry of supported Schema.org structured data types and property requirements.
 */
class SchemaRegistry {

	/**
	 * Registered schema types metadata.
	 *
	 * @var array
	 */
	protected static array $types = array(
		'Article' => array(
			'label'       => 'Article',
			'description' => 'General editorial or news articles.',
			'required'    => array( '@context', '@type', 'headline' ),
			'recommended' => array( 'author', 'datePublished', 'dateModified', 'image', 'publisher', 'description' ),
		),
		'BlogPosting' => array(
			'label'       => 'Blog Posting',
			'description' => 'Articles and journal entries on a blog.',
			'required'    => array( '@context', '@type', 'headline' ),
			'recommended' => array( 'author', 'datePublished', 'dateModified', 'image', 'publisher', 'description' ),
		),
		'NewsArticle' => array(
			'label'       => 'News Article',
			'description' => 'Time-sensitive news reporting and press releases.',
			'required'    => array( '@context', '@type', 'headline', 'datePublished' ),
			'recommended' => array( 'author', 'dateModified', 'image', 'publisher', 'dateline' ),
		),
		'WebPage' => array(
			'label'       => 'Web Page',
			'description' => 'Standard generic web page.',
			'required'    => array( '@context', '@type', 'name', 'url' ),
			'recommended' => array( 'description', 'isPartOf', 'breadcrumb' ),
		),
		'AboutPage' => array(
			'label'       => 'About Page',
			'description' => 'Page describing an organization, business, or website background.',
			'required'    => array( '@context', '@type', 'name', 'url' ),
			'recommended' => array( 'description', 'mainEntity' ),
		),
		'ContactPage' => array(
			'label'       => 'Contact Page',
			'description' => 'Page containing business contact details and inquiry points.',
			'required'    => array( '@context', '@type', 'name', 'url' ),
			'recommended' => array( 'description', 'mainEntity' ),
		),
		'FAQPage' => array(
			'label'       => 'FAQ Page',
			'description' => 'Page presenting a list of frequently asked questions and answers.',
			'required'    => array( '@context', '@type', 'mainEntity' ),
			'recommended' => array( 'name', 'description' ),
		),
		'Product' => array(
			'label'       => 'Product',
			'description' => 'Commercial item, digital download, or merchandise.',
			'required'    => array( '@context', '@type', 'name' ),
			'recommended' => array( 'image', 'description', 'sku', 'offers', 'brand' ),
		),
		'Service' => array(
			'label'       => 'Service',
			'description' => 'Service provided by an organization or business.',
			'required'    => array( '@context', '@type', 'name', 'provider' ),
			'recommended' => array( 'description', 'serviceType', 'areaServed' ),
		),
		'Recipe' => array(
			'label'       => 'Recipe',
			'description' => 'Culinary instructions and ingredient specifications.',
			'required'    => array( '@context', '@type', 'name', 'recipeIngredient' ),
			'recommended' => array( 'image', 'cookTime', 'prepTime', 'recipeInstructions' ),
		),
		'HowTo' => array(
			'label'       => 'How-To',
			'description' => 'Step-by-step instructions for completing a task.',
			'required'    => array( '@context', '@type', 'name', 'step' ),
			'recommended' => array( 'description', 'image', 'totalTime' ),
		),
		'Organization' => array(
			'label'       => 'Organization',
			'description' => 'Business, institution, or collective identity.',
			'required'    => array( '@context', '@type', 'name', 'url' ),
			'recommended' => array( 'logo', 'contactPoint', 'sameAs', 'address' ),
		),
		'LocalBusiness' => array(
			'label'       => 'Local Business',
			'description' => 'Physical or regional business location.',
			'required'    => array( '@context', '@type', 'name', 'address' ),
			'recommended' => array( 'telephone', 'openingHours', 'url', 'geo' ),
		),
		'BreadcrumbList' => array(
			'label'       => 'Breadcrumb List',
			'description' => 'Hierarchical site navigation breadcrumbs.',
			'required'    => array( '@context', '@type', 'itemListElement' ),
			'recommended' => array(),
		),
	);

	/**
	 * Get all registered schema types.
	 *
	 * @return array
	 */
	public static function get_all(): array {
		return apply_filters( 'dpls_schema_ai_registered_types', self::$types );
	}

	/**
	 * Check if a schema type is supported.
	 *
	 * @param string $type Schema type.
	 * @return bool
	 */
	public static function is_supported( string $type ): bool {
		$all = self::get_all();
		return isset( $all[ $type ] );
	}

	/**
	 * Get schema type definition.
	 *
	 * @param string $type Schema type.
	 * @return array|null
	 */
	public static function get( string $type ): ?array {
		$all = self::get_all();
		return $all[ $type ] ?? null;
	}
}
