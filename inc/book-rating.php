<?php
/**
 * Book Rating block helpers.
 *
 * @package TwentyTwentyFiveChild
 */

/**
 * Read Google Books API key from option, then constant fallback.
 */
function child_get_google_books_api_key(): string {
	$api_key = (string) get_option( 'child_google_books_api_key', '' );

	if ( '' === $api_key && defined( 'CHILD_GOOGLE_BOOKS_API_KEY' ) ) {
		$api_key = (string) CHILD_GOOGLE_BOOKS_API_KEY;
	}

	return $api_key;
}

/**
 * Render the Google Books settings page.
 */
function child_render_book_rating_settings_page(): void {
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<form action="options.php" method="post">
			<?php
			settings_fields( 'child_book_rating' );
			do_settings_sections( 'child-book-rating' );
			submit_button( __( 'Save Settings', 'child' ) );
			?>
		</form>
	</div>
	<?php
}

/**
 * Register Google Books settings page.
 */
function child_register_book_rating_settings_page(): void {
	add_options_page(
		__( 'Book Rating Settings', 'child' ),
		__( 'Book Rating', 'child' ),
		'manage_options',
		'child-book-rating',
		'child_render_book_rating_settings_page'
	);
}
add_action( 'admin_menu', 'child_register_book_rating_settings_page' );

/**
 * Register Google Books settings and field.
 */
function child_register_book_rating_settings(): void {
	register_setting(
		'child_book_rating',
		'child_google_books_api_key',
		[
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		]
	);

	add_settings_section(
		'child_book_rating_section',
		__( 'Google Books API Configuration', 'child' ),
		'child_render_book_rating_section_description',
		'child-book-rating'
	);

	add_settings_field(
		'child_google_books_api_key',
		__( 'Google Books API Key', 'child' ),
		'child_render_book_rating_api_field',
		'child-book-rating',
		'child_book_rating_section'
	);
}
add_action( 'admin_init', 'child_register_book_rating_settings' );

/**
 * Render Google Books section text.
 */
function child_render_book_rating_section_description(): void {
	echo '<p>' . wp_kses_post(
		sprintf(
			/* translators: %s: URL to Google Books API settings */
			__( 'To avoid API rate limits, you can provide a Google Books API key. Get a free API key at %s. This is optional but recommended.', 'child' ),
			'<a href="https://developers.google.com/books/docs/v1/using" target="_blank" rel="noopener noreferrer">developers.google.com/books/docs/v1/using</a>'
		)
	) . '</p>';
}

/**
 * Render Google Books key input.
 */
function child_render_book_rating_api_field(): void {
	$value = esc_attr( get_option( 'child_google_books_api_key', '' ) );
	?>
	<input
		type="password"
		name="child_google_books_api_key"
		value="<?php echo $value; ?>"
		class="regular-text"
	/>
	<p class="description">
		<?php esc_html_e( 'Your API key will be used to search books without hitting rate limits.', 'child' ); ?>
	</p>
	<?php
}

/**
 * Register a REST endpoint for Google Books lookups to avoid client-side rate limits.
 */
function child_register_books_lookup_route(): void {
	register_rest_route(
		'child/v1',
		'/books',
		[
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
			'args'                => [
				'q'          => [
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
				],
				'maxResults' => [
					'required'          => false,
					'default'           => 5,
					'sanitize_callback' => 'absint',
				],
			],
			'callback'            => 'child_books_lookup_callback',
		]
	);
}
add_action( 'rest_api_init', 'child_register_books_lookup_route' );

/**
 * Handle Google Books lookups via the REST API.
 *
 * @param WP_REST_Request $request Request data.
 * @return WP_REST_Response|WP_Error
 */
function child_books_lookup_callback( WP_REST_Request $request ) {
	$query       = trim( (string) $request->get_param( 'q' ) );
	$max_results = (int) $request->get_param( 'maxResults' );

	if ( '' === $query ) {
		return new WP_Error( 'missing_query', __( 'Bitte gib einen Suchbegriff ein.', 'child' ), [ 'status' => 400 ] );
	}

	$max_results = max( 1, min( 10, $max_results ) );
	$cache_key   = 'child_books_' . md5( $query . '|' . $max_results );
	$cached      = get_transient( $cache_key );

	if ( false !== $cached ) {
		return rest_ensure_response( $cached );
	}

	// Build API URL with query parameters
	$api_params = [
		'q'          => $query,
		'maxResults' => $max_results,
	];

	$api_key = child_get_google_books_api_key();
	if ( '' !== $api_key ) {
		$api_params['key'] = $api_key;
	}

	$api_url = 'https://www.googleapis.com/books/v1/volumes?' . http_build_query( $api_params );

	$response = wp_remote_get(
		$api_url,
		[
			'timeout' => 8,
		]
	);

	if ( is_wp_error( $response ) ) {
		return new WP_Error( 'books_lookup_failed', __( 'Die Buchsuche konnte nicht geladen werden.', 'child' ), [ 'status' => 500 ] );
	}

	$status = wp_remote_retrieve_response_code( $response );
	$body   = wp_remote_retrieve_body( $response );

	if ( 429 === $status ) {
		return new WP_Error(
			'rate_limited',
			__( 'Google Books API-Limit erreicht. Bitte einen API-Schlüssel in den Einstellungen hinterlegen.', 'child' ),
			[ 'status' => 429 ]
		);
	}

	if ( 400 === $status || 401 === $status || 403 === $status ) {
		return new WP_Error(
			'api_auth_failed',
			__( 'API-Authentifizierung fehlgeschlagen. Bitte überprüfe deinen API-Schlüssel in den Einstellungen.', 'child' ),
			[ 'status' => $status ]
		);
	}

	if ( $status < 200 || $status >= 300 ) {
		return new WP_Error(
			'books_lookup_failed',
			sprintf(
				/* translators: %d: HTTP status code */
				__( 'Die Buchsuche konnte nicht geladen werden. (Fehler: %d)', 'child' ),
				$status
			),
			[ 'status' => $status ]
		);
	}

	$data = json_decode( $body, true );
	if ( null === $data ) {
		return new WP_Error( 'invalid_response', __( 'Die Buchsuche lieferte keine gültige Antwort.', 'child' ), [ 'status' => 500 ] );
	}

	set_transient( $cache_key, $data, HOUR_IN_SECONDS * 12 );

	return rest_ensure_response( $data );
}
