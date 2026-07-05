<?php
/**
 * Render Media Cover Grid Block.
 *
 * @package TwentyTwentyFiveChild
 */

return function( array $attributes ): string {
	if ( ! function_exists( 'child_get_media_cover_grid_items' ) ) {
		return '';
	}

	$default_types     = [ 'book', 'movie', 'tv', 'game', 'music' ];
	$default_formats   = [ 'portrait', 'square', 'landscape' ];
	$media_types       = $attributes['mediaTypes'] ?? $default_types;
	$allowed_types     = array_values( array_intersect( $default_types, is_array( $media_types ) ? $media_types : $default_types ) );
	$cover_formats     = $attributes['coverFormats'] ?? $default_formats;
	$allowed_formats   = array_values( array_intersect( $default_formats, is_array( $cover_formats ) ? $cover_formats : $default_formats ) );
	$max_items         = max( 1, min( 120, absint( $attributes['maxItems'] ?? 48 ) ) );
	$link_to           = in_array( $attributes['linkTo'] ?? 'post', [ 'post', 'external', 'none' ], true ) ? $attributes['linkTo'] : 'post';
	$sort_order        = in_array( $attributes['sortOrder'] ?? 'newest', [ 'newest', 'oldest', 'title' ], true ) ? $attributes['sortOrder'] : 'newest';
	$show_title        = (bool) ( $attributes['showTitle'] ?? true );
	$show_meta         = (bool) ( $attributes['showMeta'] ?? true );
	$show_type         = (bool) ( $attributes['showType'] ?? true );
	$allow_duplicates  = (bool) ( $attributes['allowDuplicates'] ?? false );

	if ( [] === $allowed_types || [] === $allowed_formats ) {
		$empty_message = [] === $allowed_types
			? esc_html__( 'Bitte wähle mindestens einen Medientyp aus.', 'child' )
			: esc_html__( 'Bitte wähle mindestens ein Format aus.', 'child' );

		return sprintf(
			'<div %s><p class="child-media-cover-grid__empty">%s</p></div>',
			get_block_wrapper_attributes( [ 'class' => 'child-media-cover-grid-block' ] ),
			$empty_message
		);
	}

	$items = child_get_media_cover_grid_items( $allow_duplicates );
	$items = array_values(
		array_filter(
			$items,
			static function( array $item ) use ( $allowed_types, $allowed_formats ): bool {
				$type         = (string) ( $item['type'] ?? '' );
				$cover_format = child_get_media_cover_grid_cover_format( $item );

				return in_array( $type, $allowed_types, true ) && in_array( $cover_format, $allowed_formats, true );
			}
		)
	);

	usort(
		$items,
		static function( array $a, array $b ) use ( $sort_order ): int {
			if ( 'title' === $sort_order ) {
				return strcasecmp( $a['title'] ?? '', $b['title'] ?? '' );
			}

			$a_time = (int) ( $a['sourcePostTimestamp'] ?? 0 );
			$b_time = (int) ( $b['sourcePostTimestamp'] ?? 0 );

			return 'oldest' === $sort_order ? $a_time <=> $b_time : $b_time <=> $a_time;
		}
	);

	$items = array_slice( $items, 0, $max_items );

	ob_start();
	?>
	<div <?php echo get_block_wrapper_attributes( [ 'class' => 'child-media-cover-grid-block' ] ); ?>>
		<?php if ( [] === $items ) : ?>
			<p class="child-media-cover-grid__empty"><?php echo esc_html__( 'Noch keine Medien gefunden.', 'child' ); ?></p>
		<?php else : ?>
			<div class="child-media-cover-grid" role="list">
				<?php foreach ( $items as $item ) : ?>
					<?php
					$link_url     = '';
					$link_target  = '';
					$link_rel     = '';
					$type         = (string) ( $item['type'] ?? '' );
					$title        = (string) ( $item['title'] ?? '' );
					$meta         = (string) ( $item['meta'] ?? '' );
					$cover_url    = (string) ( $item['coverUrl'] ?? '' );
					$cover_format = child_get_media_cover_grid_cover_format( $item );
					$type_label   = child_get_media_cover_grid_type_label( $type );
					$source_title  = (string) ( $item['sourcePostTitle'] ?? '' );
					$mention_count = max( 1, absint( $item['mentionCount'] ?? 1 ) );

					if ( 'post' === $link_to ) {
						$link_url = (string) ( $item['sourcePostUrl'] ?? '' );
					} elseif ( 'external' === $link_to && ! empty( $item['externalUrl'] ) ) {
						$link_url    = (string) $item['externalUrl'];
						$link_target = ' target="_blank"';
						$link_rel    = ' rel="noopener noreferrer"';
					}

					$tag_name = $link_url ? 'a' : 'div';
					?>
					<<?php echo tag_escape( $tag_name ); ?> class="child-media-cover-grid__item child-media-cover-grid__item--<?php echo esc_attr( $type ); ?>"<?php echo $link_url ? ' href="' . esc_url( $link_url ) . '"' . $link_target . $link_rel : ''; ?> role="listitem" aria-label="<?php echo esc_attr( $title ); ?>">
						<div class="child-media-cover-grid__cover child-media-cover-grid__cover--<?php echo esc_attr( $cover_format ); ?>">
							<?php if ( $cover_url ) : ?>
								<img src="<?php echo esc_url( $cover_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" />
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
									<h3 class="child-media-cover-grid__title"><?php echo esc_html( $title ); ?></h3>
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
