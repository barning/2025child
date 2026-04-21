<?php
/**
 * Append footer links for RSS readers.
 *
 * @package TwentyTwentyFiveChild
 */
const CHILD_RSS_BONUS_META_TITLE = '_child_rss_bonus_title';
const CHILD_RSS_BONUS_META_MESSAGE = '_child_rss_bonus_message';
const CHILD_RSS_BONUS_DEFAULT_TITLE = 'PS for RSS readers:';
const CHILD_RSS_BONUS_DEFAULT_MESSAGE = 'Thanks for reading via RSS. You are seeing this little bonus note before everyone else.';

/**
 * Register RSS bonus settings metabox for posts.
 */
function child_register_rss_bonus_metabox(): void {
	add_meta_box(
		'child-rss-bonus-settings',
		'RSS Bonus Message',
		'child_render_rss_bonus_metabox',
		'post',
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes', 'child_register_rss_bonus_metabox' );

/**
 * Render RSS bonus settings metabox fields.
 */
function child_render_rss_bonus_metabox( WP_Post $post ): void {
	$title = get_post_meta( $post->ID, CHILD_RSS_BONUS_META_TITLE, true );
	$message = get_post_meta( $post->ID, CHILD_RSS_BONUS_META_MESSAGE, true );

	if ( ! is_string( $title ) ) {
		$title = '';
	}

	if ( ! is_string( $message ) ) {
		$message = '';
	}

	wp_nonce_field( 'child_rss_bonus_metabox', 'child_rss_bonus_metabox_nonce' );
	?>
	<p>
		<label for="child-rss-bonus-title"><strong>PS title</strong></label>
		<input
			type="text"
			id="child-rss-bonus-title"
			name="child_rss_bonus_title"
			value="<?php echo esc_attr( $title ); ?>"
			class="widefat"
			placeholder="<?php echo esc_attr( CHILD_RSS_BONUS_DEFAULT_TITLE ); ?>"
		/>
	</p>
	<p>
		<label for="child-rss-bonus-message"><strong>PS message</strong></label>
		<textarea
			id="child-rss-bonus-message"
			name="child_rss_bonus_message"
			rows="4"
			class="widefat"
			placeholder="<?php echo esc_attr( CHILD_RSS_BONUS_DEFAULT_MESSAGE ); ?>"
		><?php echo esc_textarea( $message ); ?></textarea>
	</p>
	<p class="description">Used only in RSS feeds for blog posts.</p>
	<?php
}

/**
 * Save RSS bonus settings for post editor.
 */
function child_save_rss_bonus_metabox( int $post_id ): void {
	$nonce = isset( $_POST['child_rss_bonus_metabox_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['child_rss_bonus_metabox_nonce'] ) ) : '';
	if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'child_rss_bonus_metabox' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$title = '';
	if ( isset( $_POST['child_rss_bonus_title'] ) ) {
		$title = sanitize_text_field( wp_unslash( $_POST['child_rss_bonus_title'] ) );
	}

	$message = '';
	if ( isset( $_POST['child_rss_bonus_message'] ) ) {
		$message = sanitize_textarea_field( wp_unslash( $_POST['child_rss_bonus_message'] ) );
	}

	if ( '' === $title ) {
		delete_post_meta( $post_id, CHILD_RSS_BONUS_META_TITLE );
	} else {
		update_post_meta( $post_id, CHILD_RSS_BONUS_META_TITLE, $title );
	}

	if ( '' === $message ) {
		delete_post_meta( $post_id, CHILD_RSS_BONUS_META_MESSAGE );
	} else {
		update_post_meta( $post_id, CHILD_RSS_BONUS_META_MESSAGE, $message );
	}
}
add_action( 'save_post_post', 'child_save_rss_bonus_metabox' );

/**
 * Determine whether RSS footer links should be appended for current item.
 */
function child_should_append_rss_footer(): bool {
	if ( ! is_feed() ) {
		return false;
	}

	$post_id = get_the_ID();
	if ( ! $post_id ) {
		return false;
	}

	return get_post_type( $post_id ) === 'post';
}

/**
 * Append web and email links to RSS item content.
 */
function child_append_rss_footer_links( string $content ): string {
	if ( ! child_should_append_rss_footer() ) {
		return $content;
	}

	$post_id = get_the_ID();
	if ( ! $post_id ) {
		return $content;
	}

	$permalink = get_permalink();
	if ( ! is_string( $permalink ) || $permalink === '' ) {
		return $content;
	}

	$footer  = child_get_rss_bonus_paragraph( (int) $post_id );
	$footer .= '<hr />';
	$footer .= '<p><a href="' . esc_url( $permalink ) . '">→ This looks better on the web</a></p>';
	$footer .= '<p><a href="mailto:moin@niklasbarning.de">→ Reply via email</a></p>';

	return $content . $footer;
}
add_filter( 'the_content_feed', 'child_append_rss_footer_links' );
add_filter( 'the_excerpt_rss', 'child_append_rss_footer_links' );

/**
 * Build a subtle feed-only bonus paragraph.
 */
function child_get_rss_bonus_paragraph( int $post_id ): string {
	$title = get_post_meta( $post_id, CHILD_RSS_BONUS_META_TITLE, true );
	$message = get_post_meta( $post_id, CHILD_RSS_BONUS_META_MESSAGE, true );

	if ( ! is_string( $title ) || '' === $title ) {
		$title = CHILD_RSS_BONUS_DEFAULT_TITLE;
	}

	if ( ! is_string( $message ) || '' === $message ) {
		$message = CHILD_RSS_BONUS_DEFAULT_MESSAGE;
	}

	return sprintf(
		'<p><strong>%s</strong> %s</p>',
		esc_html( $title ),
		esc_html( $message )
	);
}
