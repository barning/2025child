<?php
/**
 * Cache, locking, and scheduling for Visual Link Preview.
 *
 * @package TwentyTwentyFiveChild
 */

/**
 * Build the metadata cache key for a URL.
 */
function child_vlp_get_cache_key( string $url ): string {
	return 'child_vlp_' . md5( $url );
}

/**
 * Build the stale-cache and cross-request lock keys for a URL.
 */
function child_vlp_get_stale_cache_key( string $url ): string {
	return child_vlp_get_cache_key( $url ) . '_stale';
}

function child_vlp_get_lock_option( string $url ): string {
	return child_vlp_get_cache_key( $url ) . '_lock';
}

/**
 * Read fresh metadata, falling back to a bounded stale copy.
 *
 * @return array{fresh:bool,data:array{url:string,title:string,desc:string,image:string}}
 */
function child_vlp_get_cached_metadata( string $url ): array {
	$fresh = get_transient( child_vlp_get_cache_key( $url ) );
	if ( is_array( $fresh ) ) {
		return [
			'fresh' => true,
			'data'  => child_vlp_normalize_metadata_shape( $fresh, $url ),
		];
	}

	$stale = get_transient( child_vlp_get_stale_cache_key( $url ) );

	return [
		'fresh' => false,
		'data'  => is_array( $stale )
			? child_vlp_normalize_metadata_shape( $stale, $url )
			: child_vlp_empty_metadata( $url ),
	];
}

/**
 * Acquire an atomic cross-request URL fetch lock.
 */
function child_vlp_acquire_fetch_lock( string $url ): bool {
	$option  = child_vlp_get_lock_option( $url );
	$expires = time() + CHILD_VLP_FETCH_LOCK_TTL;

	if ( add_option( $option, $expires, '', false ) ) {
		return true;
	}

	if ( (int) get_option( $option, 0 ) >= time() ) {
		return false;
	}

	delete_option( $option );

	return add_option( $option, $expires, '', false );
}

/**
 * Queue a single background refresh for an editor-approved URL.
 */
function child_vlp_schedule_refresh( string $url ): bool {
	$url = child_vlp_normalize_url_for_cache( $url );
	if ( '' === $url || wp_next_scheduled( CHILD_VLP_REFRESH_HOOK, [ $url ] ) ) {
		return false;
	}

	return (bool) wp_schedule_single_event( time() + 1, CHILD_VLP_REFRESH_HOOK, [ $url ] );
}

/**
 * Fetch and store metadata from WP-Cron, never from frontend rendering.
 */
function child_vlp_refresh_metadata( string $cache_url ): void {
	$cache_url = child_vlp_normalize_url_for_cache( $cache_url );
	if ( '' === $cache_url || ! child_vlp_acquire_fetch_lock( $cache_url ) ) {
		return;
	}

	$lock_option = child_vlp_get_lock_option( $cache_url );

	try {
		$url = child_vlp_normalize_url( $cache_url );
		if ( '' === $url ) {
			set_transient(
				child_vlp_get_cache_key( $cache_url ),
				child_vlp_empty_metadata( $cache_url ),
				CHILD_VLP_NEGATIVE_CACHE_TTL
			);
			return;
		}

		$metadata = child_vlp_fetch_metadata( $url );
		$has_data = '' !== $metadata['title'] || '' !== $metadata['desc'] || '' !== $metadata['image'];
		$ttl      = $has_data ? CHILD_VLP_CACHE_TTL : CHILD_VLP_NEGATIVE_CACHE_TTL;

		set_transient( child_vlp_get_cache_key( $cache_url ), $metadata, $ttl );
		if ( $has_data ) {
			set_transient( child_vlp_get_stale_cache_key( $cache_url ), $metadata, CHILD_VLP_STALE_CACHE_TTL );
		}
	} finally {
		delete_option( $lock_option );
	}
}
add_action( CHILD_VLP_REFRESH_HOOK, 'child_vlp_refresh_metadata' );

/**
 * Find preview URLs recursively in saved blocks.
 *
 * @return string[]
 */
function child_vlp_find_urls_in_blocks( array $blocks ): array {
	$urls = [];

	foreach ( $blocks as $block ) {
		if ( ! is_array( $block ) ) {
			continue;
		}

		if ( 'child/visual-link-preview' === ( $block['blockName'] ?? '' ) ) {
			$url = child_vlp_normalize_url_for_cache( (string) ( $block['attrs']['url'] ?? '' ) );
			if ( '' !== $url ) {
				$urls[] = $url;
			}
		}

		if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
			$urls = array_merge( $urls, child_vlp_find_urls_in_blocks( $block['innerBlocks'] ) );
		}
	}

	return array_values( array_unique( $urls ) );
}

/**
 * Queue metadata refreshes when an authorized editor saves preview blocks.
 */
function child_vlp_schedule_saved_post_refreshes( int $post_id, WP_Post $post ): void {
	if (
		wp_is_post_revision( $post_id )
		|| wp_is_post_autosave( $post_id )
		|| ! current_user_can( 'edit_post', $post_id )
	) {
		return;
	}

	foreach ( child_vlp_find_urls_in_blocks( parse_blocks( $post->post_content ) ) as $url ) {
		child_vlp_schedule_refresh( $url );
	}
}
add_action( 'save_post', 'child_vlp_schedule_saved_post_refreshes', 20, 2 );
