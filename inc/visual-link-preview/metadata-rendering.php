<?php
/**
 * Metadata parsing and card rendering for Visual Link Preview.
 *
 * @package TwentyTwentyFiveChild
 */

/**
 * Return an empty metadata payload for a URL.
 */
function child_vlp_empty_metadata( string $url ): array {
	return [
		'url'   => $url,
		'title' => '',
		'desc'  => '',
		'image' => '',
	];
}

/**
 * Resolve protocol-relative and root-relative image URLs.
 */
function child_vlp_resolve_image_url( string $image, string $page_url ): string {
	$image = trim( $image );
	if ( '' === $image ) {
		return '';
	}

	if ( 0 === strpos( $image, '//' ) ) {
		$page_scheme = wp_parse_url( $page_url, PHP_URL_SCHEME );
		$image       = ( $page_scheme ? $page_scheme : 'https' ) . ':' . $image;
	} elseif ( 0 === strpos( $image, '/' ) ) {
		$parts  = wp_parse_url( $page_url );
		$scheme = isset( $parts['scheme'] ) ? $parts['scheme'] : 'https';
		$host   = isset( $parts['host'] ) ? $parts['host'] : '';
		$port   = isset( $parts['port'] ) ? ':' . $parts['port'] : '';
		$image  = $scheme . '://' . $host . $port . $image;
	}

	return child_vlp_normalize_url( $image );
}

/**
 * Normalize cached/provider data before rendering it.
 *
 * @return array{url:string,title:string,desc:string,image:string}
 */
function child_vlp_normalize_metadata_shape( array $metadata, string $fallback_url ): array {
	return [
		'url'   => child_vlp_normalize_url_for_cache( (string) ( $metadata['url'] ?? $fallback_url ) ) ?: $fallback_url,
		'title' => sanitize_text_field( (string) ( $metadata['title'] ?? '' ) ),
		'desc'  => sanitize_text_field( (string) ( $metadata['desc'] ?? '' ) ),
		'image' => esc_url_raw( (string) ( $metadata['image'] ?? '' ), [ 'http', 'https' ] ),
	];
}

/**
 * Fetch and parse metadata from a URL.
 *
 * @return array{url:string,title:string,desc:string,image:string}
 */
function child_vlp_fetch_metadata( string $url ): array {
	$url = child_vlp_normalize_url( $url );
	if ( '' === $url ) {
		return child_vlp_empty_metadata( '' );
	}

	$response = wp_safe_remote_get(
		$url,
		[
			'timeout'             => 5,
			'redirection'         => 2,
			'limit_response_size' => CHILD_VLP_MAX_BODY_BYTES,
			'headers'             => [ 'user-agent' => 'WordPress; VisualLinkPreview/1.0' ],
		]
	);

	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		return child_vlp_empty_metadata( $url );
	}

	$content_type = strtolower( (string) wp_remote_retrieve_header( $response, 'content-type' ) );
	if ( '' !== $content_type && false === strpos( $content_type, 'text/html' ) && false === strpos( $content_type, 'application/xhtml+xml' ) ) {
		return child_vlp_empty_metadata( $url );
	}

	$html = wp_remote_retrieve_body( $response );
	if ( '' === $html ) {
		return child_vlp_empty_metadata( $url );
	}

	$previous_libxml_state = libxml_use_internal_errors( true );
	$doc                   = new DOMDocument();
	$loaded                = $doc->loadHTML( $html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING );
	$title                 = '';
	$desc                  = '';
	$image                 = '';

	if ( $loaded ) {
		$xpath   = new DOMXPath( $doc );
		$queries = [
			'title' => [
				"//meta[@property='og:title']/@content",
				"//meta[@name='twitter:title']/@content",
				'//title/text()',
			],
			'desc'  => [
				"//meta[@property='og:description']/@content",
				"//meta[@name='twitter:description']/@content",
				"//meta[@name='description']/@content",
			],
			'image' => [
				"//meta[@property='og:image:secure_url']/@content",
				"//meta[@property='og:image']/@content",
				"//meta[@name='twitter:image']/@content",
			],
		];

		foreach ( $queries['title'] as $query ) {
			$nodes = $xpath->query( $query );
			if ( $nodes && $nodes->length ) {
				$title = sanitize_text_field( $nodes->item( 0 )->nodeValue );
				break;
			}
		}

		foreach ( $queries['desc'] as $query ) {
			$nodes = $xpath->query( $query );
			if ( $nodes && $nodes->length ) {
				$desc = sanitize_text_field( $nodes->item( 0 )->nodeValue );
				break;
			}
		}

		foreach ( $queries['image'] as $query ) {
			$nodes = $xpath->query( $query );
			if ( $nodes && $nodes->length ) {
				$image = child_vlp_resolve_image_url( $nodes->item( 0 )->nodeValue, $url );
				break;
			}
		}
	}
	libxml_clear_errors();
	libxml_use_internal_errors( $previous_libxml_state );

	return [
		'url'   => $url,
		'title' => wp_html_excerpt( $title, 300, '…' ),
		'desc'  => wp_html_excerpt( $desc, 600, '…' ),
		'image' => $image,
	];
}

/**
 * Render cached metadata or a safe plain-link placeholder.
 */
function child_vlp_render_card( array $metadata, string $fallback_url = '' ): string {
	$metadata = child_vlp_normalize_metadata_shape( $metadata, $fallback_url );
	$url      = $metadata['url'];

	if ( '' === $url ) {
		return '';
	}

	$title = $metadata['title'];
	$desc  = $metadata['desc'];
	$image = $metadata['image'];
	$host  = (string) wp_parse_url( $url, PHP_URL_HOST );
	$label = '' !== $title ? $title : ( '' !== $host ? $host : $url );
	$out   = '<a class="child-url-card" href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">';

	if ( '' !== $image ) {
		$out .= '<div class="child-url-card__media"><img src="' . esc_url( $image ) . '" alt="" loading="lazy" decoding="async" /></div>';
	}

	$out .= '<div class="child-url-card__content">';
	$out .= '<div class="child-url-card__title">' . esc_html( $label );
	if ( '' !== $title && '' !== $host ) {
		$out .= ' <span class="child-url-card__dot">·</span> <span class="child-url-card__host">' . esc_html( $host ) . '</span>';
	}
	$out .= '</div>';

	if ( '' !== $desc ) {
		$out .= '<div class="child-url-card__desc">' . esc_html( $desc ) . '</div>';
	}

	$out .= '</div></a>';

	return '<div class="wp-block-child-visual-link-preview">' . $out . '</div>';
}
