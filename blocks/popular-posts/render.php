<?php
/**
 * Server-side render for Popular Posts block (styled card)
 */
return function( $attributes ) {
    $selected_posts = isset( $attributes['selectedPosts'] ) ? $attributes['selectedPosts'] : [];
    $title = isset( $attributes['title'] ) ? wp_strip_all_tags( $attributes['title'] ) : __( 'Some Favorites To Get You Started', 'child' );
    $emoji = isset( $attributes['emoji'] ) ? wp_strip_all_tags( $attributes['emoji'] ) : '✨';

    if (empty($selected_posts)) {
        return '<div class="wp-block-child-popular-posts"><div class="child-popular-card"><p>' . esc_html__( 'Please select some posts.', 'child' ) . '</p></div></div>';
    }

    $query = new WP_Query([
        'post_type'      => 'post',
        'post__in'       => $selected_posts,
        'orderby'        => 'post__in',
        'no_found_rows'  => true,
        'post_status'    => 'publish',
    ]);

    $out  = '<div class="child-popular-card">';
    $out .= '<div class="child-popular-card__header">';
    $out .= '<div class="child-popular-card__emoji" aria-hidden="true">' . esc_html( $emoji ) . '</div>';
    $out .= '<h3 class="child-popular-card__title">' . esc_html( $title ) . '</h3>';
    $out .= '</div>';

    if ( $query->have_posts() ) {
        $out .= '<ol class="child-popular-card__list">';
        foreach ( $query->posts as $post ) {
            $out .= '<li class="child-popular-card__item"><a class="child-popular-card__link" href="' . get_permalink( $post ) . '">' . esc_html( get_the_title( $post ) ) . '</a></li>';
        }
        $out .= '</ol>';
    } else {
        $out .= '<p>' . esc_html__( 'No popular posts yet.', 'child' ) . '</p>';
    }

    $out .= '</div>';

    return '<div class="wp-block-child-popular-posts">' . $out . '</div>';
};
