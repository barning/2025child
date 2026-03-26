<?php
/**
 * human.json support for author/vouch assertions.
 *
 * @package TwentyTwentyFiveChild
 */

/**
 * Option name for storing newline-separated human.json vouch URLs.
 */
const CHILD_HUMAN_JSON_VOUCHES_OPTION = 'child_human_json_vouches';

/**
 * Sanitize human.json vouch list entered as newline-separated URLs.
 */
function child_sanitize_human_json_vouches( string $value ): string {
	$lines     = preg_split( '/\R+/', $value ) ?: [];
	$sanitized = [];
	$seen_urls = [];

	foreach ( $lines as $line ) {
		$line = trim( $line );

		if ( '' === $line ) {
			continue;
		}

		$url = esc_url_raw( $line );

		if ( '' === $url ) {
			continue;
		}

		$url = trailingslashit( $url );
		$key = strtolower( $url );

		if ( isset( $seen_urls[ $key ] ) ) {
			continue;
		}

		$seen_urls[ $key ] = true;
		$sanitized[]       = $url;
	}

	sort( $sanitized, SORT_NATURAL | SORT_FLAG_CASE );

	return implode( "\n", $sanitized );
}

/**
 * Register human.json settings in wp-admin.
 */
function child_register_human_json_admin_settings(): void {
	register_setting(
		'child_human_json',
		CHILD_HUMAN_JSON_VOUCHES_OPTION,
		[
			'type'              => 'string',
			'sanitize_callback' => 'child_sanitize_human_json_vouches',
			'default'           => '',
		]
	);

	add_settings_section(
		'child_human_json_section',
		__( 'human.json', 'child' ),
		static function(): void {
			echo '<p>' . esc_html__( 'Add one URL per line for people/sites you vouch for.', 'child' ) . '</p>';
		},
		'child-human-json'
	);

	add_settings_field(
		'child_human_json_vouches_field',
		__( 'Vouched sites', 'child' ),
		'child_render_human_json_vouches_field',
		'child-human-json',
		'child_human_json_section'
	);
}
add_action( 'admin_init', 'child_register_human_json_admin_settings' );

/**
 * Render textarea field for vouch URLs.
 */
function child_render_human_json_vouches_field(): void {
	$value = (string) get_option( CHILD_HUMAN_JSON_VOUCHES_OPTION, '' );

	echo '<textarea name="' . esc_attr( CHILD_HUMAN_JSON_VOUCHES_OPTION ) . '" id="' . esc_attr( CHILD_HUMAN_JSON_VOUCHES_OPTION ) . '" rows="10" cols="60" class="large-text code">' . esc_textarea( $value ) . '</textarea>';
	echo '<p class="description">' . esc_html__( 'Use full URLs. Example: https://example.com/', 'child' ) . '</p>';
}

/**
 * Add human.json settings page to wp-admin.
 */
function child_register_human_json_options_page(): void {
	add_options_page(
		__( 'human.json', 'child' ),
		__( 'human.json', 'child' ),
		'manage_options',
		'child-human-json',
		'child_render_human_json_options_page'
	);
}
add_action( 'admin_menu', 'child_register_human_json_options_page' );

/**
 * Render human.json options page.
 */
function child_render_human_json_options_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'human.json Settings', 'child' ); ?></h1>
		<form action="options.php" method="post">
			<?php
			settings_fields( 'child_human_json' );
			do_settings_sections( 'child-human-json' );
			submit_button();
			?>
		</form>
	</div>
	<?php
}

/**
 * Add <link rel="human-json"> to site head.
 */
function child_output_human_json_link_tag(): void {
	echo '<link rel="human-json" href="' . esc_url( child_get_human_json_url() ) . '" />' . "\n";
}
add_action( 'wp_head', 'child_output_human_json_link_tag' );

/**
 * Get the canonical human.json URL for this site.
 */
function child_get_human_json_url(): string {
	return home_url( '/json/human.json' );
}

/**
 * Add rewrite rule for /json/{filename}.
 */
function child_register_json_rewrite_rules(): void {
	add_rewrite_rule(
		'^json(?:/([^/]+))?/?$',
		'index.php?child_json_param=$matches[1]',
		'top'
	);
}
add_action( 'init', 'child_register_json_rewrite_rules' );

/**
 * Register custom query variable for /json route.
 *
 * @param string[] $vars Existing query vars.
 *
 * @return string[]
 */
function child_register_json_query_var( array $vars ): array {
	$vars[] = 'child_json_param';

	return $vars;
}
add_filter( 'query_vars', 'child_register_json_query_var' );

/**
 * Resolve and sanitize vouches for human.json response.
 *
 * @return string[]
 */
function child_get_human_json_vouch_urls(): array {
	$raw_vouches = (string) get_option( CHILD_HUMAN_JSON_VOUCHES_OPTION, '' );
	$lines       = preg_split( '/\R+/', $raw_vouches ) ?: [];
	$urls        = [];

	foreach ( $lines as $line ) {
		$line = trim( $line );

		if ( '' === $line ) {
			continue;
		}

		$url = esc_url_raw( $line );

		if ( '' === $url ) {
			continue;
		}

		$urls[] = trailingslashit( $url );
	}

	$urls = array_values( array_unique( $urls ) );
	sort( $urls, SORT_NATURAL | SORT_FLAG_CASE );

	/**
	 * Filter the list of vouch URLs included in /json/human.json.
	 *
	 * @param string[] $urls Sanitized and sorted URL list.
	 */
	return apply_filters( 'child_human_json_vouches', $urls );
}

/**
 * Serve /json/human.json as JSON.
 */
function child_serve_json_routes(): void {
	$json_param = (string) get_query_var( 'child_json_param' );

	if ( '' === $json_param ) {
		return;
	}

	if ( 0 !== strcasecmp( 'human.json', $json_param ) ) {
		wp_send_json( null, 404, JSON_PRETTY_PRINT );
	}

	$modified = gmdate( 'Y-m-d', (int) filemtime( __FILE__ ) );
	$vouches  = [];

	foreach ( child_get_human_json_vouch_urls() as $human_url ) {
		$vouches[] = [
			'url'        => $human_url,
			'vouched_at' => $modified,
		];
	}

	header( 'Access-Control-Allow-Origin: *' );
	wp_send_json(
		[
			'version' => '0.1.1',
			'url'     => esc_url( home_url( '/' ) ),
			'vouches' => $vouches,
		],
		200,
		JSON_PRETTY_PRINT
	);
}
add_action( 'template_redirect', 'child_serve_json_routes' );
