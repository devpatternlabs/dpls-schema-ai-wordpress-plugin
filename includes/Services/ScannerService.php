<?php
/**
 * Content Scanner Service.
 *
 * @package DPLS\SchemaAI\Services
 * @author  Dev Pattern Labs
 * @license GPL-2.0-or-later
 */

namespace DPLS\SchemaAI\Services;

use DPLS\SchemaAI\Adapters\ElementorAdapter;
use DPLS\SchemaAI\Adapters\PageBuilderAdapterInterface;
use DPLS\SchemaAI\Adapters\StandardAdapter;
use DPLS\SchemaAI\Common\Constants;
use DPLS\SchemaAI\Common\Utils;
use DPLS\SchemaAI\Generator\ContentClassifier;
use DPLS\SchemaAI\Integrations\WooCommerceIntegration;
use DPLS\SchemaAI\Interfaces\ScannerInterface;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Scans content, normalizes representations, classifies types, and handles background batch jobs.
 */
class ScannerService implements ScannerInterface {

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
	 * WooCommerce Integration.
	 *
	 * @var WooCommerceIntegration
	 */
	protected WooCommerceIntegration $woocommerce;

	/**
	 * Page builder adapters.
	 *
	 * @var PageBuilderAdapterInterface[]
	 */
	protected array $adapters = array();

