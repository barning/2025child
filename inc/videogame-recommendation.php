<?php
/**
 * Videogame recommendation integration (RAWG settings + AJAX).
 *
 * @package TwentyTwentyFiveChild
 */

/**
 * Read RAWG key from option, then constant fallback.
 */
function child_get_rawg_api_key(): string {
	$api_key = (string) get_option( 'child_rawg_api_key', '' );

	if ( '' === $api_key && defined( 'RAWG_API_KEY' ) ) {
		$api_key = (string) RAWG_API_KEY;
	}

	return $api_key;
}

/**
 * Read IGDB client ID from option, then constant fallback.
 */
function child_get_igdb_client_id(): string {
	$client_id = (string) get_option( 'child_igdb_client_id', '' );

	if ( '' === $client_id && defined( 'IGDB_CLIENT_ID' ) ) {
		$client_id = (string) IGDB_CLIENT_ID;
	}

	return $client_id;
}

/**
 * Read IGDB client secret from option, then constant fallback.
 */
function child_get_igdb_client_secret(): string {
	$client_secret = (string) get_option( 'child_igdb_client_secret', '' );

	if ( '' === $client_secret && defined( 'IGDB_CLIENT_SECRET' ) ) {
		$client_secret = (string) IGDB_CLIENT_SECRET;
	}

	return $client_secret;
}

/**
 * Get a cached Twitch app token for IGDB requests.
 */
function child_get_igdb_access_token(): string {
	$client_id     = child_get_igdb_client_id();
	$client_secret = child_get_igdb_client_secret();

	if ( '' === $client_id || '' === $client_secret ) {
		return '';
	}

	$cached_token = get_transient( 'child_igdb_access_token' );
	if ( is_string( $cached_token ) && '' !== $cached_token ) {
		return $cached_token;
	}

	$response = wp_safe_remote_post(
		'https://id.twitch.tv/oauth2/token',
		[
			'timeout' => 10,
			'body'    => [
				'client_id'     => $client_id,
				'client_secret' => $client_secret,
				'grant_type'    => 'client_credentials',
			],
		]
	);

	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		return '';
	}

	$data         = json_decode( wp_remote_retrieve_body( $response ), true );
	$access_token = is_array( $data ) ? (string) ( $data['access_token'] ?? '' ) : '';
	$expires_in   = is_array( $data ) ? absint( $data['expires_in'] ?? HOUR_IN_SECONDS ) : HOUR_IN_SECONDS;

	if ( '' === $access_token ) {
		return '';
	}

	set_transient( 'child_igdb_access_token', $access_token, max( HOUR_IN_SECONDS, $expires_in - 300 ) );

	return $access_token;
}

/**
 * Build an IGDB image URL for a cover image ID.
 */
function child_build_igdb_cover_url( string $image_id, string $size = 'cover_big' ): string {
	$image_id = preg_replace( '/[^a-zA-Z0-9_-]/', '', $image_id );
	$size     = preg_replace( '/[^a-zA-Z0-9_]/', '', $size );

	if ( '' === $image_id ) {
		return '';
	}

	return esc_url_raw( sprintf( 'https://images.igdb.com/igdb/image/upload/t_%s/%s.jpg', $size ?: 'cover_big', $image_id ) );
}

/**
 * Fetch a portrait cover from IGDB by game title.
 */
function child_find_igdb_cover_for_game( string $title ): array {
	$title        = trim( $title );
	$client_id    = child_get_igdb_client_id();
	$access_token = child_get_igdb_access_token();

	if ( '' === $title || '' === $client_id || '' === $access_token ) {
		return [];
	}

	$response = wp_safe_remote_post(
		'https://api.igdb.com/v4/games',
		[
			'timeout' => 10,
			'headers' => [
				'Accept'        => 'application/json',
				'Authorization' => 'Bearer ' . $access_token,
				'Client-ID'     => $client_id,
			],
			'body'    => sprintf(
				'search "%s"; fields cover.image_id,cover.width,cover.height; where cover != null; limit 1;',
				addcslashes( $title, '\\"' )
			),
		]
	);

	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		return [];
	}

	$data  = json_decode( wp_remote_retrieve_body( $response ), true );
	$cover = is_array( $data[0]['cover'] ?? null ) ? $data[0]['cover'] : [];

	if ( empty( $cover['image_id'] ) ) {
		return [];
	}

	return [
		'cover_url'    => child_build_igdb_cover_url( (string) $cover['image_id'], 'cover_big' ),
		'cover_format' => 'portrait',
	];
}

