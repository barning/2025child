<?php
/**
 * Server-side render for Popular Posts block
 */
return function( $attributes ) {
    $number = isset( $attributes['number'] ) ? intval( $attributes['number'] ) : 5;
    $query = new WP_Query([
        'post_type'      => 'post',
        'posts_per_page' => $number,
        'meta_key'       => '_child_post_views',
        'orderby'        => 'meta_value_num',
        'order'          => 'DESC',
        'no_found_rows'  => true,
        'post_status'    => 'publish',
    ]);
    if ( ! $query->have_posts() ) return '';
    $out = '<div class="wp-block-child-popular-posts"><ul>';
    foreach ( $query->posts as $post ) {
        $out .= '<li><a href="' . get_permalink( $post ) . '">' . esc_html( get_the_title( $post ) ) . '</a></li>';
    }
    $out .= '</ul></div>';
    wp_reset_postdata();
    return $out;
};
