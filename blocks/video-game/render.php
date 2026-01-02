<?php
/**
 * Render Video Game Block
 *
 * @param array  $attributes The block attributes
 * @param string $content    The block content
 * @return string Returns the block content
 */

return function($attributes) {
    $wrapper_attributes = get_block_wrapper_attributes();
    $game_title = $attributes['gameTitle'] ?? '';
    $cover_url = $attributes['coverUrl'] ?? '';

    if (empty($game_title)) {
        return '';
    }

    $cover_attrs = '';
    $cover_style = '';
    if ( ! empty( $cover_url ) ) {
        $cover_attrs = ' data-cover-url="' . esc_url( $cover_url ) . '"';
        $cover_style = ' style="--cover-bg: url(\'' . esc_url( $cover_url ) . '\');"';
    }
    
    ob_start(); ?>
    <div <?php echo $wrapper_attributes; ?>>
        <div class="child-game-card" aria-label="<?php echo esc_attr__( 'Videospiel', 'child' ); ?>">
            <div class="child-game-card__media"<?php echo $cover_attrs . $cover_style; ?>>
                <?php if ( ! empty( $cover_url ) ) : ?>
                    <img 
                        src="<?php echo esc_url( $cover_url ); ?>" 
                        alt="<?php echo esc_attr( $game_title ); ?>" 
                        class="child-game-card__cover"
                        loading="lazy"
                        crossorigin="anonymous"
                    />
                <?php else : ?>
                    <div class="child-game-card__placeholder" aria-hidden="true"></div>
                <?php endif; ?>
            </div>
            
            <div class="child-game-card__meta">
                <h3 class="child-game-card__title"><?php echo esc_html( $game_title ); ?></h3>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
};
