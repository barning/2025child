<?php
/**
 * Render Videogame Recommendation Block
 *
 * @param array  $attributes The block attributes
 * @return string Returns the block content
 */

return function( $attributes ) {
	/**
	 * Get platform display info from platform name
	 * @param string $platform_name - Raw platform name from API
	 * @return array - ['name' => string, 'color' => string]
	 */
	$get_platform_info = function( $platform_name ) {
		$name = strtolower( $platform_name );
		
		$platforms = [
			['match' => ['playstation 5', 'ps5'], 'name' => 'PS5', 'color' => '#003087'],
			['match' => ['playstation 4', 'ps4'], 'name' => 'PS4', 'color' => '#003087'],
			['match' => ['playstation'], 'name' => 'PlayStation', 'color' => '#003087'],
			['match' => ['xbox series'], 'name' => 'Xbox Series', 'color' => '#107C10'],
			['match' => ['xbox one'], 'name' => 'Xbox One', 'color' => '#107C10'],
			['match' => ['xbox'], 'name' => 'Xbox', 'color' => '#107C10'],
			['match' => ['nintendo switch', 'switch'], 'name' => 'Switch', 'color' => '#E60012'],
			['match' => ['nintendo'], 'name' => 'Nintendo', 'color' => '#E60012'],
			['match' => ['pc', 'windows'], 'name' => 'PC', 'color' => '#0078D4'],
			['match' => ['ios', 'iphone'], 'name' => 'iOS', 'color' => '#555555'],
			['match' => ['android'], 'name' => 'Android', 'color' => '#3DDC84'],
			['match' => ['linux'], 'name' => 'Linux', 'color' => '#FCC624'],
			['match' => ['mac'], 'name' => 'macOS', 'color' => '#999999']
		];
		
		foreach ( $platforms as $platform ) {
			foreach ( $platform['match'] as $match ) {
				if ( strpos( $name, $match ) !== false ) {
					return ['name' => $platform['name'], 'color' => $platform['color']];
				}
			}
		}
		
		return ['name' => $platform_name, 'color' => '#666666'];
	};
	
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
					<img 
						src="<?php echo esc_url( $cover_url ); ?>" 
						alt="<?php echo esc_attr( $game_title ); ?>" 
						class="child-game-card__cover"
						loading="lazy"
					/>
				<?php else : ?>
					<div class="child-game-card__placeholder" aria-hidden="true"></div>
				<?php endif; ?>
			</div>
			
			<div class="child-game-card__meta">
				<?php if ( ! empty( $platforms ) && is_array( $platforms ) ) : ?>
					<div class="child-game-card__platforms" aria-label="<?php echo esc_attr( __( 'Plattformen', 'child' ) ); ?>">
						<?php foreach ( array_slice( $platforms, 0, 5 ) as $platform ) : 
							$platform_info = $get_platform_info( $platform );
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

				<?php if ( ! empty( $shop_url ) ) : ?>
					<p class="child-game-card__link-row">
						<a class="child-game-card__link" href="<?php echo esc_url( $shop_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'Zum Shop', 'child' ); ?></a>
					</p>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
};