	/**
	 * Constructor.
	 *
	 * @param SettingsService             $settings    Settings service.
	 * @param HistoryService              $history     History service.
	 * @param ContentClassifier|null      $classifier  Optional classifier.
	 * @param WooCommerceIntegration|null $woocommerce Optional WooCommerce integration.
	 */
	public function __construct(
		SettingsService $settings,
		HistoryService $history,
		?ContentClassifier $classifier = null,
		?WooCommerceIntegration $woocommerce = null
	) {
		$this->settings    = $settings;
		$this->history     = $history;
		$this->classifier  = $classifier ?? new ContentClassifier();
		$this->woocommerce = $woocommerce ?? new WooCommerceIntegration( $settings );

		// Register content extraction adapters.
		$this->adapters = array(
			new ElementorAdapter(),
			new StandardAdapter(),
		);
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
	 * Extract structured content record from a post without executing shortcodes/PHP.
	 *
	 * @param \WP_Post $post Post object.
	 * @param bool     $force Whether to bypass hash cache.
	 * @return array|null Normalized content record or null if ineligible.
	 */
	public function extract_structured_content( \WP_Post $post, bool $force = false ): ?array {
		if ( ! $post instanceof \WP_Post ) {
			return null;
		}

		// 1. Revision / autosave check.
		if ( function_exists( 'wp_is_post_revision' ) && wp_is_post_revision( $post->ID ) ) {
			return null;
		}
		if ( function_exists( 'wp_is_post_autosave' ) && wp_is_post_autosave( $post->ID ) ) {
			return null;
		}

		// 2. Publication status check.
		if ( 'publish' !== $post->post_status ) {
			return null;
		}

		// 3. Password protection check.
		if ( ! empty( $post->post_password ) ) {
			return null;
		}

		// 4. Enabled post type check.
		if ( ! $this->settings->is_post_type_enabled( $post->post_type ) ) {
			return null;
		}

		// 5. Extract raw content using suitable adapter.
		$raw_content = '';
		foreach ( $this->adapters as $adapter ) {
			if ( $adapter->is_applicable( $post ) ) {
				$raw_content = $adapter->extract_content( $post );
				break;
			}
		}
		if ( empty( $raw_content ) ) {
			$raw_content = (string) $post->post_content;
		}

		// 6. Normalize HTML and extract headings.
		// Strip shortcodes without executing them!
		if ( function_exists( 'strip_shortcodes' ) ) {
			$no_shortcodes = strip_shortcodes( $raw_content );
		} else {
			$no_shortcodes = preg_replace( '/\[\/?([a-zA-Z0-9_\-]+)[^\]]*\]/', '', $raw_content );
		}

		// Extract headings structure.
		$headings = array();
		if ( preg_match_all( '/<h([1-6])[^>]*>(.*?)<\/h\1>/is', $no_shortcodes, $h_matches, PREG_SET_ORDER ) ) {
			foreach ( $h_matches as $hm ) {
				$h_text = trim( wp_strip_all_tags( $hm[2] ) );
				if ( ! empty( $h_text ) ) {
					$headings[] = array(
						'level' => (int) $hm[1],
						'text'  => $h_text,
					);
				}
			}
		}

		// Convert block tags to line breaks to preserve text layout.
		$text_with_breaks = preg_replace( '/<h[1-6][^>]*>(.*?)<\/h[1-6]>/is', "\n\n# $1\n", $no_shortcodes );
		$text_with_breaks = preg_replace( '/<(p|div|li|tr|blockquote)[^>]*>/is', "\n", $text_with_breaks );
		$text_with_breaks = preg_replace( '/<br\s*\/?>/is', "\n", $text_with_breaks );

		// Strip remaining HTML tags safely.
		$clean_text = wp_strip_all_tags( $text_with_breaks );
		$clean_text = html_entity_decode( $clean_text, ENT_QUOTES, 'UTF-8' );
		$clean_text = preg_replace( '/[ \t]+/', ' ', $clean_text );
		$clean_text = trim( (string) preg_replace( '/\n\s*\n+/', "\n\n", $clean_text ) );

		// 7. Excerpt derivation.
		$excerpt = ! empty( $post->post_excerpt ) ? trim( wp_strip_all_tags( $post->post_excerpt ) ) : '';
		if ( empty( $excerpt ) && ! empty( $clean_text ) ) {
			$excerpt = wp_trim_words( $clean_text, 40, '...' );
		}

		// 8. Featured image.
		$thumb_id   = (int) get_post_thumbnail_id( $post->ID );
		$image_url  = null;
		$image_alt  = '';
		if ( $thumb_id ) {
			$img_src = wp_get_attachment_image_url( $thumb_id, 'full' );
			if ( $img_src ) {
				$image_url = $img_src;
				$image_alt = (string) get_post_meta( $thumb_id, '_wp_attachment_image_alt', true );
			}
		}

		// 9. Author details.
		$author_id   = (int) $post->post_author;
		$author_name = get_the_author_meta( 'display_name', $author_id );
		$author_url  = get_the_author_meta( 'user_url', $author_id );

		// 10. Taxonomies.
		$categories = array();
		$raw_cats   = get_the_category( $post->ID );
		if ( is_array( $raw_cats ) ) {
			foreach ( $raw_cats as $cat ) {
				$categories[] = array(
					'id'   => $cat->term_id ?? 0,
					'name' => $cat->name ?? '',
					'slug' => $cat->slug ?? '',
				);
			}
		}

		$tags     = array();
		$raw_tags = get_the_tags( $post->ID );
		if ( is_array( $raw_tags ) ) {
			foreach ( $raw_tags as $t ) {
				$tags[] = array(
					'id'   => $t->term_id ?? 0,
					'name' => $t->name ?? '',
					'slug' => $t->slug ?? '',
				);
			}
		}

		// 11. Relevant metadata (e.g. WooCommerce/E-commerce/Custom fields).
		$metadata = array(
			'_price'         => get_post_meta( $post->ID, '_price', true ),
			'_regular_price' => get_post_meta( $post->ID, '_regular_price', true ),
			'_sale_price'    => get_post_meta( $post->ID, '_sale_price', true ),
			'_sku'           => get_post_meta( $post->ID, '_sku', true ),
			'_stock_status'  => get_post_meta( $post->ID, '_stock_status', true ),
		);

		$wc_data = array();
		if ( 'product' === $post->post_type && $this->woocommerce->is_enabled() ) {
			$wc_data = $this->woocommerce->extract_product_data( $post );
			if ( ! empty( $wc_data ) ) {
				if ( ! empty( $wc_data['price'] ) ) {
					$metadata['_price'] = $wc_data['price'];
				}
				if ( ! empty( $wc_data['regular_price'] ) ) {
					$metadata['_regular_price'] = $wc_data['regular_price'];
				}
				if ( ! empty( $wc_data['sale_price'] ) ) {
					$metadata['_sale_price'] = $wc_data['sale_price'];
				}
				if ( ! empty( $wc_data['sku'] ) ) {
					$metadata['_sku'] = $wc_data['sku'];
				}
				if ( ! empty( $wc_data['stock_status'] ) ) {
					$metadata['_stock_status'] = $wc_data['stock_status'];
				}
			}
		}

		$permalink = get_permalink( $post );

		// 12. Compute content hash.
		$hash_payload = implode(
			'|',
			array(
				$post->post_title,
				$excerpt,
				$clean_text,
				(string) $thumb_id,
				$permalink,
				$post->post_status,
				(string) $post->post_modified,
			)
		);
		$content_hash = md5( $hash_payload );

		// Check if record is cached and unchanged.
		$stored_hash = get_post_meta( $post->ID, Constants::META_CONTENT_HASH, true );
		if ( ! $force && $stored_hash === $content_hash ) {
			$cached = get_post_meta( $post->ID, Constants::META_NORMALIZED_CONTENT, true );
			if ( is_array( $cached ) && ! empty( $cached ) ) {
				return $cached;
			}
		}

		// 13. Construct complete normalized record.
		$record = array(
			'post_id'        => $post->ID,
			'post_type'      => $post->post_type,
			'post_status'    => $post->post_status,
			'title'          => $post->post_title,
			'slug'           => $post->post_name,
			'permalink'      => $permalink,
			'excerpt'        => $excerpt,
			'plain_text'     => $clean_text,
			'raw_content'    => $raw_content,
			'headings'       => $headings,
			'image_url'      => $image_url,
			'image_alt'      => $image_alt,
			'author'         => array(
				'id'   => $author_id,
				'name' => $author_name ? $author_name : 'Editorial Staff',
				'url'  => $author_url ? $author_url : '',
			),
			'date_published' => get_the_date( 'c', $post ),
			'date_modified'  => get_the_modified_date( 'c', $post ),
			'categories'     => $categories,
			'tags'           => $tags,
			'metadata'       => $metadata,
			'woocommerce'    => $wc_data,
			'content_hash'   => $content_hash,
			'scanned_at'     => time(),
		);

		// Persist normalized record.
		update_post_meta( $post->ID, Constants::META_NORMALIZED_CONTENT, $record );
		update_post_meta( $post->ID, Constants::META_CONTENT_HASH, $content_hash );
		update_post_meta( $post->ID, Constants::META_LAST_ANALYZED, time() );

		return $record;
	}

	/**
	 * Recommend Schema type based on post characteristics.
	 *
	 * @param \WP_Post $post Post object.
	 * @return string Recommended schema type.
	 */
	public function recommend_schema_type( \WP_Post $post ): string {
		$record = $this->extract_structured_content( $post );
		if ( ! $record ) {
			return ( 'page' === $post->post_type ) ? 'WebPage' : 'BlogPosting';
		}

		$result = $this->classifier->classify( $record );
		return $result['type'];
	}

	/**
	 * Analyze a single post, store suggestion, and return classification report.
	 *
	 * @param int  $post_id Post ID.
	 * @param bool $force Force re-scan.
	 * @return array Analysis results.
	 */
	public function analyze_post( int $post_id, bool $force = false ): array {
		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post ) {
			return array(
				'success' => false,
				'message' => __( 'Post not found.', 'dpls-schema-ai' ),
			);
		}

		// Validation of status / privacy / type.
		if ( 'publish' !== $post->post_status ) {
			return array(
				'success' => false,
				/* translators: %s: Post status name */
				'message' => sprintf( __( 'Post status is "%s"; only published content can be scanned.', 'dpls-schema-ai' ), $post->post_status ),
			);
		}

		if ( ! empty( $post->post_password ) ) {
			return array(
				'success' => false,
				'message' => __( 'Password-protected content is excluded from scanning by policy.', 'dpls-schema-ai' ),
			);
		}

		if ( ! $this->settings->is_post_type_enabled( $post->post_type ) ) {
			return array(
				'success' => false,
				/* translators: %s: Post type name */
				'message' => sprintf( __( 'Post type "%s" is not enabled in settings.', 'dpls-schema-ai' ), $post->post_type ),
			);
		}

		$record = $this->extract_structured_content( $post, $force );
		if ( ! $record ) {
			return array(
				'success' => false,
				'message' => __( 'Unable to extract structured content record.', 'dpls-schema-ai' ),
			);
		}

		$classification = $this->classifier->classify( $record );
		$suggested_type = $classification['type'];
		$confidence     = $classification['confidence'] ?? 0.8;
		$reasons        = $classification['reasons'] ?? array();
		$review_req     = $classification['review_required'] ?? false;
		$now            = time();

		$current_status = get_post_meta( $post_id, Constants::META_SCHEMA_STATUS, true );
		if ( empty( $current_status ) || Constants::STATUS_NONE === $current_status ) {
			$new_status = Constants::STATUS_PENDING_REVIEW;
		} else {
			$stored_hash = get_post_meta( $post_id, Constants::META_CONTENT_HASH, true );
			if ( $stored_hash && $stored_hash !== $record['content_hash'] && Constants::STATUS_APPLIED === $current_status ) {
				$new_status = Constants::STATUS_NEEDS_UPDATE;
			} else {
				$new_status = $current_status;
			}
		}

		// Persist analysis metadata.
		update_post_meta( $post_id, Constants::META_SUGGESTED_SCHEMA, $suggested_type );
		update_post_meta( $post_id, Constants::META_LAST_ANALYZED, $now );
		update_post_meta( $post_id, Constants::META_SCHEMA_STATUS, $new_status );

		$this->history->log_event(
			'analyze',
			$post_id,
			sprintf(
				/* translators: 1: Post title, 2: Schema type, 3: Confidence percentage */
				__( 'Analyzed "%1$s" &rarr; Recommended "%2$s" (%3$d%% confidence).', 'dpls-schema-ai' ),
				$post->post_title,
				$suggested_type,
				(int) ( $confidence * 100 )
			),
			array(
				'suggested_type'  => $suggested_type,
				'confidence'      => $confidence,
				'reasons'         => $reasons,
				'review_required' => $review_req,
				'status'          => $new_status,
			)
		);

		return array(
			'success'         => true,
			'post_id'         => $post_id,
			'suggested_type'  => $suggested_type,
			'confidence'      => $confidence,
			'reasons'         => $reasons,
			'review_required' => $review_req,
			'status'          => $new_status,
			'status_badge'    => Utils::get_status_badge( $new_status ),
			'last_analyzed'   => Utils::format_date( $now ),
			'content_hash'    => $record['content_hash'],
		);
	}

