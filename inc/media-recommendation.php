<?php
/**
 * Media recommendation integration (TMDB settings + AJAX).
 *
 * @package TwentyTwentyFiveChild
 */

/**
 * Read TMDB key from option, then constant fallback.
 */
function child_get_tmdb_api_key(): string {
	$api_key = (string) get_option( 'child_tmdb_api_key', '' );

	if ( '' === $api_key && defined( 'TMDB_API_KEY' ) ) {
		$api_key = (string) TMDB_API_KEY;
	}

	return $api_key;
}

/**
 * Preserve a saved TMDB key unless an explicit replacement or removal is requested.
 */
function child_sanitize_tmdb_api_key( $value ): string {
	if ( isset( $_POST['child_tmdb_api_key_clear'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Settings API verifies the request.
		return '';
	}

	$value = sanitize_text_field( (string) $value );

	return '' !== $value ? $value : (string) get_option( 'child_tmdb_api_key', '' );
}

/**
 * Render the TMDB settings page.
 */
function child_render_media_recommendation_settings_page(): void {
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<form action="options.php" method="post">
			<?php
			settings_fields( 'child_media_recommendation' );
			do_settings_sections( 'child-media-recommendation' );
			submit_button( __( 'Einstellungen speichern', 'child' ) );
			?>
		</form>
	</div>
	<?php
}

/**
 * Register TMDB settings page.
 */
function child_register_media_recommendation_settings_page(): void {
	add_options_page(
		__( 'Film-/Serien-Einstellungen', 'child' ),
		__( 'Film-/Serien-Empfehlung', 'child' ),
		'manage_options',
		'child-media-recommendation',
		'child_render_media_recommendation_settings_page'
	);
}
add_action( 'admin_menu', 'child_register_media_recommendation_settings_page' );

/**
 * Register TMDB settings and field.
 */
function child_register_media_recommendation_settings(): void {
	register_setting(
		'child_media_recommendation',
		'child_tmdb_api_key',
		[
			'type'              => 'string',
			'sanitize_callback' => 'child_sanitize_tmdb_api_key',
			'default'           => '',
		]
	);

	add_settings_section(
		'child_media_recommendation_section',
		__( 'TMDB-API-Konfiguration', 'child' ),
		'child_render_media_recommendation_section_description',
		'child-media-recommendation'
	);

	add_settings_field(
		'child_tmdb_api_key',
		__( 'TMDB-API-Schlüssel', 'child' ),
		'child_render_media_recommendation_api_field',
		'child-media-recommendation',
		'child_media_recommendation_section'
	);
}
add_action( 'admin_init', 'child_register_media_recommendation_settings' );

/**
 * Render TMDB section text.
 */
function child_render_media_recommendation_section_description(): void {
	echo '<p>' . wp_kses_post(
		sprintf(
			/* translators: %s: URL to TMDB API settings */
			__( 'Für den Film-/Serien-Block benötigst du einen kostenlosen API-Schlüssel von The Movie Database. Deinen Schlüssel erhältst du unter %s.', 'child' ),
			'<a href="https://www.themoviedb.org/settings/api" target="_blank" rel="noopener noreferrer">themoviedb.org/settings/api</a>'
		)
	) . '</p>';
}

/**
 * Render TMDB key input.
 */
function child_render_media_recommendation_api_field(): void {
	$has_saved_key = '' !== (string) get_option( 'child_tmdb_api_key', '' );
	$has_constant  = defined( 'TMDB_API_KEY' ) && ! empty( TMDB_API_KEY );

	echo '<input type="password" id="child_tmdb_api_key" name="child_tmdb_api_key" value="" class="regular-text" autocomplete="new-password" placeholder="' . esc_attr( $has_saved_key ? __( 'Gespeichert — neuen Schlüssel zum Ersetzen eingeben', 'child' ) : __( 'TMDB-API-Schlüssel eingeben', 'child' ) ) . '" />';

	if ( $has_constant && ! $has_saved_key ) {
		echo '<p class="description">' . esc_html__( 'Aktuell wird der API-Schlüssel aus wp-config.php verwendet. Gib hier einen Schlüssel ein, um ihn zu überschreiben.', 'child' ) . '</p>';
		return;
	}

	echo '<p class="description">' . esc_html__( 'Wird in der WordPress-Optionstabelle gespeichert. Leer lassen, um den gespeicherten Schlüssel zu behalten.', 'child' ) . '</p>';
	if ( $has_saved_key ) {
		echo '<label><input type="checkbox" name="child_tmdb_api_key_clear" value="1" /> ' . esc_html__( 'Gespeicherten Schlüssel entfernen', 'child' ) . '</label>';
	}
}

/**
 * AJAX endpoint for TMDB searches from block editor.
 */
function child_handle_tmdb_search_ajax(): void {
	check_ajax_referer( 'child-media-search', 'nonce' );

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

	$api_key = child_get_tmdb_api_key();
	if ( '' === $api_key ) {
		wp_send_json_error( 'Der TMDB-API-Schlüssel ist nicht konfiguriert. Hinterlege ihn unter Einstellungen > Film-/Serien-Empfehlung oder als TMDB_API_KEY in wp-config.php.', 500 );
	}

	$wp_locale   = get_locale();
	$tmdb_locale = str_replace( '_', '-', $wp_locale );

	$movie_data = child_provider_get_json(
		'https://api.themoviedb.org/3/search/movie?api_key=' . rawurlencode( $api_key ) . '&query=' . rawurlencode( $query ) . '&language=' . rawurlencode( $tmdb_locale ),
		[ 'timeout' => 10 ],
		'tmdb_movie',
		6 * HOUR_IN_SECONDS
	);
	$tv_data    = child_provider_get_json(
		'https://api.themoviedb.org/3/search/tv?api_key=' . rawurlencode( $api_key ) . '&query=' . rawurlencode( $query ) . '&language=' . rawurlencode( $tmdb_locale ),
		[ 'timeout' => 10 ],
		'tmdb_tv',
		6 * HOUR_IN_SECONDS
	);

	if ( is_wp_error( $movie_data ) || is_wp_error( $tv_data ) ) {
		$error      = is_wp_error( $movie_data ) ? $movie_data : $tv_data;
		$error_data = $error->get_error_data();
		$status     = (int) ( $error_data['provider_status'] ?? $error_data['status'] ?? 502 );

		if ( in_array( $status, [ 401, 403 ], true ) ) {
			wp_send_json_error( 'Die TMDB-API-Authentifizierung ist fehlgeschlagen. Prüfe deinen API-Schlüssel.', $status );
		}
		if ( 429 === $status ) {
			wp_send_json_error( 'Das TMDB-API-Anfragelimit wurde erreicht. Warte einen Moment und versuche es erneut.', 429 );
		}

		wp_send_json_error( 'Die TMDB-API-Anfrage ist fehlgeschlagen.', 502 );
	}

	wp_send_json_success(
		[
			'movies' => is_array( $movie_data['results'] ?? null ) ? $movie_data['results'] : [],
			'tv'     => is_array( $tv_data['results'] ?? null ) ? $tv_data['results'] : [],
		]
	);
}
add_action( 'wp_ajax_child_tmdb_search', 'child_handle_tmdb_search_ajax' );

/**
 * Provide AJAX data in editor.
 */
function child_localize_media_search(): void {
	child_localize_block_editor_script(
		'child/media-recommendation',
		'childMediaSearch',
		[
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'child-media-search' ),
		]
	);
}
add_action( 'enqueue_block_editor_assets', 'child_localize_media_search' );