/**
 * Render RAWG settings page.
 */
function child_render_videogame_recommendation_settings_page(): void {
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

/**
 * Register RAWG settings page.
 */
function child_register_videogame_recommendation_settings_page(): void {
	add_options_page(
		__( 'Videogame Recommendation Settings', 'child' ),
		__( 'Videogame Recommendation', 'child' ),
		'manage_options',
		'child-videogame-recommendation',
		'child_render_videogame_recommendation_settings_page'
	);
}
add_action( 'admin_menu', 'child_register_videogame_recommendation_settings_page' );

/**
 * Register RAWG settings and field.
 */
function child_register_videogame_recommendation_settings(): void {
	register_setting(
		'child_videogame_recommendation',
		'child_rawg_api_key',
		[
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		]
	);

	register_setting(
		'child_videogame_recommendation',
		'child_igdb_client_id',
		[
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		]
	);

	register_setting(
		'child_videogame_recommendation',
		'child_igdb_client_secret',
		[
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		]
	);

	add_settings_section(
		'child_videogame_recommendation_section',
		__( 'RAWG API Configuration', 'child' ),
		'child_render_videogame_recommendation_section_description',
		'child-videogame-recommendation'
	);

	add_settings_field(
		'child_rawg_api_key',
		__( 'RAWG API Key', 'child' ),
		'child_render_videogame_recommendation_api_field',
		'child-videogame-recommendation',
		'child_videogame_recommendation_section'
	);

	add_settings_field(
		'child_igdb_client_id',
		__( 'IGDB Client ID', 'child' ),
		'child_render_igdb_client_id_field',
		'child-videogame-recommendation',
		'child_videogame_recommendation_section'
	);

	add_settings_field(
		'child_igdb_client_secret',
		__( 'IGDB Client Secret', 'child' ),
		'child_render_igdb_client_secret_field',
		'child-videogame-recommendation',
		'child_videogame_recommendation_section'
	);
}
add_action( 'admin_init', 'child_register_videogame_recommendation_settings' );

/**
 * Render RAWG section description.
 */
function child_render_videogame_recommendation_section_description(): void {
	echo '<p>' . wp_kses_post(
		sprintf(
			/* translators: %s: URL to RAWG API docs */
			__( 'To use the Videogame Recommendation block, you need a free API key from RAWG. Add optional IGDB credentials to enrich results with portrait game covers. Get your RAWG API key at %s.', 'child' ),
			'<a href="https://rawg.io/apidocs" target="_blank" rel="noopener noreferrer">rawg.io/apidocs</a>'
		)
	) . '</p>';
}

/**
 * Render RAWG key input.
 */
function child_render_videogame_recommendation_api_field(): void {
	$value        = (string) get_option( 'child_rawg_api_key', '' );
	$has_constant = defined( 'RAWG_API_KEY' ) && ! empty( RAWG_API_KEY );

	echo '<input type="text" id="child_rawg_api_key" name="child_rawg_api_key" value="' . esc_attr( $value ) . '" class="regular-text" placeholder="' . esc_attr__( 'Enter your RAWG API key', 'child' ) . '" />';

	if ( $has_constant && '' === $value ) {
		echo '<p class="description">' . esc_html__( 'Currently using API key from wp-config.php. Enter a key here to override it.', 'child' ) . '</p>';
		return;
	}

	echo '<p class="description">' . esc_html__( 'Your API key will be stored securely in the database.', 'child' ) . '</p>';
}

/**
 * Render IGDB client ID input.
 */
function child_render_igdb_client_id_field(): void {
	$value        = (string) get_option( 'child_igdb_client_id', '' );
	$has_constant = defined( 'IGDB_CLIENT_ID' ) && ! empty( IGDB_CLIENT_ID );

	echo '<input type="text" id="child_igdb_client_id" name="child_igdb_client_id" value="' . esc_attr( $value ) . '" class="regular-text" placeholder="' . esc_attr__( 'Enter your IGDB Client ID', 'child' ) . '" />';

	if ( $has_constant && '' === $value ) {
		echo '<p class="description">' . esc_html__( 'Currently using IGDB Client ID from wp-config.php. Enter a value here to override it.', 'child' ) . '</p>';
		return;
	}

	echo '<p class="description">' . esc_html__( 'Optional: Used to fetch portrait videogame covers from IGDB.', 'child' ) . '</p>';
}

/**
 * Render IGDB client secret input.
 */
function child_render_igdb_client_secret_field(): void {
	$value        = (string) get_option( 'child_igdb_client_secret', '' );
	$has_constant = defined( 'IGDB_CLIENT_SECRET' ) && ! empty( IGDB_CLIENT_SECRET );

	echo '<input type="password" id="child_igdb_client_secret" name="child_igdb_client_secret" value="' . esc_attr( $value ) . '" class="regular-text" placeholder="' . esc_attr__( 'Enter your IGDB Client Secret', 'child' ) . '" autocomplete="new-password" />';

	if ( $has_constant && '' === $value ) {
		echo '<p class="description">' . esc_html__( 'Currently using IGDB Client Secret from wp-config.php. Enter a value here to override it.', 'child' ) . '</p>';
		return;
	}

	echo '<p class="description">' . esc_html__( 'Optional: Stored in the database and only used server-side for IGDB access tokens.', 'child' ) . '</p>';
}

/**
 * AJAX endpoint for RAWG searches from block editor.
 */
function child_handle_rawg_search_ajax(): void {
	check_ajax_referer( 'child-game-search', 'nonce' );

	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( 'Unauthorized', 403 );
	}

	$query = sanitize_text_field( wp_unslash( $_GET['query'] ?? '' ) );
	if ( '' === $query ) {
		wp_send_json_error( 'Query required', 400 );
	}

	$api_key = child_get_rawg_api_key();
	if ( '' === $api_key ) {
		wp_send_json_error( 'RAWG API key not configured. Please configure it in Settings > Videogame Recommendation or add RAWG_API_KEY to wp-config.php', 500 );
	}

	$response = wp_safe_remote_get(
		'https://api.rawg.io/api/games?key=' . rawurlencode( $api_key ) . '&search=' . rawurlencode( $query ) . '&page_size=10',
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
				$message = 'RAWG API returned an unexpected response. HTTP status code: ' . (int) $status_code;
		}

		wp_send_json_error( $message, $status_code );
	}

	$data = json_decode( wp_remote_retrieve_body( $response ), true );

	$games = array_map(
		static function( array $game ): array {
			$igdb_cover  = child_find_igdb_cover_for_game( (string) ( $game['name'] ?? '' ) );
			$cover_url   = (string) ( $igdb_cover['cover_url'] ?? '' );
			$cover_format = (string) ( $igdb_cover['cover_format'] ?? '' );

			return [
				'id'               => $game['id'] ?? 0,
				'name'             => $game['name'] ?? '',
				'released'         => $game['released'] ?? '',
				'background_image' => $game['background_image'] ?? '',
				'cover_url'        => $cover_url,
				'cover_format'     => $cover_format ?: 'landscape',
				'slug'             => $game['slug'] ?? '',
				'website'          => $game['website'] ?? '',
				'platforms'        => array_map(
					static function( array $platform ): string {
						return $platform['platform']['name'] ?? '';
					},
					$game['platforms'] ?? []
				),
				'genres'           => array_map(
					static function( array $genre ): string {
						return $genre['name'] ?? '';
					},
					$game['genres'] ?? []
				),
			];
		},
		$data['results'] ?? []
	);

	wp_send_json_success( [ 'games' => $games ] );
}
add_action( 'wp_ajax_child_rawg_search', 'child_handle_rawg_search_ajax' );

/**
 * Provide AJAX data in editor.
 */
function child_localize_videogame_search(): void {
	child_localize_block_editor_script(
		'child/videogame-recommendation',
		'childGameSearch',
		[
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'child-game-search' ),
		]
	);
}
add_action( 'enqueue_block_editor_assets', 'child_localize_videogame_search' );
