<?php
/**
 * Video Game Block Registration and IGDB API Integration
 */

// Register the block
add_action( 'init', function() {
    register_block_type( get_stylesheet_directory() . '/build/video-game', [
        'render_callback' => require get_stylesheet_directory() . '/blocks/video-game/render.php'
    ] );

    $css_path = get_stylesheet_directory() . '/build/video-game/style-index.css';
    if ( file_exists( $css_path ) ) {
        wp_enqueue_block_style( 'child/video-game', [
            'handle' => 'child-video-game-style',
            'src'    => get_stylesheet_directory_uri() . '/build/video-game/style-index.css',
            'path'   => $css_path,
        ] );
    }
} );

// Fallback: ensure frontend always has the CSS even without block supports
add_action( 'wp_enqueue_scripts', function() {
    static $css_mtime = null;
    if ( null === $css_mtime ) {
        $css_path = get_stylesheet_directory() . '/build/video-game/style-index.css';
        $css_mtime = file_exists( $css_path ) ? filemtime( $css_path ) : false;
    }
    if ( false !== $css_mtime ) {
        wp_enqueue_style( 'child-video-game-style-global', get_stylesheet_directory_uri() . '/build/video-game/style-index.css', [], $css_mtime );
    }
}, 20 );

// Add frontend ambilight effect script
add_action( 'wp_footer', function() {
    if ( ! has_block( 'child/video-game' ) ) {
        return;
    }
    ?>
    <script>
    (function() {
        // Use IIFE to avoid global namespace pollution
        window.childGameAmbilightInit = function(containerId, imgElement) {
            try {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                const container = document.getElementById(containerId);
                
                if (!container || !ctx) return;
                
                // Ensure image is loaded
                if (!imgElement.complete || !imgElement.naturalWidth) {
                    imgElement.addEventListener('load', function() {
                        window.childGameAmbilightInit(containerId, imgElement);
                    });
                    return;
                }
                
                canvas.width = 50;
                canvas.height = 50;
                
                ctx.drawImage(imgElement, 0, 0, 50, 50);
                
                const imageData = ctx.getImageData(0, 0, 50, 50);
                const data = imageData.data;
                
                let r = 0, g = 0, b = 0, count = 0;
                
                // Sample from the edges of the image for better ambilight effect
                for (let i = 0; i < data.length; i += 4) {
                    const pixelIndex = i / 4;
                    const x = pixelIndex % 50;
                    const y = Math.floor(pixelIndex / 50);
                    
                    // Only sample edge pixels
                    if (x < 5 || x > 45 || y < 5 || y > 45) {
                        r += data[i];
                        g += data[i + 1];
                        b += data[i + 2];
                        count++;
                    }
                }
                
                // Prevent division by zero and ensure we have valid data
                if (count > 0) {
                    r = Math.round(r / count);
                    g = Math.round(g / count);
                    b = Math.round(b / count);
                    container.style.setProperty('--ambilight-color', `rgb(${r}, ${g}, ${b})`);
                }
            } catch (error) {
                // Silently fail for CORS errors or other canvas issues
                console.warn('Ambilight effect error:', error);
            }
        };
    })();
    </script>
    <?php
}, 100 );

/**
 * Register REST API endpoint for IGDB search
 * 
 * Note: This implementation uses a simple search approach.
 * For production use with the real IGDB API, you would need:
 * 1. Register for IGDB API credentials (Twitch account required)
 * 2. Store Client ID and Client Secret in WordPress options
 * 3. Implement OAuth token management with proper expiration handling
 * 4. Use the IGDB v4 API endpoint: https://api.igdb.com/v4/games
 */
