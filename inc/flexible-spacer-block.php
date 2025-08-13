<?php
/**
 * Flexible Spacer Block registration
 */

add_action( 'init', function() {
    register_block_type( get_stylesheet_directory() . '/build/flexible-spacer', [
        'render_callback' => require get_stylesheet_directory() . '/blocks/flexible-spacer/render.php',
    ] );

    // Ensure frontend styles are loaded for the block
    $css_path = get_stylesheet_directory() . '/build/flexible-spacer/style-index.css';
    if ( file_exists( $css_path ) ) {
        wp_enqueue_block_style( 'child/flexible-spacer', [
            'handle' => 'child-flexible-spacer-style',
            'src'    => get_stylesheet_directory_uri() . '/build/flexible-spacer/style-index.css',
            'path'   => $css_path,
        ] );
    }
});

// Safety-net: enqueue style globally
add_action( 'wp_enqueue_scripts', function() {
    $css_path = get_stylesheet_directory() . '/build/flexible-spacer/style-index.css';
    if ( file_exists( $css_path ) ) {
        wp_enqueue_style( 'child-flexible-spacer-style-global', get_stylesheet_directory_uri() . '/build/flexible-spacer/style-index.css', [], filemtime( $css_path ) );
    }
}, 20 );
