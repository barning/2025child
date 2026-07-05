<?php
/**
 * Render Music Recommendation Block.
 *
 * @package TwentyTwentyFiveChild
 */

return function( array $attributes ): string {
	$title = trim( (string) ( $attributes['title'] ?? '' ) );
	if ( '' === $title ) {
		return '';
	}

	$music_type   = 'album' === ( $attributes['musicType'] ?? '' ) ? 'album' : 'song';
	$type_label   = 'album' === $music_type ? __( 'Album', 'child' ) : __( 'Song', 'child' );
	$artist       = trim( (string) ( $attributes['artist'] ?? '' ) );
	$album_title  = trim( (string) ( $attributes['albumTitle'] ?? '' ) );
	$release_year = trim( (string) ( $attributes['releaseYear'] ?? '' ) );
	$cover_url    = esc_url( (string) ( $attributes['coverUrl'] ?? '' ) );
	$preview_url  = 'song' === $music_type ? esc_url( (string) ( $attributes['previewUrl'] ?? '' ) ) : '';

	ob_start();
	?>
	<div <?php echo get_block_wrapper_attributes(); ?>>
		<div class="child-music-card child-music-card--<?php echo esc_attr( $music_type ); ?>" aria-label="<?php echo esc_attr( $type_label ); ?>">
			<div class="child-music-card__media">
				<?php if ( $cover_url ) : ?>
					<img src="<?php echo $cover_url; ?>" alt="<?php echo esc_attr( $title ); ?>" class="child-music-card__cover" loading="lazy" />
				<?php else : ?>
					<div class="child-music-card__placeholder" aria-hidden="true">♪</div>
				<?php endif; ?>

				<?php if ( $preview_url ) : ?>
					<button
						type="button"
						class="child-music-card__preview-button"
						data-preview-url="<?php echo esc_url( $preview_url ); ?>"
						data-play-label="<?php echo esc_attr__( 'Hörprobe abspielen', 'child' ); ?>"
						data-pause-label="<?php echo esc_attr__( 'Hörprobe pausieren', 'child' ); ?>"
						aria-label="<?php echo esc_attr__( 'Hörprobe abspielen', 'child' ); ?>"
						aria-pressed="false"
					>
						<span class="child-music-card__preview-icon" aria-hidden="true">▶</span>
					</button>
				<?php endif; ?>
			</div>

			<div class="child-music-card__meta">
				<span class="child-music-card__type"><?php echo esc_html( $type_label ); ?></span>
				<h3 class="child-music-card__title"><?php echo esc_html( $title ); ?></h3>
				<?php if ( $artist ) : ?>
					<p class="child-music-card__artist"><?php echo esc_html( $artist ); ?></p>
				<?php endif; ?>
				<?php if ( 'song' === $music_type && $album_title && $album_title !== $title ) : ?>
					<p class="child-music-card__album"><?php echo esc_html( $album_title ); ?></p>
				<?php endif; ?>
				<?php if ( $release_year ) : ?>
					<p class="child-music-card__year"><?php echo esc_html( $release_year ); ?></p>
				<?php endif; ?>

			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
};
