<?php
/**
 * Open Stories admin integration and feed endpoint.
 *
 * @package TwentyTwentyFiveChild
 */

const CHILD_OPEN_STORIES_POST_TYPE = 'child_story';

/**
 * Register a story post type used to author Open Stories items.
 */
function child_register_open_stories_post_type(): void {
	$labels = [
		'name'               => __( 'Open Stories', 'child' ),
		'singular_name'      => __( 'Open Story', 'child' ),
		'add_new'            => __( 'Add Story', 'child' ),
		'add_new_item'       => __( 'Add New Story', 'child' ),
		'edit_item'          => __( 'Edit Story', 'child' ),
		'new_item'           => __( 'New Story', 'child' ),
		'view_item'          => __( 'View Story', 'child' ),
		'search_items'       => __( 'Search Stories', 'child' ),
		'not_found'          => __( 'No stories found.', 'child' ),
		'not_found_in_trash' => __( 'No stories found in trash.', 'child' ),
		'menu_name'          => __( 'Open Stories', 'child' ),
	];

	register_post_type(
		CHILD_OPEN_STORIES_POST_TYPE,
		[
			'labels'             => $labels,
			'public'             => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_rest'       => true,
			'menu_icon'          => 'dashicons-format-image',
			'supports'           => [ 'title', 'thumbnail', 'editor', 'author' ],
			'capability_type'    => 'post',
			'publicly_queryable' => false,
		]
	);
}
add_action( 'init', 'child_register_open_stories_post_type' );

/**
 * Register story meta fields in REST and DB.
 */
function child_register_open_stories_meta(): void {
	$meta_fields = [
		'child_open_stories_url'              => [ 'type' => 'string' ],
		'child_open_stories_mime_type'        => [ 'type' => 'string' ],
		'child_open_stories_alt'              => [ 'type' => 'string' ],
		'child_open_stories_video_title'      => [ 'type' => 'string' ],
		'child_open_stories_caption'          => [ 'type' => 'string' ],
		'child_open_stories_duration_seconds' => [ 'type' => 'number' ],
		'child_open_stories_date_expired'     => [ 'type' => 'string' ],
		'child_open_stories_content_warning'  => [ 'type' => 'string' ],
	];

	foreach ( $meta_fields as $meta_key => $schema ) {
		register_post_meta(
			CHILD_OPEN_STORIES_POST_TYPE,
			$meta_key,
			[
				'show_in_rest'       => [ 'schema' => $schema ],
				'single'             => true,
				'type'               => $schema['type'],
				'sanitize_callback'  => 'sanitize_text_field',
				'auth_callback'      => static fn() => current_user_can( 'edit_posts' ),
				'default'            => '',
			]
		);
	}
}
add_action( 'init', 'child_register_open_stories_meta' );

/**
 * Add meta box to story editor.
 */
