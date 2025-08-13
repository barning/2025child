<?php
/**
 * Minimal replacement for Image Placeholders plugin:
 * - Replaces missing post thumbnails with a default placeholder image.
 */

add_filter( 'post_thumbnail_html', function( $html, $post_id, $post_thumbnail_id, $size, $attr ) {
    if ( $html ) return $html;
    $placeholder_url = get_stylesheet_directory_uri() . '/placeholder.png';
    $alt = esc_attr( get_the_title( $post_id ) );
    return '<img src="' . esc_url( $placeholder_url ) . '" alt="' . $alt . '" class="wp-post-image placeholder" />';
}, 10, 5 );
