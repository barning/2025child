<?php
/**
 * Background fetch helpers for Visual Link Preview.
 *
 * @package TwentyTwentyFiveChild
 */

const CHILD_VLP_CACHE_TTL = DAY_IN_SECONDS;
const CHILD_VLP_NEGATIVE_CACHE_TTL = HOUR_IN_SECONDS;
const CHILD_VLP_MAX_BODY_BYTES = 250000;
const CHILD_VLP_RATE_LIMIT_TTL = MINUTE_IN_SECONDS;
const CHILD_VLP_RATE_LIMIT_MAX = 10;

/**
 * Build the metadata cache key for a URL.
 */
function child_vlp_get_cache_key( string $url ): string {
	return 'child_vlp_' . md5( $url );
}

/**
 * Validate and normalize preview URLs before fetching or rendering.
 */
function child_vlp_normalize_url( string $url ): string {
	$url = esc_url_raw( trim( $url ), [ 'http', 'https' ] );

	if ( '' === $url ) {
		return '';
	}

	$parts = wp_parse_url( $url );
	if ( ! is_array( $parts ) || empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
		return '';
	}

	if ( ! in_array( strtolower( $parts['scheme'] ), [ 'http', 'https' ], true ) ) {
		return '';
	}

	if ( ! child_vlp_host_is_public( (string) $parts['host'] ) ) {
		return '';
	}

	return $url;
}


/**
 * Determine whether a hostname resolves only to public IP addresses.
 */
function child_vlp_host_is_public( string $host ): bool {
	$host = trim( $host, "[] \t\n\r\0\x0B" );
	if ( '' === $host || 'localhost' === strtolower( $host ) ) {
		return false;
	}

	if ( filter_var( $host, FILTER_VALIDATE_IP ) ) {
		return (bool) filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE );
	}

	$records = gethostbynamel( $host );
	if ( false === $records || [] === $records ) {
		return false;
	}

	foreach ( $records as $ip ) {
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
			return false;
		}
	}

	return true;
}

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
 * Get client IP for coarse unauthenticated rate limiting.
 */
function child_vlp_get_rate_limit_key(): string {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';

	return 'child_vlp_rate_' . md5( $ip );
}

/**
 * Check and increment a small rate limit for unauthenticated background fetches.
 */
function child_vlp_rate_limit_exceeded(): bool {
	$key   = child_vlp_get_rate_limit_key();
	$count = (int) get_transient( $key );

	if ( $count >= CHILD_VLP_RATE_LIMIT_MAX ) {
		return true;
	}

	set_transient( $key, $count + 1, CHILD_VLP_RATE_LIMIT_TTL );
	return false;
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

	libxml_use_internal_errors( true );
	$doc    = new DOMDocument();
	$loaded = $doc->loadHTML( $html );
	$title  = '';
	$desc   = '';
	$image  = '';

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

	return [
		'url'   => $url,
		'title' => $title,
		'desc'  => $desc,
		'image' => $image,
	];
}

/**
 * Background fetch endpoint for Visual Link Preview.
 */
function child_vlp_handle_background_fetch(): void {
	if ( child_vlp_rate_limit_exceeded() ) {
		wp_die( 'rate-limited', '', [ 'response' => 429 ] );
	}

	$url = isset( $_POST['url'] ) ? child_vlp_normalize_url( wp_unslash( $_POST['url'] ) ) : '';
	if ( '' === $url ) {
		wp_die( 'no-url', '', [ 'response' => 400 ] );
	}

	$cache_key = child_vlp_get_cache_key( $url );
	$cached    = get_transient( $cache_key );
	if ( is_array( $cached ) ) {
		wp_die( 'cached' );
	}

	$metadata = child_vlp_fetch_metadata( $url );
	$ttl      = '' === $metadata['title'] && '' === $metadata['desc'] && '' === $metadata['image'] ? CHILD_VLP_NEGATIVE_CACHE_TTL : CHILD_VLP_CACHE_TTL;
	set_transient( $cache_key, $metadata, $ttl );

	wp_die( 'ok' );
}
add_action( 'admin_post_child_vlp_fetch', 'child_vlp_handle_background_fetch' );
add_action( 'admin_post_nopriv_child_vlp_fetch', 'child_vlp_handle_background_fetch' );
