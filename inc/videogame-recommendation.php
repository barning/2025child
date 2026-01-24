<?php
/**
 * Videogame Recommendation Block Registration
 */

// RAWG API Configuration
// You can configure the API key in two ways:
// 1. Via WordPress Admin: Settings > Videogame Recommendation (recommended)
// 2. Via wp-config.php: define('RAWG_API_KEY', 'your_api_key_here');
// Get a free API key at https://rawg.io/apidocs

// Helper function to get RAWG API key from settings or constant
function child_get_rawg_api_key() {
    // First, check if it's set in options (from settings page)
    $api_key = get_option( 'child_rawg_api_key', '' );
    
    // Fallback to wp-config.php constant for backwards compatibility
    if ( empty( $api_key ) && defined( 'RAWG_API_KEY' ) ) {
        $api_key = RAWG_API_KEY;
    }
    
    return $api_key;
}

// Add settings page for RAWG API key
add_action( 'admin_menu', function() {
    add_options_page(
        __( 'Videogame Recommendation Settings', 'child' ),
        __( 'Videogame Recommendation', 'child' ),
        'manage_options',
        'child-videogame-recommendation',
        function() {
            ?>
            <div class="wrap">
                <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
                <form action="options.php" method="post">
                    <?php
                    settings_fields( 'child_videogame_recommendation' );
                    do_settings_sections( 'child-videogame-recommendation' );
                    submit_button( __( 'Save Settings', 'child' ) );
                    ?>
                </form>
            </div>
            <?php
        }
    );
} );

// Register settings
add_action( 'admin_init', function() {
    register_setting( 'child_videogame_recommendation', 'child_rawg_api_key', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => ''
    ] );
    
    add_settings_section(
        'child_videogame_recommendation_section',
        __( 'RAWG API Configuration', 'child' ),
        function() {
            echo '<p>' . sprintf(
                /* translators: %s: URL to RAWG API docs */
                __( 'To use the Videogame Recommendation block, you need a free API key from RAWG. Get your API key at <a href="%s" target="_blank" rel="noopener noreferrer">rawg.io/apidocs</a>.', 'child' ),
                'https://rawg.io/apidocs'
            ) . '</p>';
        },
        'child-videogame-recommendation'
    );
    
    add_settings_field(
        'child_rawg_api_key',
        __( 'RAWG API Key', 'child' ),
        function() {
            $value = get_option( 'child_rawg_api_key', '' );
            $has_constant = defined( 'RAWG_API_KEY' ) && ! empty( RAWG_API_KEY );
            
            echo '<input type="text" id="child_rawg_api_key" name="child_rawg_api_key" value="' . esc_attr( $value ) . '" class="regular-text" placeholder="' . esc_attr__( 'Enter your RAWG API key', 'child' ) . '" />';
            
            if ( $has_constant && empty( $value ) ) {
                echo '<p class="description">' . __( 'Currently using API key from wp-config.php. Enter a key here to override it.', 'child' ) . '</p>';
            } else {
                echo '<p class="description">' . __( 'Your API key will be stored securely in the database.', 'child' ) . '</p>';
            }
        },
        'child-videogame-recommendation',
        'child_videogame_recommendation_section'
    );
} );

// Register the block + styles similar to other blocks
add_action( 'init', function() {
    register_block_type( get_stylesheet_directory() . '/build/videogame-recommendation', [
        'render_callback' => require get_stylesheet_directory() . '/blocks/videogame-recommendation/render.php'
    ] );

    $css_path = get_stylesheet_directory() . '/build/videogame-recommendation/style-index.css';
    if ( file_exists( $css_path ) ) {
        wp_enqueue_block_style( 'child/videogame-recommendation', [
            'handle' => 'child-videogame-recommendation-style',
            'src'    => get_stylesheet_directory_uri() . '/build/videogame-recommendation/style-index.css',
            'path'   => $css_path,
        ] );
    }
} );

// Fallback: ensure frontend always has the CSS even without block supports
add_action( 'wp_enqueue_scripts', function() {
    static $css_mtime = null;
    if ( null === $css_mtime ) {
        $css_path = get_stylesheet_directory() . '/build/videogame-recommendation/style-index.css';
        $css_mtime = file_exists( $css_path ) ? filemtime( $css_path ) : false;
    }
    if ( false !== $css_mtime ) {
        wp_enqueue_style( 'child-videogame-recommendation-style-global', get_stylesheet_directory_uri() . '/build/videogame-recommendation/style-index.css', [], $css_mtime );
    }
}, 20 );

// AJAX endpoint for RAWG search (authenticated users)
add_action( 'wp_ajax_child_rawg_search', function() {
    check_ajax_referer( 'child-game-search', 'nonce' );
    
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( 'Unauthorized', 403 );
    }

    $query = sanitize_text_field( $_GET['query'] ?? '' );
    if ( empty( $query ) ) {
        wp_send_json_error( 'Query required', 400 );
    }

    $api_key = child_get_rawg_api_key();
    if ( empty( $api_key ) ) {
        wp_send_json_error( 'RAWG API key not configured. Please configure it in Settings > Videogame Recommendation or add RAWG_API_KEY to wp-config.php', 500 );
    }

    // Search games using RAWG API
    $response = wp_safe_remote_get(
        'https://api.rawg.io/api/games?key=' . urlencode( $api_key ) . 
        '&search=' . urlencode( $query ) . '&page_size=10',
        [ 'timeout' => 10 ]
    );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( 'API request failed', 500 );
    }

    $status_code = wp_remote_retrieve_response_code( $response );
    if ( 200 !== $status_code ) {
        switch ( $status_code ) {
            case 401:
                $message = 'RAWG API request unauthorized. Please check that your API key is valid.';
                break;
            case 403:
                $message = 'RAWG API request forbidden. Your API key may not have access to this resource.';
                break;
            case 429:
                $message = 'RAWG API rate limit exceeded. Please wait and try again later.';
                break;
            default:
                $message = 'RAWG API returned an unexpected response. HTTP status code: ' . intval( $status_code );
                break;
        }

        wp_send_json_error( $message, $status_code );
    }

    $data = json_decode( wp_remote_retrieve_body( $response ), true );

    // Enrich results with formatted data
    $games = array_map( function( $game ) {
        return [
            'id' => $game['id'] ?? 0,
            'name' => $game['name'] ?? '',
            'released' => $game['released'] ?? '',
            'background_image' => $game['background_image'] ?? '',
            'platforms' => array_map( function( $platform ) {
                return $platform['platform']['name'] ?? '';
            }, $game['platforms'] ?? [] ),
            'genres' => array_map( function( $genre ) {
                return $genre['name'] ?? '';
            }, $game['genres'] ?? [] )
        ];
    }, $data['results'] ?? [] );

    $results = [
        'games' => $games
    ];

    wp_send_json_success( $results );
} );

// Localize script with AJAX URL and nonce
add_action( 'enqueue_block_editor_assets', function() {
    wp_localize_script(
        'wp-block-editor',
        'childGameSearch',
        [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'child-game-search' )
        ]
    );
} );
