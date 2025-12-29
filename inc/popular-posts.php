<?php
/**
 * Minimal replacement for WordPress Popular Posts plugin:
 * - Tracks post views in post meta.
 * - Provides a Gutenberg block to display the top N posts by views.
 */

// Increment post views on single post view
add_action( 'wp', function() {
    if ( is_singular( 'post' ) ) {
        $post_id = get_queried_object_id();
        // Avoid counting views from users who can edit posts (admins/editors)
        if ( current_user_can( 'edit_posts' ) ) {
            return;
        }

        // Debounce repeated view increments from the same visitor by using a cookie.
        // This reduces DB write load under high traffic while keeping view counts
        // roughly representative. Cookie expires after 6 hours by default.
        $cookie_name = 'child_viewed_' . $post_id;
        if ( empty( $_COOKIE[ $cookie_name ] ) ) {
            $views = (int) get_post_meta( $post_id, '_child_post_views', true );
            update_post_meta( $post_id, '_child_post_views', $views + 1 );
            $expire = time() + ( 6 * HOUR_IN_SECONDS );
            $path = defined( 'COOKIEPATH' ) ? COOKIEPATH : '/';
            setcookie( $cookie_name, '1', $expire, $path );
            // Also populate superglobal so further logic in the same request sees the cookie
            $_COOKIE[ $cookie_name ] = '1';
        }
    }
});

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
    $css_path = get_stylesheet_directory() . '/build/popular-posts/style-index.css';
    if ( file_exists( $css_path ) ) {
        wp_enqueue_style( 'child-popular-posts-style-global', get_stylesheet_directory_uri() . '/build/popular-posts/style-index.css', [], filemtime( $css_path ) );
    }
}, 20 );
