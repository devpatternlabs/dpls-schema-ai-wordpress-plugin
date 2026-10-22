<?php
/**
 * Rule-Based Content Classifier.
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
 * Classifies normalized content and recommends appropriate Schema.org types using heuristics.
 */
class ContentClassifier {

	/**
	 * Classify normalized content.
	 *
	 * @param array $record Normalized content record.
	 * @return array Classification result: type, confidence, reasons, questions, review_required.
	 */
	public function classify( array $record ): array {
		$post_type = strtolower( $record['post_type'] ?? 'post' );
		$title     = strtolower( $record['title'] ?? '' );
		$slug      = strtolower( $record['slug'] ?? '' );
		$content   = strtolower( $record['plain_text'] ?? '' );
		$headings  = $record['headings'] ?? array();
		$metadata  = $record['metadata'] ?? array();

		$reasons    = array();
		$confidence = 0.50;
		$type       = ( 'page' === $post_type ) ? 'WebPage' : 'BlogPosting';

		// 1. Product Check.
		if ( 'product' === $post_type || ! empty( $metadata['_price'] ) || ! empty( $metadata['_regular_price'] ) || ! empty( $metadata['_sku'] ) ) {
			return array(
				'type'            => 'Product',
				'confidence'      => 0.95,
				'reasons'         => array( __( 'Detected product post type and pricing/SKU metadata.', 'dpls-schema-ai' ) ),
				'review_required' => false,
			);
		}

		// 2. FAQ Page Check (requires qualifying question-and-answer content with at least 2 questions).
		$qa_items = $this->extract_qa_items( $headings, $record['raw_content'] ?? '' );
		if ( count( $qa_items ) >= 2 ) {
			$has_faq_title = str_contains( $title, 'faq' ) || str_contains( $slug, 'faq' ) || str_contains( $title, 'frequently asked' );
			$conf          = $has_faq_title ? 0.95 : 0.85;
			return array(
				'type'            => 'FAQPage',
				'confidence'      => $conf,
				'reasons'         => array(
					/* translators: %d: Number of structured Q&A pairs */
					sprintf( __( 'Identified %d structured Q&A pairs in content.', 'dpls-schema-ai' ), count( $qa_items ) ),
				),
				'qa_items'        => $qa_items,
				'review_required' => false,
			);
		}

		// 3. ContactPage Check (Requires evidence of contact-page purpose).
		$has_contact_slug = str_contains( $slug, 'contact' ) || str_contains( $title, 'contact' );
		$has_email_signal = (bool) preg_match( '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $content );
		$has_phone_signal = (bool) preg_match( '/(\+?[0-9]{1,3}[-.\s]?)?(\(?\d{2,4}\)?[-.\s]?)?\d{3,4}[-.\s]?\d{3,4}/', $content );
		$has_form_signal  = str_contains( $record['raw_content'] ?? '', 'contact-form' ) || str_contains( $record['raw_content'] ?? '', 'wpforms' ) || str_contains( $content, 'send us a message' );

		if ( $has_contact_slug && ( $has_email_signal || $has_phone_signal || $has_form_signal ) ) {
			return array(
				'type'            => 'ContactPage',
				'confidence'      => 0.92,
				'reasons'         => array( __( 'Contact page with verified communication signals (email/phone/form).', 'dpls-schema-ai' ) ),
				'review_required' => false,
			);
		} elseif ( $has_contact_slug ) {
			// Title suggests contact, but lacks concrete contact signals -> mark for review.
			return array(
				'type'            => 'ContactPage',
				'confidence'      => 0.65,
				'reasons'         => array( __( 'Contact title detected, but missing concrete contact endpoints.', 'dpls-schema-ai' ) ),
				'review_required' => true,
			);
		}

		// 4. AboutPage Check (Requires evidence describing the organization).
		$has_about_slug  = str_contains( $slug, 'about' ) || str_contains( $title, 'about' );
		$has_org_signals = str_contains( $content, 'our company' ) || str_contains( $content, 'our mission' ) || str_contains( $content, 'our team' ) || str_contains( $content, 'who we are' ) || str_contains( $content, 'founded in' ) || str_contains( $content, 'our story' );

		if ( $has_about_slug && $has_org_signals ) {
			return array(
				'type'            => 'AboutPage',
				'confidence'      => 0.90,
				'reasons'         => array( __( 'About page with substantive organizational background description.', 'dpls-schema-ai' ) ),
				'review_required' => false,
			);
		} elseif ( $has_about_slug ) {
			return array(
				'type'            => 'AboutPage',
				'confidence'      => 0.65,
				'reasons'         => array( __( 'About title detected, but lacks explicit organizational narrative signals.', 'dpls-schema-ai' ) ),
				'review_required' => true,
			);
		}

		// 5. Recipe Check (Ingredients + cooking times).
		$has_ingredients = str_contains( $content, 'ingredients' ) && ( str_contains( $content, 'cups' ) || str_contains( $content, 'tablespoon' ) || str_contains( $content, 'grams' ) || str_contains( $content, 'tbsp' ) || str_contains( $content, 'tsp' ) );
		$has_cook_times  = str_contains( $content, 'prep time' ) || str_contains( $content, 'cook time' ) || str_contains( $content, 'servings' );

		if ( $has_ingredients && $has_cook_times ) {
			return array(
				'type'            => 'Recipe',
				'confidence'      => 0.92,
				'reasons'         => array( __( 'Detected culinary recipe structure with ingredients and timing instructions.', 'dpls-schema-ai' ) ),
				'review_required' => false,
			);
		}

		// 6. HowTo Check.
		$has_howto_title = str_starts_with( $title, 'how to' ) || str_contains( $title, 'how to ' );
		$has_steps       = ( str_contains( $content, 'step 1' ) && str_contains( $content, 'step 2' ) ) || ( str_contains( $content, '1.' ) && str_contains( $content, '2.' ) && str_contains( $content, '3.' ) );

		if ( $has_howto_title && $has_steps ) {
			return array(
				'type'            => 'HowTo',
				'confidence'      => 0.88,
				'reasons'         => array( __( 'Detected instructional tutorial with sequential actionable steps.', 'dpls-schema-ai' ) ),
				'review_required' => false,
			);
		}

		// 7. Service Check.
		$has_service_slug = str_contains( $slug, 'service' ) || str_contains( $title, 'service' );
		$has_service_text = str_contains( $content, 'our services' ) || str_contains( $content, 'we provide' ) || str_contains( $content, 'service packages' );

		if ( ( 'service' === $post_type ) || ( $has_service_slug && $has_service_text ) ) {
			return array(
				'type'            => 'Service',
				'confidence'      => 0.82,
				'reasons'         => array( __( 'Detected commercial service descriptions and offering specifications.', 'dpls-schema-ai' ) ),
				'review_required' => false,
			);
		}

		// 8. NewsArticle Check.
		$categories = $record['categories'] ?? array();
		$is_news    = false;
		foreach ( $categories as $cat ) {
			if ( str_contains( strtolower( $cat['name'] ?? '' ), 'news' ) || str_contains( strtolower( $cat['slug'] ?? '' ), 'news' ) ) {
				$is_news = true;
				break;
			}
		}

		if ( $is_news && 'post' === $post_type ) {
			return array(
				'type'            => 'NewsArticle',
				'confidence'      => 0.82,
				'reasons'         => array( __( 'Categorized as news reporting content.', 'dpls-schema-ai' ) ),
				'review_required' => false,
			);
		}

		// 9. Standard Blog Posts.
		if ( 'post' === $post_type ) {
			return array(
				'type'            => 'BlogPosting',
				'confidence'      => 0.85,
				'reasons'         => array( __( 'Standard chronological blog post.', 'dpls-schema-ai' ) ),
				'review_required' => false,
			);
		}

		// 10. Default / Fallback WebPage.
		return array(
			'type'            => 'WebPage',
			'confidence'      => 0.80,
			'reasons'         => array( __( 'Standard published site page.', 'dpls-schema-ai' ) ),
			'review_required' => false,
		);
	}

	/**
	 * Extract question and answer pairs from content headings and body text.
	 *
	 * @param array  $headings Extracted headings list.
	 * @param string $raw_html Raw content.
	 * @return array Array of ['question' => '...', 'answer' => '...'].
	 */
	public function extract_qa_items( array $headings, string $raw_html ): array {
		$qa = array();

		// Check for question patterns in headings.
		if ( ! empty( $headings ) ) {
			foreach ( $headings as $h ) {
				$text = trim( $h['text'] ?? '' );
				$is_q = str_ends_with( $text, '?' ) ||
					preg_match( '/^(what|how|why|can|is|who|where|when|do|does)\b/i', $text );

				if ( $is_q && strlen( $text ) > 8 ) {
					// Extract paragraph following this heading in raw html.
					$escaped = preg_quote( $text, '/' );
					if ( preg_match( '/<h[1-6][^>]*>' . $escaped . '<\/h[1-6]>\s*(?:<p[^>]*>)?(.*?)(?:<\/p>|<h[1-6]|$)/is', $raw_html, $m ) ) {
						$ans = trim( wp_strip_all_tags( $m[1] ) );
						if ( strlen( $ans ) > 10 ) {
							$qa[] = array(
								'question' => $text,
								'answer'   => $ans,
							);
						}
					}
				}
			}
		}

		// Also check definition lists <dt>Question</dt><dd>Answer</dd>.
		if ( count( $qa ) < 2 && preg_match_all( '/<dt[^>]*>(.*?)<\/dt>\s*<dd[^>]*>(.*?)<\/dd>/is', $raw_html, $dl_matches, PREG_SET_ORDER ) ) {
			foreach ( $dl_matches as $dl ) {
				$q = trim( wp_strip_all_tags( $dl[1] ) );
				$a = trim( wp_strip_all_tags( $dl[2] ) );
				if ( strlen( $q ) > 5 && strlen( $a ) > 10 ) {
					$qa[] = array(
						'question' => $q,
						'answer'   => $a,
					);
				}
			}
		}

		return $qa;
	}
}