add_action( 'rest_api_init', function () {
    register_rest_route( 'child/v1', '/igdb-search', [
        'methods' => 'GET',
        'callback' => 'child_igdb_search',
        'permission_callback' => function() {
            return current_user_can( 'edit_posts' );
        },
        'args' => [
            'search' => [
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ],
    ] );
} );

/**
 * IGDB Search API Handler
 * 
 * This function handles searching for games via IGDB API.
 * For the actual implementation, you need to configure IGDB credentials.
 */
function child_igdb_search( $request ) {
    $search_term = $request->get_param( 'search' );
    
    // Check if IGDB credentials are configured
    $client_id = child_get_igdb_client_id();
    $client_secret = child_get_igdb_client_secret();
    
    if ( empty( $client_id ) || empty( $client_secret ) ) {
        // Return mock data for development/testing
        return child_igdb_mock_search( $search_term );
    }
    
    // Get or refresh OAuth token
    $access_token = child_igdb_get_access_token( $client_id, $client_secret );
    
    if ( is_wp_error( $access_token ) ) {
        return new WP_Error( 'igdb_auth_error', 'Failed to authenticate with IGDB', [ 'status' => 500 ] );
    }
    
    // Make request to IGDB API
    // The search term is passed as part of the query body in IGDB's query language format
    // We need to properly escape the search term for the IGDB query language
    $escaped_search = str_replace( ['"', '\\'], ['\"', '\\\\'], $search_term );
    
    $response = wp_safe_remote_post( 'https://api.igdb.com/v4/games', [
        'headers' => [
            'Client-ID' => $client_id,
            'Authorization' => 'Bearer ' . $access_token,
        ],
        'body' => 'search "' . $escaped_search . '"; fields name,cover.url; limit 5;',
        'timeout' => 15,
    ] );
    
    if ( is_wp_error( $response ) ) {
        return new WP_Error( 'igdb_request_error', 'Failed to fetch from IGDB', [ 'status' => 500 ] );
    }
    
    $response_code = wp_remote_retrieve_response_code( $response );
    if ( $response_code !== 200 ) {
        return new WP_Error( 'igdb_api_error', 'IGDB API returned an error', [ 'status' => $response_code ] );
    }
    
    $body = wp_remote_retrieve_body( $response );
    $games_data = json_decode( $body, true );
    
    if ( ! is_array( $games_data ) ) {
        return [ 'games' => [] ];
    }
    
    // Transform IGDB response to our format
    $games = array_map( function( $game ) {
        $cover_url = '';
        if ( isset( $game['cover']['url'] ) ) {
            // Convert IGDB thumbnail to larger image
            $cover_url = str_replace( 't_thumb', 't_cover_big', 'https:' . $game['cover']['url'] );
        }
        
        return [
            'id' => $game['id'] ?? 0,
            'name' => $game['name'] ?? '',
            'cover_url' => $cover_url,
        ];
    }, $games_data );
    
    // Filter out any invalid games (those without IDs)
    $games = array_filter( $games, function( $game ) {
        return ! empty( $game['id'] );
    } );
    
    return [ 'games' => array_values( $games ) ];
}

/**
 * Get or refresh IGDB OAuth access token
 */
function child_igdb_get_access_token( $client_id, $client_secret ) {
    $transient_key = 'child_igdb_access_token';
    $cached_token = get_transient( $transient_key );
    
    if ( $cached_token ) {
        return $cached_token;
    }
    
    // Request new token from Twitch OAuth
    $response = wp_safe_remote_post( 'https://id.twitch.tv/oauth2/token', [
        'body' => [
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'grant_type' => 'client_credentials',
        ],
        'timeout' => 15,
    ] );
    
    if ( is_wp_error( $response ) ) {
        return $response;
    }
    
    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );
    
    if ( ! isset( $data['access_token'] ) ) {
        return new WP_Error( 'igdb_token_error', 'Failed to get access token' );
    }
    
    // Cache token for its expiration time (minus 5 minutes for safety)
    $expires_in = isset( $data['expires_in'] ) ? (int) $data['expires_in'] - 300 : 3600;
    set_transient( $transient_key, $data['access_token'], $expires_in );
    
    return $data['access_token'];
}

/**
 * Mock search function for development/testing
 * Returns sample game data when IGDB credentials are not configured
 */
function child_igdb_mock_search( $search_term ) {
    $search_lower = strtolower( $search_term );
    
    $mock_games = [
        [
            'id' => 1,
            'name' => 'The Legend of Zelda: Breath of the Wild',
            'cover_url' => 'https://images.igdb.com/igdb/image/upload/t_cover_big/co3p2d.jpg',
        ],
        [
            'id' => 2,
            'name' => 'Super Mario Odyssey',
            'cover_url' => 'https://images.igdb.com/igdb/image/upload/t_cover_big/co1r7h.jpg',
        ],
        [
            'id' => 3,
            'name' => 'The Witcher 3: Wild Hunt',
            'cover_url' => 'https://images.igdb.com/igdb/image/upload/t_cover_big/co1wyy.jpg',
        ],
        [
            'id' => 4,
            'name' => 'Red Dead Redemption 2',
            'cover_url' => 'https://images.igdb.com/igdb/image/upload/t_cover_big/co1q1f.jpg',
        ],
        [
            'id' => 5,
            'name' => 'Elden Ring',
            'cover_url' => 'https://images.igdb.com/igdb/image/upload/t_cover_big/co4jni.jpg',
        ],
    ];
    
    // Simple filtering based on search term
    $filtered_games = array_filter( $mock_games, function( $game ) use ( $search_lower ) {
        return stripos( $game['name'], $search_lower ) !== false;
    } );
    
    // If no matches, return all games
    if ( empty( $filtered_games ) ) {
        $filtered_games = $mock_games;
    }
    
    return [ 'games' => array_values( $filtered_games ) ];
}

