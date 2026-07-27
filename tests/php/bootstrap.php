<?php
/**
 * Minimal WordPress compatibility layer for pure theme unit tests.
 *
 * @package TwentyTwentyFiveChild
 */

define( 'MINUTE_IN_SECONDS', 60 );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'DAY_IN_SECONDS', 86400 );
define( 'WEEK_IN_SECONDS', 604800 );
define( 'YEAR_IN_SECONDS', 31536000 );
define( 'COOKIEPATH', '/' );
define( 'COOKIE_DOMAIN', '' );

$GLOBALS['child_test_options']    = array();
$GLOBALS['child_test_transients'] = array();
$GLOBALS['child_test_scheduled']  = array();
$GLOBALS['child_test_post_status'] = array();
$GLOBALS['child_test_response']   = array(
	'response' => array( 'code' => 200 ),
	'headers'  => array(),
	'body'     => '{}',
);

if ( ! class_exists( 'Child_Test_WPDB' ) ) {
	class Child_Test_WPDB {
		public string $prefix = 'wp_';
		public string $last_error = '';
		public array $likes = array();

		public function prepare( string $query, ...$args ): string {
			foreach ( $args as $arg ) {
				$replacement = is_int( $arg ) ? (string) $arg : "'" . addslashes( (string) $arg ) . "'";
				$query       = preg_replace( '/%[dis]/', $replacement, $query, 1 ) ?? $query;
			}

			return $query;
		}

		public function get_var( string $query ) {
			if ( str_contains( $query, 'COUNT(*)' ) && preg_match( '/post_id = (\d+)/', $query, $matches ) ) {
				$post_id = (int) $matches[1];
				return count( array_filter( $this->likes, static fn( array $like ): bool => $like['post_id'] === $post_id ) );
			}

			if (
				str_contains( $query, 'SELECT 1' )
				&& preg_match( "/post_id = (\d+) AND visitor_hash = '([^']+)'/", $query, $matches )
			) {
				foreach ( $this->likes as $like ) {
					if ( $like['post_id'] === (int) $matches[1] && $like['visitor_hash'] === $matches[2] ) {
						return '1';
					}
				}
			}

			return null;
		}

		public function insert( string $table, array $data ): int|false {
			foreach ( $this->likes as $like ) {
				if ( $like === $data ) {
					return false;
				}
			}

			$this->likes[] = $data;
			return 1;
		}

		public function delete( string $table, array $data ): int|false {
			foreach ( $this->likes as $index => $like ) {
				if ( $like === $data ) {
					unset( $this->likes[ $index ] );
					$this->likes = array_values( $this->likes );
					return 1;
				}
			}

			return 0;
		}
	}
}

$GLOBALS['wpdb'] = new Child_Test_WPDB();

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		public function __construct(
			public string $code = '',
			public string $message = '',
			public mixed $data = null
		) {
		}
	}
}

if ( ! function_exists( '__' ) ) {
	function __( string $text ): string {
		return $text;
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $value ): string {
		return (string) json_encode( $value );
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( string $key, $default = false ) {
		return $GLOBALS['child_test_options'][ $key ] ?? $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( string $key, $value, bool $autoload = true ): bool {
		$GLOBALS['child_test_options'][ $key ] = $value;
		return true;
	}
}

if ( ! function_exists( 'add_option' ) ) {
	function add_option( string $key, $value ): bool {
		if ( array_key_exists( $key, $GLOBALS['child_test_options'] ) ) {
			return false;
		}

		$GLOBALS['child_test_options'][ $key ] = $value;
		return true;
	}
}

if ( ! function_exists( 'delete_option' ) ) {
	function delete_option( string $key ): bool {
		unset( $GLOBALS['child_test_options'][ $key ] );
		return true;
	}
}

if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( string $key ) {
		return $GLOBALS['child_test_transients'][ $key ] ?? false;
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( string $key, $value ): bool {
		$GLOBALS['child_test_transients'][ $key ] = $value;
		return true;
	}
}

if ( ! function_exists( 'delete_transient' ) ) {
	function delete_transient( string $key ): bool {
		unset( $GLOBALS['child_test_transients'][ $key ] );
		return true;
	}
}

if ( ! function_exists( 'wp_safe_remote_get' ) ) {
	function wp_safe_remote_get() {
		return $GLOBALS['child_test_response'];
	}
}

if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) {
	function wp_remote_retrieve_response_code( array $response ): int {
		return (int) ( $response['response']['code'] ?? 0 );
	}
}

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
	function wp_remote_retrieve_body( array $response ): string {
		return (string) ( $response['body'] ?? '' );
	}
}

