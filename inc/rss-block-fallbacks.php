<?php
/**
 * Shared, feed-safe rendering helpers for custom blocks.
 *
 * @package TwentyTwentyFiveChild
 */

/**
 * Whether a block is currently being rendered for a feed item.
 */
function child_is_feed_render(): bool {
	return is_feed();
}

/**
 * Return the current post permalink when one is available.
 */
function child_get_feed_post_url(): string {
	$post_id = get_the_ID();
	if ( ! $post_id ) {
		return '';
	}

	$permalink = get_permalink( $post_id );
	return is_string( $permalink ) ? $permalink : '';
}

/**
 * Render a compact card that remains readable when a feed reader strips styles.
 *
 * @param array{title:string,url?:string,image?:string,image_alt?:string,image_width?:int,image_height?:int,meta?:array<int,string>,description?:string,links?:array<int,array{url:string,label:string}>} $args Card data.
 */
function child_render_feed_card( array $args ): string {
	$title        = trim( (string) ( $args['title'] ?? '' ) );
	$url          = esc_url( (string) ( $args['url'] ?? '' ) );
	$image        = esc_url( (string) ( $args['image'] ?? '' ) );
	$image_alt    = (string) ( $args['image_alt'] ?? $title );
	$image_width  = max( 1, absint( $args['image_width'] ?? 600 ) );
	$image_height = max( 1, absint( $args['image_height'] ?? 900 ) );
	$meta         = is_array( $args['meta'] ?? null ) ? $args['meta'] : [];
	$description  = trim( (string) ( $args['description'] ?? '' ) );
	$links        = is_array( $args['links'] ?? null ) ? $args['links'] : [];

	if ( '' === $title && '' === $image && '' === $url ) {
		return '';
	}

	$out = '<div class="child-rss-card" style="margin:1.5em 0;padding:1em;border:1px solid #d6d6d6;border-radius:12px;">';

	if ( '' !== $image ) {
		$image_html = sprintf(
			'<img src="%s" alt="%s" width="%d" height="%d" loading="lazy" style="display:block;max-width:100%%;height:auto;margin:0 auto 1em;" />',
			$image,
			esc_attr( $image_alt ),
			$image_width,
			$image_height
		);
		$out       .= '' !== $url ? '<a href="' . $url . '">' . $image_html . '</a>' : $image_html;
	}

	if ( '' !== $title ) {
		$title_html = '<strong>' . esc_html( $title ) . '</strong>';
		$out       .= '<p style="margin:.5em 0;font-size:1.15em;">' . ( '' !== $url ? '<a href="' . $url . '">' . $title_html . '</a>' : $title_html ) . '</p>';
	}

	$meta = array_values(
		array_filter(
			array_map(
				static fn( $value ): string => trim( wp_strip_all_tags( (string) $value ) ),
				$meta
			)
		)
	);
	if ( [] !== $meta ) {
		$out .= '<p style="margin:.5em 0;">' . esc_html( implode( ' · ', $meta ) ) . '</p>';
	}

	if ( '' !== $description ) {
		$out .= '<p style="margin:.5em 0;">' . esc_html( $description ) . '</p>';
	}

	$rendered_links = [];
	foreach ( $links as $link ) {
		$link_url   = esc_url( (string) ( $link['url'] ?? '' ) );
		$link_label = trim( (string) ( $link['label'] ?? '' ) );
		if ( '' === $link_url || '' === $link_label ) {
			continue;
		}
		$rendered_links[] = '<a href="' . $link_url . '">' . esc_html( $link_label ) . '</a>';
	}
	if ( [] !== $rendered_links ) {
		$out .= '<p style="margin:.75em 0 0;">' . implode( ' · ', $rendered_links ) . '</p>';
	}

	return $out . '</div>';
}

/**
 * Render a simple link back to the feed item's website version.
 */
function child_render_feed_post_link( string $label ): string {
	$url = child_get_feed_post_url();
	if ( '' === $url ) {
		return '';
	}

	return '<p><a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></p>';
}