// Helper function to get IGDB API credentials
function child_get_igdb_client_id() {
    // First, check if it's set in options (from settings page)
    $client_id = get_option( 'child_igdb_client_id', '' );
    
    // Fallback to wp-config.php constant for backwards compatibility
    if ( empty( $client_id ) && defined( 'IGDB_CLIENT_ID' ) ) {
        $client_id = IGDB_CLIENT_ID;
    }
    
    return $client_id;
}

function child_get_igdb_client_secret() {
    // First, check if it's set in options (from settings page)
    $client_secret = get_option( 'child_igdb_client_secret', '' );
    
    // Fallback to wp-config.php constant for backwards compatibility
    if ( empty( $client_secret ) && defined( 'IGDB_CLIENT_SECRET' ) ) {
        $client_secret = IGDB_CLIENT_SECRET;
    }
    
    return $client_secret;
}

// Add settings page for IGDB API credentials
add_action( 'admin_menu', function() {
    add_options_page(
        __( 'Video Game Block Settings', 'child' ),
        __( 'Video Game Block', 'child' ),
        'manage_options',
        'child-video-game',
        function() {
            ?>
            <div class="wrap">
                <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
                <form action="options.php" method="post">
                    <?php
                    settings_fields( 'child_video_game' );
                    do_settings_sections( 'child-video-game' );
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
    register_setting( 'child_video_game', 'child_igdb_client_id', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => ''
    ] );
    
    register_setting( 'child_video_game', 'child_igdb_client_secret', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => ''
    ] );
    
    add_settings_section(
        'child_video_game_section',
        __( 'IGDB API Configuration', 'child' ),
        function() {
            echo '<p>' . sprintf(
                /* translators: %s: URL to IGDB/Twitch API settings */
                __( 'To use the Video Game block, you need a free API key from IGDB (powered by Twitch). Get your credentials at <a href="%s" target="_blank" rel="noopener noreferrer">dev.twitch.tv</a>.', 'child' ),
                'https://dev.twitch.tv/'
            ) . '</p>';
        },
        'child-video-game'
    );
    
    add_settings_field(
        'child_igdb_client_id',
        __( 'IGDB Client ID', 'child' ),
        function() {
            $value = get_option( 'child_igdb_client_id', '' );
            $has_constant = defined( 'IGDB_CLIENT_ID' ) && ! empty( IGDB_CLIENT_ID );
            
            echo '<input type="text" id="child_igdb_client_id" name="child_igdb_client_id" value="' . esc_attr( $value ) . '" class="regular-text" placeholder="' . esc_attr__( 'Enter your IGDB Client ID', 'child' ) . '" />';
            
            if ( $has_constant && empty( $value ) ) {
                echo '<p class="description">' . __( 'Currently using Client ID from wp-config.php. Enter a key here to override it.', 'child' ) . '</p>';
            } else {
                echo '<p class="description">' . __( 'Your Client ID will be stored securely in the database.', 'child' ) . '</p>';
            }
        },
        'child-video-game',
        'child_video_game_section'
    );
    
    add_settings_field(
        'child_igdb_client_secret',
        __( 'IGDB Client Secret', 'child' ),
        function() {
            $value = get_option( 'child_igdb_client_secret', '' );
            $has_constant = defined( 'IGDB_CLIENT_SECRET' ) && ! empty( IGDB_CLIENT_SECRET );
            
            echo '<input type="password" id="child_igdb_client_secret" name="child_igdb_client_secret" value="' . esc_attr( $value ) . '" class="regular-text" placeholder="' . esc_attr__( 'Enter your IGDB Client Secret', 'child' ) . '" />';
            
            if ( $has_constant && empty( $value ) ) {
                echo '<p class="description">' . __( 'Currently using Client Secret from wp-config.php. Enter a key here to override it.', 'child' ) . '</p>';
            } else {
                echo '<p class="description">' . __( 'Your Client Secret will be stored securely in the database.', 'child' ) . '</p>';
            }
        },
        'child-video-game',
        'child_video_game_section'
    );
} );
