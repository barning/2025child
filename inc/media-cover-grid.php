<?php
/**
 * Media Cover Grid helpers.
 *
 * @package TwentyTwentyFiveChild
 */

const CHILD_MEDIA_COVER_GRID_CACHE_KEY       = 'child_media_cover_grid_items_v3';
const CHILD_MEDIA_COVER_GRID_STALE_CACHE_KEY = 'child_media_cover_grid_items_stale_v3';
const CHILD_MEDIA_COVER_GRID_LOCK_OPTION     = 'child_media_cover_grid_rebuild_lock_v3';
const CHILD_MEDIA_COVER_GRID_LOCK_TTL        = 60;

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
		'music' => __( 'Musik', 'child' ),
	];

	return $labels[ $type ] ?? __( 'Medium', 'child' );
}


/**
 * Get the validated cover format for a media-grid item.
 *
 * @param array<string, mixed> $item Media item.
 * @return string
 */
function child_get_media_cover_grid_cover_format( array $item ): string {
	$format = (string) ( $item['coverFormat'] ?? '' );

	if ( in_array( $format, [ 'portrait', 'square', 'landscape' ], true ) ) {
		return $format;
	}

	$type = (string) ( $item['type'] ?? '' );

	if ( 'music' === $type ) {
		return 'square';
	}

	if ( 'game' === $type ) {
		return 'landscape';
	}

	return 'portrait';
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
		$stale_items = get_transient( CHILD_MEDIA_COVER_GRID_STALE_CACHE_KEY );

		if ( child_media_cover_grid_acquire_rebuild_lock() ) {
			try {
				$items = child_build_media_cover_grid_items();
				set_transient( CHILD_MEDIA_COVER_GRID_CACHE_KEY, $items, HOUR_IN_SECONDS * 12 );
				set_transient( CHILD_MEDIA_COVER_GRID_STALE_CACHE_KEY, $items, DAY_IN_SECONDS * 7 );
			} finally {
				delete_option( CHILD_MEDIA_COVER_GRID_LOCK_OPTION );
			}
		} else {
			$items = is_array( $stale_items ) ? $stale_items : [];
		}
	}

	if ( $allow_duplicates ) {
		return $items;
	}

	return child_dedupe_media_cover_grid_items( $items );
}

/**
 * Acquire a cross-request rebuild lock, recovering an expired lock.
 */
function child_media_cover_grid_acquire_rebuild_lock(): bool {
	$expires = time() + CHILD_MEDIA_COVER_GRID_LOCK_TTL;

	if ( add_option( CHILD_MEDIA_COVER_GRID_LOCK_OPTION, $expires, '', false ) ) {
		return true;
	}

	$current_expiry = (int) get_option( CHILD_MEDIA_COVER_GRID_LOCK_OPTION, 0 );
	if ( $current_expiry >= time() ) {
		return false;
	}

	delete_option( CHILD_MEDIA_COVER_GRID_LOCK_OPTION );

	return add_option( CHILD_MEDIA_COVER_GRID_LOCK_OPTION, $expires, '', false );
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
 * @param WP_Post                          $post   Source post.
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
 * Flush the cached media-grid item list.
 */
function child_flush_media_cover_grid_cache(): void {
	$items = get_transient( CHILD_MEDIA_COVER_GRID_CACHE_KEY );
	if ( is_array( $items ) ) {
		set_transient( CHILD_MEDIA_COVER_GRID_STALE_CACHE_KEY, $items, DAY_IN_SECONDS * 7 );
	}

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
