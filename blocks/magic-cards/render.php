<?php
/**
 * Render Magic Cards Block
 *
 * @param array  $attributes The block attributes
 * @param string $content    The block content
 * @return string Returns the block content
 */

return function ( $attributes ) {
	$wrapper_attributes = get_block_wrapper_attributes();
	$display_type       = $attributes['displayType'] ?? 'single';

	if ( child_is_feed_render() ) {
		$post_url = child_get_feed_post_url();
		if ( 'moxfield' === $display_type ) {
			$moxfield_url = esc_url( (string) ( $attributes['moxfieldUrl'] ?? '' ) );
			return child_render_feed_card(
				[
					'title' => __( 'Magic-Deck auf Moxfield', 'child' ),
					'url'   => $moxfield_url ?: $post_url,
					'links' => $post_url ? [
						[
							'url'   => $post_url,
							'label' => __( 'Beitrag auf der Website ansehen', 'child' ),
						],
					] : [],
				]
			);
		}

		return child_render_feed_card(
			[
				'title'        => (string) ( $attributes['cardName'] ?? '' ),
				'url'          => $post_url,
				'image'        => (string) ( $attributes['cardImageUrl'] ?? '' ),
				'image_width'  => 488,
				'image_height' => 680,
			]
		);
	}

	if ( $display_type === 'moxfield' ) {
		return child_render_moxfield_embed( $attributes, $wrapper_attributes );
	} else {
		return child_render_magic_card( $attributes, $wrapper_attributes );
	}
};

/**
 * Render a Moxfield deck embed
 */
function child_render_moxfield_embed( $attributes, $wrapper_attributes ) {
	$moxfield_url = $attributes['moxfieldUrl'] ?? '';

	if ( empty( $moxfield_url ) ) {
		return '';
	}

	// Extract deck ID from URL
	// Moxfield deck IDs contain alphanumeric characters, hyphens, and underscores
	// Example: https://moxfield.com/decks/4My29fffy0eWok-VYMORYQ
	// Limit to 100 characters to prevent potential DoS attacks
	if ( ! preg_match( '/moxfield\.com\/decks\/([a-zA-Z0-9_-]{1,100})/', $moxfield_url, $matches ) ) {
		return '';
	}

	$deck_id   = $matches[1];
	$embed_url = 'https://www.moxfield.com/embed/' . esc_attr( $deck_id );

	ob_start(); ?>
	<div
	<?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by get_block_wrapper_attributes().
	echo $wrapper_attributes;
	?>
	>
		<div class="child-magic-moxfield">
			<iframe
				src="<?php echo esc_url( $embed_url ); ?>"
				class="child-magic-moxfield__iframe"
				loading="lazy"
				frameborder="0"
				allowfullscreen
				title="<?php echo esc_attr__( 'Eingebettetes Moxfield-Deck', 'child' ); ?>"
			></iframe>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Render a single Magic card
 */
function child_render_magic_card( $attributes, $wrapper_attributes ) {
	$card_name      = $attributes['cardName'] ?? '';
	$card_image_url = $attributes['cardImageUrl'] ?? '';

	if ( empty( $card_name ) ) {
		return '';
	}

	ob_start();
	?>
	<div
	<?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by get_block_wrapper_attributes().
	echo $wrapper_attributes;
	?>
	>
		<div class="child-magic-card" aria-label="<?php echo esc_attr__( 'Magic:-The-Gathering-Karte', 'child' ); ?>">
			<div class="child-magic-card__media">
				<?php if ( ! empty( $card_image_url ) ) : ?>
					<img
						src="<?php echo esc_url( $card_image_url ); ?>"
						alt="<?php echo esc_attr( $card_name ); ?>"
						class="child-magic-card__image"
						loading="lazy"
						width="488"
						height="680"
					/>
				<?php else : ?>
					<div class="child-magic-card__placeholder" aria-hidden="true">
						<span>🃏</span>
					</div>
				<?php endif; ?>
			</div>
			<div class="child-magic-card__meta">
				<p class="child-magic-card__name"><?php echo esc_html( $card_name ); ?></p>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
