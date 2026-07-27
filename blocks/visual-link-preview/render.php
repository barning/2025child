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

	// ServerSideRender requests in the editor may enqueue a refresh. Public
	// frontend renders remain read-only and never perform DNS or HTTP work.
	if ( ! $metadata['fresh'] && current_user_can( 'edit_posts' ) ) {
		child_vlp_schedule_refresh( $cache_url );
	}

	return child_vlp_render_card( $metadata['data'], $cache_url );
};
