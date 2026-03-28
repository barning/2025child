<?php
/**
 * Anonymous post likes storage and REST API.
 *
 * @package TwentyTwentyFiveChild
 */

const CHILD_POST_LIKES_SCHEMA_VERSION = '1';
const CHILD_POST_LIKES_TABLE_SUFFIX = 'child_post_likes';
const CHILD_POST_LIKES_COOKIE = 'child_post_like_visitor';

/**
 * Resolve the full likes table name.
 */
function child_post_likes_get_table_name(): string {
	global $wpdb;

	return $wpdb->prefix . CHILD_POST_LIKES_TABLE_SUFFIX;
}

/**
 * Ensure likes table exists.
 */
function child_post_likes_maybe_create_table(): void {
	$schema_version = get_option( 'child_post_likes_schema_version', '' );
	if ( CHILD_POST_LIKES_SCHEMA_VERSION === $schema_version ) {
		return;
	}

	global $wpdb;
	$table_name = child_post_likes_get_table_name();
	$charset_collate = $wpdb->get_charset_collate();

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$sql = "CREATE TABLE {$table_name} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		post_id bigint(20) unsigned NOT NULL,
		visitor_hash char(64) NOT NULL,
		created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		UNIQUE KEY post_visitor (post_id, visitor_hash),
		KEY post_id (post_id)
	) {$charset_collate};";

	dbDelta( $sql );
	update_option( 'child_post_likes_schema_version', CHILD_POST_LIKES_SCHEMA_VERSION, false );
}
add_action( 'init', 'child_post_likes_maybe_create_table', 5 );

/**
 * Ensure current visitor has a stable cookie token.
 */
function child_post_likes_ensure_visitor_token(): string {
	$token = isset( $_COOKIE[ CHILD_POST_LIKES_COOKIE ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ CHILD_POST_LIKES_COOKIE ] ) ) : '';

	if ( '' === $token ) {
		$token = wp_generate_uuid4();
		setcookie(
			CHILD_POST_LIKES_COOKIE,
			$token,
			[
				'expires'  => time() + YEAR_IN_SECONDS,
				'path'     => COOKIEPATH ? COOKIEPATH : '/',
				'domain'   => COOKIE_DOMAIN,
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			]
		);
		$_COOKIE[ CHILD_POST_LIKES_COOKIE ] = $token;
	}

	return $token;
}

/**
 * Get hashed visitor token for storage.
 */
function child_post_likes_get_visitor_hash(): string {
	$token = child_post_likes_ensure_visitor_token();

	return hash( 'sha256', $token . wp_salt( 'auth' ) );
}

/**
 * Get like count for post.
 */
function child_post_likes_get_count( int $post_id ): int {
	if ( $post_id <= 0 ) {
		return 0;
	}

	global $wpdb;
	$table_name = child_post_likes_get_table_name();

	$count = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$table_name} WHERE post_id = %d",
			$post_id
		)
	);

	return max( 0, (int) $count );
}

/**
 * Whether current visitor liked this post.
 */
function child_post_likes_has_current_visitor_liked( int $post_id ): bool {
	if ( $post_id <= 0 ) {
		return false;
	}

	global $wpdb;
	$table_name = child_post_likes_get_table_name();
	$visitor_hash = child_post_likes_get_visitor_hash();

	$exists = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT 1 FROM {$table_name} WHERE post_id = %d AND visitor_hash = %s LIMIT 1",
			$post_id,
			$visitor_hash
		)
	);

	return '1' === (string) $exists;
}

/**
 * Normalize desired like state input.
 */
function child_post_likes_normalize_desired_state( $value ): ?bool {
	if ( is_bool( $value ) ) {
		return $value;
	}

	if ( is_numeric( $value ) ) {
		return (int) $value === 1;
	}

	if ( is_string( $value ) ) {
		$normalized = strtolower( trim( $value ) );
		if ( in_array( $normalized, [ '1', 'true', 'yes' ], true ) ) {
			return true;
		}
		if ( in_array( $normalized, [ '0', 'false', 'no' ], true ) ) {
			return false;
		}
	}

	return null;
}

/**
 * Set like status for current visitor on post.
 *
 * @return array{liked:bool,count:int}
 */
function child_post_likes_set_state( int $post_id, bool $desired_state ): array {
	global $wpdb;
	$table_name = child_post_likes_get_table_name();
	$visitor_hash = child_post_likes_get_visitor_hash();
	$liked = child_post_likes_has_current_visitor_liked( $post_id );

	if ( $desired_state && ! $liked ) {
		$wpdb->insert(
			$table_name,
			[
				'post_id'      => $post_id,
				'visitor_hash' => $visitor_hash,
			],
			[ '%d', '%s' ]
		);
		$liked = true;
	} elseif ( ! $desired_state && $liked ) {
		$wpdb->delete(
			$table_name,
			[
				'post_id'      => $post_id,
				'visitor_hash' => $visitor_hash,
			],
			[ '%d', '%s' ]
		);
		$liked = false;
	}

	return [
		'liked' => $liked,
		'count' => child_post_likes_get_count( $post_id ),
	];
}

/**
 * Validate post id request argument.
 */
function child_post_likes_validate_post_id( $value ): bool {
	$post_id = (int) $value;

	return $post_id > 0 && 'publish' === get_post_status( $post_id );
}

/**
 * Register post likes REST route.
 */
function child_post_likes_register_rest_routes(): void {
	register_rest_route(
		'child/v1',
		'/post-likes/(?P<post_id>\d+)',
		[
			[
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => '__return_true',
				'callback'            => static function( WP_REST_Request $request ): WP_REST_Response {
					$post_id = (int) $request->get_param( 'post_id' );

					return rest_ensure_response(
						[
							'liked' => child_post_likes_has_current_visitor_liked( $post_id ),
							'count' => child_post_likes_get_count( $post_id ),
						]
					);
				},
				'args'                => [
					'post_id' => [
						'required'          => true,
						'validate_callback' => 'child_post_likes_validate_post_id',
					],
				],
			],
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => '__return_true',
				'callback'            => static function( WP_REST_Request $request ): WP_REST_Response {
					$post_id = (int) $request->get_param( 'post_id' );
					$desired_state = child_post_likes_normalize_desired_state( $request->get_param( 'liked' ) );

					if ( null === $desired_state ) {
						$desired_state = ! child_post_likes_has_current_visitor_liked( $post_id );
					}

					return rest_ensure_response( child_post_likes_set_state( $post_id, $desired_state ) );
				},
				'args'                => [
					'post_id' => [
						'required'          => true,
						'validate_callback' => 'child_post_likes_validate_post_id',
					],
					'liked'   => [
						'required' => false,
					],
				],
			],
		]
	);
}
add_action( 'rest_api_init', 'child_post_likes_register_rest_routes' );
