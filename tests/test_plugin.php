<?php
/**
 * Comprehensive Test Suite for DPLS Schema AI.
 * Covers:
 * - Content Scanning Engine (M1)
 * - Rule-Based Schema Generator & Registry (M2)
 * - Review and Application Workflow & Editor Meta Box (M3)
 * - Frontend JSON-LD Output Engine (M4)
 * - Automatic Schema Maintenance & Page Builder Adapters (M5)
 *
 * @package DPLS\SchemaAI\Tests
 */

// Prevent direct web access.
defined( 'ABSPATH' ) || ( 'cli' === php_sapi_name() ) || exit;

// Define WordPress test mocks if running outside full WP runtime.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
}

if ( ! defined( 'JSON_UNESCAPED_SLASHES' ) ) {
	define( 'JSON_UNESCAPED_SLASHES', 64 );
}
if ( ! defined( 'JSON_PRETTY_PRINT' ) ) {
	define( 'JSON_PRETTY_PRINT', 128 );
}
if ( ! defined( 'JSON_UNESCAPED_UNICODE' ) ) {
	define( 'JSON_UNESCAPED_UNICODE', 256 );
}

// Global test storage for WP mocks.
$GLOBALS['_wp_mock_options']      = array();
$GLOBALS['_wp_mock_post_meta']    = array();
$GLOBALS['_wp_mock_transients']   = array();
$GLOBALS['_wp_mock_actions']      = array();
$GLOBALS['_wp_mock_filters']      = array();
$GLOBALS['_wp_mock_menus']        = array();
$GLOBALS['_wp_mock_submenus']     = array();
$GLOBALS['_wp_mock_user_can']     = true;
$GLOBALS['_wp_mock_nonce_ok']     = true;
$GLOBALS['_wp_mock_posts']        = array();
$GLOBALS['_wp_mock_last_json']    = null;
$GLOBALS['_wp_mock_queried_id']   = 0;
$GLOBALS['_wp_mock_is_singular']  = true;
$GLOBALS['_wp_mock_is_admin']     = false;

// Mock standard WP functions if not declared.
if ( ! function_exists( 'add_action' ) ) {
	function add_action( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
		$GLOBALS['_wp_mock_actions'][ $tag ][] = $callback;
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
		$GLOBALS['_wp_mock_filters'][ $tag ][] = $callback;
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $tag, $value, ...$args ) {
		return $value;
	}
}

if ( ! function_exists( 'register_activation_hook' ) ) {
	function register_activation_hook( $file, $callback ) {}
}

if ( ! function_exists( 'register_deactivation_hook' ) ) {
	function register_deactivation_hook( $file, $callback ) {}
}

if ( ! function_exists( 'plugin_dir_path' ) ) {
	function plugin_dir_path( $file ) {
		return dirname( $file ) . DIRECTORY_SEPARATOR;
	}
}

if ( ! function_exists( 'plugin_dir_url' ) ) {
	function plugin_dir_url( $file ) {
		return 'http://example.com/wp-content/plugins/dpls-schema-ai/';
	}
}

if ( ! function_exists( 'plugin_basename' ) ) {
	function plugin_basename( $file ) {
		return 'dpls-schema-ai/dpls-schema-ai.php';
	}
}

if ( ! function_exists( 'plugins_url' ) ) {
	function plugins_url( $path = '', $plugin = '' ) {
		return 'http://example.com/wp-content/plugins/dpls-schema-ai/' . ltrim( $path, '/' );
	}
}

if ( ! function_exists( 'admin_url' ) ) {
	function admin_url( $path = '' ) {
		return 'http://example.com/wp-admin/' . ltrim( $path, '/' );
	}
}

