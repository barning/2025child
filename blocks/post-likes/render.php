<?php
/**
 * Render callback for Post Likes block.
 *
 * @return callable
 */

return function(): string {
	if ( ! is_singular() ) {
		return '';
	}

	$post_id = get_the_ID();
	if ( ! $post_id ) {
		return '';
	}

	$count = child_post_likes_get_count( (int) $post_id );
	$liked = child_post_likes_has_current_visitor_liked( (int) $post_id );

	ob_start();
	?>
	<div <?php echo get_block_wrapper_attributes(); ?>>
		<button
			type="button"
			class="child-post-likes__button<?php echo $liked ? ' is-liked' : ''; ?>"
			data-post-id="<?php echo esc_attr( (string) $post_id ); ?>"
			aria-label="<?php esc_attr_e( 'Toggle like', 'child' ); ?>"
			aria-pressed="<?php echo $liked ? 'true' : 'false'; ?>"
		>
			<span class="child-post-likes__icon" aria-hidden="true">❤</span>
			<span class="child-post-likes__count"><?php echo esc_html( (string) $count ); ?></span>
		</button>
	</div>
	<?php

	return (string) ob_get_clean();
};
