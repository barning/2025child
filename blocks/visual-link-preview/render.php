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

	if ( child_is_feed_render() ) {
		$data = child_vlp_normalize_metadata_shape( $metadata['data'], $cache_url );
		$host = (string) wp_parse_url( $data['url'], PHP_URL_HOST );
		return child_render_feed_card(
			[
				'title'        => $data['title'] ?: ( $host ?: $data['url'] ),
				'url'          => $data['url'],
				'image'        => $data['image'],
				'image_alt'    => '',
				'image_width'  => 1200,
				'image_height' => 630,
				'meta'         => $host ? [ $host ] : [],
				'description'  => $data['desc'],
			]
		);
	}

	return child_vlp_render_card( $metadata['data'], $cache_url );
};