if ( ! function_exists( 'wp_remote_retrieve_header' ) ) {
	function wp_remote_retrieve_header( array $response, string $header ): string {
		return (string) ( $response['headers'][ $header ] ?? '' );
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $value ): bool {
		return $value instanceof WP_Error;
	}
}

if ( ! function_exists( 'remove_accents' ) ) {
	function remove_accents( string $value ): string {
		return $value;
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( string $value, array $protocols = array() ): string {
		return filter_var( $value, FILTER_VALIDATE_URL ) ? $value : '';
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( string $value ): string {
		return esc_url_raw( $value );
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( string $value ): string {
		return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'wp_parse_url' ) ) {
	function wp_parse_url( string $url, int $component = -1 ) {
		return parse_url( $url, $component );
	}
}

if ( ! function_exists( 'wp_html_excerpt' ) ) {
	function wp_html_excerpt( string $text, int $count, string $more = '' ): string {
		return strlen( $text ) > $count ? substr( $text, 0, $count ) . $more : $text;
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( string $value ): string {
		return preg_replace( '/[^a-z0-9_\\-]/', '', strtolower( $value ) ) ?? '';
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( string $value ): string {
		return trim( strip_tags( $value ) );
	}
}

if ( ! function_exists( 'absint' ) ) {
	function absint( $value ): int {
		return abs( (int) $value );
	}
}

if ( ! function_exists( 'add_action' ) ) {
	function add_action(): void {
	}
}

if ( ! function_exists( 'wp_next_scheduled' ) ) {
	function wp_next_scheduled( string $hook, array $args = array() ) {
		$key = $hook . ':' . serialize( $args );
		return $GLOBALS['child_test_scheduled'][ $key ] ?? false;
	}
}

if ( ! function_exists( 'wp_schedule_single_event' ) ) {
	function wp_schedule_single_event( int $timestamp, string $hook, array $args = array() ): bool {
		$key                                      = $hook . ':' . serialize( $args );
		$GLOBALS['child_test_scheduled'][ $key ] = $timestamp;
		return true;
	}
}

if ( ! function_exists( 'wp_generate_uuid4' ) ) {
	function wp_generate_uuid4(): string {
		return '11111111-1111-4111-8111-111111111111';
	}
}

if ( ! function_exists( 'wp_salt' ) ) {
	function wp_salt(): string {
		return 'test-salt';
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( string $value ): string {
		return stripslashes( $value );
	}
}

if ( ! function_exists( 'is_ssl' ) ) {
	function is_ssl(): bool {
		return false;
	}
}

if ( ! function_exists( 'get_post_status' ) ) {
	function get_post_status( int $post_id ): string|false {
		return $GLOBALS['child_test_post_status'][ $post_id ] ?? false;
	}
}

require_once dirname( __DIR__, 2 ) . '/inc/media-cover-grid-dedupe.php';
require_once dirname( __DIR__, 2 ) . '/inc/media-cover-grid-normalizers.php';
require_once dirname( __DIR__, 2 ) . '/inc/post-likes.php';
require_once dirname( __DIR__, 2 ) . '/inc/provider-http.php';
require_once dirname( __DIR__, 2 ) . '/inc/visual-link-preview-async.php';
