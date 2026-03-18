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
}
add_action( 'admin_init', 'child_register_videogame_recommendation_settings' );

/**
 * Render RAWG section description.
 */
function child_render_videogame_recommendation_section_description(): void {
	echo '<p>' . wp_kses_post(
		sprintf(
			/* translators: %s: URL to RAWG API docs */
			__( 'To use the Videogame Recommendation block, you need a free API key from RAWG. Get your API key at %s.', 'child' ),
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
			return [
				'id'               => $game['id'] ?? 0,
				'name'             => $game['name'] ?? '',
				'released'         => $game['released'] ?? '',
				'background_image' => $game['background_image'] ?? '',
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