	/**
	 * Start a new resumable background batch scan.
	 *
	 * @param int $batch_size Chunk size.
	 * @return array
	 */
	public function start_batch_scan( int $batch_size = 20 ): array {
		// Check for duplicate running lock.
		if ( get_transient( Constants::TRANSIENT_SCAN_LOCK ) ) {
			$progress = $this->get_scan_progress();
			return array(
				'success'  => true,
				'message'  => __( 'Scanning is already in progress.', 'dpls-schema-ai' ),
				'progress' => $progress,
			);
		}

		$enabled_types = $this->settings->get( 'enabled_post_types', array( 'post', 'page' ) );

		// Query all publish IDs.
		$query = new \WP_Query(
			array(
				'post_type'      => $enabled_types,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'has_password'   => false,
				'no_found_rows'  => true,
			)
		);

		$post_ids = ! empty( $query->posts ) ? array_map( 'absint', $query->posts ) : array();
		$total    = count( $post_ids );

		$job_data = array(
			'id'         => uniqid( 'dpls_scan_', true ),
			'total'      => $total,
			'scanned'    => 0,
			'batch_size' => max( 5, $batch_size ),
			'post_ids'   => $post_ids,
			'status'     => 'running',
			'started_at' => time(),
		);

		update_option( Constants::OPTION_SCAN_JOB, $job_data, false );
		set_transient( Constants::TRANSIENT_SCAN_LOCK, 1, 300 ); // 5 minute lock.

		return array(
			'success'  => true,
			'total'    => $total,
			'scanned'  => 0,
			'percent'  => 0,
			'status'   => 'running',
		);
	}

