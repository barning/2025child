<?php
/**
 * Media Cover Grid block normalizers.
 *
 * @package TwentyTwentyFiveChild
 */

/**
 * Return registered block normalizers for media cover grid items.
 *
 * @return array<string, callable(array<string, mixed>): array<string, mixed>|null>
 */
function child_get_media_cover_grid_normalizers(): array {
	$normalizers = [
		'child/book-rating' => 'child_normalize_media_cover_grid_book_block',
		'child/media-recommendation' => 'child_normalize_media_cover_grid_media_recommendation_block',
		'child/videogame-recommendation' => 'child_normalize_media_cover_grid_videogame_block',
		'child/music-recommendation' => 'child_normalize_media_cover_grid_music_block',
	];

	/**
	 * Filter block normalizers used to build media cover grid items.
	 *
	 * Normalizers receive parsed block attributes and should return an item without
	 * source-post metadata, or null when the block is incomplete.
	 *
	 * @param array<string, callable> $normalizers Normalizers keyed by block name.
	 */
	return apply_filters( 'child_media_cover_grid_normalizers', $normalizers );
}

/**
 * Normalize one supported block into a shared media item shape.
 *
 * @param array<string, mixed> $block Parsed Gutenberg block.
 * @param WP_Post             $post  Source post.
 * @return array<string, mixed>|null
 */
function child_normalize_media_cover_grid_block( array $block, WP_Post $post ): ?array {
	$block_name  = (string) ( $block['blockName'] ?? '' );
	$normalizers = child_get_media_cover_grid_normalizers();

	if ( ! isset( $normalizers[ $block_name ] ) || ! is_callable( $normalizers[ $block_name ] ) ) {
		return null;
	}

	$attrs = is_array( $block['attrs'] ?? null ) ? $block['attrs'] : [];
	$item  = call_user_func( $normalizers[ $block_name ], $attrs );

	if ( ! is_array( $item ) ) {
		return null;
	}

	return child_add_media_cover_grid_source_post( $item, $post );
}

/**
 * Normalize book rating block attributes.
 *
 * @param array<string, mixed> $attrs Parsed block attributes.
 * @return array<string, mixed>|null
 */
function child_normalize_media_cover_grid_book_block( array $attrs ): ?array {
	$title = trim( (string) ( $attrs['bookTitle'] ?? '' ) );
	if ( '' === $title ) {
		return null;
	}

	return [
		'type'        => 'book',
		'title'       => $title,
		'meta'        => trim( (string) ( $attrs['author'] ?? '' ) ),
		'coverUrl'    => esc_url_raw( (string) ( $attrs['coverUrl'] ?? '' ) ),
		'coverFormat' => 'portrait',
		'externalUrl' => esc_url_raw( (string) ( $attrs['shopUrl'] ?? '' ) ),
	];
}

/**
 * Normalize movie and TV recommendation block attributes.
 *
 * @param array<string, mixed> $attrs Parsed block attributes.
 * @return array<string, mixed>|null
 */
function child_normalize_media_cover_grid_media_recommendation_block( array $attrs ): ?array {
	$title = trim( (string) ( $attrs['mediaTitle'] ?? '' ) );
	if ( '' === $title ) {
		return null;
	}

	$media_type = 'tv' === ( $attrs['mediaType'] ?? '' ) ? 'tv' : 'movie';

	return [
		'type'        => $media_type,
		'title'       => $title,
		'meta'        => trim( (string) ( $attrs['releaseYear'] ?? '' ) ),
		'coverUrl'    => esc_url_raw( (string) ( $attrs['posterUrl'] ?? '' ) ),
		'coverFormat' => 'portrait',
		'externalUrl' => esc_url_raw( (string) ( $attrs['serviceUrl'] ?? '' ) ),
		'tmdbId'      => absint( $attrs['tmdbId'] ?? 0 ),
	];
}

/**
 * Normalize videogame recommendation block attributes.
 *
 * @param array<string, mixed> $attrs Parsed block attributes.
 * @return array<string, mixed>|null
 */
function child_normalize_media_cover_grid_videogame_block( array $attrs ): ?array {
	$title = trim( (string) ( $attrs['gameTitle'] ?? '' ) );
	if ( '' === $title ) {
		return null;
	}

	$platforms = is_array( $attrs['platforms'] ?? null ) ? array_filter( array_map( 'strval', $attrs['platforms'] ) ) : [];
	$year      = trim( (string) ( $attrs['releaseYear'] ?? '' ) );
	if ( '' === $year && ! empty( $attrs['releaseDate'] ) ) {
		$timestamp = strtotime( (string) $attrs['releaseDate'] );
		$year      = $timestamp ? date_i18n( 'Y', $timestamp ) : '';
	}

	$meta_parts = array_filter( [ $year, implode( ', ', array_slice( $platforms, 0, 3 ) ) ] );

	return [
		'type'        => 'game',
		'title'       => $title,
		'meta'        => implode( ' · ', $meta_parts ),
		'coverUrl'    => esc_url_raw( (string) ( $attrs['coverUrl'] ?? '' ) ),
		'coverFormat' => 'landscape',
		'externalUrl' => esc_url_raw( (string) ( $attrs['shopUrl'] ?? '' ) ),
		'rawgId'      => absint( $attrs['rawgId'] ?? 0 ),
	];
}


/**
 * Normalize music recommendation block attributes.
 *
 * @param array<string, mixed> $attrs Parsed block attributes.
 * @return array<string, mixed>|null
 */
function child_normalize_media_cover_grid_music_block( array $attrs ): ?array {
	$title = trim( (string) ( $attrs['title'] ?? '' ) );
	if ( '' === $title ) {
		return null;
	}

	$artist       = trim( (string) ( $attrs['artist'] ?? '' ) );
	$album_title  = trim( (string) ( $attrs['albumTitle'] ?? '' ) );
	$release_year = trim( (string) ( $attrs['releaseYear'] ?? '' ) );
	$meta_parts   = array_filter( [ $artist, 'song' === ( $attrs['musicType'] ?? '' ) ? $album_title : '', $release_year ] );

	return [
		'type'        => 'music',
		'title'       => $title,
		'meta'        => implode( ' · ', $meta_parts ),
		'coverUrl'    => esc_url_raw( (string) ( $attrs['coverUrl'] ?? '' ) ),
		'coverFormat' => 'square',
		'externalUrl' => esc_url_raw( (string) ( $attrs['providerUrl'] ?? '' ) ),
		'providerId'  => sanitize_text_field( (string) ( $attrs['providerId'] ?? '' ) ),
	];
}

/**
 * Add source-post metadata to a normalized media item.
 *
 * @param array<string, mixed> $item Normalized media item.
 * @param WP_Post             $post Source post.
 * @return array<string, mixed>
 */
function child_add_media_cover_grid_source_post( array $item, WP_Post $post ): array {
	$source_post = [
		'id'        => (int) $post->ID,
		'title'     => get_the_title( $post ),
		'url'       => get_permalink( $post ),
		'timestamp' => (int) get_post_time( 'U', true, $post ),
	];

	$item['sourcePostId']        = $source_post['id'];
	$item['sourcePostTitle']     = $source_post['title'];
	$item['sourcePostUrl']       = $source_post['url'];
	$item['sourcePostTimestamp'] = $source_post['timestamp'];
	$item['sourcePosts']         = [ $source_post ];
	$item['mentionCount']        = 1;
	$item['dedupeKey']           = child_get_media_cover_grid_dedupe_key( $item );

	return $item;
}
