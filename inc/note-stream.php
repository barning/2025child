<?php
/** Notes in homepage streams and general feeds. */
function child_note_stream_main_query( WP_Query $query ): void {
	if ( is_admin() || ! $query->is_main_query() || ( ! $query->is_home() && ! $query->is_feed() ) ) {
		return;
	}
	if ( $query->is_comment_feed || $query->is_singular() || $query->is_archive() || $query->get( 'post_type' ) ) {
		return;
	}
	$query->set( 'post_type', [ 'post', CHILD_NOTE_POST_TYPE ] );
}
add_action( 'pre_get_posts', 'child_note_stream_main_query' );

function child_note_stream_query_loop( array $query ): array {
	if ( ! is_admin() && is_front_page() && ( $query['post_type'] ?? 'post' ) === 'post' && empty( $query['tax_query'] ) && empty( $query['s'] ) ) {
		$query['post_type'] = [ 'post', CHILD_NOTE_POST_TYPE ];
	}
	return $query;
}
add_filter( 'query_loop_block_query_vars', 'child_note_stream_query_loop' );

function child_note_stream_date( string $html, array $block, WP_Block $instance ): string {
	if ( is_admin() || is_feed() || ! is_front_page() ) {
		return $html;
	}
	$post_id = (int) ( $instance->context['postId'] ?? 0 );
	if ( ! child_is_note_post_id( $post_id ) ) {
		return $html;
	}
	$html      = preg_replace( '/(<div\b[^>]*>)/', '$1' . esc_html__( 'Notiz', 'child' ) . ' · ', $html, 1 ) ?? $html;
	$parent_id = child_note_thread_get_parent_id( $post_id );
	$parent    = $parent_id ? get_post( $parent_id ) : null;
	if ( $parent instanceof WP_Post && 'publish' === $parent->post_status && ! post_password_required( $parent ) ) {
		$summary = wp_trim_words( wp_strip_all_tags( strip_shortcodes( $parent->post_content ) ), 7, ' …' );
		/* translators: %s: excerpt from the preceding Note. */
		$label = sprintf( __( 'Fortsetzung von: %s', 'child' ), $summary );
		$html .= '<p class="child-note-stream-parent"><a href="' . esc_url( get_permalink( $parent ) ) . '">'
			. esc_html( $label ) . '</a></p>';
	}
	return $html;
}
add_filter( 'render_block_core/post-date', 'child_note_stream_date', 10, 3 );
