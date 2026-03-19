<?php
/**
 * Book Rating block helpers.
 *
 * @package TwentyTwentyFiveChild
 */

/**
 * Register a REST endpoint for Google Books lookups to avoid client-side rate limits.
 */
function child_register_books_lookup_route(): void {
	register_rest_route(
		'child/v1',
		'/books',
		[
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
			'args'                => [
				'q'          => [
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
				],
				'maxResults' => [
					'required'          => false,
					'default'           => 5,
					'sanitize_callback' => 'absint',
				],
			],
			'callback'            => 'child_books_lookup_callback',
		]
	);
}
add_action( 'rest_api_init', 'child_register_books_lookup_route' );

/**
 * Handle Google Books lookups via the REST API.
 *
 * @param WP_REST_Request $request Request data.
 * @return WP_REST_Response|WP_Error
 */
function child_books_lookup_callback( WP_REST_Request $request ) {
	$query       = trim( (string) $request->get_param( 'q' ) );
	$max_results = (int) $request->get_param( 'maxResults' );

	if ( '' === $query ) {
		return new WP_Error( 'missing_query', __( 'Bitte gib einen Suchbegriff ein.', 'child' ), [ 'status' => 400 ] );
	}

	$max_results = max( 1, min( 10, $max_results ) );
	$cache_key   = 'child_books_' . md5( $query . '|' . $max_results );
	$cached      = get_transient( $cache_key );

	if ( false !== $cached ) {
		return rest_ensure_response( $cached );
	}

	$api_url = add_query_arg(
		[
			'q'          => $query,
			'maxResults' => $max_results,
		],
		'https://www.googleapis.com/books/v1/volumes'
	);

	if ( defined( 'CHILD_GOOGLE_BOOKS_API_KEY' ) && CHILD_GOOGLE_BOOKS_API_KEY ) {
		$api_url = add_query_arg( 'key', CHILD_GOOGLE_BOOKS_API_KEY, $api_url );
	}

	$response = wp_remote_get(
		$api_url,
		[
			'timeout' => 8,
		]
	);

	if ( is_wp_error( $response ) ) {
		return new WP_Error( 'books_lookup_failed', __( 'Die Buchsuche konnte nicht geladen werden.', 'child' ), [ 'status' => 500 ] );
	}

	$status = wp_remote_retrieve_response_code( $response );
	$body   = wp_remote_retrieve_body( $response );

	if ( 429 === $status ) {
		return new WP_Error(
			'rate_limited',
			__( 'Google Books API-Limit erreicht.', 'child' ),
			[ 'status' => 429 ]
		);
	}

	if ( $status < 200 || $status >= 300 ) {
		return new WP_Error(
			'books_lookup_failed',
			__( 'Die Buchsuche konnte nicht geladen werden.', 'child' ),
			[ 'status' => $status ]
		);
	}

	$data = json_decode( $body, true );
	if ( null === $data ) {
		return new WP_Error( 'invalid_response', __( 'Die Buchsuche lieferte keine gültige Antwort.', 'child' ), [ 'status' => 500 ] );
	}

	set_transient( $cache_key, $data, HOUR_IN_SECONDS * 12 );

	return rest_ensure_response( $data );
}
