<?php
/**
 * Register Hero Teaser block.
 */

add_action( 'init', function() {
    register_block_type( get_stylesheet_directory() . '/build/hero-teaser', [
        'render_callback' => require get_stylesheet_directory() . '/blocks/hero-teaser/render.php',
    ] );

    // Attach style to the block when available
    $css_path = get_stylesheet_directory() . '/build/hero-teaser/style-index.css';
    if ( file_exists( $css_path ) ) {
        wp_enqueue_block_style( 'child/hero-teaser', [
            'handle' => 'child-hero-teaser-style',
            'src'    => get_stylesheet_directory_uri() . '/build/hero-teaser/style-index.css',
            'path'   => $css_path,
        ] );
    }
});

// Safety-net: enqueue frontend style globally
add_action( 'wp_enqueue_scripts', function() {
    $css_path = get_stylesheet_directory() . '/build/hero-teaser/style-index.css';
    if ( file_exists( $css_path ) ) {
        wp_enqueue_style( 'child-hero-teaser-style-global', get_stylesheet_directory_uri() . '/build/hero-teaser/style-index.css', [], filemtime( $css_path ) );
    }
}, 20 );
