<?php
/**
 * Server-side render for the Popular Posts block.
 *
 * @package TwentyTwentyFiveChild
 */

declare( strict_types=1 );

namespace Child\Blocks\PopularPosts;

const DEFAULT_TITLE = 'Some Favorites To Get You Started';
const DEFAULT_EMOJI = '✨';

/**
 * Render the card header.
 *
 * @param string $emoji Header emoji.
 * @param string $title Header title.
 * @return string
 */
function render_header( string $emoji, string $title ): string {
	return sprintf(
		'<div class="child-popular-card__header">
			<div class="child-popular-card__emoji" aria-hidden="true">%s</div>
			<h3 class="child-popular-card__title">%s</h3>
		</div>',
		esc_html( $emoji ),
		esc_html( $title )
	);
}

/**
 * Render the selected posts.
 *
 * @param \WP_Query $query Selected-post query.
 * @return string
 */
function render_posts_list( \WP_Query $query ): string {
	if ( ! $query->have_posts() ) {
		return sprintf( '<p>%s</p>', esc_html__( 'No popular posts yet.', 'child' ) );
	}

	$items = array_map(
		static function ( $post ): string {
			return sprintf(
				'<li class="child-popular-card__item">
					<a class="child-popular-card__link" href="%s">%s</a>
				</li>',
				esc_url( get_permalink( $post ) ),
				esc_html( get_the_title( $post ) )
			);
		},
		$query->posts
	);

	return sprintf( '<ul class="child-popular-card__list">%s</ul>', implode( '', $items ) );
}

/**
 * Render callback.
 *
 * @param array<string, mixed> $attributes Block attributes.
 * @return string
 */
return function ( array $attributes ): string {
	$selected_posts = array_map( 'absint', $attributes['selectedPosts'] ?? array() );
	$title          = wp_strip_all_tags( $attributes['title'] ?? __( 'Some Favorites To Get You Started', 'child' ) );
	$emoji          = wp_strip_all_tags( $attributes['emoji'] ?? DEFAULT_EMOJI );

	if ( empty( $selected_posts ) ) {
		return sprintf(
			'<div class="wp-block-child-popular-posts"><div class="child-popular-card"><p>%s</p></div></div>',
			esc_html__( 'Please select some posts.', 'child' )
		);
	}

	$query = new \WP_Query(
		array(
			'post_type'     => 'post',
			'post__in'      => $selected_posts,
			'orderby'       => 'post__in',
			'no_found_rows' => true,
			'post_status'   => 'publish',
		)
	);

	$content = sprintf(
		'<div class="child-popular-card">%s%s</div>',
		render_header( $emoji, $title ),
		render_posts_list( $query )
	);

	return sprintf( '<div class="wp-block-child-popular-posts">%s</div>', $content );
};
