<?php
/**
 * Append footer links for RSS readers.
 *
 * @package TwentyTwentyFiveChild
 */

/**
 * Determine whether RSS footer links should be appended for current item.
 */
function child_should_append_rss_footer(): bool {
	if ( ! is_feed() ) {
		return false;
	}

	$post_id = get_the_ID();
	if ( ! $post_id ) {
		return false;
	}

	return get_post_type( $post_id ) === 'post';
}

/**
 * Append web and email links to RSS item content.
 */
function child_append_rss_footer_links( string $content, string $feed_type = '' ): string {
	if ( ! child_should_append_rss_footer() ) {
		return $content;
	}

	if ( '' !== $feed_type && ! in_array( $feed_type, [ 'rss2', 'atom', 'rss', 'rdf' ], true ) ) {
		return $content;
	}

	$post_id = get_the_ID();
	if ( ! $post_id ) {
		return $content;
	}

	$permalink = get_permalink( $post_id );
	if ( ! is_string( $permalink ) || $permalink === '' ) {
		return $content;
	}

	$footer  = '<hr />';
	$footer .= '<p><a href="' . esc_url( $permalink ) . '">→ View on site</a></p>';
	$footer .= '<p><a href="mailto:moin@niklasbarning.de">→ Reply via email</a></p>';

	return $content . $footer;
}
add_filter( 'the_content_feed', 'child_append_rss_footer_links', 10, 2 );
add_filter( 'the_excerpt_rss', 'child_append_rss_footer_links', 10, 2 );
