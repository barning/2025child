<?php
/**
 * Render Pixelfed Feed Block.
 *
 * @return callable
 */

return function( $attributes ) {
	$feed_url = isset( $attributes['feedUrl'] ) ? trim( (string) $attributes['feedUrl'] ) : '';
	$items_to_show = isset( $attributes['itemsToShow'] ) ? (int) $attributes['itemsToShow'] : 6;
	$items_to_show = max( 1, min( 18, $items_to_show ) );

	if ( '' === $feed_url || ! wp_http_validate_url( $feed_url ) ) {
		return '';
	}

	if ( ! function_exists( 'fetch_feed' ) ) {
		require_once ABSPATH . WPINC . '/feed.php';
	}

	$feed = fetch_feed( $feed_url );
	if ( is_wp_error( $feed ) ) {
		return sprintf(
			'<p %s>%s</p>',
			get_block_wrapper_attributes(),
			esc_html__( 'Unable to load Pixelfed feed right now.', 'child' )
		);
	}

	$max_items = $feed->get_item_quantity( $items_to_show );
	$items = $feed->get_items( 0, $max_items );

	if ( empty( $items ) ) {
		return sprintf(
			'<p %s>%s</p>',
			get_block_wrapper_attributes(),
			esc_html__( 'No images found in this Pixelfed feed.', 'child' )
		);
	}

	ob_start();
	?>
	<div <?php echo get_block_wrapper_attributes(); ?>>
		<div class="child-pixelfed-feed-grid">
			<?php foreach ( $items as $item ) : ?>
				<?php
				$item_link = $item->get_link();
				$image_url = '';

				$enclosure = $item->get_enclosure();
				if ( $enclosure && 0 === strpos( (string) $enclosure->get_type(), 'image/' ) ) {
					$image_url = $enclosure->get_link();
				}

				if ( ! $image_url ) {
					$content = (string) $item->get_content();
					if ( preg_match( '/<img[^>]+src=["\']([^"\']+)["\']/i', $content, $matches ) ) {
						$image_url = $matches[1];
					}
				}

				if ( ! $image_url ) {
					$description = (string) $item->get_description();
					if ( preg_match( '/<img[^>]+src=["\']([^"\']+)["\']/i', $description, $matches ) ) {
						$image_url = $matches[1];
					}
				}

				if ( ! $image_url || ! $item_link ) {
					continue;
				}
				?>
				<a class="child-pixelfed-feed-item" href="<?php echo esc_url( $item_link ); ?>" target="_blank" rel="noopener noreferrer">
					<img
						class="child-pixelfed-feed-item__image"
						src="<?php echo esc_url( $image_url ); ?>"
						alt="<?php echo esc_attr( wp_strip_all_tags( (string) $item->get_title() ) ); ?>"
						loading="lazy"
					/>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
	<?php

	return ob_get_clean();
};
