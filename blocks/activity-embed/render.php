<?php
/**
 * Server-side render for Activity Embed block.
 */

/**
 * Extract activity id from a URL path.
 *
 * @param string $path URL path.
 * @return string
 */
function child_activity_embed_extract_id( string $path ): string {
	$patterns = [
		'#/activities/(\d+)#',
		'#/activity/(\d+)#',
	];

	foreach ( $patterns as $pattern ) {
		if ( preg_match( $pattern, $path, $matches ) ) {
			return $matches[1];
		}
	}

	return '';
}

/**
 * Detect supported provider.
 *
 * @param string $host Parsed URL host.
 * @return string
 */
function child_activity_embed_detect_provider( string $host ): string {
	$host = strtolower( $host );
	$host = preg_replace( '/^www\./', '', $host );

	if ( 'connect.garmin.com' === $host ) {
		return 'garmin';
	}

	if ( 'strava.com' === $host || 'www.strava.com' === $host ) {
		return 'strava';
	}

	return '';
}

return function( $attributes ) {
	$wrapper_attributes = get_block_wrapper_attributes();
	$url                = isset( $attributes['url'] ) ? esc_url_raw( trim( (string) $attributes['url'] ) ) : '';

	if ( '' === $url ) {
		return sprintf(
			'<div %1$s><p class="child-activity-embed__notice">%2$s</p></div>',
			$wrapper_attributes,
			esc_html__( 'Paste Garmin or Strava activity link.', 'child' )
		);
	}

	if ( ! wp_http_validate_url( $url ) ) {
		return sprintf(
			'<div %1$s><p class="child-activity-embed__notice">%2$s</p></div>',
			$wrapper_attributes,
			esc_html__( 'Invalid URL. Please paste a valid Garmin or Strava activity link.', 'child' )
		);
	}

	$host     = (string) wp_parse_url( $url, PHP_URL_HOST );
	$path     = (string) wp_parse_url( $url, PHP_URL_PATH );
	$provider = child_activity_embed_detect_provider( $host );

	if ( '' === $provider ) {
		return sprintf(
			'<div %1$s><p class="child-activity-embed__notice">%2$s</p></div>',
			$wrapper_attributes,
			esc_html__( 'Unsupported provider. Please use a Garmin Connect or Strava activity URL.', 'child' )
		);
	}

	$embed_markup = '';

	if ( 'strava' === $provider ) {
		$embed_markup = wp_oembed_get(
			$url,
			[
				'width' => 1200,
			]
		);

		if ( false === $embed_markup ) {
			$activity_id = child_activity_embed_extract_id( $path );
			if ( '' !== $activity_id ) {
				$iframe_url   = 'https://www.strava.com/activities/' . rawurlencode( $activity_id ) . '/embed';
				$embed_markup = sprintf(
					'<div class="child-activity-embed__iframe-wrap"><iframe src="%1$s" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen title="%2$s"></iframe></div>',
					esc_url( $iframe_url ),
					esc_attr__( 'Strava activity preview', 'child' )
				);
			}
		}
	}

	if ( 'garmin' === $provider ) {
		$activity_id = child_activity_embed_extract_id( $path );
		if ( '' !== $activity_id ) {
			$iframe_url   = 'https://connect.garmin.com/modern/activity/embed/' . rawurlencode( $activity_id );
			$embed_markup = sprintf(
				'<div class="child-activity-embed__iframe-wrap"><iframe src="%1$s" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen title="%2$s"></iframe></div>',
				esc_url( $iframe_url ),
				esc_attr__( 'Garmin activity preview', 'child' )
			);
		}
	}

	if ( '' === $embed_markup ) {
		return sprintf(
			'<div %1$s><p class="child-activity-embed__notice">%2$s</p></div>',
			$wrapper_attributes,
			esc_html__( 'Could not generate an embed for this activity URL. Please check that the activity is public.', 'child' )
		);
	}

	return sprintf(
		'<div %1$s><div class="child-activity-embed__content child-activity-embed__content--%2$s">%3$s</div></div>',
		$wrapper_attributes,
		esc_attr( $provider ),
		$embed_markup
	);
};
