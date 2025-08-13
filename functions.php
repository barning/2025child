<?php
// Enqueue parent and child theme styles
add_action( 'wp_enqueue_scripts', function() {
    $parent_style = 'twentytwentyfive-style';
    wp_enqueue_style( $parent_style, get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'child-style', get_stylesheet_uri(), array( $parent_style ), wp_get_theme()->get('Version') );
});

// Auto-load all PHP files in inc/ for modular structure
foreach ( glob( get_stylesheet_directory() . '/inc/*.php' ) as $file ) {
    require_once $file;
}
