<?php
/**
 * Render Media Cover Grid Block.
 *
 * @package TwentyTwentyFiveChild
 */

return function ( array $attributes ): string {
	if ( ! function_exists( 'child_get_media_cover_grid_items' ) ) {
		return '';
	}

	$default_types    = [ 'book', 'movie', 'tv', 'game', 'music' ];
	$media_types      = $attributes['mediaTypes'] ?? $default_types;
	$allowed_types    = array_values( array_intersect( $default_types, is_array( $media_types ) ? $media_types : $default_types ) );
	$link_to          = in_array( $attributes['linkTo'] ?? 'post', [ 'post', 'external', 'none' ], true ) ? $attributes['linkTo'] : 'post';
	$sort_order       = in_array( $attributes['sortOrder'] ?? 'newest', [ 'newest', 'oldest', 'title' ], true ) ? $attributes['sortOrder'] : 'newest';
	$show_title       = (bool) ( $attributes['showTitle'] ?? true );
	$show_meta        = (bool) ( $attributes['showMeta'] ?? true );
	$show_type        = (bool) ( $attributes['showType'] ?? true );
	$allow_duplicates = (bool) ( $attributes['allowDuplicates'] ?? false );

	if ( [] === $allowed_types ) {
		return sprintf(
			'<div %s><p class="child-media-cover-grid__empty">%s</p></div>',
			get_block_wrapper_attributes( [ 'class' => 'child-media-cover-grid-block' ] ),
			esc_html__( 'Bitte wähle mindestens einen Medientyp aus.', 'child' )
		);
	}

	$items = child_get_media_cover_grid_items( $allow_duplicates );
	$items = array_values(
		array_filter(
			$items,
			static function ( array $item ) use ( $allowed_types ): bool {
				return in_array( $item['type'] ?? '', $allowed_types, true );
			}
		)
	);

	usort(
		$items,
		static function ( array $a, array $b ) use ( $sort_order ): int {
			if ( 'title' === $sort_order ) {
				return strcasecmp( $a['title'] ?? '', $b['title'] ?? '' );
			}

			$a_time = (int) ( $a['sourcePostTimestamp'] ?? 0 );
			$b_time = (int) ( $b['sourcePostTimestamp'] ?? 0 );

			return 'oldest' === $sort_order ? $a_time <=> $b_time : $b_time <=> $a_time;
		}
	);

	if ( child_is_feed_render() ) {
		if ( [] === $items ) {
			return child_render_feed_post_link( __( 'Mediensammlung auf der Website ansehen', 'child' ) );
		}

		$out = '<div class="child-rss-collection">';
		foreach ( array_slice( $items, 0, 4 ) as $item ) {
			$type         = (string) ( $item['type'] ?? '' );
			$title        = (string) ( $item['title'] ?? '' );
			$cover_format = child_get_media_cover_grid_cover_format( $item );
			$dimensions   = [
				'portrait'  => [ 600, 900 ],
				'square'    => [ 600, 600 ],
				'landscape' => [ 1600, 900 ],
			][ $cover_format ] ?? [ 600, 900 ];
			$link_url     = 'external' === $link_to
				? (string) ( $item['externalUrl'] ?? '' )
				: ( 'none' === $link_to ? '' : (string) ( $item['sourcePostUrl'] ?? '' ) );

			$out .= child_render_feed_card(
				[
					'title'        => $show_title ? $title : '',
					'url'          => $link_url,
					'image'        => (string) ( $item['coverUrl'] ?? '' ),
					'image_alt'    => $title,
					'image_width'  => $dimensions[0],
					'image_height' => $dimensions[1],
					'meta'         => array_filter(
						[
							$show_type ? child_get_media_cover_grid_type_label( $type ) : '',
							$show_meta ? (string) ( $item['meta'] ?? '' ) : '',
						]
					),
				]
			);
		}

		$out .= child_render_feed_post_link( __( 'Mediensammlung auf der Website ansehen', 'child' ) );
		return $out . '</div>';
	}

	$item_types = array_values(
		array_filter(
			array_unique(
				array_map(
					static function ( array $item ): string {
						return (string) ( $item['type'] ?? '' );
					},
					$items
				)
			)
		)
	);

	ob_start();
	?>
	<div
	<?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core.
	echo get_block_wrapper_attributes( [ 'class' => 'child-media-cover-grid-block' ] );
	?>
	>
		<?php if ( [] === $items ) : ?>
			<p class="child-media-cover-grid__empty"><?php echo esc_html__( 'Noch keine Medien gefunden.', 'child' ); ?></p>
		<?php else : ?>
			<?php if ( count( $item_types ) > 1 ) : ?>
				<div class="child-media-cover-grid__controls" role="group" aria-label="<?php echo esc_attr__( 'Medientypen', 'child' ); ?>">
					<span class="child-media-cover-grid__control-label"><?php echo esc_html__( 'Typ', 'child' ); ?></span>
					<div class="child-media-cover-grid__filter-list">
						<button class="child-media-cover-grid__filter is-active" type="button" data-child-media-filter-group="type" data-child-media-filter-value="all" aria-pressed="true">
							<?php echo esc_html__( 'Alle', 'child' ); ?>
						</button>
						<?php foreach ( $item_types as $item_type ) : ?>
							<button class="child-media-cover-grid__filter" type="button" data-child-media-filter-group="type" data-child-media-filter-value="<?php echo esc_attr( $item_type ); ?>" aria-pressed="false">
								<?php echo esc_html( child_get_media_cover_grid_type_label( $item_type ) ); ?>
							</button>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>
			<p class="child-media-cover-grid__empty child-media-cover-grid__empty--filtered" hidden><?php echo esc_html__( 'Keine Medien für diese Filter gefunden.', 'child' ); ?></p>
			<div class="child-media-cover-grid" role="list">
				<?php $current_year = null; ?>
				<?php foreach ( $items as $item ) : ?>
					<?php
					$link_url         = '';
					$link_target      = '';
					$link_rel         = '';
					$type             = (string) ( $item['type'] ?? '' );
					$title            = (string) ( $item['title'] ?? '' );
					$meta             = (string) ( $item['meta'] ?? '' );
					$cover_url        = (string) ( $item['coverUrl'] ?? '' );
					$cover_format     = child_get_media_cover_grid_cover_format( $item );
					$cover_dimensions = [
						'portrait'  => [ 600, 900 ],
						'square'    => [ 600, 600 ],
						'landscape' => [ 1600, 900 ],
					][ $cover_format ] ?? [ 600, 900 ];
					$type_label       = child_get_media_cover_grid_type_label( $type );
					$source_title     = (string) ( $item['sourcePostTitle'] ?? '' );
					$source_timestamp = (int) ( $item['sourcePostTimestamp'] ?? 0 );
					$item_year        = $source_timestamp ? wp_date( 'Y', $source_timestamp ) : esc_html__( 'Unbekannt', 'child' );
					$mention_count    = max( 1, absint( $item['mentionCount'] ?? 1 ) );

					if ( 'post' === $link_to ) {
						$link_url = (string) ( $item['sourcePostUrl'] ?? '' );
					} elseif ( 'external' === $link_to && ! empty( $item['externalUrl'] ) ) {
						$link_url    = (string) $item['externalUrl'];
						$link_target = '_blank';
						$link_rel    = 'noopener noreferrer';
					}

					$tag_name = $link_url ? 'a' : 'div';
					?>
					<?php if ( $item_year !== $current_year ) : ?>
						<div class="child-media-cover-grid__year" data-child-media-year="<?php echo esc_attr( $item_year ); ?>" aria-hidden="false">
							<span class="child-media-cover-grid__year-label"><?php echo esc_html( $item_year ); ?></span>
						</div>
						<?php $current_year = $item_year; ?>
					<?php endif; ?>
					<<?php echo tag_escape( $tag_name ); ?> class="child-media-cover-grid__item child-media-cover-grid__item--<?php echo esc_attr( $type ); ?>" data-child-media-type="<?php echo esc_attr( $type ); ?>"
					<?php
					if ( $link_url ) :
						?>
						href="<?php echo esc_url( $link_url ); ?>"<?php endif; ?>
						<?php
						if ( $link_target ) :
							?>
						target="<?php echo esc_attr( $link_target ); ?>"<?php endif; ?>
						<?php
						if ( $link_rel ) :
							?>
	rel="<?php echo esc_attr( $link_rel ); ?>"<?php endif; ?> role="listitem" aria-label="<?php echo esc_attr( $title ); ?>">
						<div class="child-media-cover-grid__cover child-media-cover-grid__cover--<?php echo esc_attr( $cover_format ); ?>">
							<?php if ( $cover_url ) : ?>
								<img
									src="<?php echo esc_url( $cover_url ); ?>"
									alt="<?php echo esc_attr( $title ); ?>"
									loading="lazy"
									decoding="async"
									fetchpriority="low"
									width="<?php echo esc_attr( (string) $cover_dimensions[0] ); ?>"
									height="<?php echo esc_attr( (string) $cover_dimensions[1] ); ?>"
									sizes="(max-width: 600px) calc((100vw - 3rem) / 2), (max-width: 900px) calc((100vw - 4rem) / 3), 180px"
								/>
							<?php else : ?>
								<span class="child-media-cover-grid__placeholder" aria-hidden="true"><?php echo esc_html( substr( $type_label, 0, 1 ) ); ?></span>
							<?php endif; ?>
						</div>

						<?php if ( $show_type || $show_title || $show_meta ) : ?>
							<div class="child-media-cover-grid__content">
								<?php if ( $show_type ) : ?>
									<span class="child-media-cover-grid__type"><?php echo esc_html( $type_label ); ?></span>
								<?php endif; ?>
								<?php if ( $show_title ) : ?>
									<p class="child-media-cover-grid__title"><?php echo esc_html( $title ); ?></p>
								<?php endif; ?>
								<?php if ( $show_meta && $meta ) : ?>
									<p class="child-media-cover-grid__meta"><?php echo esc_html( $meta ); ?></p>
								<?php endif; ?>
								<?php if ( $show_meta && $source_title ) : ?>
									<p class="child-media-cover-grid__source">
										<?php
										if ( $mention_count > 1 ) {
											printf(
												/* translators: %d: number of source posts */
												esc_html( _n( 'Erwähnt in %d Beitrag', 'Erwähnt in %d Beiträgen', $mention_count, 'child' ) ),
												(int) $mention_count
											);
										} else {
											printf(
												/* translators: %s: source post title */
												esc_html__( 'Aus: %s', 'child' ),
												esc_html( $source_title )
											);
										}
										?>
									</p>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</<?php echo tag_escape( $tag_name ); ?>>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
};
