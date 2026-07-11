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
 * Read SteamGridDB key from option, then constant fallback.
 */
function child_get_steamgriddb_api_key(): string {
	$api_key = (string) get_option( 'child_steamgriddb_api_key', '' );

	if ( '' === $api_key && defined( 'STEAMGRIDDB_API_KEY' ) ) {
		$api_key = (string) STEAMGRIDDB_API_KEY;
	}

	return $api_key;
}

/**
 * Perform a SteamGridDB API request.
 */
function child_request_steamgriddb_api( string $path ): array {
	$api_key = child_get_steamgriddb_api_key();
	if ( '' === $api_key ) {
		return [];
	}

	$response = wp_safe_remote_get(
		'https://www.steamgriddb.com/api/v2/' . ltrim( $path, '/' ),
		[
			'timeout' => 10,
			'headers' => [
				'Accept'        => 'application/json',
				'Authorization' => 'Bearer ' . $api_key,
			],
		]
	);

	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		return [];
	}

	$data = json_decode( wp_remote_retrieve_body( $response ), true );

	return is_array( $data['data'] ?? null ) ? $data['data'] : [];
}

/**
 * Pick the best portrait SteamGridDB grid image from an API response.
 */
function child_pick_steamgriddb_portrait_grid( array $grids ): array {
	foreach ( $grids as $grid ) {
		if ( ! is_array( $grid ) || empty( $grid['url'] ) ) {
			continue;
		}

		$width  = absint( $grid['width'] ?? 0 );
		$height = absint( $grid['height'] ?? 0 );

		if ( $width > 0 && $height > 0 && $height <= $width ) {
			continue;
		}

		return [
			'cover_url'    => esc_url_raw( (string) $grid['url'] ),
			'cover_format' => 'portrait',
		];
	}

	return [];
}

/**
 * Fetch a portrait cover from SteamGridDB by game title.
 */
function child_find_steamgriddb_cover_for_game( string $title ): array {
	$title = trim( $title );
	if ( '' === $title || '' === child_get_steamgriddb_api_key() ) {
		return [];
	}

	$games = child_request_steamgriddb_api( 'search/autocomplete/' . rawurlencode( $title ) );
	if ( [] === $games || empty( $games[0]['id'] ) ) {
		return [];
	}

	$game_id = absint( $games[0]['id'] );
	if ( 0 === $game_id ) {
		return [];
	}

	$grids = child_request_steamgriddb_api( 'grids/game/' . $game_id . '?dimensions=600x900' );
	$cover = child_pick_steamgriddb_portrait_grid( $grids );
	if ( [] !== $cover ) {
		return $cover;
	}

	$grids = child_request_steamgriddb_api( 'grids/game/' . $game_id );

	return child_pick_steamgriddb_portrait_grid( $grids );
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
		'child_steamgriddb_api_key',
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
		'child_steamgriddb_api_key',
		__( 'SteamGridDB API Key', 'child' ),
		'child_render_steamgriddb_api_field',
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
			/* translators: 1: URL to RAWG API docs. 2: URL to SteamGridDB API docs. */
			__( 'To use the Videogame Recommendation block, you need a free API key from RAWG. Add an optional SteamGridDB API key to enrich results with portrait game covers. Get your RAWG API key at %1$s and your SteamGridDB API key at %2$s.', 'child' ),
			'<a href="https://rawg.io/apidocs" target="_blank" rel="noopener noreferrer">rawg.io/apidocs</a>',
			'<a href="https://www.steamgriddb.com/api/v2" target="_blank" rel="noopener noreferrer">steamgriddb.com/api/v2</a>'
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
 * Render SteamGridDB key input.
 */
function child_render_steamgriddb_api_field(): void {
	$value        = (string) get_option( 'child_steamgriddb_api_key', '' );
	$has_constant = defined( 'STEAMGRIDDB_API_KEY' ) && ! empty( STEAMGRIDDB_API_KEY );

	echo '<input type="password" id="child_steamgriddb_api_key" name="child_steamgriddb_api_key" value="' . esc_attr( $value ) . '" class="regular-text" placeholder="' . esc_attr__( 'Enter your SteamGridDB API key', 'child' ) . '" autocomplete="new-password" />';

	if ( $has_constant && '' === $value ) {
		echo '<p class="description">' . esc_html__( 'Currently using SteamGridDB API key from wp-config.php. Enter a key here to override it.', 'child' ) . '</p>';
		return;
	}

	echo '<p class="description">' . esc_html__( 'Optional: Used server-side to fetch portrait videogame covers from SteamGridDB.', 'child' ) . '</p>';
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
			$steamgriddb_cover  = child_find_steamgriddb_cover_for_game( (string) ( $game['name'] ?? '' ) );
			$cover_url    = (string) ( $steamgriddb_cover['cover_url'] ?? '' );
			$cover_format = (string) ( $steamgriddb_cover['cover_format'] ?? '' );

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
