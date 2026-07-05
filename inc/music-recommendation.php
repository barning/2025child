<?php
/**
 * Music recommendation integration (Apple/iTunes Search API + REST).
 *
 * @package TwentyTwentyFiveChild
 */

/**
 * Register a REST endpoint for music searches.
 */
function child_register_music_lookup_route(): void {
	register_rest_route(
		'child/v1',
		'/music',
		[
			'methods'             => 'GET',
			'permission_callback' => static function(): bool {
				return current_user_can( 'edit_posts' );
			},
			'args'                => [
				'q'         => [
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
				],
				'musicType' => [
					'required'          => false,
					'default'           => 'song',
					'sanitize_callback' => 'sanitize_key',
				],
			],
			'callback'            => 'child_music_lookup_callback',
		]
	);
}
add_action( 'rest_api_init', 'child_register_music_lookup_route' );

/**
 * Handle Apple/iTunes music lookups via the REST API.
 *
 * @param WP_REST_Request $request Request data.
 * @return WP_REST_Response|WP_Error
 */
function child_music_lookup_callback( WP_REST_Request $request ) {
	$query      = trim( (string) $request->get_param( 'q' ) );
	$music_type = 'album' === $request->get_param( 'musicType' ) ? 'album' : 'song';

	if ( '' === $query ) {
		return new WP_Error( 'missing_query', __( 'Bitte gib einen Suchbegriff ein.', 'child' ), [ 'status' => 400 ] );
	}

	$country = substr( (string) get_locale(), 3, 2 );
	$country = preg_match( '/^[A-Z]{2}$/', $country ) ? $country : 'DE';
	$entity  = 'album' === $music_type ? 'album' : 'song';

	$api_url = add_query_arg(
		[
			'term'    => $query,
			'media'   => 'music',
			'entity'  => $entity,
			'country' => $country,
			'limit'   => 10,
		],
		'https://itunes.apple.com/search'
	);

	$response = wp_safe_remote_get(
		$api_url,
		[
			'timeout' => 8,
		]
	);

	if ( is_wp_error( $response ) ) {
		return new WP_Error( 'music_lookup_failed', __( 'Die Musiksuche konnte nicht geladen werden.', 'child' ), [ 'status' => 500 ] );
	}

	$status = wp_remote_retrieve_response_code( $response );
	if ( $status < 200 || $status >= 300 ) {
		return new WP_Error( 'music_lookup_failed', __( 'Die Musiksuche konnte nicht geladen werden.', 'child' ), [ 'status' => $status ] );
	}

	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $data ) ) {
		return new WP_Error( 'invalid_response', __( 'Die Musiksuche lieferte keine gültige Antwort.', 'child' ), [ 'status' => 500 ] );
	}

	$results = array_values(
		array_filter(
			array_map(
				static function( array $item ) use ( $music_type ): ?array {
					$title = 'album' === $music_type ? (string) ( $item['collectionName'] ?? '' ) : (string) ( $item['trackName'] ?? '' );
					if ( '' === trim( $title ) ) {
						return null;
					}

					$release_date = (string) ( $item['releaseDate'] ?? '' );
					$release_year = '';
					if ( '' !== $release_date ) {
						$timestamp    = strtotime( $release_date );
						$release_year = $timestamp ? gmdate( 'Y', $timestamp ) : '';
					}

					$artwork = (string) ( $item['artworkUrl100'] ?? '' );
					if ( '' !== $artwork ) {
						$artwork = str_replace( '100x100bb', '600x600bb', $artwork );
					}

					return [
						'id'          => (string) ( 'album' === $music_type ? ( $item['collectionId'] ?? '' ) : ( $item['trackId'] ?? '' ) ),
						'musicType'   => $music_type,
						'title'       => $title,
						'artist'      => (string) ( $item['artistName'] ?? '' ),
						'albumTitle'  => (string) ( $item['collectionName'] ?? '' ),
						'releaseYear' => $release_year,
						'coverUrl'    => esc_url_raw( $artwork ),
						'provider'    => 'Apple/iTunes',
						'providerUrl' => esc_url_raw( (string) ( 'album' === $music_type ? ( $item['collectionViewUrl'] ?? '' ) : ( $item['trackViewUrl'] ?? '' ) ) ),
						'previewUrl'  => 'song' === $music_type ? esc_url_raw( (string) ( $item['previewUrl'] ?? '' ) ) : '',
					];
				},
				is_array( $data['results'] ?? null ) ? $data['results'] : []
			)
		)
	);

	return rest_ensure_response( [ 'results' => $results ] );
}
