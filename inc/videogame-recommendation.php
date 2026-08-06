<?php
/**
 * Videogame recommendation integration (IGDB settings + AJAX).
 *
 * @package TwentyTwentyFiveChild
 */

/**
 * Read IGDB client ID from option, then constant fallback.
 */
function child_get_igdb_client_id(): string {
	$api_key = (string) get_option( 'child_igdb_client_id', '' );

	if ( '' === $api_key && defined( 'IGDB_CLIENT_ID' ) ) {
		$api_key = (string) IGDB_CLIENT_ID;
	}

	return $api_key;
}

/** Read IGDB client secret from option, then constant fallback. */
function child_get_igdb_client_secret(): string {
	$secret = (string) get_option( 'child_igdb_client_secret', '' );

	if ( '' === $secret && defined( 'IGDB_CLIENT_SECRET' ) ) {
		$secret = (string) IGDB_CLIENT_SECRET;
	}

	return $secret;
}

/** Backwards-compatible RAWG key accessor (unused by the IGDB integration). */
function child_get_rawg_api_key(): string {
	return (string) get_option( 'child_rawg_api_key', '' );
}

/** Obtain and cache an IGDB application access token. */
function child_get_igdb_access_token() {
	$client_id     = child_get_igdb_client_id();
	$client_secret = child_get_igdb_client_secret();
	if ( '' === $client_id || '' === $client_secret ) {
		return '';
	}

	$cache_key = 'child_igdb_token_' . md5( $client_id );
	$cached    = get_transient( $cache_key );
	if ( is_string( $cached ) && '' !== $cached ) {
		return $cached;
	}

	$url  = 'https://id.twitch.tv/oauth2/token?client_id=' . rawurlencode( $client_id ) . '&client_secret=' . rawurlencode( $client_secret ) . '&grant_type=client_credentials';
	$args = [ 'timeout' => 10, 'headers' => [ 'Accept' => 'application/json' ] ];
	if ( function_exists( 'wp_safe_remote_post' ) ) {
		$response = wp_safe_remote_post( $url, $args );
	} elseif ( function_exists( 'wp_remote_post' ) ) {
		$response = wp_remote_post( $url, $args );
	} else {
		return '';
	}
	if ( is_wp_error( $response ) ) {
		return '';
	}
	$status = (int) wp_remote_retrieve_response_code( $response );
	$data   = json_decode( (string) wp_remote_retrieve_body( $response ), true );
	if ( $status < 200 || $status >= 300 || ! is_array( $data ) || empty( $data['access_token'] ) ) {
		return '';
	}

	$expires = max( 60, absint( $data['expires_in'] ?? HOUR_IN_SECONDS ) - 60 );
	set_transient( $cache_key, (string) $data['access_token'], $expires );
	return (string) $data['access_token'];
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

/** Preserve an IGDB credential unless explicitly replaced or removed. */
function child_sanitize_igdb_credential( $value, string $option ): string {
	$clear_key = $option . '_clear';
	if ( isset( $_POST[ $clear_key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return '';
	}
	$value = sanitize_text_field( (string) $value );
	return '' !== $value ? $value : (string) get_option( $option, '' );
}

function child_sanitize_igdb_client_id( $value ): string {
	return child_sanitize_igdb_credential( $value, 'child_igdb_client_id' );
}

function child_sanitize_igdb_client_secret( $value ): string {
	return child_sanitize_igdb_credential( $value, 'child_igdb_client_secret' );
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
 * Render IGDB settings page.
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
 * Register IGDB settings page.
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
 * Register IGDB settings and fields.
 */
function child_register_videogame_recommendation_settings(): void {
	register_setting( 'child_videogame_recommendation', 'child_igdb_client_id', [ 'type' => 'string', 'sanitize_callback' => 'child_sanitize_igdb_client_id', 'default' => '' ] );
	register_setting( 'child_videogame_recommendation', 'child_igdb_client_secret', [ 'type' => 'string', 'sanitize_callback' => 'child_sanitize_igdb_client_secret', 'default' => '' ] );

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
		__( 'IGDB-API-Konfiguration', 'child' ),
		'child_render_videogame_recommendation_section_description',
		'child-videogame-recommendation'
	);

	add_settings_field(
		'child_igdb_client_id',
		__( 'IGDB-Client-ID', 'child' ),
		'child_render_igdb_client_id_field',
		'child-videogame-recommendation',
		'child_videogame_recommendation_section'
	);
	add_settings_field( 'child_igdb_client_secret', __( 'IGDB-Client-Secret', 'child' ), 'child_render_igdb_client_secret_field', 'child-videogame-recommendation', 'child_videogame_recommendation_section' );

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
 * Render IGDB section description.
 */
function child_render_videogame_recommendation_section_description(): void {
	echo '<p>' . wp_kses_post( sprintf(
			/* translators: 1: URL to IGDB docs. 2: URL to SteamGridDB API docs. */
			__( 'Für den Videospiel-Block benötigst du eine kostenlose IGDB-Client-ID und ein Client-Secret. Optional kannst du einen SteamGridDB-API-Schlüssel für zusätzliche Hochformat-Cover hinterlegen. IGDB-Zugangsdaten erhältst du über die Twitch-Entwicklerkonsole unter %1$s; SteamGridDB findest du unter %2$s.', 'child' ),
			'<a href="https://dev.twitch.tv/console" target="_blank" rel="noopener noreferrer">dev.twitch.tv/console</a>',
			'<a href="https://www.steamgriddb.com/api/v2" target="_blank" rel="noopener noreferrer">steamgriddb.com/api/v2</a>'
			) ) . '</p>';
}

function child_render_igdb_client_id_field(): void {
	$has_saved = '' !== (string) get_option( 'child_igdb_client_id', '' );
	$constant  = defined( 'IGDB_CLIENT_ID' ) && ! empty( IGDB_CLIENT_ID );
	echo '<input type="text" id="child_igdb_client_id" name="child_igdb_client_id" value="" class="regular-text" autocomplete="off" placeholder="' . esc_attr( $has_saved ? __( 'Gespeichert — neue ID zum Ersetzen eingeben', 'child' ) : __( 'IGDB-Client-ID eingeben', 'child' ) ) . '" />';
	if ( $constant && ! $has_saved ) {
		echo '<p class="description">' . esc_html__( 'Aktuell wird die Client-ID aus wp-config.php verwendet.', 'child' ) . '</p>';
	}
	if ( $has_saved ) {
		echo '<label><input type="checkbox" name="child_igdb_client_id_clear" value="1" /> ' . esc_html__( 'Gespeicherte Client-ID entfernen', 'child' ) . '</label>';
	}
}

function child_render_igdb_client_secret_field(): void {
	$has_saved = '' !== (string) get_option( 'child_igdb_client_secret', '' );
	$constant  = defined( 'IGDB_CLIENT_SECRET' ) && ! empty( IGDB_CLIENT_SECRET );
	echo '<input type="password" id="child_igdb_client_secret" name="child_igdb_client_secret" value="" class="regular-text" autocomplete="new-password" placeholder="' . esc_attr( $has_saved ? __( 'Gespeichert — neues Secret zum Ersetzen eingeben', 'child' ) : __( 'IGDB-Client-Secret eingeben', 'child' ) ) . '" />';
	if ( $constant && ! $has_saved ) {
		echo '<p class="description">' . esc_html__( 'Aktuell wird das Client-Secret aus wp-config.php verwendet.', 'child' ) . '</p>';
	}
	if ( $has_saved ) {
		echo '<label><input type="checkbox" name="child_igdb_client_secret_clear" value="1" /> ' . esc_html__( 'Gespeichertes Client-Secret entfernen', 'child' ) . '</label>';
	}
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
function child_handle_igdb_search_ajax(): void {
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

	$client_id = child_get_igdb_client_id();
	$token     = child_get_igdb_access_token();
	if ( '' === $client_id || '' === $token ) {
		wp_send_json_error( 'Die IGDB-Zugangsdaten sind nicht konfiguriert. Hinterlege Client-ID und Client-Secret unter Einstellungen > Videospiel-Empfehlung oder als IGDB_CLIENT_ID und IGDB_CLIENT_SECRET in wp-config.php.', 500 );
	}

	$fields = 'fields id,name,slug,first_release_date,cover.url,websites.url,platforms.name,genres.name; search "' . str_replace( [ '\\', '"' ], [ '\\\\', '\\"' ], $query ) . '"; limit 10;';
	$args   = [ 'timeout' => 10, 'headers' => [ 'Accept' => 'application/json', 'Client-ID' => $client_id, 'Authorization' => 'Bearer ' . $token, 'Content-Type' => 'text/plain' ], 'body' => $fields ];
	if ( function_exists( 'wp_safe_remote_post' ) ) {
		$response = wp_safe_remote_post( 'https://api.igdb.com/v4/games', $args );
	} elseif ( function_exists( 'wp_remote_post' ) ) {
		$response = wp_remote_post( 'https://api.igdb.com/v4/games', $args );
	} else {
		wp_send_json_error( 'Die IGDB-API konnte nicht erreicht werden.', 502 );
	}
	if ( is_wp_error( $response ) ) {
		wp_send_json_error( 'Die IGDB-API konnte nicht erreicht werden.', 502 );
	}
	$status = (int) wp_remote_retrieve_response_code( $response );
	$data   = json_decode( (string) wp_remote_retrieve_body( $response ), true );
	if ( $status < 200 || $status >= 300 || ! is_array( $data ) ) {
		$message = 429 === $status ? 'Das IGDB-Anfragelimit wurde erreicht. Warte einen Moment und versuche es später erneut.' : ( in_array( $status, [ 401, 403 ], true ) ? 'Die IGDB-Anfrage wurde nicht autorisiert. Prüfe Client-ID und Client-Secret.' : 'Die IGDB-API hat eine unerwartete Antwort geliefert. HTTP-Statuscode: ' . $status );
		wp_send_json_error( $message, in_array( $status, [ 401, 403, 429 ], true ) ? $status : 502 );
	}

	$raw_games = array_slice( array_values( array_filter( $data, 'is_array' ) ), 0, 10 );
	$raw_games = array_values( array_filter( $raw_games, 'is_array' ) );

	$games = array_map(
		static function ( array $game, int $index ): array {
			// Keep enrichment bounded: at most three visible results, cached for a week.
			$steamgriddb_covers = $index < 3 ? child_find_steamgriddb_covers_for_game( (string) ( $game['name'] ?? '' ) ) : [];
			$cover_url          = (string) ( $steamgriddb_covers[0]['url'] ?? '' );
			$cover_format       = (string) ( $steamgriddb_covers[0]['cover_format'] ?? '' );

			$release = ! empty( $game['first_release_date'] ) ? gmdate( 'Y-m-d', absint( $game['first_release_date'] ) ) : '';
			$cover   = (string) ( $game['cover']['url'] ?? '' );
			$cover   = 0 === strpos( $cover, '//' ) ? 'https:' . $cover : $cover;
			$website = (string) ( $game['websites'][0]['url'] ?? '' );
			return [
				'id'               => $game['id'] ?? 0,
				'igdbId'           => $game['id'] ?? 0,
				'provider'         => 'igdb',
				'name'             => $game['name'] ?? '',
				'released'         => $release,
				'background_image' => $cover,
				'cover_url'        => $cover_url ?: $cover,
				'cover_format'     => $cover_format ?: 'landscape',
				'cover_variants'   => $steamgriddb_covers,
				'slug'             => $game['slug'] ?? '',
				'website'          => $website,
				'platforms'        => array_map(
					static function ( array $platform ): string {
						return $platform['name'] ?? '';
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
add_action( 'wp_ajax_child_igdb_search', 'child_handle_igdb_search_ajax' );
// Keep existing editor builds functional during migration.
add_action( 'wp_ajax_child_rawg_search', 'child_handle_igdb_search_ajax' );

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
