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
 * Preserve a saved Books key unless an explicit replacement or removal is requested.
 */
function child_sanitize_google_books_api_key( $value ): string {
	if ( isset( $_POST['child_google_books_api_key_clear'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Settings API verifies the request.
		return '';
	}

	$value = sanitize_text_field( (string) $value );

	return '' !== $value ? $value : (string) get_option( 'child_google_books_api_key', '' );
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
			'sanitize_callback' => 'child_sanitize_google_books_api_key',
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
	$has_saved_key = '' !== (string) get_option( 'child_google_books_api_key', '' );
	?>
	<input
		type="password"
		name="child_google_books_api_key"
		value=""
		class="regular-text"
		autocomplete="new-password"
		placeholder="<?php echo esc_attr( $has_saved_key ? __( 'Saved — enter a new key to replace it', 'child' ) : __( 'Enter an API key', 'child' ) ); ?>"
	/>
	<p class="description">
		<?php esc_html_e( 'Stored in the WordPress options table and used only for server-side book searches. Leave blank to keep the saved key.', 'child' ); ?>
	</p>
	<?php if ( $has_saved_key ) : ?>
		<label><input type="checkbox" name="child_google_books_api_key_clear" value="1" /> <?php esc_html_e( 'Remove the saved key', 'child' ); ?></label>
	<?php endif; ?>
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
			'permission_callback' => static function (): bool {
				return current_user_can( 'edit_posts' );
			},
			'args'                => [
				'q'          => [
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => static function ( $value ): bool {
						$length = function_exists( 'mb_strlen' ) ? mb_strlen( (string) $value ) : strlen( (string) $value );

						return $length > 0 && $length <= 160;
					},
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

	$query_length = function_exists( 'mb_strlen' ) ? mb_strlen( $query ) : strlen( $query );
	if ( $query_length > 160 ) {
		return new WP_Error( 'query_too_long', __( 'Der Suchbegriff ist zu lang.', 'child' ), [ 'status' => 400 ] );
	}

	$max_results = max( 1, min( 10, $max_results ) );

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

	$data = child_provider_get_json(
		$api_url,
		[
			'timeout' => 8,
		],
		'books',
		12 * HOUR_IN_SECONDS
	);

	if ( is_wp_error( $data ) ) {
		$error_data = $data->get_error_data();
		$status     = (int) ( $error_data['provider_status'] ?? $error_data['status'] ?? 502 );
		if ( 429 === $status ) {
			return new WP_Error(
				'rate_limited',
				__( 'Google Books API-Limit erreicht. Bitte einen API-Schlüssel in den Einstellungen hinterlegen.', 'child' ),
				[ 'status' => 429 ]
			);
		}

		if ( in_array( $status, [ 400, 401, 403 ], true ) ) {
			return new WP_Error(
				'api_auth_failed',
				__( 'API-Authentifizierung fehlgeschlagen. Bitte überprüfe deinen API-Schlüssel in den Einstellungen.', 'child' ),
				[ 'status' => $status ]
			);
		}

		return new WP_Error(
			'books_lookup_failed',
			__( 'Die Buchsuche konnte nicht geladen werden.', 'child' ),
			[ 'status' => 502 ]
		);
	}

	return rest_ensure_response( $data );
}
