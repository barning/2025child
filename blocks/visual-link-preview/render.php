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

	$response = wp_safe_remote_get( $url, [
		'timeout'      => 8,
		'redirection'  => 5,
		'headers'      => [ 'user-agent' => 'WordPress; VisualLinkPreview/1.0' ],
	] );

	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		$embed = wp_oembed_get( $url );
		return $embed ? '<div class="wp-block-child-visual-link-preview">' . $embed . '</div>' : '<div class="wp-block-child-visual-link-preview"><a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $url ) . '</a></div>';
	}

	$html = wp_remote_retrieve_body( $response );
	if ( ! $html ) {
		return '<div class="wp-block-child-visual-link-preview"><a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $url ) . '</a></div>';
	}

	$title = '';
	$desc  = '';
	$image = '';

	libxml_use_internal_errors( true );
	$doc = new DOMDocument();
	$loaded = $doc->loadHTML( $html );
	if ( $loaded ) {
		$xpath = new DOMXPath( $doc );
		$queries = [
			'title' => [
				"//meta[@property='og:title']/@content",
				"//meta[@name='twitter:title']/@content",
				'//title/text()'
			],
			'desc' => [
				"//meta[@property='og:description']/@content",
				"//meta[@name='twitter:description']/@content",
				"//meta[@name='description']/@content"
			],
			'image' => [
				"//meta[@property='og:image:secure_url']/@content",
				"//meta[@property='og:image']/@content",
				"//meta[@name='twitter:image']/@content"
			],
		];

		foreach ( $queries['title'] as $q ) {
			$nodes = $xpath->query( $q );
			if ( $nodes && $nodes->length ) { $title = trim( $nodes->item(0)->nodeValue ); break; }
		}
		foreach ( $queries['desc'] as $q ) {
			$nodes = $xpath->query( $q );
			if ( $nodes && $nodes->length ) { $desc = trim( $nodes->item(0)->nodeValue ); break; }
		}
		foreach ( $queries['image'] as $q ) {
			$nodes = $xpath->query( $q );
			if ( $nodes && $nodes->length ) { $image = trim( $nodes->item(0)->nodeValue ); break; }
		}
	}
	libxml_clear_errors();

	if ( $image && 0 === strpos( $image, '//' ) ) {
		$image = ( is_ssl() ? 'https:' : 'http:' ) . $image;
	} elseif ( $image && 0 === strpos( $image, '/' ) ) {
		$parts = wp_parse_url( $url );
		$scheme = isset( $parts['scheme'] ) ? $parts['scheme'] : 'https';
		$host   = isset( $parts['host'] ) ? $parts['host'] : '';
		$port   = isset( $parts['port'] ) ? ':' . $parts['port'] : '';
		$image  = $scheme . '://' . $host . $port . $image;
	}

	$data = [ 'url' => $url, 'title' => $title, 'desc' => $desc, 'image' => $image ];
	set_transient( $cache_key, $data, HOUR_IN_SECONDS * 12 );

	return child_vlp_render_card( $url, $title, $desc, $image );
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