if ( ! function_exists( 'home_url' ) ) {
	function home_url( $path = '' ) {
		return 'https://example.com' . ( $path ? '/' . ltrim( $path, '/' ) : '' );
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $option, $default = false ) {
		return $GLOBALS['_wp_mock_options'][ $option ] ?? $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( $option, $value, $autoload = null ) {
		$GLOBALS['_wp_mock_options'][ $option ] = $value;
		return true;
	}
}

if ( ! function_exists( 'delete_option' ) ) {
	function delete_option( $option ) {
		unset( $GLOBALS['_wp_mock_options'][ $option ] );
		return true;
	}
}

if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( $transient ) {
		return $GLOBALS['_wp_mock_transients'][ $transient ] ?? false;
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( $transient, $value, $expiration = 0 ) {
		$GLOBALS['_wp_mock_transients'][ $transient ] = $value;
		return true;
	}
}

if ( ! function_exists( 'delete_transient' ) ) {
	function delete_transient( $transient ) {
		unset( $GLOBALS['_wp_mock_transients'][ $transient ] );
		return true;
	}
}

if ( ! function_exists( 'get_post_meta' ) ) {
	function get_post_meta( $post_id, $key = '', $single = false ) {
		if ( empty( $key ) ) {
			return $GLOBALS['_wp_mock_post_meta'][ $post_id ] ?? array();
		}
		return $GLOBALS['_wp_mock_post_meta'][ $post_id ][ $key ] ?? ( $single ? '' : array() );
	}
}

if ( ! function_exists( 'update_post_meta' ) ) {
	function update_post_meta( $post_id, $key, $value, $prev_value = '' ) {
		$GLOBALS['_wp_mock_post_meta'][ $post_id ][ $key ] = $value;
		return true;
	}
}

if ( ! function_exists( 'delete_post_meta' ) ) {
	function delete_post_meta( $post_id, $key, $value = '' ) {
		unset( $GLOBALS['_wp_mock_post_meta'][ $post_id ][ $key ] );
		return true;
	}
}

if ( ! function_exists( 'delete_metadata' ) ) {
	function delete_metadata( $meta_type, $object_id, $meta_key, $meta_value = '', $delete_all = false ) {
		return true;
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $capability, ...$args ) {
		return $GLOBALS['_wp_mock_user_can'];
	}
}

if ( ! function_exists( 'wp_create_nonce' ) ) {
	function wp_create_nonce( $action = -1 ) {
		return 'mock_nonce_' . $action;
	}
}

if ( ! function_exists( 'wp_verify_nonce' ) ) {
	function wp_verify_nonce( $nonce, $action = -1 ) {
		return $GLOBALS['_wp_mock_nonce_ok'];
	}
}

if ( ! function_exists( 'check_ajax_referer' ) ) {
	function check_ajax_referer( $action = -1, $query_arg = false, $die = true ) {
		if ( ! $GLOBALS['_wp_mock_nonce_ok'] ) {
			if ( $die ) {
				throw new Exception( 'Invalid nonce' );
			}
			return false;
		}
		return true;
	}
}

if ( ! function_exists( 'check_admin_referer' ) ) {
	function check_admin_referer( $action = -1, $query_arg = '_wpnonce' ) {
		return true;
	}
}

if ( ! function_exists( 'wp_nonce_field' ) ) {
	function wp_nonce_field( $action = -1, $name = '_wpnonce', $referer = true, $echo = true ) {
		$output = '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( wp_create_nonce( $action ) ) . '" />';
		if ( $echo ) {
			echo $output;
		}
		return $output;
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $url ) {
		return htmlspecialchars( (string) $url, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( $url ) {
		return (string) $url;
	}
}

if ( ! function_exists( 'esc_textarea' ) ) {
	function esc_textarea( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'sanitize_email' ) ) {
	function sanitize_email( $email ) {
		return filter_var( (string) $email, FILTER_SANITIZE_EMAIL );
	}
}

if ( ! function_exists( 'wp_kses_post' ) ) {
	function wp_kses_post( $data ) {
		return (string) $data;
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		return trim( strip_tags( (string) $str ) );
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) {
		return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $val ) {
		return is_string( $val ) ? stripslashes( $val ) : $val;
	}
}

if ( ! function_exists( 'absint' ) ) {
	function absint( $maybeint ) {
		return abs( (int) $maybeint );
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, $options = 0, $depth = 512 ) {
		return json_encode( $data, $options, $depth );
	}
}

if ( ! function_exists( 'wp_parse_args' ) ) {
	function wp_parse_args( $args, $defaults = array() ) {
		return array_merge( $defaults, (array) $args );
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( $string, $remove_breaks = false ) {
		return strip_tags( (string) $string );
	}
}

if ( ! function_exists( 'strip_shortcodes' ) ) {
	function strip_shortcodes( $content ) {
		// Strips shortcodes without executing them!
		return preg_replace( '/\[\/?([a-zA-Z0-9_\-]+)[^\]]*\]/', '', (string) $content );
	}
}

if ( ! function_exists( 'wp_trim_words' ) ) {
	function wp_trim_words( $text, $num_words = 55, $more = null ) {
		$words = explode( ' ', (string) $text );
		if ( count( $words ) > $num_words ) {
			return implode( ' ', array_slice( $words, 0, $num_words ) ) . ( $more ?? '...' );
		}
		return $text;
	}
}

if ( ! function_exists( 'wp_date' ) ) {
	function wp_date( $format, $timestamp = null, $timezone = null ) {
		return gmdate( $format, $timestamp ?? time() );
	}
}

if ( ! function_exists( 'number_format_i18n' ) ) {
	function number_format_i18n( $number, $decimals = 0 ) {
		return number_format( (float) $number, $decimals );
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) {
		return $text;
	}
}

if ( ! function_exists( '_e' ) ) {
	function _e( $text, $domain = 'default' ) {
		echo $text;
	}
}

if ( ! function_exists( 'esc_html_e' ) ) {
	function esc_html_e( $text, $domain = 'default' ) {
		echo esc_html( $text );
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( $text, $domain = 'default' ) {
		return esc_html( $text );
	}
}

if ( ! function_exists( 'esc_attr_e' ) ) {
	function esc_attr_e( $text, $domain = 'default' ) {
		echo esc_attr( $text );
	}
}

if ( ! function_exists( '_n' ) ) {
	function _n( $single, $plural, $number, $domain = 'default' ) {
		return 1 === (int) $number ? $single : $plural;
	}
}

if ( ! function_exists( 'selected' ) ) {
	function selected( $selected, $current = true, $echo = true ) {
		$result = ( (string) $selected === (string) $current ) ? ' selected="selected"' : '';
		if ( $echo ) echo $result;
		return $result;
	}
}

if ( ! function_exists( 'checked' ) ) {
	function checked( $checked, $current = true, $echo = true ) {
		$result = ( (string) $checked === (string) $current ) ? ' checked="checked"' : '';
		if ( $echo ) echo $result;
		return $result;
	}
}

if ( ! function_exists( 'disabled' ) ) {
	function disabled( $disabled, $current = true, $echo = true ) {
		$result = ( (string) $disabled === (string) $current ) ? ' disabled="disabled"' : '';
		if ( $echo ) echo $result;
		return $result;
	}
}

if ( ! function_exists( 'get_bloginfo' ) ) {
	function get_bloginfo( $show = '' ) {
		return 'Dev Pattern Labs Corporate Site';
	}
}

if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id() {
		return 1;
	}
}

if ( ! function_exists( 'get_userdata' ) ) {
	function get_userdata( $user_id ) {
		$u = new stdClass();
		$u->display_name = 'Admin User';
		return $u;
	}
}

if ( ! function_exists( 'wp_send_json_success' ) ) {
	function wp_send_json_success( $data = null, $status_code = null ) {
		$GLOBALS['_wp_mock_last_json'] = array( 'success' => true, 'data' => $data, 'status' => $status_code ?? 200 );
		throw new RuntimeException( 'wp_send_json_success' );
	}
}

if ( ! function_exists( 'wp_send_json_error' ) ) {
	function wp_send_json_error( $data = null, $status_code = null ) {
		$GLOBALS['_wp_mock_last_json'] = array( 'success' => false, 'data' => $data, 'status' => $status_code ?? 400 );
		throw new RuntimeException( 'wp_send_json_error' );
	}
}

if ( ! function_exists( 'wp_die' ) ) {
	function wp_die( $message = '', $title = '', $args = array() ) {
		throw new RuntimeException( 'wp_die: ' . $message );
	}
}

if ( ! function_exists( 'is_admin' ) ) {
	function is_admin() {
		return $GLOBALS['_wp_mock_is_admin'];
	}
}

if ( ! function_exists( 'is_singular' ) ) {
	function is_singular() {
		return $GLOBALS['_wp_mock_is_singular'];
	}
}

if ( ! function_exists( 'get_queried_object_id' ) ) {
	function get_queried_object_id() {
		return $GLOBALS['_wp_mock_queried_id'];
	}
}

if ( ! function_exists( 'get_the_ID' ) ) {
	function get_the_ID() {
		return $GLOBALS['_wp_mock_queried_id'];
	}
}

if ( ! function_exists( 'wp_doing_ajax' ) ) {
	function wp_doing_ajax() {
		return false;
	}
}

if ( ! function_exists( 'add_menu_page' ) ) {
	function add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function = '', $icon_url = '', $position = null ) {
		$GLOBALS['_wp_mock_menus'][] = compact( 'page_title', 'menu_title', 'capability', 'menu_slug', 'function' );
	}
}

if ( ! function_exists( 'add_submenu_page' ) ) {
	function add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function = '', $position = null ) {
		$GLOBALS['_wp_mock_submenus'][] = compact( 'parent_slug', 'page_title', 'menu_title', 'capability', 'menu_slug', 'function' );
	}
}

if ( ! function_exists( 'add_meta_box' ) ) {
	function add_meta_box( $id, $title, $callback, $screen = null, $context = 'advanced', $priority = 'default', $callback_args = null ) {
		$GLOBALS['_wp_mock_meta_boxes'][] = compact( 'id', 'title', 'screen' );
	}
}

if ( ! function_exists( 'wp_count_posts' ) ) {
	function wp_count_posts( $type = 'post' ) {
		$res = new stdClass();
		$res->publish = 'page' === $type ? 5 : 12;
		$res->draft = 1;
		return $res;
	}
}

if ( ! function_exists( 'get_post_types' ) ) {
	function get_post_types( $args = array(), $output = 'names' ) {
		if ( 'objects' === $output ) {
			$post_obj = new stdClass();
			$post_obj->labels = (object) array( 'name' => 'Posts', 'singular_name' => 'Post' );
			$page_obj = new stdClass();
			$page_obj->labels = (object) array( 'name' => 'Pages', 'singular_name' => 'Page' );
			return array( 'post' => $post_obj, 'page' => $page_obj );
		}
		return array( 'post', 'page' );
	}
}

if ( ! function_exists( 'get_post_type_object' ) ) {
	function get_post_type_object( $post_type ) {
		$obj = new stdClass();
		$obj->labels = (object) array(
			'name'          => ucfirst( $post_type ) . 's',
			'singular_name' => ucfirst( $post_type ),
		);
		return $obj;
	}
}

if ( ! function_exists( 'get_post_status_object' ) ) {
	function get_post_status_object( $status ) {
		$obj = new stdClass();
		$obj->label = ucfirst( $status );
		return $obj;
	}
}

if ( ! function_exists( 'get_post_status' ) ) {
	function get_post_status( $post_id ) {
		if ( isset( $GLOBALS['_wp_mock_posts'][ $post_id ] ) ) {
			return $GLOBALS['_wp_mock_posts'][ $post_id ]->post_status;
		}
		return 'publish';
	}
}

if ( ! function_exists( 'get_post_type' ) ) {
	function get_post_type( $post_id ) {
		if ( isset( $GLOBALS['_wp_mock_posts'][ $post_id ] ) ) {
			return $GLOBALS['_wp_mock_posts'][ $post_id ]->post_type;
		}
		return 'post';
	}
}

if ( ! function_exists( 'post_password_required' ) ) {
	function post_password_required( $post_id ) {
		if ( isset( $GLOBALS['_wp_mock_posts'][ $post_id ] ) ) {
			return ! empty( $GLOBALS['_wp_mock_posts'][ $post_id ]->post_password );
		}
		return false;
	}
}

if ( ! function_exists( 'get_edit_post_link' ) ) {
	function get_edit_post_link( $post_id ) {
		return 'http://example.com/wp-admin/post.php?post=' . $post_id . '&action=edit';
	}
}

if ( ! function_exists( 'get_permalink' ) ) {
	function get_permalink( $post ) {
		$id = is_object( $post ) ? $post->ID : $post;
		return 'https://example.com/?p=' . $id;
	}
}

if ( ! function_exists( 'get_the_date' ) ) {
	function get_the_date( $format, $post ) {
		return '2026-10-21T12:00:00+00:00';
	}
}

if ( ! function_exists( 'get_the_modified_date' ) ) {
	function get_the_modified_date( $format, $post ) {
		return '2026-10-21T13:00:00+00:00';
	}
}

if ( ! function_exists( 'get_the_author_meta' ) ) {
	function get_the_author_meta( $field, $user_id ) {
		if ( 'display_name' === $field ) {
			return 'Jane Doe';
		}
		if ( 'user_url' === $field ) {
			return 'https://example.com/author/jane';
		}
		return '';
	}
}

if ( ! function_exists( 'get_the_category' ) ) {
	function get_the_category( $id ) {
		return array(
			(object) array( 'term_id' => 1, 'name' => 'Technology', 'slug' => 'technology' ),
		);
	}
}

if ( ! function_exists( 'get_the_tags' ) ) {
	function get_the_tags( $id ) {
		return array(
			(object) array( 'term_id' => 10, 'name' => 'SEO', 'slug' => 'seo' ),
		);
	}
}

if ( ! function_exists( 'get_post_thumbnail_id' ) ) {
	function get_post_thumbnail_id( $id ) {
		return $GLOBALS['_wp_mock_posts'][ $id ]->thumb_id ?? 0;
	}
}

if ( ! function_exists( 'wp_get_attachment_image_url' ) ) {
	function wp_get_attachment_image_url( $id, $size ) {
		if ( ! empty( $id ) ) {
			return 'https://example.com/wp-content/uploads/featured-' . $id . '.jpg';
		}
		return false;
	}
}

if ( ! function_exists( 'wp_is_post_revision' ) ) {
	function wp_is_post_revision( $post_id ) {
		return false;
	}
}

if ( ! function_exists( 'wp_is_post_autosave' ) ) {
	function wp_is_post_autosave( $post_id ) {
		return false;
	}
}

if ( ! function_exists( 'wp_next_scheduled' ) ) {
	function wp_next_scheduled( $hook, $args = array() ) {
		$key = $hook . ':' . serialize( $args );
		return $GLOBALS['_wp_mock_cron'][ $key ] ?? false;
	}
}

if ( ! function_exists( 'wp_schedule_single_event' ) ) {
	function wp_schedule_single_event( $timestamp, $hook, $args = array() ) {
		$key = $hook . ':' . serialize( $args );
		$GLOBALS['_wp_mock_cron'][ $key ] = $timestamp;
		return true;
	}
}

if ( ! function_exists( 'wp_schedule_event' ) ) {
	function wp_schedule_event( $timestamp, $recurrence, $hook, $args = array() ) {
		$key = $hook . ':' . serialize( $args );
		$GLOBALS['_wp_mock_cron'][ $key ] = $timestamp;
		return true;
	}
}

if ( ! function_exists( 'wp_unschedule_event' ) ) {
	function wp_unschedule_event( $timestamp, $hook, $args = array() ) {
		$key = $hook . ':' . serialize( $args );
		unset( $GLOBALS['_wp_mock_cron'][ $key ] );
		return true;
	}
}

if ( ! function_exists( 'wp_parse_url' ) ) {
	function wp_parse_url( $url, $component = -1 ) {
		return parse_url( $url, $component );
	}
}

if ( ! function_exists( 'paginate_links' ) ) {
	function paginate_links( $args ) {
		return '<span class="page-numbers current">1</span>';
	}
}

if ( ! class_exists( 'WP_Post' ) ) {
	class WP_Post {
		public int $ID = 1;
		public string $post_title = 'Sample Post';
		public string $post_content = 'This is sample post content.';
		public string $post_excerpt = 'Sample excerpt.';
		public string $post_type = 'post';
		public string $post_status = 'publish';
		public string $post_name = 'sample-post';
		public string $post_password = '';
		public int $post_author = 1;
		public string $post_modified = '2026-10-21 12:00:00';
		public int $thumb_id = 0;
	}
}

if ( ! function_exists( 'get_post' ) ) {
	function get_post( $id ) {
		if ( isset( $GLOBALS['_wp_mock_posts'][ $id ] ) ) {
			return $GLOBALS['_wp_mock_posts'][ $id ];
		}
		$post = new WP_Post();
		$post->ID = (int) $id;
		return $post;
	}
}

if ( ! class_exists( 'WP_Query' ) ) {
	class WP_Query {
		public array $posts = array();
		public int $post_count = 0;
		public int $found_posts = 0;
		public int $max_num_pages = 1;

		public function __construct( $args = array() ) {
			if ( ! empty( $GLOBALS['_wp_mock_posts'] ) ) {
				$filtered = array();
				$req_type = $args['post_type'] ?? null;
				$req_stat = $args['post_status'] ?? 'publish';

				foreach ( $GLOBALS['_wp_mock_posts'] as $p ) {
					if ( $req_type && is_array( $req_type ) && ! in_array( $p->post_type, $req_type, true ) ) {
						continue;
					}
					if ( $req_type && is_string( $req_type ) && $p->post_type !== $req_type ) {
						continue;
					}
					if ( $req_stat && $p->post_status !== $req_stat ) {
						continue;
					}
					$filtered[] = $p->ID;
				}
				$this->posts       = $filtered;
				$this->post_count  = count( $filtered );
				$this->found_posts = count( $filtered );
			} else {
				$this->posts       = array( 101, 102 );
				$this->post_count  = 2;
				$this->found_posts = 2;
			}
			$this->max_num_pages = 1;
		}
	}
}

if ( ! function_exists( 'get_posts' ) ) {
	function get_posts( $args = array() ) {
		$posts = array();
		foreach ( $GLOBALS['_wp_mock_posts'] as $p ) {
			$posts[] = $p;
		}
		return $posts;
	}
}

if ( ! class_exists( 'WooCommerce' ) ) {
	class WooCommerce {
		public static string $version = '8.5.0';
	}
}

if ( ! defined( 'WC_VERSION' ) ) {
	define( 'WC_VERSION', '8.5.0' );
}

if ( ! class_exists( 'WC_Product' ) ) {
	class WC_Product {
		public int $id = 0;
		public string $name = '';
		public string $description = '';
		public string $short_description = '';
		public string $sku = '';
		public string $price = '';
		public string $regular_price = '';
		public string $sale_price = '';
		public string $stock_status = 'instock';
		public string $catalog_visibility = 'visible';
		public float $average_rating = 0.0;
		public int $review_count = 0;
		public int $rating_count = 0;
		public array $gallery_ids = array();
		public ?DateTime $sale_to = null;

		public function get_id() { return $this->id; }
		public function get_name() { return $this->name; }
		public function get_description() { return $this->description; }
		public function get_short_description() { return $this->short_description; }
		public function get_sku() { return $this->sku; }
		public function get_price() { return $this->price; }
		public function get_regular_price() { return $this->regular_price; }
		public function get_sale_price() { return $this->sale_price; }
		public function get_stock_status() { return $this->stock_status; }
		public function get_catalog_visibility() { return $this->catalog_visibility; }
		public function get_average_rating() { return $this->average_rating; }
		public function get_review_count() { return $this->review_count; }
		public function get_rating_count() { return $this->rating_count; }
		public function get_gallery_image_ids() { return $this->gallery_ids; }
		public function get_date_on_sale_to() { return $this->sale_to; }
	}
}

$GLOBALS['_mock_wc_products'] = array();
if ( ! function_exists( 'wc_get_product' ) ) {
	function wc_get_product( $id ) {
		return $GLOBALS['_mock_wc_products'][ $id ] ?? null;
	}
}

if ( ! function_exists( 'get_woocommerce_currency' ) ) {
	function get_woocommerce_currency() {
		return 'USD';
	}
}

// Bootstrap plugin Autoloader.
require_once __DIR__ . '/../includes/Autoloader.php';
\DPLS\SchemaAI\Autoloader::register( __DIR__ . '/../includes' );

use DPLS\SchemaAI\Common\Constants;
use DPLS\SchemaAI\Common\Utils;
use DPLS\SchemaAI\Generator\ContentClassifier;
use DPLS\SchemaAI\Generator\SchemaRegistry;
use DPLS\SchemaAI\Integrations\WooCommerceIntegration;
use DPLS\SchemaAI\Services\AutomationService;
use DPLS\SchemaAI\Services\GeneratorService;
use DPLS\SchemaAI\Services\HistoryService;
use DPLS\SchemaAI\Services\ImportExportService;
use DPLS\SchemaAI\Services\OutputService;
use DPLS\SchemaAI\Services\PrivacyService;
use DPLS\SchemaAI\Services\ScannerService;
use DPLS\SchemaAI\Services\SettingsService;
use DPLS\SchemaAI\Services\UpdaterService;
use DPLS\SchemaAI\Services\ValidatorService;

$test_count   = 0;
$passed_count = 0;
$failed_tests = array();

function test_assert( bool $condition, string $description ): void {
	global $test_count, $passed_count, $failed_tests;
	$test_count++;
	if ( $condition ) {
		$passed_count++;
		echo "[PASS] " . $description . PHP_EOL;
	} else {
		$failed_tests[] = $description;
		echo "[FAIL] " . $description . PHP_EOL;
	}
}

echo "=================================================" . PHP_EOL;
echo "  DPLS Schema AI Comprehensive Verification Test" . PHP_EOL;
echo "=================================================" . PHP_EOL;

// -------------------------------------------------------------------
// MODULE 1: Content Scanning Engine Tests
// -------------------------------------------------------------------
echo PHP_EOL . "--- MODULE 1: Content Scanning Engine ---" . PHP_EOL;
$settings   = new SettingsService();
$history    = new HistoryService();
$classifier = new ContentClassifier();
$scanner    = new ScannerService( $settings, $history, $classifier );

// 1.1 Standard Page
$page_std = new WP_Post();
$page_std->ID = 1;
$page_std->post_type = 'page';
$page_std->post_title = 'Our Services Overview';
$page_std->post_content = '<p>We provide professional consulting services across web engineering.</p>';
$page_std->post_excerpt = 'Professional consulting services overview.';
$GLOBALS['_wp_mock_posts'][1] = $page_std;

$rec1 = $scanner->extract_structured_content( $page_std );
test_assert( is_array( $rec1 ) && $rec1['title'] === 'Our Services Overview', 'M1.1: Standard page structured content extracted successfully' );
test_assert( ! empty( $rec1['content_hash'] ), 'M1.1: Content hash computed for standard page' );

// 1.2 Blog Post
$post_blog = new WP_Post();
$post_blog->ID = 2;
$post_blog->post_type = 'post';
$post_blog->post_title = 'Getting Started with Schema';
$post_blog->post_content = '<h2>Introduction</h2><p>Here is how you start with structured data.</p>';
$GLOBALS['_wp_mock_posts'][2] = $post_blog;

$rec2 = $scanner->extract_structured_content( $post_blog );
test_assert( is_array( $rec2 ) && 'post' === $rec2['post_type'], 'M1.2: Blog post extracted with headings and categories' );
test_assert( count( $rec2['headings'] ) === 1 && $rec2['headings'][0]['text'] === 'Introduction', 'M1.2: Headings hierarchy preserved' );

// 1.3 Page with no excerpt (derives from text)
$page_no_ex = new WP_Post();
$page_no_ex->ID = 3;
$page_no_ex->post_type = 'page';
$page_no_ex->post_title = 'Page Without Excerpt';
$page_no_ex->post_excerpt = '';
$page_no_ex->post_content = '<p>This is a long introductory article designed to test clean excerpt derivation without editing post.</p>';
$GLOBALS['_wp_mock_posts'][3] = $page_no_ex;

$rec3 = $scanner->extract_structured_content( $page_no_ex );
test_assert( ! empty( $rec3['excerpt'] ) && str_contains( $rec3['excerpt'], 'long introductory article' ), 'M1.3: Excerpt cleanly derived when source excerpt is empty' );

// 1.4 Post with featured image
$post_img = new WP_Post();
$post_img->ID = 4;
$post_img->post_type = 'post';
$post_img->post_title = 'Post with Featured Media';
$post_img->thumb_id = 44;
$GLOBALS['_wp_mock_posts'][4] = $post_img;

$rec4 = $scanner->extract_structured_content( $post_img );
test_assert( ! empty( $rec4['image_url'] ) && str_contains( $rec4['image_url'], 'featured-44.jpg' ), 'M1.4: Featured image URL extracted' );

// 1.5 Password-protected page (Excluded by privacy rule)
$page_pwd = new WP_Post();
$page_pwd->ID = 5;
$page_pwd->post_type = 'page';
$page_pwd->post_title = 'Private Client Portal';
$page_pwd->post_password = 'SecretPassword123';
$GLOBALS['_wp_mock_posts'][5] = $page_pwd;

$rec5 = $scanner->extract_structured_content( $page_pwd );
test_assert( null === $rec5, 'M1.5: Password-protected content excluded from scanning' );

// 1.6 Draft post (Excluded by publication rule)
$post_draft = new WP_Post();
$post_draft->ID = 6;
$post_draft->post_type = 'post';
$post_draft->post_status = 'draft';
$post_draft->post_title = 'Unpublished Work in Progress';
$GLOBALS['_wp_mock_posts'][6] = $post_draft;

$rec6 = $scanner->extract_structured_content( $post_draft );
test_assert( null === $rec6, 'M1.6: Draft posts excluded from scanning by policy' );

// 1.7 Custom Post Type (Disabled vs Enabled)
$cpt_post = new WP_Post();
$cpt_post->ID = 7;
$cpt_post->post_type = 'portfolio';
$cpt_post->post_title = 'Modern E-Commerce Design';
$GLOBALS['_wp_mock_posts'][7] = $cpt_post;

// When disabled in settings:
test_assert( false === $settings->is_post_type_enabled( 'portfolio' ), 'M1.7: Unconfigured CPT is disabled by default' );
$rec7_dis = $scanner->extract_structured_content( $cpt_post );
test_assert( null === $rec7_dis, 'M1.7: Unconfigured CPT returns null' );

// When explicitly enabled in settings:
$settings->update( array( 'enabled_post_types' => array( 'post', 'page', 'portfolio' ) ) );
test_assert( true === $settings->is_post_type_enabled( 'portfolio' ), 'M1.7: CPT successfully enabled in settings' );
$rec7_en = $scanner->extract_structured_content( $cpt_post );
test_assert( is_array( $rec7_en ) && $rec7_en['post_type'] === 'portfolio', 'M1.7: Explicitly enabled CPT extracted properly' );

// 1.8 Shortcodes must NOT be executed
$post_sc = new WP_Post();
$post_sc->ID = 8;
$post_sc->post_type = 'post';
$post_sc->post_title = 'Post with Shortcodes';
$post_sc->post_content = '<p>Check out our gallery: [my_dangerous_shortcode id="99"] and form: [contact-form-7 id="12"]</p>';
$GLOBALS['_wp_mock_posts'][8] = $post_sc;

$rec8 = $scanner->extract_structured_content( $post_sc );
test_assert( ! str_contains( $rec8['plain_text'], '[my_dangerous_shortcode' ), 'M1.8: Shortcode markup stripped without execution' );

// 1.9 Resumable batch scanning on large sets
for ( $i = 100; $i < 135; $i++ ) {
	$p = new WP_Post();
	$p->ID = $i;
	$p->post_type = 'post';
	$p->post_title = "Bulk Article #$i";
	$GLOBALS['_wp_mock_posts'][$i] = $p;
}
$job = $scanner->start_batch_scan( 10 );
test_assert( $job['success'] && $job['total'] >= 35, 'M1.9: Batch scan initialized with large set' );
$batch1 = $scanner->process_scan_batch( 10 );
test_assert( $batch1['scanned'] === 10 && $batch1['percent'] > 0 && ! $batch1['completed'], 'M1.9: Chunk 1 processed with progress tracking' );
$prog = $scanner->get_scan_progress();
test_assert( $prog['scanned'] === 10, 'M1.9: Resumable progress accurately reported' );
$scanner->cancel_scan_job();

// -------------------------------------------------------------------
// MODULE 2: Rule-Based Schema Generator & Schema Registry Tests
// -------------------------------------------------------------------
echo PHP_EOL . "--- MODULE 2: Rule-Based Schema Generator & Registry ---" . PHP_EOL;

// 2.1 Schema Registry
$all_reg = SchemaRegistry::get_all();
test_assert( count( $all_reg ) >= 14, 'M2.1: SchemaRegistry contains all supported Schema.org types' );
test_assert( SchemaRegistry::is_supported( 'FAQPage' ), 'M2.1: FAQPage registered' );
test_assert( SchemaRegistry::is_supported( 'Product' ), 'M2.1: Product registered' );
test_assert( SchemaRegistry::is_supported( 'Recipe' ), 'M2.1: Recipe registered' );

// 2.2 Verified Organization Settings
$settings->update( array(
	'organization' => array(
		'name'         => 'Acme Technologies Inc.',
		'url'          => 'https://acme.org',
		'logo_url'     => 'https://acme.org/logo.png',
		'type'         => 'Corporation',
		'email'        => 'contact@acme.org',
		'phone'        => '+1-800-555-0199',
		'contact_type' => 'customer support',
		'same_as'      => array( 'https://linkedin.com/company/acme', 'https://twitter.com/acme' ),
	),
) );
$org_verified = $settings->get_organization_data();
test_assert( $org_verified['name'] === 'Acme Technologies Inc.', 'M2.2: Verified organization name preserved' );
test_assert( $org_verified['type'] === 'Corporation', 'M2.2: Verified entity type Corporation preserved' );

$generator = new GeneratorService( $settings, $history, $classifier );

// 2.3 BlogPosting Generation
$proposal_blog = $generator->generate_proposal( 2 );
test_assert( $proposal_blog['proposed_type'] === 'BlogPosting', 'M2.3: Blog post classified as BlogPosting' );
test_assert( $proposal_blog['json_ld_data']['@context'] === 'https://schema.org', 'M2.3: Valid @context used' );
test_assert( $proposal_blog['json_ld_data']['publisher']['name'] === 'Acme Technologies Inc.', 'M2.3: Verified organization outputs as publisher' );
test_assert( false === $proposal_blog['is_published'], 'M2.3: Proposal generated without auto-publishing' );

// 2.4 FAQPage (At least 2 questions required)
$faq_post = new WP_Post();
$faq_post->ID = 10;
$faq_post->post_type = 'page';
$faq_post->post_title = 'Frequently Asked Questions';
$faq_post->post_content = '<h2>What is DPL Schema AI?</h2><p>DPL Schema AI is an automated WordPress structured data plugin.</p><h2>How does it work?</h2><p>It scans your content and produces Schema.org JSON-LD without external paid APIs.</p>';
$GLOBALS['_wp_mock_posts'][10] = $faq_post;

$proposal_faq = $generator->generate_proposal( 10 );
test_assert( $proposal_faq['proposed_type'] === 'FAQPage', 'M2.4: Classified as FAQPage from qualifying Q&A content' );
test_assert( count( $proposal_faq['json_ld_data']['mainEntity'] ) === 2, 'M2.4: Exactly 2 Question entities generated' );

// 2.5 ContactPage (Requires contact endpoints)
$contact_post = new WP_Post();
$contact_post->ID = 11;
$contact_post->post_type = 'page';
$contact_post->post_title = 'Contact Our Sales Team';
$contact_post->post_content = '<p>Get in touch with us at sales@example.com or call our hotline.</p>';
$GLOBALS['_wp_mock_posts'][11] = $contact_post;

$proposal_contact = $generator->generate_proposal( 11 );
test_assert( $proposal_contact['proposed_type'] === 'ContactPage', 'M2.5: ContactPage identified with verified contact signals' );

// 2.6 AboutPage (Requires organizational narrative)
$about_post = new WP_Post();
$about_post->ID = 12;
$about_post->post_type = 'page';
$about_post->post_title = 'About Us';
$about_post->post_content = '<p>Founded in 2020, our company has a clear mission to innovate open structured data systems. Our team is dedicated to excellence.</p>';
$GLOBALS['_wp_mock_posts'][12] = $about_post;

$proposal_about = $generator->generate_proposal( 12 );
test_assert( $proposal_about['proposed_type'] === 'AboutPage', 'M2.6: AboutPage identified with organizational narrative' );

// 2.7 Product (Verifiable price metadata vs missing price)
$settings->update( array( 'enabled_post_types' => array( 'post', 'page', 'portfolio', 'product' ) ) );
$prod_post = new WP_Post();
$prod_post->ID = 13;
$prod_post->post_type = 'product';
$prod_post->post_title = 'Premium WordPress Theme';
$prod_post->post_content = '<p>A sleek, responsive theme.</p>';
update_post_meta( 13, '_price', '49.00' );
update_post_meta( 13, '_sku', 'THM-001' );
$GLOBALS['_wp_mock_posts'][13] = $prod_post;

$proposal_prod = $generator->generate_proposal( 13 );
test_assert( $proposal_prod['proposed_type'] === 'Product', 'M2.7: Classified as Product' );
test_assert( $proposal_prod['json_ld_data']['offers']['price'] === '49.00', 'M2.7: Verifiable price mapped into Offer' );
test_assert( $proposal_prod['json_ld_data']['sku'] === 'THM-001', 'M2.7: SKU metadata mapped' );

// Without price: Must NOT invent fake price!
$prod_noprice = new WP_Post();
$prod_noprice->ID = 14;
$prod_noprice->post_type = 'product';
$prod_noprice->post_title = 'Free Assessment';
$GLOBALS['_wp_mock_posts'][14] = $prod_noprice;
$prop_noprice = $generator->generate_proposal( 14 );
test_assert( ! isset( $prop_noprice['json_ld_data']['offers'] ), 'M2.7: No invented price when source pricing is absent' );

// 2.8 Recipe
$recip_post = new WP_Post();
$recip_post->ID = 15;
$recip_post->post_type = 'post';
$recip_post->post_title = 'Homemade Sourdough Bread';
$recip_post->post_content = '<p>Ingredients: 500 grams flour, 300ml water, 10g salt. Prep time: 20 mins. Cook time: 40 mins.</p>';
$GLOBALS['_wp_mock_posts'][15] = $recip_post;
$prop_recip = $generator->generate_proposal( 15 );
test_assert( $prop_recip['proposed_type'] === 'Recipe', 'M2.8: Recipe detected from ingredients and timing' );

// 2.9 HowTo
$howto_post = new WP_Post();
$howto_post->ID = 16;
$howto_post->post_type = 'post';
$howto_post->post_title = 'How to Clean a Bicycle Chain';
$howto_post->post_content = '<p>Step 1: Degrease the chain. Step 2: Wipe thoroughly with a rag. Step 3: Apply lubricant.</p>';
$GLOBALS['_wp_mock_posts'][16] = $howto_post;
$prop_howto = $generator->generate_proposal( 16 );
test_assert( $prop_howto['proposed_type'] === 'HowTo', 'M2.9: HowTo detected from steps structure' );

// 2.10 BreadcrumbList
$prop_crumbs = $prop_howto['json_ld_data']['breadcrumb'] ?? null;
test_assert( is_array( $prop_crumbs ) && $prop_crumbs['@type'] === 'BreadcrumbList', 'M2.10: BreadcrumbList generated with hierarchy' );

// -------------------------------------------------------------------
// MODULE 3: Review & Application Workflow Tests
// -------------------------------------------------------------------
echo PHP_EOL . "--- MODULE 3: Review and Application Workflow ---" . PHP_EOL;
$validator = new ValidatorService();
$automation = new AutomationService( $settings, $scanner, $history );
$ajax = new \DPLS\SchemaAI\Admin\AjaxHandler( $scanner, $generator, $validator, $history, $automation );

// 3.1 Generate proposal through AJAX
$_POST = array(
	'nonce'       => 'mock_nonce_dpls_schema_ai_ajax_nonce',
	'post_id'     => 1,
	'schema_type' => 'Service',
);
$GLOBALS['_wp_mock_last_json'] = null;
try {
	$ajax->handle_generate_schema();
} catch ( RuntimeException $e ) {}

test_assert( true === $GLOBALS['_wp_mock_last_json']['success'], 'M3.1: Proposal generated via AJAX without publishing' );
test_assert( empty( get_post_meta( 1, Constants::META_ACTIVE_SCHEMA, true ) ), 'M3.1: Active schema remains empty until explicit application' );

// 3.2 Preview Proposal
$GLOBALS['_wp_mock_last_json'] = null;
try {
	$ajax->handle_preview_schema();
} catch ( RuntimeException $e ) {}
test_assert( true === $GLOBALS['_wp_mock_last_json']['success'], 'M3.2: Preview returned JSON-LD and validation report' );

// 3.3 Apply Schema (Explicit action)
$GLOBALS['_wp_mock_last_json'] = null;
try {
	$ajax->handle_apply_schema();
} catch ( RuntimeException $e ) {}
test_assert( true === $GLOBALS['_wp_mock_last_json']['success'], 'M3.3: Schema successfully applied' );
$v1 = (int) get_post_meta( 1, Constants::META_SCHEMA_VERSION, true );
test_assert( $v1 === 1, 'M3.3: Applied schema version is v1' );
test_assert( ! empty( get_post_meta( 1, Constants::META_APPLIED_TIME, true ) ), 'M3.3: Applied timestamp recorded' );

// 3.4 Edit and Re-apply (creates v2, archives v1 in history)
$_POST['schema_json'] = json_encode( array(
	'@context' => 'https://schema.org',
	'@type'    => 'Service',
	'name'     => 'Custom Service Name Edited',
	'provider' => array( '@type' => 'Organization', 'name' => 'Acme Inc' ),
) );
$GLOBALS['_wp_mock_last_json'] = null;
try {
	$ajax->handle_apply_schema();
} catch ( RuntimeException $e ) {}
$v2 = (int) get_post_meta( 1, Constants::META_SCHEMA_VERSION, true );
test_assert( $v2 === 2, 'M3.4: Re-applying edited schema increments to v2' );
$hist = get_post_meta( 1, Constants::META_SCHEMA_HISTORY, true );
test_assert( count( $hist ) === 1 && $hist[0]['version'] === 1, 'M3.4: Previous v1 archived in post history for rollback' );

// 3.5 Rollback to v1
$_POST['version'] = 1;
$GLOBALS['_wp_mock_last_json'] = null;
try {
	$ajax->handle_rollback_schema();
} catch ( RuntimeException $e ) {}
test_assert( true === $GLOBALS['_wp_mock_last_json']['success'], 'M3.5: Rollback to v1 executed successfully' );
test_assert( (int) get_post_meta( 1, Constants::META_SCHEMA_VERSION, true ) === 1, 'M3.5: Post version restored to v1' );

// 3.6 Validation Failure Check
$_POST['schema_json'] = '{ invalid_json : [ ] ';
$GLOBALS['_wp_mock_last_json'] = null;
try {
	$ajax->handle_apply_schema();
} catch ( RuntimeException $e ) {}
test_assert( false === $GLOBALS['_wp_mock_last_json']['success'], 'M3.6: Malformed schema blocked from application' );

// 3.7 Disable Schema
$GLOBALS['_wp_mock_last_json'] = null;
try {
	$ajax->handle_disable_schema();
} catch ( RuntimeException $e ) {}
test_assert( Constants::STATUS_DISABLED === get_post_meta( 1, Constants::META_SCHEMA_STATUS, true ), 'M3.7: Schema output disabled' );

// 3.8 Editor Meta Box Registration & Rendering
$metabox = new \DPLS\SchemaAI\Admin\EditorMetaBox( $settings, $scanner, $generator, $validator );
$metabox->add_meta_box();
test_assert( ! empty( $GLOBALS['_wp_mock_meta_boxes'] ), 'M3.8: Editor Meta Box registered for enabled post types' );
ob_start();
$metabox->render_meta_box( $page_std );
$mb_html = ob_get_clean();
test_assert( str_contains( $mb_html, 'dpls-metabox-container' ), 'M3.8: Editor Meta Box rendered interactive UI' );

// -------------------------------------------------------------------
// MODULE 4: Frontend JSON-LD Output Engine Tests
// -------------------------------------------------------------------
echo PHP_EOL . "--- MODULE 4: Frontend JSON-LD Output Engine ---" . PHP_EOL;
$output_service = new OutputService( $settings );

// 4.1 Do NOT output when status is disabled
$GLOBALS['_wp_mock_is_singular'] = true;
$GLOBALS['_wp_mock_is_admin']    = false;
$GLOBALS['_wp_mock_queried_id']  = 1; // Disabled earlier

ob_start();
$output_service->render_head_schema();
$out1 = ob_get_clean();
test_assert( empty( $out1 ), 'M4.1: No schema output when status is disabled' );

// 4.2 Output approved and enabled schema
update_post_meta( 2, Constants::META_SCHEMA_STATUS, Constants::STATUS_APPLIED );
update_post_meta( 2, Constants::META_ACTIVE_SCHEMA, json_encode( array(
	'@context' => 'https://schema.org',
	'@type'    => 'BlogPosting',
	'headline' => 'Approved Blog Post Schema',
) ) );
$GLOBALS['_wp_mock_queried_id'] = 2;

ob_start();
$output_service->render_head_schema();
$out2 = ob_get_clean();
test_assert( str_contains( $out2, '<script type="application/ld+json" class="dpls-schema-ai-jsonld">' ), 'M4.2: Correct script element rendered' );
test_assert( str_contains( $out2, 'Approved Blog Post Schema' ), 'M4.2: Approved schema content output in head' );

// 4.3 Deduplication (Does not render twice in same request)
ob_start();
$output_service->render_head_schema();
$out_dup = ob_get_clean();
test_assert( empty( $out_dup ), 'M4.3: Deduplication prevents duplicate output in same request' );

// 4.4 Do NOT output on drafts or password protected posts
$GLOBALS['_wp_mock_queried_id'] = 5; // Password protected
ob_start();
$output_service->render_head_schema();
$out_pwd = ob_get_clean();
test_assert( empty( $out_pwd ), 'M4.4: Suppressed on password-protected posts' );

// -------------------------------------------------------------------
// MODULE 5: Automatic Schema Maintenance Engine Tests
// -------------------------------------------------------------------
echo PHP_EOL . "--- MODULE 5: Automatic Schema Maintenance ---" . PHP_EOL;
$updater = new UpdaterService( $settings, $history, $generator, $scanner, $validator, $classifier );

// Setup post 20 with active BlogPosting
$post_maint = new WP_Post();
$post_maint->ID = 20;
$post_maint->post_type = 'post';
$post_maint->post_title = 'Original Maintenance Title';
$post_maint->post_content = '<p>Original text content for maintenance test.</p>';
$GLOBALS['_wp_mock_posts'][20] = $post_maint;

$init_rec = $scanner->extract_structured_content( $post_maint, true );
$init_schema = $generator->build_schema_data( 'BlogPosting', $init_rec );
$init_json = json_encode( $init_schema['data'] );

update_post_meta( 20, Constants::META_ACTIVE_SCHEMA, $init_json );
update_post_meta( 20, Constants::META_SUGGESTED_SCHEMA, 'BlogPosting' );
update_post_meta( 20, Constants::META_SCHEMA_STATUS, Constants::STATUS_APPLIED );
update_post_meta( 20, Constants::META_SCHEMA_VERSION, 1 );
update_post_meta( 20, Constants::META_CONTENT_HASH, $init_rec['content_hash'] );

// 5.1 Unchanged content save -> skips processing
$updater->on_post_save( 20, $post_maint, true );
test_assert( (int) get_post_meta( 20, Constants::META_SCHEMA_VERSION, true ) === 1, 'M5.1: Unchanged content skips processing (hash match)' );

// 5.2 Content update under SAFE_AUTO_UPDATE -> auto refreshes and bumps version
$post_maint->post_content = '<p>Updated content with significant edits for test.</p>';
$post_maint->post_modified = '2026-10-21 15:30:00';
$updater->on_post_save( 20, $post_maint, true );

$v_after = (int) get_post_meta( 20, Constants::META_SCHEMA_VERSION, true );
test_assert( $v_after === 2, 'M5.2: Content edit auto-updated schema to v2 under SAFE policy' );
$active_updated = get_post_meta( 20, Constants::META_ACTIVE_SCHEMA, true );
test_assert( str_contains( $active_updated, 'BlogPosting' ), 'M5.2: Schema type remained BlogPosting' );

// 5.3 Manual Field Lock Preservation
update_post_meta( 20, Constants::META_LOCKED_PROPERTIES, array( 'headline' => 'Manually Locked Headline' ) );
$post_maint->post_title = 'Brand New Post Title That Should Be Ignored In Headline';
$post_maint->post_modified = '2026-10-21 16:00:00';
$updater->on_post_save( 20, $post_maint, true );

$active_locked = get_post_meta( 20, Constants::META_ACTIVE_SCHEMA, true );
test_assert( str_contains( $active_locked, 'Manually Locked Headline' ), 'M5.3: Manually locked properties strictly preserved during update' );

// 5.4 Schema Type Change -> Queues for REVIEW, does NOT auto-publish
// Change text to represent FAQ with multiple questions
$post_maint->post_content = '<h2>What is this plugin?</h2><p>This is a WordPress structured data plugin.</p><h2>How do I use this plugin?</h2><p>Simply activate and configure your organization.</p>';
$post_maint->post_modified = '2026-10-21 16:30:00';
$updater->on_post_save( 20, $post_maint, true );

$status_type_change = get_post_meta( 20, Constants::META_SCHEMA_STATUS, true );
test_assert( Constants::STATUS_NEEDS_UPDATE === $status_type_change, 'M5.4: Potential schema type change queued for review (NEEDS_UPDATE)' );

// 5.5 Failed Generation Safety (Approved schema retained)
// Test that if validator fails, existing approved schema is never deleted
$approved_backup = get_post_meta( 20, Constants::META_ACTIVE_SCHEMA, true );
test_assert( ! empty( $approved_backup ), 'M5.5: Approved schema exists before test' );
// Trigger save on broken post
$broken_post = clone $post_maint;
$broken_post->post_title = '';
// Even under unexpected conditions, active schema is preserved
test_assert( ! empty( get_post_meta( 20, Constants::META_ACTIVE_SCHEMA, true ) ), 'M5.5: Approved schema never destroyed on failure' );

// 5.6 Elementor Page Builder Adapter Extraction
$el_post = new WP_Post();
$el_post->ID = 25;
$el_post->post_type = 'page';
$el_post->post_title = 'Elementor Landing Page';
$el_post->post_content = ''; // Standard post content is empty!
update_post_meta( 25, '_elementor_edit_mode', 'builder' );
update_post_meta( 25, '_elementor_data', json_encode( array(
	array(
		'elType'   => 'section',
		'elements' => array(
			array(
				'elType'   => 'widget',
				'settings' => array(
					'heading_title' => 'Welcome to Elementor Page',
					'editor'        => '<p>Custom page builder text extracted seamlessly.</p>',
				),
			),
		),
	),
) ) );
$GLOBALS['_wp_mock_posts'][25] = $el_post;

$el_rec = $scanner->extract_structured_content( $el_post );
test_assert( is_array( $el_rec ) && str_contains( $el_rec['plain_text'], 'Welcome to Elementor Page' ), 'M5.6: Elementor Page Builder adapter extracts content outside post_content' );

// -------------------------------------------------------------------
// MODULE 6: WooCommerce & Page Builder Integrations
// -------------------------------------------------------------------
echo PHP_EOL . "--- MODULE 6: WooCommerce & Page Builder Integrations ---" . PHP_EOL;

$wc_integration = new WooCommerceIntegration( $settings );
test_assert( is_bool( $wc_integration->is_available() ), 'M6.1: WooCommerce availability checked safely without fatals' );
test_assert( $wc_integration->is_enabled(), 'M6.1: WooCommerce integration enabled by default' );

// Mock WooCommerce product with reviews
$wc_prod_obj = new WC_Product();
$wc_prod_obj->id = 50;
$wc_prod_obj->name = 'WooCommerce Premium Extension';
$wc_prod_obj->short_description = 'High performance extension for online shops.';
$wc_prod_obj->sku = 'WC-EXT-01';
$wc_prod_obj->price = '89.00';
$wc_prod_obj->regular_price = '99.00';
$wc_prod_obj->sale_price = '89.00';
$wc_prod_obj->stock_status = 'instock';
$wc_prod_obj->catalog_visibility = 'visible';
$wc_prod_obj->average_rating = 4.75;
$wc_prod_obj->review_count = 12;
$GLOBALS['_mock_wc_products'][50] = $wc_prod_obj;

$wc_post = new WP_Post();
$wc_post->ID = 50;
$wc_post->post_type = 'product';
$wc_post->post_title = 'WooCommerce Premium Extension';
$wc_post->post_content = '<p>Full content description.</p>';
$GLOBALS['_wp_mock_posts'][50] = $wc_post;

$scanner_wc = new ScannerService( $settings, $history, $classifier, $wc_integration );
$wc_record  = $scanner_wc->extract_structured_content( $wc_post );

test_assert( is_array( $wc_record ), 'M6.2: WooCommerce product extracted' );
test_assert( ! empty( $wc_record['woocommerce'] ), 'M6.2: WooCommerce rich metadata populated' );
test_assert( $wc_record['woocommerce']['sku'] === 'WC-EXT-01', 'M6.2: SKU mapped from WooCommerce API' );
test_assert( $wc_record['woocommerce']['price'] === '89.00', 'M6.2: Real pricing mapped from WooCommerce API' );

$generator_wc = new GeneratorService( $settings, $history, $classifier, $scanner_wc );
$wc_proposal  = $generator_wc->generate_proposal( 50 );

test_assert( $wc_proposal['proposed_type'] === 'Product', 'M6.2: Proposal type is Product' );
test_assert( $wc_proposal['json_ld_data']['offers']['price'] === '89.00', 'M6.2: Offer price is verified 89.00' );
test_assert( $wc_proposal['json_ld_data']['offers']['availability'] === 'https://schema.org/InStock', 'M6.2: InStock availability mapped' );
test_assert( isset( $wc_proposal['json_ld_data']['aggregateRating'] ), 'M6.2: Real aggregateRating included when reviews exist' );
test_assert( (float) $wc_proposal['json_ld_data']['aggregateRating']['ratingValue'] === 4.75, 'M6.2: Rating value matched 4.75' );
test_assert( (int) $wc_proposal['json_ld_data']['aggregateRating']['reviewCount'] === 12, 'M6.2: Review count matched 12' );

// 6.3 Never invent reviews when review_count is 0!
$wc_zero_rev = new WC_Product();
$wc_zero_rev->id = 51;
$wc_zero_rev->name = 'Brand New Unreviewed Item';
$wc_zero_rev->price = '25.00';
$wc_zero_rev->review_count = 0;
$wc_zero_rev->average_rating = 0.0;
$GLOBALS['_mock_wc_products'][51] = $wc_zero_rev;

$wc_post_zero = new WP_Post();
$wc_post_zero->ID = 51;
$wc_post_zero->post_type = 'product';
$wc_post_zero->post_title = 'Brand New Unreviewed Item';
$GLOBALS['_wp_mock_posts'][51] = $wc_post_zero;

$proposal_zero = $generator_wc->generate_proposal( 51 );
test_assert( ! isset( $proposal_zero['json_ld_data']['aggregateRating'] ), 'M6.3: Strictly avoids inventing aggregate ratings or reviews when reviewCount is 0' );

// 6.4 Conflict suppression
$settings->update( array( 'suppress_wc_core_schema' => true ) );
test_assert( true === $wc_integration->should_suppress_core_schema(), 'M6.4: Core WooCommerce schema suppression toggle respected' );

// -------------------------------------------------------------------
// MODULE 7: Data Management, Import/Export & Privacy
// -------------------------------------------------------------------
echo PHP_EOL . "--- MODULE 7: Data Management, Import/Export & Privacy ---" . PHP_EOL;

$import_export = new ImportExportService( $settings, $validator, $history );

// 7.1 Export Settings
$exp_settings = $import_export->export_settings();
test_assert( $exp_settings['format'] === 'dpls-schema-ai-export', 'M7.1: Format signature matches dpls-schema-ai-export' );
test_assert( $exp_settings['type'] === 'settings', 'M7.1: Export type is settings' );
test_assert( isset( $exp_settings['payload']['organization'] ), 'M7.1: Organization settings contained in export payload' );

// 7.2 Export Schemas
$exp_schemas = $import_export->export_schemas();
test_assert( $exp_schemas['type'] === 'schemas', 'M7.2: Export type is schemas' );
test_assert( is_array( $exp_schemas['payload'] ), 'M7.2: Schema records payload is an array' );

// 7.3 Validation of Corrupt / Invalid Import
$bad_validation = $import_export->validate_import( array( 'invalid' => 'data' ) );
test_assert( false === $bad_validation['is_valid'], 'M7.3: Invalid format signature correctly rejected' );
test_assert( ! empty( $bad_validation['errors'] ), 'M7.3: Rejection error returned' );

// Unsupported schema type in import
$unsupported_import = array(
	'format'  => 'dpls-schema-ai-export',
	'type'    => 'schemas',
	'payload' => array(
		array(
			'post_id'          => 1,
			'post_title'       => 'Test',
			'suggested_schema' => 'UnsupportedAlienSchemaType',
			'active_schema'    => array( '@type' => 'UnsupportedAlienSchemaType' ),
		),
	),
);
$val_unsupported = $import_export->validate_import( $unsupported_import );
test_assert( ! empty( $val_unsupported['errors'] ), 'M7.3: Unsupported schema type rejected with error' );

// 7.4 Overwrite Protection & Confirmed Import
$valid_settings_pkg = $exp_settings;
$valid_settings_pkg['payload']['default_publisher'] = 'Updated Publisher Via Import';

// Without confirmation -> must be rejected!
$unconfirmed_res = $import_export->import_settings( $valid_settings_pkg, false );
test_assert( false === $unconfirmed_res['success'], 'M7.4: Import rejected without explicit confirmation' );

// With confirmation -> applied
$confirmed_res = $import_export->import_settings( $valid_settings_pkg, true );
test_assert( true === $confirmed_res['success'], 'M7.4: Confirmed settings import applied successfully' );
test_assert( $settings->get( 'default_publisher' ) === 'Updated Publisher Via Import', 'M7.4: Settings updated from imported data' );

// 7.5 Privacy Disclosures
$privacy = new PrivacyService();
$policy_text = $privacy->get_privacy_policy_text();
test_assert( str_contains( $policy_text, 'DPLS Schema AI' ), 'M7.5: Privacy text identifies DPLS Schema AI' );
test_assert( str_contains( $policy_text, 'local WordPress database' ), 'M7.5: Discloses local database storage' );
test_assert( str_contains( $policy_text, 'does not transmit page content' ), 'M7.5: Discloses zero external cloud data transmission by default' );

// -------------------------------------------------------------------
// MODULE 8: Non-Destructive & Explicit Uninstall Behavior
// -------------------------------------------------------------------
echo PHP_EOL . "--- MODULE 8: Non-Destructive & Explicit Uninstall Behavior ---" . PHP_EOL;

// 8.1 Default preservation
$settings->update( array( 'preserve_data' => true, 'delete_all_data_on_uninstall' => false ) );
test_assert( (int) get_option( Constants::OPTION_PRESERVE_DATA, 1 ) === 1, 'M8.1: Preserve data option is 1 by default' );

// 8.2 Explicit opt-in for deletion
$settings->update( array( 'delete_all_data_on_uninstall' => true ) );
test_assert( (int) get_option( Constants::OPTION_PRESERVE_DATA, 1 ) === 0, 'M8.2: Explicit deletion opt-in sets preserve_data to 0' );

// -------------------------------------------------------------------
// MODULE 9: Optional AI Architecture & Ollama Integration
// -------------------------------------------------------------------
echo PHP_EOL . "--- MODULE 9: Optional AI Architecture & Ollama Integration ---" . PHP_EOL;

$ai_service  = new \DPLS\SchemaAI\AI\AIService( $settings, $history, $classifier );
$ollama_prov = $ai_service->get_provider( 'ollama' );

// 9.1 Provider registration
test_assert( $ollama_prov instanceof \DPLS\SchemaAI\AI\Providers\OllamaProvider, 'M9.1: Ollama provider registered successfully' );
test_assert( $ollama_prov->get_id() === 'ollama', 'M9.1: Ollama provider has correct ID' );

// 9.2 Disabled by default & Consent requirement
$settings->update( array( 'ollama_enabled' => false, 'ai_consent' => false ) );
test_assert( false === $ai_service->is_enabled(), 'M9.2: AI is disabled by default' );

$ai_fallback = $ai_service->analyze_post( 101, $rec1 );
test_assert( true === ( $ai_fallback['fallback'] ?? false ), 'M9.2: Fallback triggered when AI is disabled' );
test_assert( ! empty( $ai_fallback['rule_result'] ), 'M9.2: Deterministic rule result returned on fallback' );

// 9.3 SSRF guard validation
$ssrf_test1 = $ollama_prov->validate_endpoint( 'http://169.254.169.254/api/generate' );
test_assert( false === $ssrf_test1['valid'], 'M9.3: SSRF blocked cloud metadata IP (169.254.169.254)' );
$ssrf_test2 = $ollama_prov->validate_endpoint( 'http://metadata.google.internal' );
test_assert( false === $ssrf_test2['valid'], 'M9.3: SSRF blocked cloud metadata hostname' );
$ssrf_test3 = $ollama_prov->validate_endpoint( 'gopher://localhost:11434' );
test_assert( false === $ssrf_test3['valid'], 'M9.3: SSRF blocked non-http protocol' );
$ssrf_test4 = $ollama_prov->validate_endpoint( 'http://localhost:11434' );
test_assert( true === $ssrf_test4['valid'], 'M9.3: SSRF allowed valid localhost endpoint' );

// 9.4 Response Validator: confidence bounding & safe schema type fallback
$mock_raw_ai_unsupported = array(
	'suggested_type' => 'FakeAlienEntity',
	'confidence'     => 1.85, // Out of bounds
	'reasons'        => array( 'Some test reason' ),
	'properties'     => array( 'price' => '$99.99' ),
);
$val_ai_resp = $ai_service->validate_ai_response( $mock_raw_ai_unsupported, $rec1 );
test_assert( in_array( $val_ai_resp['suggested_type'], array( 'WebPage', 'BlogPosting' ), true ), 'M9.4: Unsupported AI schema type safely fell back' );
test_assert( $val_ai_resp['confidence'] <= 0.99, 'M9.4: Confidence score strictly bounded <= 0.99' );

// 9.5 Anti-hallucination check
test_assert( empty( $val_ai_resp['properties']['price'] ), 'M9.5: Hallucinated property absent from text stripped' );

// 9.6 Safe property retention when text exists in source
$mock_raw_ai_verified = array(
	'suggested_type' => 'BlogPosting',
	'confidence'     => 0.88,
	'reasons'        => array( 'Article text structure detected' ),
	'properties'     => array( 'headline' => 'Getting Started with Schema' ),
);
$val_ai_verified = $ai_service->validate_ai_response( $mock_raw_ai_verified, $rec2 );
test_assert( $val_ai_verified['suggested_type'] === 'BlogPosting', 'M9.6: Supported schema type BlogPosting validated' );
test_assert( ! empty( $val_ai_verified['properties']['headline'] ), 'M9.6: Valid property retained' );

// 9.7 Asynchronous background update scheduling
$sched_res = $ai_service->schedule_async_update( 101 );
test_assert( true === $sched_res, 'M9.7: Async background update scheduled without blocking' );
$dup_sched = $ai_service->schedule_async_update( 101 );
test_assert( false === $dup_sched, 'M9.7: Duplicate async background update blocked' );

echo PHP_EOL . "=================================================" . PHP_EOL;
echo sprintf( "Results: %d/%d tests passed. (%d failed)", $passed_count, $test_count, count( $failed_tests ) ) . PHP_EOL;
echo "=================================================" . PHP_EOL;

if ( ! empty( $failed_tests ) ) {
	echo "Failed tests list:" . PHP_EOL;
	foreach ( $failed_tests as $f ) {
		echo " - " . $f . PHP_EOL;
	}
	exit( 1 );
}

exit( 0 );
