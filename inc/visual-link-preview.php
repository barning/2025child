<?php
/**
 * Minimal replacement for Visual Link Preview plugin:
 * - Provides a Gutenberg block to display a rich preview for a given URL using oEmbed/OG.
 */

add_action( 'init', function() {
    register_block_type( get_stylesheet_directory() . '/build/visual-link-preview', [
        'render_callback' => require get_stylesheet_directory() . '/blocks/visual-link-preview/render.php',
    ] );

    // Attach style to the block (conditional when block is present)
    $css_path = get_stylesheet_directory() . '/build/visual-link-preview/style-index.css';
    if ( file_exists( $css_path ) ) {
        wp_enqueue_block_style( 'child/visual-link-preview', [
            'handle' => 'child-visual-link-preview-style',
            'src'    => get_stylesheet_directory_uri() . '/build/visual-link-preview/style-index.css',
            'path'   => $css_path,
        ] );
    }
});

// Safety-net: enqueue the frontend style globally so it is always available
add_action( 'wp_enqueue_scripts', function() {
    static $css_mtime = null;
    if ( null === $css_mtime ) {
        $css_path = get_stylesheet_directory() . '/build/visual-link-preview/style-index.css';
        $css_mtime = file_exists( $css_path ) ? filemtime( $css_path ) : false;
    }
    if ( false !== $css_mtime ) {
        wp_enqueue_style( 'child-visual-link-preview-style-global', get_stylesheet_directory_uri() . '/build/visual-link-preview/style-index.css', [], $css_mtime );
    }
}, 20 );
