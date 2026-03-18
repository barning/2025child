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
			submit_button( __( 'Save Settings', 'child' ) );
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
		__( 'Media Recommendation Settings', 'child' ),
		__( 'Media Recommendation', 'child' ),
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
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		]
	);

	add_settings_section(
		'child_media_recommendation_section',
		__( 'TMDB API Configuration', 'child' ),
		'child_render_media_recommendation_section_description',
		'child-media-recommendation'
	);

	add_settings_field(
		'child_tmdb_api_key',
		__( 'TMDB API Key', 'child' ),
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
			__( 'To use the Media Recommendation block, you need a free API key from The Movie Database. Get your API key at %s.', 'child' ),
			'<a href="https://www.themoviedb.org/settings/api" target="_blank" rel="noopener noreferrer">themoviedb.org/settings/api</a>'
		)
	) . '</p>';
}

/**
 * Render TMDB key input.
 */
function child_render_media_recommendation_api_field(): void {
	$value        = (string) get_option( 'child_tmdb_api_key', '' );
	$has_constant = defined( 'TMDB_API_KEY' ) && ! empty( TMDB_API_KEY );

	echo '<input type="text" id="child_tmdb_api_key" name="child_tmdb_api_key" value="' . esc_attr( $value ) . '" class="regular-text" placeholder="' . esc_attr__( 'Enter your TMDB API key', 'child' ) . '" />';

	if ( $has_constant && '' === $value ) {
		echo '<p class="description">' . esc_html__( 'Currently using API key from wp-config.php. Enter a key here to override it.', 'child' ) . '</p>';
		return;
	}

	echo '<p class="description">' . esc_html__( 'Your API key will be stored securely in the database.', 'child' ) . '</p>';
}

/**
 * AJAX endpoint for TMDB searches from block editor.
 */
function child_handle_tmdb_search_ajax(): void {
	check_ajax_referer( 'child-media-search', 'nonce' );

	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( 'Unauthorized', 403 );
	}

	$query = sanitize_text_field( wp_unslash( $_GET['query'] ?? '' ) );
	if ( '' === $query ) {
		wp_send_json_error( 'Query required', 400 );
	}

	$api_key = child_get_tmdb_api_key();
	if ( '' === $api_key ) {
		wp_send_json_error( 'TMDB API key not configured. Please configure it in Settings > Media Recommendation or add TMDB_API_KEY to wp-config.php', 500 );
	}

	$wp_locale   = get_locale();
	$tmdb_locale = str_replace( '_', '-', $wp_locale );

	$movie_response = wp_safe_remote_get(
		'https://api.themoviedb.org/3/search/movie?api_key=' . rawurlencode( $api_key ) . '&query=' . rawurlencode( $query ) . '&language=' . rawurlencode( $tmdb_locale ),
		[ 'timeout' => 10 ]
	);
	$tv_response = wp_safe_remote_get(
		'https://api.themoviedb.org/3/search/tv?api_key=' . rawurlencode( $api_key ) . '&query=' . rawurlencode( $query ) . '&language=' . rawurlencode( $tmdb_locale ),
		[ 'timeout' => 10 ]
	);

	if ( is_wp_error( $movie_response ) || is_wp_error( $tv_response ) ) {
		wp_send_json_error( 'API request failed', 500 );
	}

	$movie_data = json_decode( wp_remote_retrieve_body( $movie_response ), true );
	$tv_data    = json_decode( wp_remote_retrieve_body( $tv_response ), true );

	wp_send_json_success(
		[
			'movies' => $movie_data['results'] ?? [],
			'tv'     => $tv_data['results'] ?? [],
		]
	);
}
add_action( 'wp_ajax_child_tmdb_search', 'child_handle_tmdb_search_ajax' );

/**
 * Provide AJAX data in editor.
 */
function child_localize_media_search(): void {
	wp_localize_script(
		'wp-block-editor',
		'childMediaSearch',
		[
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'child-media-search' ),
		]
	);
}
add_action( 'enqueue_block_editor_assets', 'child_localize_media_search' );
