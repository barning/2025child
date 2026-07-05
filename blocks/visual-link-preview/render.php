<?php
/**
 * Server-side render for Visual Link Preview block.
 *
 * @package TwentyTwentyFiveChild
 */

return function( $attributes ) {
	$cache_url = isset( $attributes['url'] ) ? child_vlp_normalize_url_for_cache( (string) $attributes['url'] ) : '';
	if ( '' === $cache_url ) {
		return '';
	}

	$cache_key = child_vlp_get_cache_key( $cache_url );
	$cached    = get_transient( $cache_key );
	if ( is_array( $cached ) ) {
		return child_vlp_render_card( $cached['url'], $cached['title'], $cached['desc'], $cached['image'] );
	}

	$url = child_vlp_normalize_url( $cache_url );
	if ( '' === $url ) {
		return '';
	}

	$metadata = child_vlp_fetch_metadata( $url );
	$ttl      = '' === $metadata['title'] && '' === $metadata['desc'] && '' === $metadata['image'] ? CHILD_VLP_NEGATIVE_CACHE_TTL : CHILD_VLP_CACHE_TTL;
	set_transient( $cache_key, $metadata, $ttl );

	return child_vlp_render_card( $metadata['url'], $metadata['title'], $metadata['desc'], $metadata['image'] );
};

/**
 * Render a visual link preview card.
 */
function child_vlp_render_card( string $url, string $title, string $desc, string $image ): string {
	$host = wp_parse_url( $url, PHP_URL_HOST );
	$out  = '<a class="child-url-card" href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">';
	if ( $image ) {
		$out .= '<div class="child-url-card__media"><img src="' . esc_url( $image ) . '" alt="" loading="lazy" decoding="async" /></div>';
	}
	$out .= '<div class="child-url-card__content">';
	if ( $title ) {
		$out .= '<div class="child-url-card__title">' . esc_html( $title );
		if ( $host ) {
			$out .= ' <span class="child-url-card__dot">·</span> <span class="child-url-card__host">' . esc_html( $host ) . '</span>';
		}
		$out .= '</div>';
	}
	if ( $desc ) {
		$out .= '<div class="child-url-card__desc">' . esc_html( $desc ) . '</div>';
	}
	$out .= '</div></a>';
	return '<div class="wp-block-child-visual-link-preview">' . $out . '</div>';
}
