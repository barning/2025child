<?php
/**
 * URL normalization and SSRF protection for Visual Link Preview.
 *
 * @package TwentyTwentyFiveChild
 */

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
