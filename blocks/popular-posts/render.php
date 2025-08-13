<?php
/**
 * Server-side render for Popular Posts block (styled card)
 */
return function( $attributes ) {
    $number = isset( $attributes['number'] ) ? max( 1, intval( $attributes['number'] ) ) : 5;
    $title  = isset( $attributes['title'] ) ? wp_strip_all_tags( $attributes['title'] ) : __( 'Some Favorites To Get You Started', 'child' );
    $emoji  = isset( $attributes['emoji'] ) ? wp_strip_all_tags( $attributes['emoji'] ) : '✨';

    $query = new WP_Query([
        'post_type'      => 'post',
        'posts_per_page' => $number,
        'meta_key'       => '_child_post_views',
        'orderby'        => 'meta_value_num',
        'order'          => 'DESC',
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
