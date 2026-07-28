<?php
/**
 * Server-side render for Visual Link Preview block.
 *
 * @package TwentyTwentyFiveChild
 */

return function ( $attributes ) {
	$cache_url = isset( $attributes['url'] ) ? child_vlp_normalize_url_for_cache( (string) $attributes['url'] ) : '';
	if ( '' === $cache_url ) {
		return '';
	}

	$metadata = child_vlp_get_cached_metadata( $cache_url );

	// Any stale render may enqueue a deduplicated WP-Cron refresh. Frontend
	// rendering itself remains free of DNS and HTTP work.
	if ( ! $metadata['fresh'] ) {
		child_vlp_schedule_refresh( $cache_url );
	}

	return child_vlp_render_card( $metadata['data'], $cache_url );
};
