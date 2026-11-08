<?php
/**
 * Render Book Rating Block
 *
 * @param array  $attributes The block attributes
 * @param string $content    The block content
 * @return string Returns the block content
 */

return function ( $attributes ) {
	$wrapper_attributes = get_block_wrapper_attributes();
	$book_title         = $attributes['bookTitle'] ?? '';
	$author             = $attributes['author'] ?? '';
	$cover_url          = $attributes['coverUrl'] ?? '';
	$shop_url           = $attributes['shopUrl'] ?? '';

	if ( empty( $book_title ) ) {
		return '';
	}

	if ( child_is_feed_render() ) {
		$post_url    = child_get_feed_post_url();
		$author_meta = [];
		if ( $author ) {
			/* translators: %s: book author. */
			$author_meta[] = sprintf( __( 'Von %s', 'child' ), $author );
		}
		return child_render_feed_card(
			[
				'title'        => (string) $book_title,
				'url'          => $shop_url ?: $post_url,
				'image'        => (string) $cover_url,
				'image_width'  => 600,
				'image_height' => 900,
				'meta'         => $author_meta,
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
		<div class="child-book-card" aria-label="<?php echo esc_attr__( 'Buch', 'child' ); ?>">
			<div class="child-book-card__media">
				<?php if ( ! empty( $cover_url ) ) : ?>
					<?php if ( ! empty( $shop_url ) ) : ?>
						<a class="child-book-card__cover-link" href="<?php echo esc_url( $shop_url ); ?>" target="_blank" rel="noopener noreferrer">
							<img
								src="<?php echo esc_url( $cover_url ); ?>"
								alt="<?php echo esc_attr( $book_title ); ?>"
								class="child-book-card__cover"
								loading="lazy"
								width="600"
								height="900"
							/>
						</a>
					<?php else : ?>
						<img
							src="<?php echo esc_url( $cover_url ); ?>"
							alt="<?php echo esc_attr( $book_title ); ?>"
							class="child-book-card__cover"
							loading="lazy"
							width="600"
							height="900"
						/>
					<?php endif; ?>
				<?php else : ?>
					<div class="child-book-card__placeholder" aria-hidden="true"></div>
				<?php endif; ?>
			</div>

			<div class="child-book-card__meta">
				<p class="child-book-card__title"><?php echo esc_html( $book_title ); ?></p>
				<?php if ( ! empty( $author ) ) : ?>
					<p class="child-book-card__author">
						<?php
							/* translators: %s: author name */
							printf( esc_html__( 'Von %s', 'child' ), esc_html( $author ) );
						?>
					</p>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
};
