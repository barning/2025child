<?php
/**
 * Media Cover Grid helpers.
 *
 * @package TwentyTwentyFiveChild
 */

const CHILD_MEDIA_COVER_GRID_CACHE_KEY = 'child_media_cover_grid_items_v2';

/**
 * Get the translated display label for a media-grid item type.
 *
 * @param string $type Item type.
 * @return string
 */
function child_get_media_cover_grid_type_label( string $type ): string {
	$labels = [
		'book'  => __( 'Buch', 'child' ),
		'movie' => __( 'Film', 'child' ),
		'tv'    => __( 'Serie', 'child' ),
		'game'  => __( 'Videospiel', 'child' ),
	];

	return $labels[ $type ] ?? __( 'Medium', 'child' );
}

/**
 * Build the media item cache from published posts.
 *
 * @param bool $allow_duplicates Whether duplicate media entries should be returned.
 * @return array<int, array<string, mixed>>
 */
function child_get_media_cover_grid_items( bool $allow_duplicates = false ): array {
	$items = get_transient( CHILD_MEDIA_COVER_GRID_CACHE_KEY );

	if ( ! is_array( $items ) ) {
		$items = child_build_media_cover_grid_items();
		set_transient( CHILD_MEDIA_COVER_GRID_CACHE_KEY, $items, HOUR_IN_SECONDS * 12 );
	}

	if ( $allow_duplicates ) {
		return $items;
	}

	return child_dedupe_media_cover_grid_items( $items );
}

/**
 * Query posts and collect all supported media block attributes.
 *
 * @return array<int, array<string, mixed>>
 */
function child_build_media_cover_grid_items(): array {
	$query = new WP_Query(
		[
			'post_type'              => 'post',
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'fields'                 => 'all',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		]
	);

	$items = [];

	foreach ( $query->posts as $post ) {
		if ( ! $post instanceof WP_Post ) {
			continue;
		}

		$blocks = parse_blocks( $post->post_content );
		$items  = array_merge( $items, child_extract_media_items_from_blocks( $blocks, $post ) );
	}

	return $items;
}

/**
 * Recursively extract media items from parsed Gutenberg blocks.
 *
 * @param array<int, array<string, mixed>> $blocks Parsed blocks.
 * @param WP_Post                         $post   Source post.
 * @return array<int, array<string, mixed>>
 */
function child_extract_media_items_from_blocks( array $blocks, WP_Post $post ): array {
	$items = [];

	foreach ( $blocks as $block ) {
		if ( ! is_array( $block ) ) {
			continue;
		}

		$item = child_normalize_media_cover_grid_block( $block, $post );
		if ( null !== $item ) {
			$items[] = $item;
		}

		if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
			$items = array_merge( $items, child_extract_media_items_from_blocks( $block['innerBlocks'], $post ) );
		}
	}

	return $items;
}

/**
 * Normalize one supported block into a shared media item shape.
 *
 * @param array<string, mixed> $block Parsed Gutenberg block.
 * @param WP_Post             $post  Source post.
 * @return array<string, mixed>|null
 */
