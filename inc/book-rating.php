<?php
/**
 * Book Rating Block Registration
 */

// Register the block + styles similar to other replacements
add_action( 'init', function() {
    register_block_type( get_stylesheet_directory() . '/build/book-rating', [
        'render_callback' => require get_stylesheet_directory() . '/blocks/book-rating/render.php'
    ] );

    $css_path = get_stylesheet_directory() . '/build/book-rating/style-index.css';
    if ( file_exists( $css_path ) ) {
        wp_enqueue_block_style( 'child/book-rating', [
            'handle' => 'child-book-rating-style',
            'src'    => get_stylesheet_directory_uri() . '/build/book-rating/style-index.css',
            'path'   => $css_path,
        ] );
    }
} );

// Fallback: ensure frontend always has the CSS even without block supports
add_action( 'wp_enqueue_scripts', function() {
    static $css_info = null;
    if ( null === $css_info ) {
        $css_path = get_stylesheet_directory() . '/build/book-rating/style-index.css';
        $css_info = file_exists( $css_path ) ? [ 'path' => $css_path, 'mtime' => filemtime( $css_path ) ] : false;
    }
    if ( $css_info ) {
        wp_enqueue_style( 'child-book-rating-style-global', get_stylesheet_directory_uri() . '/build/book-rating/style-index.css', [], $css_info['mtime'] );
    }
}, 20 );
