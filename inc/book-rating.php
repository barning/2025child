<?php
/**
 * Book Rating Block Registration
 */

// Register the block
add_action( 'init', function() {
    register_block_type( get_stylesheet_directory() . '/build/book-rating', [
        'render_callback' => require get_stylesheet_directory() . '/blocks/book-rating/render.php'
    ]);
});