function child_normalize_media_cover_grid_block( array $block, WP_Post $post ): ?array {
	$block_name = (string) ( $block['blockName'] ?? '' );
	$attrs      = is_array( $block['attrs'] ?? null ) ? $block['attrs'] : [];
	$item       = null;

	if ( 'child/book-rating' === $block_name ) {
		$title = trim( (string) ( $attrs['bookTitle'] ?? '' ) );
		if ( '' === $title ) {
			return null;
		}

		$item = [
			'type'        => 'book',
			'title'       => $title,
			'meta'        => trim( (string) ( $attrs['author'] ?? '' ) ),
			'coverUrl'    => esc_url_raw( (string) ( $attrs['coverUrl'] ?? '' ) ),
			'externalUrl' => esc_url_raw( (string) ( $attrs['shopUrl'] ?? '' ) ),
		];
	} elseif ( 'child/media-recommendation' === $block_name ) {
		$title = trim( (string) ( $attrs['mediaTitle'] ?? '' ) );
		if ( '' === $title ) {
			return null;
		}

		$media_type = 'tv' === ( $attrs['mediaType'] ?? '' ) ? 'tv' : 'movie';
		$item       = [
			'type'        => $media_type,
			'title'       => $title,
			'meta'        => trim( (string) ( $attrs['releaseYear'] ?? '' ) ),
			'coverUrl'    => esc_url_raw( (string) ( $attrs['posterUrl'] ?? '' ) ),
			'externalUrl' => esc_url_raw( (string) ( $attrs['serviceUrl'] ?? '' ) ),
			'tmdbId'      => absint( $attrs['tmdbId'] ?? 0 ),
		];
	} elseif ( 'child/videogame-recommendation' === $block_name ) {
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

		$item = [
			'type'        => 'game',
			'title'       => $title,
			'meta'        => implode( ' · ', $meta_parts ),
			'coverUrl'    => esc_url_raw( (string) ( $attrs['coverUrl'] ?? '' ) ),
			'externalUrl' => esc_url_raw( (string) ( $attrs['shopUrl'] ?? '' ) ),
			'rawgId'      => absint( $attrs['rawgId'] ?? 0 ),
		];
	}

	if ( null === $item ) {
		return null;
	}

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

/**
 * Compute a stable de-duplication key for a media item.
 *
 * @param array<string, mixed> $item Normalized media item.
 * @return string
 */
function child_get_media_cover_grid_dedupe_key( array $item ): string {
	$type  = (string) ( $item['type'] ?? '' );
	$title = child_normalize_media_cover_grid_key_part( (string) ( $item['title'] ?? '' ) );
	$meta  = child_normalize_media_cover_grid_key_part( (string) ( $item['meta'] ?? '' ) );

	if ( ( 'movie' === $type || 'tv' === $type ) && ! empty( $item['tmdbId'] ) ) {
		return $type . ':tmdb:' . (int) $item['tmdbId'];
	}

	if ( 'game' === $type && ! empty( $item['rawgId'] ) ) {
		return 'game:rawg:' . (int) $item['rawgId'];
	}

	if ( 'book' === $type && '' !== $title ) {
		$author = $meta;

		if ( '' !== $author ) {
			return 'book:title-author:' . $title . ':' . $author;
		}
	}

	if ( '' !== $title ) {
		$year = child_get_media_cover_grid_year_from_meta( $meta );

		if ( '' !== $year ) {
			return $type . ':title-year:' . $title . ':' . $year;
		}

		return $type . ':title:' . $title;
	}

	return implode(
		':',
		[
			$type,
			child_normalize_media_cover_grid_key_part( (string) ( $item['externalUrl'] ?? '' ) ),
			child_normalize_media_cover_grid_key_part( (string) ( $item['coverUrl'] ?? '' ) ),
		]
	);
}

/**
 * Normalize one string segment for stable keys.
 *
 * @param string $value Raw value.
 * @return string
 */
function child_normalize_media_cover_grid_key_part( string $value ): string {
	$value = strtolower( remove_accents( trim( $value ) ) );
	$value = preg_replace( '/\s+/', ' ', $value );

	return null === $value ? '' : $value;
}


/**
 * Extract a year from normalized metadata when available.
 *
 * @param string $meta Normalized metadata.
 * @return string
 */
function child_get_media_cover_grid_year_from_meta( string $meta ): string {
	if ( preg_match( '/\b(19|20)\d{2}\b/', $meta, $matches ) ) {
		return $matches[0];
	}

	return '';
}

/**
 * Remove duplicates, keeping the newest source post per key.
 *
 * @param array<int, array<string, mixed>> $items Media items.
 * @return array<int, array<string, mixed>>
 */
function child_dedupe_media_cover_grid_items( array $items ): array {
	$deduped = [];

	foreach ( $items as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}

		$key = (string) ( $item['dedupeKey'] ?? child_get_media_cover_grid_dedupe_key( $item ) );
		if ( '' === $key ) {
			$deduped[] = child_add_media_cover_grid_mention_summary( $item );
			continue;
		}

		if ( ! isset( $deduped[ $key ] ) ) {
			$deduped[ $key ] = child_add_media_cover_grid_mention_summary( $item );
			continue;
		}

		$deduped[ $key ] = child_merge_media_cover_grid_duplicate_item( $deduped[ $key ], $item );
	}

	return array_values( $deduped );
}

/**
 * Merge a duplicate media mention into the item kept for the grid.
 *
 * @param array<string, mixed> $kept      Existing deduplicated item.
 * @param array<string, mixed> $duplicate Duplicate item.
 * @return array<string, mixed>
 */
