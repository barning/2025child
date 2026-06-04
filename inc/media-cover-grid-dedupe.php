<?php
/**
 * Media Cover Grid de-duplication helpers.
 *
 * @package TwentyTwentyFiveChild
 */

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
