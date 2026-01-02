<?php
/**
 * Media Recommendation Block Registration
 */

// TMDB API Configuration
// To use this block, add your TMDB API key to wp-config.php:
// define('TMDB_API_KEY', 'your_api_key_here');
// Get a free API key at https://www.themoviedb.org/settings/api

// Register the block + styles similar to other replacements
add_action( 'init', function() {
    register_block_type( get_stylesheet_directory() . '/build/media-recommendation', [
        'render_callback' => require get_stylesheet_directory() . '/blocks/media-recommendation/render.php'
    ] );

    $css_path = get_stylesheet_directory() . '/build/media-recommendation/style-index.css';
    if ( file_exists( $css_path ) ) {
        wp_enqueue_block_style( 'child/media-recommendation', [
            'handle' => 'child-media-recommendation-style',
            'src'    => get_stylesheet_directory_uri() . '/build/media-recommendation/style-index.css',
            'path'   => $css_path,
        ] );
    }
} );

// Fallback: ensure frontend always has the CSS even without block supports
add_action( 'wp_enqueue_scripts', function() {
    static $css_mtime = null;
    if ( null === $css_mtime ) {
        $css_path = get_stylesheet_directory() . '/build/media-recommendation/style-index.css';
        $css_mtime = file_exists( $css_path ) ? filemtime( $css_path ) : false;
    }
    if ( false !== $css_mtime ) {
        wp_enqueue_style( 'child-media-recommendation-style-global', get_stylesheet_directory_uri() . '/build/media-recommendation/style-index.css', [], $css_mtime );
    }
}, 20 );

// Add frontend ambilight effect script
add_action( 'wp_footer', function() {
    if ( ! has_block( 'child/media-recommendation' ) ) {
        return;
    }
    ?>
    <script>
    function childMediaAmbilightInit(containerId, imgElement) {
        try {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            const container = document.getElementById(containerId);
            
            if (!container || !ctx) return;
            
            canvas.width = 50;
            canvas.height = 50;
            
            ctx.drawImage(imgElement, 0, 0, 50, 50);
            
            const imageData = ctx.getImageData(0, 0, 50, 50);
            const data = imageData.data;
            
            let r = 0, g = 0, b = 0, count = 0;
            
            for (let i = 0; i < data.length; i += 4) {
                const pixelIndex = i / 4;
                const x = pixelIndex % 50;
                const y = Math.floor(pixelIndex / 50);
                
                if (x < 5 || x > 45 || y < 5 || y > 45) {
                    r += data[i];
                    g += data[i + 1];
                    b += data[i + 2];
                    count++;
                }
            }
            
            r = Math.round(r / count);
            g = Math.round(g / count);
            b = Math.round(b / count);
            
            container.style.setProperty('--ambilight-color', `rgb(${r}, ${g}, ${b})`);
        } catch (error) {
            console.warn('Ambilight effect error:', error);
        }
    }
    </script>
    <?php
}, 100 );

// AJAX endpoint for TMDB search (authenticated users)
add_action( 'wp_ajax_child_tmdb_search', function() {
    check_ajax_referer( 'child-media-search', 'nonce' );
    
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( 'Unauthorized', 403 );
    }

    $query = sanitize_text_field( $_GET['query'] ?? '' );
    if ( empty( $query ) ) {
        wp_send_json_error( 'Query required', 400 );
    }

    $api_key = defined( 'TMDB_API_KEY' ) ? TMDB_API_KEY : '';
    if ( empty( $api_key ) ) {
        wp_send_json_error( 'TMDB API key not configured. Please add TMDB_API_KEY to wp-config.php', 500 );
    }

    // Search both movies and TV shows
    $movie_response = wp_safe_remote_get(
        'https://api.themoviedb.org/3/search/movie?api_key=' . urlencode( $api_key ) . 
        '&query=' . urlencode( $query ) . '&language=de-DE',
        [ 'timeout' => 10 ]
    );

    $tv_response = wp_safe_remote_get(
        'https://api.themoviedb.org/3/search/tv?api_key=' . urlencode( $api_key ) . 
        '&query=' . urlencode( $query ) . '&language=de-DE',
        [ 'timeout' => 10 ]
    );

    if ( is_wp_error( $movie_response ) || is_wp_error( $tv_response ) ) {
        wp_send_json_error( 'API request failed', 500 );
    }

    $movie_data = json_decode( wp_remote_retrieve_body( $movie_response ), true );
    $tv_data = json_decode( wp_remote_retrieve_body( $tv_response ), true );

    $results = [
        'movies' => $movie_data['results'] ?? [],
        'tv' => $tv_data['results'] ?? []
    ];

    wp_send_json_success( $results );
} );

// Localize script with AJAX URL and nonce
add_action( 'enqueue_block_editor_assets', function() {
    wp_localize_script(
        'wp-block-editor',
        'childMediaSearch',
        [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'child-media-search' )
        ]
    );
} );
