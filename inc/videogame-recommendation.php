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
 * Preserve a saved RAWG key unless an explicit replacement or removal is requested.
 */
function child_sanitize_rawg_api_key( $value ): string {
	if ( isset( $_POST['child_rawg_api_key_clear'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Settings API verifies the request.
		return '';
	}

	$value = sanitize_text_field( (string) $value );

	return '' !== $value ? $value : (string) get_option( 'child_rawg_api_key', '' );
}

/**
 * Preserve a saved SteamGridDB key unless an explicit replacement or removal is requested.
 */
function child_sanitize_steamgriddb_api_key( $value ): string {
	if ( isset( $_POST['child_steamgriddb_api_key_clear'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Settings API verifies the request.
		return '';
	}

	$value = sanitize_text_field( (string) $value );

	return '' !== $value ? $value : (string) get_option( 'child_steamgriddb_api_key', '' );
}

/**
 * Perform a SteamGridDB API request.
 */
function child_request_steamgriddb_api( string $path ): array {
	$api_key = child_get_steamgriddb_api_key();
	if ( '' === $api_key ) {
		return [];
	}

	$data = child_provider_get_json(
		'https://www.steamgriddb.com/api/v2/' . ltrim( $path, '/' ),
		[
			'timeout' => 10,
			'headers' => [
				'Accept'        => 'application/json',
				'Authorization' => 'Bearer ' . $api_key,
			],
		],
		'steamgriddb',
		7 * DAY_IN_SECONDS
	);

	if ( is_wp_error( $data ) ) {
		return [];
	}

	return is_array( $data['data'] ?? null ) ? $data['data'] : [];
}

/**
 * Normalize portrait SteamGridDB grid images from an API response.
 *
 * @return array<int, array{url: string, cover_format: string, width: int, height: int}>
 */
function child_normalize_steamgriddb_portrait_grids( array $grids ): array {
	$variants = [];
	$seen     = [];

	foreach ( $grids as $grid ) {
		if ( ! is_array( $grid ) || empty( $grid['url'] ) ) {
			continue;
		}

		$url    = esc_url_raw( (string) $grid['url'] );
		$width  = absint( $grid['width'] ?? 0 );
		$height = absint( $grid['height'] ?? 0 );

		if ( '' === $url || isset( $seen[ $url ] ) || ( $width > 0 && $height > 0 && $height <= $width ) ) {
			continue;
		}

		$variants[]   = [
			'url'          => $url,
			'cover_format' => 'portrait',
			'width'        => $width,
			'height'       => $height,
		];
		$seen[ $url ] = true;
	}

	return array_slice( $variants, 0, 12 );
}

/**
 * Fetch portrait cover variants from SteamGridDB by game title.
 */
function child_find_steamgriddb_covers_for_game( string $title ): array {
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

	$variants = child_normalize_steamgriddb_portrait_grids(
		child_request_steamgriddb_api( 'grids/game/' . $game_id . '?dimensions=600x900' )
	);
	if ( [] !== $variants ) {
		return $variants;
	}

	return child_normalize_steamgriddb_portrait_grids(
		child_request_steamgriddb_api( 'grids/game/' . $game_id )
	);
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
			submit_button( __( 'Einstellungen speichern', 'child' ) );
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
		__( 'Videospiel-Einstellungen', 'child' ),
		__( 'Videospiel-Empfehlung', 'child' ),
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
			'sanitize_callback' => 'child_sanitize_rawg_api_key',
			'default'           => '',
		]
	);

	register_setting(
		'child_videogame_recommendation',
		'child_steamgriddb_api_key',
		[
			'type'              => 'string',
			'sanitize_callback' => 'child_sanitize_steamgriddb_api_key',
			'default'           => '',
		]
	);

	add_settings_section(
		'child_videogame_recommendation_section',
		__( 'RAWG-API-Konfiguration', 'child' ),
		'child_render_videogame_recommendation_section_description',
		'child-videogame-recommendation'
	);

	add_settings_field(
		'child_rawg_api_key',
		__( 'RAWG-API-Schlüssel', 'child' ),
		'child_render_videogame_recommendation_api_field',
		'child-videogame-recommendation',
		'child_videogame_recommendation_section'
	);

	add_settings_field(
		'child_steamgriddb_api_key',
		__( 'SteamGridDB-API-Schlüssel', 'child' ),
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
			__( 'Für den Videospiel-Block benötigst du einen kostenlosen API-Schlüssel von RAWG. Optional kannst du einen SteamGridDB-API-Schlüssel für zusätzliche Hochformat-Cover hinterlegen. Deinen RAWG-Schlüssel erhältst du unter %1$s und deinen SteamGridDB-Schlüssel unter %2$s.', 'child' ),
			'<a href="https://rawg.io/apidocs" target="_blank" rel="noopener noreferrer">rawg.io/apidocs</a>',
			'<a href="https://www.steamgriddb.com/api/v2" target="_blank" rel="noopener noreferrer">steamgriddb.com/api/v2</a>'
		)
	) . '</p>';
}

/**
 * Render RAWG key input.
 */
function child_render_videogame_recommendation_api_field(): void {
	$has_saved_key = '' !== (string) get_option( 'child_rawg_api_key', '' );
	$has_constant  = defined( 'RAWG_API_KEY' ) && ! empty( RAWG_API_KEY );

	echo '<input type="password" id="child_rawg_api_key" name="child_rawg_api_key" value="" class="regular-text" autocomplete="new-password" placeholder="' . esc_attr( $has_saved_key ? __( 'Gespeichert — neuen Schlüssel zum Ersetzen eingeben', 'child' ) : __( 'RAWG-API-Schlüssel eingeben', 'child' ) ) . '" />';

	if ( $has_constant && ! $has_saved_key ) {
		echo '<p class="description">' . esc_html__( 'Aktuell wird der API-Schlüssel aus wp-config.php verwendet. Gib hier einen Schlüssel ein, um ihn zu überschreiben.', 'child' ) . '</p>';
		return;
	}

	echo '<p class="description">' . esc_html__( 'Wird in der WordPress-Optionstabelle gespeichert. Leer lassen, um den gespeicherten Schlüssel zu behalten.', 'child' ) . '</p>';
	if ( $has_saved_key ) {
		echo '<label><input type="checkbox" name="child_rawg_api_key_clear" value="1" /> ' . esc_html__( 'Gespeicherten Schlüssel entfernen', 'child' ) . '</label>';
	}
}

/**
 * Render SteamGridDB key input.
 */
function child_render_steamgriddb_api_field(): void {
	$has_saved_key = '' !== (string) get_option( 'child_steamgriddb_api_key', '' );
	$has_constant  = defined( 'STEAMGRIDDB_API_KEY' ) && ! empty( STEAMGRIDDB_API_KEY );

	echo '<input type="password" id="child_steamgriddb_api_key" name="child_steamgriddb_api_key" value="" class="regular-text" placeholder="' . esc_attr( $has_saved_key ? __( 'Gespeichert — neuen Schlüssel zum Ersetzen eingeben', 'child' ) : __( 'SteamGridDB-API-Schlüssel eingeben', 'child' ) ) . '" autocomplete="new-password" />';

	if ( $has_constant && ! $has_saved_key ) {
		echo '<p class="description">' . esc_html__( 'Aktuell wird der SteamGridDB-API-Schlüssel aus wp-config.php verwendet. Gib hier einen Schlüssel ein, um ihn zu überschreiben.', 'child' ) . '</p>';
		return;
	}

	echo '<p class="description">' . esc_html__( 'Optional; wird in der WordPress-Optionstabelle gespeichert und serverseitig verwendet. Leer lassen, um den gespeicherten Schlüssel zu behalten.', 'child' ) . '</p>';
	if ( $has_saved_key ) {
		echo '<label><input type="checkbox" name="child_steamgriddb_api_key_clear" value="1" /> ' . esc_html__( 'Gespeicherten Schlüssel entfernen', 'child' ) . '</label>';
	}
}

/**
 * AJAX endpoint for RAWG searches from block editor.
 */
function child_handle_rawg_search_ajax(): void {
	check_ajax_referer( 'child-game-search', 'nonce' );

	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( 'Nicht autorisiert', 403 );
	}

	$query = sanitize_text_field( wp_unslash( $_GET['query'] ?? '' ) );
	if ( '' === $query ) {
		wp_send_json_error( 'Suchbegriff erforderlich', 400 );
	}
	$query_length = function_exists( 'mb_strlen' ) ? mb_strlen( $query ) : strlen( $query );
	if ( $query_length > 160 ) {
		wp_send_json_error( 'Der Suchbegriff ist zu lang', 400 );
	}

	$api_key = child_get_rawg_api_key();
	if ( '' === $api_key ) {
		wp_send_json_error( 'Der RAWG-API-Schlüssel ist nicht konfiguriert. Hinterlege ihn unter Einstellungen > Videospiel-Empfehlung oder als RAWG_API_KEY in wp-config.php.', 500 );
	}

	$data = child_provider_get_json(
		'https://api.rawg.io/api/games?key=' . rawurlencode( $api_key ) . '&search=' . rawurlencode( $query ) . '&page_size=10',
		[ 'timeout' => 10 ],
		'rawg',
		6 * HOUR_IN_SECONDS
	);

	if ( is_wp_error( $data ) ) {
		$error_data  = $data->get_error_data();
		$status_code = (int) ( $error_data['provider_status'] ?? $error_data['status'] ?? 502 );
		switch ( $status_code ) {
			case 401:
				$message = 'Die RAWG-API-Anfrage wurde nicht autorisiert. Prüfe, ob der API-Schlüssel gültig ist.';
				break;
			case 403:
				$message = 'Die RAWG-API-Anfrage wurde abgelehnt. Dein API-Schlüssel hat möglicherweise keinen Zugriff auf diese Ressource.';
				break;
			case 429:
				$message = 'Das RAWG-API-Anfragelimit wurde erreicht. Warte einen Moment und versuche es später erneut.';
				break;
			default:
				$message = 'Die RAWG-API hat eine unerwartete Antwort geliefert. HTTP-Statuscode: ' . (int) $status_code;
		}

		wp_send_json_error( $message, in_array( $status_code, [ 401, 403, 429 ], true ) ? $status_code : 502 );
	}

	$raw_games = is_array( $data['results'] ?? null ) ? array_slice( $data['results'], 0, 10 ) : [];
	$raw_games = array_values( array_filter( $raw_games, 'is_array' ) );

	$games = array_map(
		static function ( array $game, int $index ): array {
			// Keep enrichment bounded: at most three visible results, cached for a week.
			$steamgriddb_covers = $index < 3 ? child_find_steamgriddb_covers_for_game( (string) ( $game['name'] ?? '' ) ) : [];
			$cover_url          = (string) ( $steamgriddb_covers[0]['url'] ?? '' );
			$cover_format       = (string) ( $steamgriddb_covers[0]['cover_format'] ?? '' );

			return [
				'id'               => $game['id'] ?? 0,
				'name'             => $game['name'] ?? '',
				'released'         => $game['released'] ?? '',
				'background_image' => $game['background_image'] ?? '',
				'cover_url'        => $cover_url,
				'cover_format'     => $cover_format ?: 'landscape',
				'cover_variants'   => $steamgriddb_covers,
				'slug'             => $game['slug'] ?? '',
				'website'          => $game['website'] ?? '',
				'platforms'        => array_map(
					static function ( array $platform ): string {
						return $platform['platform']['name'] ?? '';
					},
					$game['platforms'] ?? []
				),
				'genres'           => array_map(
					static function ( array $genre ): string {
						return $genre['name'] ?? '';
					},
					$game['genres'] ?? []
				),
			];
		},
		$raw_games,
		array_keys( $raw_games )
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
