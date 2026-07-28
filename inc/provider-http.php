<?php
/**
 * Shared, narrowly scoped helpers for server-side provider requests.
 *
 * @package TwentyTwentyFiveChild
 */

const CHILD_PROVIDER_CACHE_MAX_KEYS = 100;

/**
 * Build a provider cache key without exposing credentials or query text.
 *
 * @param string $cache_namespace Provider namespace.
 * @param string $request_identity Stable request identity.
 * @return string
 */
function child_provider_cache_key( string $cache_namespace, string $request_identity ): string {
	$cache_namespace = substr( sanitize_key( $cache_namespace ), 0, 24 );

	return 'child_api_' . $cache_namespace . '_' . md5( $request_identity );
}

/**
 * Store a provider response while keeping database-backed transient cardinality bounded.
 *
 * @param string $cache_namespace Provider namespace.
 * @param string $cache_key       Transient key.
 * @param mixed  $value           Cache value.
 * @param int    $ttl             Cache lifetime in seconds.
 */
function child_provider_cache_set( string $cache_namespace, string $cache_key, $value, int $ttl ): void {
	$registry_key = 'child_api_keys_' . substr( sanitize_key( $cache_namespace ), 0, 24 );
	$registry     = get_option( $registry_key, [] );
	$registry     = is_array( $registry ) ? $registry : [];

	unset( $registry[ $cache_key ] );
	$registry[ $cache_key ] = time();

	$registry_count = count( $registry );
	while ( $registry_count > CHILD_PROVIDER_CACHE_MAX_KEYS ) {
		$oldest_key = (string) array_key_first( $registry );
		unset( $registry[ $oldest_key ] );
		delete_transient( $oldest_key );
		--$registry_count;
	}

	update_option( $registry_key, $registry, false );
	set_transient( $cache_key, $value, max( MINUTE_IN_SECONDS, $ttl ) );
}

/**
 * Fetch and decode a JSON API response with cache and consistent validation.
 *
 * @param string               $url             Provider URL.
 * @param array<string, mixed> $args            HTTP request arguments.
 * @param string               $cache_namespace Provider namespace.
 * @param int                  $cache_ttl       Cache lifetime in seconds.
 * @return array<string, mixed>|WP_Error
 */
function child_provider_get_json(
	string $url,
	array $args,
	string $cache_namespace,
	int $cache_ttl = HOUR_IN_SECONDS
) {
	$cache_key = child_provider_cache_key( $cache_namespace, $url . '|' . wp_json_encode( $args['headers'] ?? [] ) );
	$cached    = get_transient( $cache_key );

	if ( is_array( $cached ) ) {
		return $cached;
	}

	$args['timeout'] = min( 12, max( 3, (int) ( $args['timeout'] ?? 8 ) ) );
	$response        = wp_safe_remote_get( $url, $args );

	if ( is_wp_error( $response ) ) {
		return new WP_Error(
			'provider_request_failed',
			__( 'The external service could not be reached.', 'child' ),
			[ 'status' => 502 ]
		);
	}

	$status = (int) wp_remote_retrieve_response_code( $response );
	if ( $status < 200 || $status >= 300 ) {
		return new WP_Error(
			'provider_http_error',
			__( 'The external service returned an error.', 'child' ),
			[
				'status'          => in_array( $status, [ 400, 401, 403, 404, 429 ], true ) ? $status : 502,
				'provider_status' => $status,
			]
		);
	}

	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $data ) || JSON_ERROR_NONE !== json_last_error() ) {
		return new WP_Error(
			'provider_invalid_json',
			__( 'The external service returned an invalid response.', 'child' ),
			[ 'status' => 502 ]
		);
	}

	child_provider_cache_set( $cache_namespace, $cache_key, $data, $cache_ttl );

	return $data;
}
