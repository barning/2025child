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
    $client_id = get_option( 'child_igdb_client_id', '' );
    $client_secret = get_option( 'child_igdb_client_secret', '' );
    
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
            'id' => $game['id'],
            'name' => $game['name'] ?? '',
            'cover_url' => $cover_url,
        ];
    }, $games_data );
    
    return [ 'games' => $games ];
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

/**
 * Add settings page for IGDB API credentials (optional)
 * Uncomment to enable settings UI
 */
/*
add_action( 'admin_menu', function() {
    add_options_page(
        'IGDB API Settings',
        'IGDB API',
        'manage_options',
        'child-igdb-settings',
        'child_igdb_settings_page'
    );
} );

function child_igdb_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    
    if ( isset( $_POST['child_igdb_save'] ) && check_admin_referer( 'child_igdb_settings' ) ) {
        update_option( 'child_igdb_client_id', sanitize_text_field( $_POST['client_id'] ?? '' ) );
        // Client secret should be stored as-is without sanitization that could alter it
        update_option( 'child_igdb_client_secret', wp_unslash( $_POST['client_secret'] ?? '' ) );
        echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
    }
    
    $client_id = get_option( 'child_igdb_client_id', '' );
    $client_secret = get_option( 'child_igdb_client_secret', '' );
    
    ?>
    <div class="wrap">
        <h1>IGDB API Settings</h1>
        <form method="post">
            <?php wp_nonce_field( 'child_igdb_settings' ); ?>
            <table class="form-table">
                <tr>
                    <th><label for="client_id">Client ID</label></th>
                    <td><input type="text" name="client_id" id="client_id" value="<?php echo esc_attr( $client_id ); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="client_secret">Client Secret</label></th>
                    <td><input type="password" name="client_secret" id="client_secret" value="<?php echo esc_attr( $client_secret ); ?>" class="regular-text"></td>
                </tr>
            </table>
            <p>
                To get IGDB API credentials:
                <ol>
                    <li>Create a Twitch account at <a href="https://dev.twitch.tv/" target="_blank">dev.twitch.tv</a></li>
                    <li>Register a new application</li>
                    <li>Copy the Client ID and Client Secret here</li>
                </ol>
            </p>
            <p class="submit">
                <button type="submit" name="child_igdb_save" class="button button-primary">Save Settings</button>
            </p>
        </form>
    </div>
    <?php
}
*/