	/**
	 * Process next batch chunk of the active scanning job.
	 *
	 * @param int $batch_size Chunk size.
	 * @return array
	 */
	public function process_scan_batch( int $batch_size = 20 ): array {
		$job = get_option( Constants::OPTION_SCAN_JOB, array() );
		if ( empty( $job ) || 'running' !== ( $job['status'] ?? '' ) ) {
			return array(
				'success' => false,
				'message' => __( 'No active scan job found.', 'dpls-schema-ai' ),
				'status'  => 'idle',
			);
		}

		$post_ids   = $job['post_ids'] ?? array();
		$batch_size = $job['batch_size'] ?? $batch_size;
		$scanned    = $job['scanned'] ?? 0;
		$total      = $job['total'] ?? count( $post_ids );

		// Refresh lock.
		set_transient( Constants::TRANSIENT_SCAN_LOCK, 1, 300 );

		$chunk = array_slice( $post_ids, $scanned, $batch_size );
		$processed_in_chunk = 0;

		foreach ( $chunk as $post_id ) {
			$this->analyze_post( (int) $post_id );
			++$processed_in_chunk;
		}

		$new_scanned = $scanned + $processed_in_chunk;
		$job['scanned'] = $new_scanned;

		$percent = $total > 0 ? min( 100, (int) round( ( $new_scanned / $total ) * 100 ) ) : 100;

		if ( $new_scanned >= $total ) {
			$job['status']       = 'completed';
			$job['completed_at'] = time();
			delete_transient( Constants::TRANSIENT_SCAN_LOCK );
			update_option( Constants::OPTION_LAST_SCAN, time() );

			$this->history->log_event(
				'bulk_scan_completed',
				null,
				sprintf(
					/* translators: %d: Number of items */
					__( 'Full scan completed for %d content items.', 'dpls-schema-ai' ),
					$total
				)
			);
		}

		update_option( Constants::OPTION_SCAN_JOB, $job, false );

		return array(
			'success'   => true,
			'total'     => $total,
			'scanned'   => $new_scanned,
			'percent'   => $percent,
			'status'    => $job['status'],
			'completed' => ( 'completed' === $job['status'] ),
		);
	}

