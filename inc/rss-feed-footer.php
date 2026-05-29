<?php
/**
 * RSS feed footer helpers.
 *
 * @package TwentyTwentyFiveChild
 */

const CHILD_RSS_FOOTER_MESSAGE_META_KEY = '_child_rss_footer_message';

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
 * Get the RSS footer message saved for a post.
 */
function child_get_rss_footer_message( int $post_id ): string {
	$message = get_post_meta( $post_id, CHILD_RSS_FOOTER_MESSAGE_META_KEY, true );

	if ( ! is_string( $message ) ) {
		return '';
	}

	return $message;
}

/**
 * Add the RSS footer message metabox to the post editor.
 */
function child_add_rss_footer_message_metabox(): void {
	add_meta_box(
		'child-rss-footer-message',
		__( 'RSS footer message', 'child' ),
		'child_render_rss_footer_message_metabox',
		'post',
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes', 'child_add_rss_footer_message_metabox' );

/**
 * Render the RSS footer message metabox.
 */
function child_render_rss_footer_message_metabox( WP_Post $post ): void {
	$message = child_get_rss_footer_message( $post->ID );

	wp_nonce_field( 'child_rss_footer_message_save', 'child_rss_footer_message_nonce' );
	printf(
		'<label for="child_rss_footer_message">%s</label><textarea id="child_rss_footer_message" name="child_rss_footer_message" style="width:100%%" rows="4">%s</textarea>',
		esc_html__( 'Message shown in RSS feeds after the post content.', 'child' ),
		esc_textarea( $message )
	);
}

/**
 * Save the RSS footer message post meta.
 */
function child_save_rss_footer_message_meta( int $post_id ): void {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! isset( $_POST['child_rss_footer_message_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['child_rss_footer_message_nonce'] ), 'child_rss_footer_message_save' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( ! isset( $_POST['child_rss_footer_message'] ) ) {
		return;
	}

	$message = sanitize_textarea_field( wp_unslash( $_POST['child_rss_footer_message'] ) );

	if ( '' === $message ) {
		delete_post_meta( $post_id, CHILD_RSS_FOOTER_MESSAGE_META_KEY );
		return;
	}

	update_post_meta( $post_id, CHILD_RSS_FOOTER_MESSAGE_META_KEY, $message );
}
add_action( 'save_post', 'child_save_rss_footer_message_meta' );

/**
 * Append web and email links to RSS item content.
 */
function child_append_rss_footer_links( string $content, string $feed_type = '' ): string {
	if ( ! child_should_append_rss_footer() ) {
		return $content;
	}

	if ( '' !== $feed_type && ! in_array( $feed_type, [ 'rss2', 'atom', 'rss', 'rdf' ], true ) ) {
		return $content;
	}

	$post_id = get_the_ID();
	if ( ! $post_id ) {
		return $content;
	}

	$permalink = get_permalink( $post_id );
	if ( ! is_string( $permalink ) || $permalink === '' ) {
		return $content;
	}

	$message = child_get_rss_footer_message( $post_id );

	$footer  = '<hr />';
	if ( $message !== '' ) {
		$footer .= wpautop( esc_html( $message ) );
	}
	$footer .= '<p><a href="' . esc_url( $permalink ) . '">→ View on site</a></p>';
	$footer .= '<p><a href="mailto:moin@niklasbarning.de">→ Reply via email</a></p>';

	return $content . $footer;
}
add_filter( 'the_content_feed', 'child_append_rss_footer_links', 10, 2 );
