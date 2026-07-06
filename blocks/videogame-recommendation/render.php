<?php
/**
 * Render Videogame Recommendation Block
 *
 * @param array  $attributes The block attributes
 * @return string Returns the block content
 */

return function( $attributes ) {
	$utils_file = __DIR__ . '/utils.php';
	if ( is_readable( $utils_file ) ) {
		require_once $utils_file;
	}
	
	$wrapper_attributes = get_block_wrapper_attributes();
	$game_title = $attributes['gameTitle'] ?? '';
	$cover_url = $attributes['coverUrl'] ?? '';
	$release_date = $attributes['releaseDate'] ?? '';
	$platforms = $attributes['platforms'] ?? [];
	$genres = $attributes['genres'] ?? [];
	$shop_url = $attributes['shopUrl'] ?? '';

	if ( empty( $game_title ) ) {
		return '';
	}

	// Format release date
	$formatted_date = '';
	if ( ! empty( $release_date ) ) {
		$timestamp = strtotime( $release_date );
		if ( $timestamp ) {
			$formatted_date = date_i18n( 'j. M Y', $timestamp );
		}
	}

	ob_start(); ?>
	<div <?php echo $wrapper_attributes; ?>>
		<div class="child-game-card" aria-label="<?php echo esc_attr( __( 'Videospiel', 'child' ) ); ?>">
			<div class="child-game-card__media">
				<?php if ( ! empty( $cover_url ) ) : ?>
					<?php if ( ! empty( $shop_url ) ) : ?>
						<a class="child-game-card__cover-link" href="<?php echo esc_url( $shop_url ); ?>" target="_blank" rel="noopener noreferrer">
							<img 
								src="<?php echo esc_url( $cover_url ); ?>" 
								alt="<?php echo esc_attr( $game_title ); ?>" 
								class="child-game-card__cover"
								loading="lazy"
							/>
						</a>
					<?php else : ?>
						<img 
							src="<?php echo esc_url( $cover_url ); ?>" 
							alt="<?php echo esc_attr( $game_title ); ?>" 
							class="child-game-card__cover"
							loading="lazy"
						/>
					<?php endif; ?>
				<?php else : ?>
					<div class="child-game-card__placeholder" aria-hidden="true"></div>
				<?php endif; ?>
			</div>
			
			<div class="child-game-card__meta">
				<?php if ( ! empty( $platforms ) && is_array( $platforms ) ) : ?>
					<div class="child-game-card__platforms" aria-label="<?php echo esc_attr( __( 'Plattformen', 'child' ) ); ?>">
						<?php foreach ( array_slice( $platforms, 0, 5 ) as $platform ) : 
							$platform_info = function_exists( 'child_get_platform_info' )
								? child_get_platform_info( $platform )
								: [ 'name' => $platform, 'color' => '#666666' ];
						?>
							<span 
								class="child-game-card__platform-chip" 
								style="background-color: <?php echo esc_attr( $platform_info['color'] ); ?>"
								title="<?php echo esc_attr( $platform ); ?>"
							>
								<?php echo esc_html( $platform_info['name'] ); ?>
							</span>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
				
				<h3 class="child-game-card__title"><?php echo esc_html( $game_title ); ?></h3>
				
				<?php if ( ! empty( $formatted_date ) ) : ?>
					<div class="child-game-card__info-row">
						<span class="child-game-card__label"><?php echo esc_html( __( 'Release date:', 'child' ) ); ?></span>
						<span class="child-game-card__value"><?php echo esc_html( $formatted_date ); ?></span>
					</div>
				<?php endif; ?>
				
				<?php if ( ! empty( $genres ) && is_array( $genres ) ) : ?>
					<div class="child-game-card__info-row">
						<span class="child-game-card__label"><?php echo esc_html( __( 'Genres:', 'child' ) ); ?></span>
						<span class="child-game-card__value"><?php echo esc_html( implode( ', ', array_slice( $genres, 0, 3 ) ) ); ?></span>
					</div>
				<?php endif; ?>

			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
};
