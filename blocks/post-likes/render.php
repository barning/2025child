<?php
/**
 * Render callback for Post Likes block.
 *
 * @return callable
 */

return function ( array $attributes ): string {
	$post_id = get_the_ID();
	if ( ! $post_id ) {
		return '';
	}

	$count = child_post_likes_get_count( (int) $post_id );
	if ( child_is_feed_render() ) {
		$reaction_emoji = trim( wp_strip_all_tags( (string) ( $attributes['reactionEmoji'] ?? '❤️' ) ) ) ?: '❤️';
		return child_render_feed_card(
			[
				'title' => sprintf(
					/* translators: 1: reaction emoji, 2: like count. */
					__( '%1$s %2$d Likes', 'child' ),
					$reaction_emoji,
					$count
				),
				'url'   => child_get_feed_post_url(),
			]
		);
	}

	if ( ! is_singular() ) {
		return '';
	}

	$liked = child_post_likes_has_current_visitor_liked( (int) $post_id );

	$size_presets = [
		'sm' => [
			'font_size' => '0.95rem',
			'padding_y' => '0.5rem',
			'padding_x' => '0.9rem',
		],
		'md' => [
			'font_size' => '1rem',
			'padding_y' => '0.8rem',
			'padding_x' => '1.3rem',
		],
		'lg' => [
			'font_size' => '1.1rem',
			'padding_y' => '1rem',
			'padding_x' => '1.6rem',
		],
	];

	$size_key  = $attributes['buttonSize'] ?? 'md';
	$size_key  = is_string( $size_key ) ? $size_key : 'md';
	$size_key  = array_key_exists( $size_key, $size_presets ) ? $size_key : 'md';
	$size_vars = $size_presets[ $size_key ];

	$align_value = $attributes['buttonAlign'] ?? 'left';
	$align_value = is_string( $align_value ) ? $align_value : 'left';
	$align_value = in_array( $align_value, [ 'left', 'center', 'right' ], true )
		? $align_value
		: 'left';

	$style_vars = [
		'--child-post-likes-bg'           => $attributes['buttonBackground'] ?? '',
		'--child-post-likes-border'       => $attributes['buttonBorder'] ?? '',
		'--child-post-likes-text'         => $attributes['buttonText'] ?? '',
		'--child-post-likes-hover-border' => $attributes['buttonHoverBorder'] ?? '',
		'--child-post-likes-font-size'    => $size_vars['font_size'],
		'--child-post-likes-padding-y'    => $size_vars['padding_y'],
		'--child-post-likes-padding-x'    => $size_vars['padding_x'],
		'--child-post-likes-align'        => $align_value,
		'--child-post-likes-liked-bg'     => $attributes['buttonLikedBackground'] ?? '',
		'--child-post-likes-focus'        => $attributes['buttonFocusOutline'] ?? '',
		'--child-post-likes-error-border' => $attributes['buttonErrorBorder'] ?? '',
	];

	$style_string = '';
	foreach ( $style_vars as $property => $value ) {
		if ( ! is_string( $value ) || $value === '' ) {
			continue;
		}

		$sanitized = trim( wp_strip_all_tags( $value ) );
		$sanitized = str_replace( ';', '', $sanitized );
		if ( $sanitized === '' ) {
			continue;
		}

		$style_string .= $property . ':' . $sanitized . ';';
	}

	$wrapper_attributes = $style_string
		? get_block_wrapper_attributes( [ 'style' => $style_string ] )
		: get_block_wrapper_attributes();
	$cta_text           = $attributes['ctaText'] ?? 'Do you like this?';
	$cta_text           = is_string( $cta_text ) ? trim( wp_strip_all_tags( $cta_text ) ) : 'Do you like this?';
	$reaction_emoji     = $attributes['reactionEmoji'] ?? '❤️';
	$reaction_emoji     = is_string( $reaction_emoji ) ? trim( wp_strip_all_tags( $reaction_emoji ) ) : '❤️';
	$reaction_emoji     = $reaction_emoji !== '' ? $reaction_emoji : '❤️';
	$status_id          = wp_unique_id( 'child-post-likes-status-' );

	ob_start();
	?>
	<div
	<?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by get_block_wrapper_attributes().
	echo $wrapper_attributes;
	?>
	>
			<button
			type="button"
			class="child-post-likes__button<?php echo $liked ? ' is-liked' : ''; ?>"
			data-post-id="<?php echo esc_attr( (string) $post_id ); ?>"
				aria-label="<?php esc_attr_e( 'Like umschalten', 'child' ); ?>"
				aria-pressed="<?php echo $liked ? 'true' : 'false'; ?>"
				aria-describedby="<?php echo esc_attr( $status_id ); ?>"
				data-liked-message="<?php esc_attr_e( 'Like gespeichert.', 'child' ); ?>"
				data-unliked-message="<?php esc_attr_e( 'Like entfernt.', 'child' ); ?>"
				data-error-message="<?php esc_attr_e( 'Das Like konnte nicht gespeichert werden. Bitte versuche es erneut.', 'child' ); ?>"
				data-count-label="<?php esc_attr_e( 'Likes insgesamt:', 'child' ); ?>"
		>
			<span class="child-post-likes__pill">
				<?php if ( $cta_text !== '' ) : ?>
					<span class="child-post-likes__cta"><?php echo esc_html( $cta_text ); ?></span>
				<?php endif; ?>
				<span class="child-post-likes__icon" aria-hidden="true"><?php echo esc_html( $reaction_emoji ); ?></span>
				<span class="child-post-likes__count"><?php echo esc_html( (string) $count ); ?></span>
			</span>
			</button>
			<span
				id="<?php echo esc_attr( $status_id ); ?>"
				class="screen-reader-text child-post-likes__status"
				role="status"
				aria-live="polite"
				aria-atomic="true"
			></span>
	</div>
	<?php

	return (string) ob_get_clean();
};
