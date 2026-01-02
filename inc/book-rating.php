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
    static $css_mtime = null;
    if ( null === $css_mtime ) {
        $css_path = get_stylesheet_directory() . '/build/book-rating/style-index.css';
        $css_mtime = file_exists( $css_path ) ? filemtime( $css_path ) : false;
    }
    if ( false !== $css_mtime ) {
        wp_enqueue_style( 'child-book-rating-style-global', get_stylesheet_directory_uri() . '/build/book-rating/style-index.css', [], $css_mtime );
    }
}, 20 );
