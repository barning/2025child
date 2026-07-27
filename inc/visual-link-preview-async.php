<?php
/**
 * Background fetch helpers for Visual Link Preview.
 *
 * @package TwentyTwentyFiveChild
 */

const CHILD_VLP_CACHE_TTL          = DAY_IN_SECONDS;
const CHILD_VLP_NEGATIVE_CACHE_TTL = HOUR_IN_SECONDS;
const CHILD_VLP_STALE_CACHE_TTL    = WEEK_IN_SECONDS;
const CHILD_VLP_MAX_BODY_BYTES     = 250000;
const CHILD_VLP_FETCH_LOCK_TTL     = 30;
const CHILD_VLP_REFRESH_HOOK       = 'child_vlp_refresh_metadata';

/**
 * Build the metadata cache key for a URL.
 */
function child_vlp_get_cache_key( string $url ): string {
	return 'child_vlp_' . md5( $url );
}

/**
 * Normalize preview URLs without DNS resolution so cache lookups stay fast and reliable.
 */
function child_vlp_normalize_url_for_cache( string $url ): string {
	$url = esc_url_raw( trim( $url ), [ 'http', 'https' ] );

	if ( '' === $url ) {
		return '';
	}

	$parts = wp_parse_url( $url );
	if ( ! is_array( $parts ) || empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
		return '';
	}

	if (
		! in_array( strtolower( $parts['scheme'] ), [ 'http', 'https' ], true )
		|| isset( $parts['user'] )
		|| isset( $parts['pass'] )
		|| ( isset( $parts['port'] ) && ! in_array( (int) $parts['port'], [ 80, 443 ], true ) )
	) {
		return '';
	}

	return $url;
}

/**
 * Validate and normalize preview URLs before fetching or rendering.
 */
function child_vlp_normalize_url( string $url ): string {
	$url = child_vlp_normalize_url_for_cache( $url );

	if ( '' === $url ) {
		return '';
	}

	$parts = wp_parse_url( $url );
	if ( ! is_array( $parts ) || empty( $parts['host'] ) || ! child_vlp_host_is_public( (string) $parts['host'] ) ) {
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

	$records = [];

	if ( function_exists( 'dns_get_record' ) ) {
		$dns_records = dns_get_record( $host, DNS_A | DNS_AAAA );
		if ( is_array( $dns_records ) ) {
			foreach ( $dns_records as $record ) {
				if ( isset( $record['ip'] ) ) {
					$records[] = $record['ip'];
				} elseif ( isset( $record['ipv6'] ) ) {
					$records[] = $record['ipv6'];
				}
			}
		}
	} else {
		$ipv4_records = gethostbynamel( $host );
		$records      = is_array( $ipv4_records ) ? $ipv4_records : [];
	}

	if ( [] === $records ) {
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