function child_merge_media_cover_grid_duplicate_item( array $kept, array $duplicate ): array {
	$kept_sources      = child_get_media_cover_grid_source_posts( $kept );
	$duplicate_sources = child_get_media_cover_grid_source_posts( $duplicate );
	$sources           = [];

	foreach ( array_merge( $kept_sources, $duplicate_sources ) as $source ) {
		$source_id = (int) ( $source['id'] ?? 0 );
		if ( $source_id <= 0 ) {
			continue;
		}

		$sources[ $source_id ] = $source;
	}

	usort(
		$sources,
		static function( array $a, array $b ): int {
			return (int) ( $b['timestamp'] ?? 0 ) <=> (int) ( $a['timestamp'] ?? 0 );
		}
	);

	$newest_source = $sources[0] ?? null;

	if ( $newest_source && (int) ( $newest_source['timestamp'] ?? 0 ) > (int) ( $kept['sourcePostTimestamp'] ?? 0 ) ) {
		$duplicate['sourcePosts']  = $sources;
		$duplicate['mentionCount'] = count( $sources );

		return $duplicate;
	}

	$kept['sourcePosts']  = $sources;
	$kept['mentionCount'] = count( $sources );

	return $kept;
}

/**
 * Ensure a media item has mention summary fields.
 *
 * @param array<string, mixed> $item Media item.
 * @return array<string, mixed>
 */
function child_add_media_cover_grid_mention_summary( array $item ): array {
	$sources              = child_get_media_cover_grid_source_posts( $item );
	$item['sourcePosts']  = $sources;
	$item['mentionCount'] = count( $sources );

	return $item;
}

/**
 * Return normalized source post summaries for a media item.
 *
 * @param array<string, mixed> $item Media item.
 * @return array<int, array{id:int,title:string,url:string,timestamp:int}>
 */
function child_get_media_cover_grid_source_posts( array $item ): array {
	if ( ! empty( $item['sourcePosts'] ) && is_array( $item['sourcePosts'] ) ) {
		return array_values(
			array_filter(
				array_map(
					static function( $source ): ?array {
						if ( ! is_array( $source ) ) {
							return null;
						}

						$source_id = (int) ( $source['id'] ?? 0 );
						if ( $source_id <= 0 ) {
							return null;
						}

						return [
							'id'        => $source_id,
							'title'     => (string) ( $source['title'] ?? '' ),
							'url'       => esc_url_raw( (string) ( $source['url'] ?? '' ) ),
							'timestamp' => (int) ( $source['timestamp'] ?? 0 ),
						];
					},
					$item['sourcePosts']
				)
			)
		);
	}

	$source_id = (int) ( $item['sourcePostId'] ?? 0 );
	if ( $source_id <= 0 ) {
		return [];
	}

	return [
		[
			'id'        => $source_id,
			'title'     => (string) ( $item['sourcePostTitle'] ?? '' ),
			'url'       => esc_url_raw( (string) ( $item['sourcePostUrl'] ?? '' ) ),
			'timestamp' => (int) ( $item['sourcePostTimestamp'] ?? 0 ),
		],
	];
}

/**
 * Flush the cached media-grid item list.
 */
function child_flush_media_cover_grid_cache(): void {
	delete_transient( CHILD_MEDIA_COVER_GRID_CACHE_KEY );
}

/**
 * Flush media-grid cache when post content changes.
 *
 * @param int $post_id Post ID.
 */
function child_flush_media_cover_grid_cache_for_post( int $post_id ): void {
	if ( 'post' !== get_post_type( $post_id ) ) {
		return;
	}

	child_flush_media_cover_grid_cache();
}
add_action( 'save_post', 'child_flush_media_cover_grid_cache_for_post' );
add_action( 'deleted_post', 'child_flush_media_cover_grid_cache_for_post' );

/**
 * Flush media-grid cache when a post moves between statuses.
 *
 * @param string  $new_status New post status.
 * @param string  $old_status Old post status.
 * @param WP_Post $post       Post object.
 */
function child_flush_media_cover_grid_cache_for_status_transition( string $new_status, string $old_status, WP_Post $post ): void {
	if ( 'post' !== $post->post_type || $new_status === $old_status ) {
		return;
	}

	child_flush_media_cover_grid_cache();
}
add_action( 'transition_post_status', 'child_flush_media_cover_grid_cache_for_status_transition', 10, 3 );
