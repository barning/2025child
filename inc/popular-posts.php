<?php
/**
 * Minimal replacement for WordPress Popular Posts plugin:
 * - Provides a Gutenberg block to display a curated list of posts.
 */

// Register the block
add_action( 'init', function() {
    register_block_type( get_stylesheet_directory() . '/build/popular-posts', [
        'render_callback' => require get_stylesheet_directory() . '/blocks/popular-posts/render.php',
    ] );

    // Ensure frontend styles are loaded
    $css_path = get_stylesheet_directory() . '/build/popular-posts/style-index.css';
    if ( file_exists( $css_path ) ) {
        wp_enqueue_block_style( 'child/popular-posts', [
            'handle' => 'child-popular-posts-style',
            'src'    => get_stylesheet_directory_uri() . '/build/popular-posts/style-index.css',
            'path'   => $css_path,
        ] );
    }
});

// Safety-net: enqueue style globally
add_action( 'wp_enqueue_scripts', function() {
    static $css_info = null;
    if ( null === $css_info ) {
        $css_path = get_stylesheet_directory() . '/build/popular-posts/style-index.css';
        $css_info = file_exists( $css_path ) ? [ 'path' => $css_path, 'mtime' => filemtime( $css_path ) ] : false;
    }
    if ( $css_info ) {
        wp_enqueue_style( 'child-popular-posts-style-global', get_stylesheet_directory_uri() . '/build/popular-posts/style-index.css', [], $css_info['mtime'] );
    }
}, 20 );
