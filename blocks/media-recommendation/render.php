<?php
/**
 * Render Media Recommendation Block
 *
 * @param array  $attributes The block attributes
 * @param string $content    The block content
 * @return string Returns the block content
 */

return function ( $attributes ) {
	$wrapper_attributes = get_block_wrapper_attributes();
	$media_title        = $attributes['mediaTitle'] ?? '';
	$media_type         = $attributes['mediaType'] ?? 'movie';
	$poster_url         = $attributes['posterUrl'] ?? '';
	$release_year       = $attributes['releaseYear'] ?? '';
	$service_url        = $attributes['serviceUrl'] ?? '';

	if ( empty( $media_title ) ) {
		return '';
	}

	$type_label = $media_type === 'movie' ? __( 'Film', 'child' ) : __( 'Serie', 'child' );

	if ( child_is_feed_render() ) {
		$post_url = child_get_feed_post_url();
		return child_render_feed_card(
			[
				'title'        => (string) $media_title,
				'url'          => $service_url ?: $post_url,
				'image'        => (string) $poster_url,
				'image_width'  => 600,
				'image_height' => 900,
				'meta'         => array_filter( [ $type_label, (string) $release_year ] ),
				'links'        => $post_url ? [
					[
						'url'   => $post_url,
						'label' => __( 'Beitrag auf der Website ansehen', 'child' ),
					],
				] : [],
			]
		);
	}

	ob_start(); ?>
	<div
	<?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by get_block_wrapper_attributes().
	echo $wrapper_attributes;
	?>
	>
		<div class="child-media-card" aria-label="<?php echo esc_attr( $type_label ); ?>">
			<div class="child-media-card__media">
				<?php if ( ! empty( $poster_url ) ) : ?>
					<?php if ( ! empty( $service_url ) ) : ?>
						<a class="child-media-card__poster-link" href="<?php echo esc_url( $service_url ); ?>" target="_blank" rel="noopener noreferrer">
							<img
								src="<?php echo esc_url( $poster_url ); ?>"
								alt="<?php echo esc_attr( $media_title ); ?>"
								class="child-media-card__poster"
								loading="lazy"
								width="600"
								height="900"
							/>
						</a>
					<?php else : ?>
						<img
							src="<?php echo esc_url( $poster_url ); ?>"
							alt="<?php echo esc_attr( $media_title ); ?>"
							class="child-media-card__poster"
							loading="lazy"
							width="600"
							height="900"
						/>
					<?php endif; ?>
				<?php else : ?>
					<div class="child-media-card__placeholder" aria-hidden="true"></div>
				<?php endif; ?>
			</div>

			<div class="child-media-card__meta">
				<p class="child-media-card__title"><?php echo esc_html( $media_title ); ?></p>
				<?php if ( ! empty( $release_year ) ) : ?>
					<p class="child-media-card__year">
						<?php echo esc_html( $release_year ); ?>
					</p>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
};