function child_add_open_stories_meta_box(): void {
	add_meta_box(
		'child-open-stories-fields',
		__( 'Open Stories Fields', 'child' ),
		'child_render_open_stories_meta_box',
		CHILD_OPEN_STORIES_POST_TYPE,
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'child_add_open_stories_meta_box' );

/**
 * Render fields used by Open Stories feed.
 */
function child_render_open_stories_meta_box( WP_Post $post ): void {
	wp_nonce_field( 'child_open_stories_save', 'child_open_stories_nonce' );

	$fields = [
		'child_open_stories_url'              => __( 'Media URL (required)', 'child' ),
		'child_open_stories_mime_type'        => __( 'MIME type (required, image/* or video/*)', 'child' ),
		'child_open_stories_alt'              => __( 'Image alt text', 'child' ),
		'child_open_stories_video_title'      => __( 'Video title/description', 'child' ),
		'child_open_stories_caption'          => __( 'Caption', 'child' ),
		'child_open_stories_duration_seconds' => __( 'Duration in seconds', 'child' ),
		'child_open_stories_date_expired'     => __( 'Expiration date (ISO 8601)', 'child' ),
		'child_open_stories_content_warning'  => __( 'Content warning', 'child' ),
	];

	echo '<table class="form-table" role="presentation">';
	foreach ( $fields as $field => $label ) {
		$value = get_post_meta( $post->ID, $field, true );
		echo '<tr><th><label for="' . esc_attr( $field ) . '">' . esc_html( $label ) . '</label></th><td>';
		echo '<input class="regular-text" type="text" id="' . esc_attr( $field ) . '" name="' . esc_attr( $field ) . '" value="' . esc_attr( (string) $value ) . '" />';
		echo '</td></tr>';
	}
	echo '</table>';
}

/**
 * Save story meta fields.
 */
function child_save_open_stories_meta( int $post_id ): void {
	if ( ! isset( $_POST['child_open_stories_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['child_open_stories_nonce'] ) ), 'child_open_stories_save' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( get_post_type( $post_id ) !== CHILD_OPEN_STORIES_POST_TYPE || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$meta_keys = [
		'child_open_stories_url',
		'child_open_stories_mime_type',
		'child_open_stories_alt',
		'child_open_stories_video_title',
		'child_open_stories_caption',
		'child_open_stories_duration_seconds',
		'child_open_stories_date_expired',
		'child_open_stories_content_warning',
	];

	foreach ( $meta_keys as $meta_key ) {
		$raw = wp_unslash( $_POST[ $meta_key ] ?? '' );
		update_post_meta( $post_id, $meta_key, sanitize_text_field( $raw ) );
	}
}
add_action( 'save_post', 'child_save_open_stories_meta' );

/**
 * Register Open Stories settings page.
 */
function child_register_open_stories_settings_page(): void {
	add_options_page(
		__( 'Open Stories Settings', 'child' ),
		__( 'Open Stories', 'child' ),
		'manage_options',
		'child-open-stories',
		'child_render_open_stories_settings_page'
	);
}
add_action( 'admin_menu', 'child_register_open_stories_settings_page' );

/**
 * Register Open Stories options.
 */
function child_register_open_stories_settings(): void {
	register_setting( 'child_open_stories', 'child_open_stories_feed_title', [
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => get_bloginfo( 'name' ) . ' Stories',
	] );

	register_setting( 'child_open_stories', 'child_open_stories_feed_url', [
		'type'              => 'string',
		'sanitize_callback' => 'esc_url_raw',
		'default'           => home_url( '/open-stories.json' ),
	] );

	register_setting( 'child_open_stories', 'child_open_stories_author_name', [
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => get_bloginfo( 'name' ),
	] );

	register_setting( 'child_open_stories', 'child_open_stories_author_url', [
		'type'              => 'string',
		'sanitize_callback' => 'esc_url_raw',
		'default'           => home_url( '/' ),
	] );

	add_settings_section(
		'child_open_stories_section',
		__( 'Feed metadata', 'child' ),
		static function(): void {
			echo '<p>' . esc_html__( 'Configure Open Stories feed defaults and publish stories from the Open Stories admin menu.', 'child' ) . '</p>';
		},
		'child-open-stories'
	);

	$fields = [
		'child_open_stories_feed_title'  => __( 'Feed title', 'child' ),
		'child_open_stories_feed_url'    => __( 'Feed URL', 'child' ),
		'child_open_stories_author_name' => __( 'Default author name', 'child' ),
		'child_open_stories_author_url'  => __( 'Default author URL', 'child' ),
	];

	foreach ( $fields as $option => $label ) {
		add_settings_field(
			$option,
			$label,
			static function() use ( $option ): void {
				$value = (string) get_option( $option, '' );
				echo '<input type="text" class="regular-text" name="' . esc_attr( $option ) . '" value="' . esc_attr( $value ) . '" />';
			},
			'child-open-stories',
			'child_open_stories_section'
		);
	}
}
add_action( 'admin_init', 'child_register_open_stories_settings' );

/**
 * Render Open Stories settings page.
 */
function child_render_open_stories_settings_page(): void {
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<p><?php echo esc_html__( 'Feed endpoint:', 'child' ); ?> <code><?php echo esc_html( home_url( '/open-stories.json' ) ); ?></code></p>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'child_open_stories' );
			do_settings_sections( 'child-open-stories' );
			submit_button( __( 'Save Settings', 'child' ) );
			?>
		</form>
	</div>
	<?php
}

/**
 * Register a public Open Stories feed endpoint.
 */
function child_register_open_stories_rewrite(): void {
	add_rewrite_rule( '^open-stories\.json$', 'index.php?child_open_stories_feed=1', 'top' );
}
add_action( 'init', 'child_register_open_stories_rewrite' );

/**
 * Register query var for feed endpoint.
 *
 * @param string[] $vars Query vars.
 * @return string[]
 */
function child_add_open_stories_query_var( array $vars ): array {
	$vars[] = 'child_open_stories_feed';
	return $vars;
}
add_filter( 'query_vars', 'child_add_open_stories_query_var' );

/**
 * Emit JSON feed for /open-stories.json requests.
 */
function child_maybe_render_open_stories_feed(): void {
	if ( '1' !== get_query_var( 'child_open_stories_feed' ) ) {
		return;
	}

	$stories = get_posts(
		[
			'post_type'      => CHILD_OPEN_STORIES_POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'orderby'        => 'date',
			'order'          => 'DESC',
		]
	);

	$default_author_name = (string) get_option( 'child_open_stories_author_name', get_bloginfo( 'name' ) );
	$default_author_url  = (string) get_option( 'child_open_stories_author_url', home_url( '/' ) );

	$items = array_values(
		array_filter(
			array_map(
				static function( WP_Post $story ) use ( $default_author_name, $default_author_url ): ?array {
					$url       = (string) get_post_meta( $story->ID, 'child_open_stories_url', true );
					$mime_type = (string) get_post_meta( $story->ID, 'child_open_stories_mime_type', true );

					if ( '' === $url || '' === $mime_type ) {
						return null;
					}

					$open_story = [
						'url'                => esc_url_raw( $url ),
						'mime_type'          => sanitize_text_field( $mime_type ),
						'date_expired'       => sanitize_text_field( (string) get_post_meta( $story->ID, 'child_open_stories_date_expired', true ) ),
						'content_warning'    => sanitize_text_field( (string) get_post_meta( $story->ID, 'child_open_stories_content_warning', true ) ),
						'duration_in_seconds'=> (float) get_post_meta( $story->ID, 'child_open_stories_duration_seconds', true ),
					];

					if ( str_starts_with( $mime_type, 'image/' ) ) {
						$open_story['alt']     = sanitize_text_field( (string) get_post_meta( $story->ID, 'child_open_stories_alt', true ) );
						$open_story['caption'] = sanitize_text_field( (string) get_post_meta( $story->ID, 'child_open_stories_caption', true ) );
					} else {
						$open_story['title'] = sanitize_text_field( (string) get_post_meta( $story->ID, 'child_open_stories_video_title', true ) );
					}

					$open_story = array_filter(
						$open_story,
						static function( $value ): bool {
							if ( is_numeric( $value ) ) {
								return (float) $value > 0;
							}

							return '' !== (string) $value;
						}
					);

					return [
						'id'           => (string) $story->ID,
						'content_text' => wp_strip_all_tags( (string) $story->post_content ),
						'authors'      => [
							[
								'name' => $default_author_name,
								'url'  => esc_url_raw( $default_author_url ),
							],
						],
						'_open_stories' => $open_story,
					];
				},
				$stories
			)
		)
	);

	$feed = [
		'version'       => 'https://jsonfeed.org/version/1.1',
		'title'         => (string) get_option( 'child_open_stories_feed_title', get_bloginfo( 'name' ) . ' Stories' ),
		'feed_url'      => (string) get_option( 'child_open_stories_feed_url', home_url( '/open-stories.json' ) ),
		'_open_stories' => [
			'version' => '0.0.9',
		],
		'items'         => $items,
	];

	status_header( 200 );
	nocache_headers();
	header( 'Content-Type: application/feed+json; charset=' . get_option( 'blog_charset' ) );
	echo wp_json_encode( $feed, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	exit;
}
add_action( 'template_redirect', 'child_maybe_render_open_stories_feed' );

/**
 * Flush rewrite rules once for Open Stories feed route.
 */
function child_maybe_flush_open_stories_rewrite_rules(): void {
	if ( '1' === get_option( 'child_open_stories_rewrite_flushed' ) ) {
		return;
	}

	child_register_open_stories_rewrite();
	flush_rewrite_rules( false );
	update_option( 'child_open_stories_rewrite_flushed', '1' );
}
add_action( 'init', 'child_maybe_flush_open_stories_rewrite_rules', 20 );

/**
 * Ensure Open Stories rewrite is refreshed on theme activation.
 */
function child_flush_open_stories_rewrite_on_switch(): void {
	delete_option( 'child_open_stories_rewrite_flushed' );
	child_register_open_stories_rewrite();
	flush_rewrite_rules( false );
	update_option( 'child_open_stories_rewrite_flushed', '1' );
}
add_action( 'after_switch_theme', 'child_flush_open_stories_rewrite_on_switch' );
