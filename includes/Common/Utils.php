<?php
/**
 * Utility functions for DPLS Schema AI.
 *
 * @package DPLS\SchemaAI\Common
 * @author  Dev Pattern Labs
 * @license GPL-2.0-or-later
 */

namespace DPLS\SchemaAI\Common;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Utility helper class.
 */
class Utils {

	/**
	 * Compute content hash for stale schema detection.
	 *
	 * @param \WP_Post|int $post Post object or ID.
	 * @return string MD5 hash of title + content.
	 */
	public static function compute_content_hash( $post ): string {
		$post_obj = is_numeric( $post ) ? get_post( $post ) : $post;
		if ( ! $post_obj instanceof \WP_Post ) {
			return '';
		}

		$payload = $post_obj->post_title . '|' . $post_obj->post_content . '|' . $post_obj->post_excerpt;
		return md5( $payload );
	}

	/**
	 * Format date safely according to WordPress site settings.
	 *
	 * @param int|string $timestamp Unix timestamp or date string.
	 * @return string Formatted human readable date.
	 */
	public static function format_date( $timestamp ): string {
		if ( empty( $timestamp ) ) {
			return __( 'Never', 'dpls-schema-ai' );
		}

		if ( ! is_numeric( $timestamp ) ) {
			$timestamp = strtotime( (string) $timestamp );
		}

		if ( ! $timestamp ) {
			return __( 'Unknown', 'dpls-schema-ai' );
		}

		$date_format = get_option( 'date_format', 'Y-m-d' );
		$time_format = get_option( 'time_format', 'H:i' );

		return wp_date( $date_format . ' ' . $time_format, (int) $timestamp );
	}

	/**
	 * Render HTML status badge with accessible markup.
	 *
	 * @param string $status Schema status code.
	 * @return string HTML badge markup.
	 */
	public static function get_status_badge( string $status ): string {
		$status_map = array(
			Constants::STATUS_APPLIED        => array(
				'label' => __( 'Active', 'dpls-schema-ai' ),
				'class' => 'dpls-badge-applied',
				'icon'  => 'dashicons-yes-alt',
			),
			Constants::STATUS_PENDING_REVIEW => array(
				'label' => __( 'Review Needed', 'dpls-schema-ai' ),
				'class' => 'dpls-badge-pending',
				'icon'  => 'dashicons-clock',
			),
			Constants::STATUS_NEEDS_UPDATE   => array(
				'label' => __( 'Needs Update', 'dpls-schema-ai' ),
				'class' => 'dpls-badge-update',
				'icon'  => 'dashicons-update',
			),
			Constants::STATUS_WARNINGS       => array(
				'label' => __( 'Warnings', 'dpls-schema-ai' ),
				'class' => 'dpls-badge-warnings',
				'icon'  => 'dashicons-warning',
			),
			Constants::STATUS_DISABLED       => array(
				'label' => __( 'Disabled', 'dpls-schema-ai' ),
				'class' => 'dpls-badge-disabled',
				'icon'  => 'dashicons-hidden',
			),
			Constants::STATUS_NONE           => array(
				'label' => __( 'Not Analyzed', 'dpls-schema-ai' ),
				'class' => 'dpls-badge-none',
				'icon'  => 'dashicons-marker',
			),
		);

		$info = $status_map[ $status ] ?? $status_map[ Constants::STATUS_NONE ];

		return sprintf(
			'<span class="dpls-badge %1$s" role="status" aria-label="%2$s"><span class="dashicons %3$s" aria-hidden="true"></span> %2$s</span>',
			esc_attr( $info['class'] ),
			esc_html( $info['label'] ),
			esc_attr( $info['icon'] )
		);
	}

	/**
	 * Normalize schema JSON-LD string with pretty printing.
	 *
	 * @param string|array $schema JSON string or associative array.
	 * @return string Encoded JSON-LD string.
	 */
	public static function encode_schema( $schema ): string {
		if ( is_string( $schema ) ) {
			$decoded = json_decode( $schema, true );
			if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
				$schema = $decoded;
			} else {
				return $schema;
			}
		}

		return (string) wp_json_encode( $schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}
}
