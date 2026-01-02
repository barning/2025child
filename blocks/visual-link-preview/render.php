<?php
/**
 * Server-side render for Visual Link Preview block
 */
return function( $attributes ) {
	$url = isset( $attributes['url'] ) ? esc_url_raw( $attributes['url'] ) : '';
	if ( ! $url ) return '';

	$cache_key = 'child_vlp_' . md5( $url );
	$cached    = get_transient( $cache_key );
	if ( is_array( $cached ) ) {
		return child_vlp_render_card( $cached['url'], $cached['title'], $cached['desc'], $cached['image'] );
	}

	// Stampede-protection: try to acquire a short-lived lock so only one
	// process fetches remote metadata at a time. Other processes render a
	// fallback (oEmbed or simple link) while the first one populates the cache.
	$lock_key = 'child_vlp_lock_' . md5( $url );
	$got_lock = false;
	if ( function_exists( 'wp_cache_add' ) ) {
		$got_lock = wp_cache_add( $lock_key, 1, 'child_vlp', 30 );
	} else {
		$got_lock = add_option( $lock_key, 1 );
	}
	if ( ! $got_lock ) {
		$embed = wp_oembed_get( $url );
		return $embed ? '<div class="wp-block-child-visual-link-preview">' . $embed . '</div>' : '<div class="wp-block-child-visual-link-preview"><a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $url ) . '</a></div>';
	}

	// We have the lock: schedule a non-blocking background fetch so the current
	// request doesn't perform the remote HTTP call. Background handler will
	// populate the transient and remove the lock.
	$endpoint = admin_url( 'admin-post.php' );
	$body = [ 'action' => 'child_vlp_fetch', 'url' => $url ];

	// fire-and-forget request to our admin-post handler
	wp_remote_post( $endpoint, [
		'body'     => $body,
		'timeout'  => 1,
		'blocking' => false,
		'headers'  => [ 'user-agent' => 'WordPress; VisualLinkPreview/1.0' ],
	] );

	// Immediately render a safe fallback (oEmbed or simple link). Future
	// requests will use the cached transient once the background job completes.
	$embed = wp_oembed_get( $url );
	return $embed ? '<div class="wp-block-child-visual-link-preview">' . $embed . '</div>' : '<div class="wp-block-child-visual-link-preview"><a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $url ) . '</a></div>';
};

function child_vlp_render_card( $url, $title, $desc, $image ) {
	$host = wp_parse_url( $url, PHP_URL_HOST );
	$out  = '<a class="child-url-card" href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">';
	if ( $image ) {
		$out .= '<div class="child-url-card__media"><img src="' . esc_url( $image ) . '" alt="" loading="lazy" /></div>';
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