	/**
	 * Get current batch scan job progress.
	 *
	 * @return array
	 */
	public function get_scan_progress(): array {
		$job = get_option( Constants::OPTION_SCAN_JOB, array() );
		if ( empty( $job ) ) {
			return array(
				'status'  => 'idle',
				'total'   => 0,
				'scanned' => 0,
				'percent' => 0,
			);
		}

		$total   = $job['total'] ?? 0;
		$scanned = $job['scanned'] ?? 0;
		$percent = $total > 0 ? min( 100, (int) round( ( $scanned / $total ) * 100 ) ) : 0;

		return array(
			'status'  => $job['status'] ?? 'idle',
			'total'   => $total,
			'scanned' => $scanned,
			'percent' => $percent,
		);
	}

	/**
	 * Cancel or reset the current scan job.
	 *
	 * @return bool
	 */
	public function cancel_scan_job(): bool {
		delete_transient( Constants::TRANSIENT_SCAN_LOCK );
		delete_option( Constants::OPTION_SCAN_JOB );
		return true;
	}

	/**
	 * Calculate actual overview statistics from WordPress content and plugin records.
	 *
	 * @return array
	 */
	public function get_overview_statistics(): array {
		$page_counts = wp_count_posts( 'page' );
		$post_counts = wp_count_posts( 'post' );

		$total_pages = isset( $page_counts->publish ) ? (int) $page_counts->publish : 0;
		$total_posts = isset( $post_counts->publish ) ? (int) $post_counts->publish : 0;

		$enabled_types = $this->settings->get( 'enabled_post_types', array( 'post', 'page' ) );

		$args_base = array(
			'post_type'      => $enabled_types,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		);

		// Managed by plugin.
		$managed_args = array_merge(
			$args_base,
			array(
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Filter by schema status meta required for dashboard counts.
				'meta_query' => array(
					array(
						'key'     => Constants::META_SCHEMA_STATUS,
						'value'   => array( Constants::STATUS_APPLIED, Constants::STATUS_WARNINGS, Constants::STATUS_NEEDS_UPDATE ),
						'compare' => 'IN',
					),
				),
			)
		);
		$managed_query = new \WP_Query( $managed_args );
		$managed_count = (int) $managed_query->post_count;

		// Awaiting review.
		$review_args = array_merge(
			$args_base,
			array(
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Filter by schema status meta required for dashboard counts.
				'meta_query' => array(
					array(
						'key'     => Constants::META_SCHEMA_STATUS,
						'value'   => Constants::STATUS_PENDING_REVIEW,
						'compare' => '=',
					),
				),
			)
		);
		$review_query = new \WP_Query( $review_args );
		$review_count = (int) $review_query->post_count;

		// Validation warnings.
		$warning_args = array_merge(
			$args_base,
			array(
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Filter by schema status meta required for dashboard counts.
				'meta_query' => array(
					array(
						'key'     => Constants::META_SCHEMA_STATUS,
						'value'   => Constants::STATUS_WARNINGS,
						'compare' => '=',
					),
				),
			)
		);
		$warning_query = new \WP_Query( $warning_args );
		$warning_count = (int) $warning_query->post_count;

		// Schema needs update.
		$update_args = array_merge(
			$args_base,
			array(
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Filter by schema status meta required for dashboard counts.
				'meta_query' => array(
					array(
						'key'     => Constants::META_SCHEMA_STATUS,
						'value'   => Constants::STATUS_NEEDS_UPDATE,
						'compare' => '=',
					),
				),
			)
		);
		$update_query = new \WP_Query( $update_args );
		$update_count = (int) $update_query->post_count;

		$last_scan = get_option( Constants::OPTION_LAST_SCAN, null );

		return array(
			'total_pages'         => $total_pages,
			'total_posts'         => $total_posts,
			'managed_count'       => $managed_count,
			'pending_review'      => $review_count,
			'validation_warnings' => $warning_count,
			'needs_update'        => $update_count,
			'last_scan'           => Utils::format_date( $last_scan ),
			'raw_last_scan'       => $last_scan,
		);
	}
}
