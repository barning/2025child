<?php
/**
 * Render Pixelfed Feed Block.
 *
 * @return callable
 */

return function( $attributes ) {
	$feed_url = isset( $attributes['feedUrl'] ) ? trim( (string) $attributes['feedUrl'] ) : '';
	$items_to_show = isset( $attributes['itemsToShow'] ) ? (int) $attributes['itemsToShow'] : 9;
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

	$get_image_dimensions = static function( $html ) {
		if ( ! preg_match( '/<img[^>]*>/i', (string) $html, $img_tag_match ) ) {
			return array( 0, 0 );
		}

		$img_tag = $img_tag_match[0];
		$width = 0;
		$height = 0;

		if ( preg_match( '/\bwidth=["\'](\d+)["\']/i', $img_tag, $width_match ) ) {
			$width = (int) $width_match[1];
		}

		if ( preg_match( '/\bheight=["\'](\d+)["\']/i', $img_tag, $height_match ) ) {
			$height = (int) $height_match[1];
		}

		return array( $width, $height );
	};

	ob_start();
	?>
	<div <?php echo get_block_wrapper_attributes(); ?>>
		<div class="child-pixelfed-feed-grid">
			<?php foreach ( $items as $index => $item ) : ?>
				<?php
				$item_link = $item->get_link();
				$image_url = '';
				$image_width = 0;
				$image_height = 0;

				$enclosure = $item->get_enclosure();
				if ( $enclosure && 0 === strpos( (string) $enclosure->get_type(), 'image/' ) ) {
					$image_url = $enclosure->get_link();
					$image_width = (int) $enclosure->get_width();
					$image_height = (int) $enclosure->get_height();
				}

				$content = (string) $item->get_content();
				if ( ! $image_url && preg_match( '/<img[^>]+src=["\']([^"\']+)["\']/i', $content, $matches ) ) {
					$image_url = $matches[1];
				}

				if ( ! $image_width || ! $image_height ) {
					list( $image_width, $image_height ) = $get_image_dimensions( $content );
				}

				if ( ! $image_url ) {
					$description = (string) $item->get_description();
					if ( preg_match( '/<img[^>]+src=["\']([^"\']+)["\']/i', $description, $matches ) ) {
						$image_url = $matches[1];
					}

					if ( ! $image_width || ! $image_height ) {
						list( $image_width, $image_height ) = $get_image_dimensions( $description );
					}
				}

				if ( ! $image_url || ! $item_link ) {
					continue;
				}

				$ratio_class = 'is-ratio-square';
				if ( $image_width > 0 && $image_height > 0 ) {
					$ratio = $image_width / $image_height;
					if ( $ratio >= 1.2 ) {
						$ratio_class = 'is-ratio-landscape';
					} elseif ( $ratio <= 0.83 ) {
						$ratio_class = 'is-ratio-portrait';
					}
				}

				$layout_class = 0 === ( $index % 7 ) ? 'is-featured-tile' : '';
				?>
				<a class="child-pixelfed-feed-item <?php echo esc_attr( trim( $ratio_class . ' ' . $layout_class ) ); ?>" href="<?php echo esc_url( $item_link ); ?>" target="_blank" rel="noopener noreferrer">
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
