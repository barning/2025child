<?php
/**
 * Render Media Recommendation Block
 *
 * @param array  $attributes The block attributes
 * @param string $content    The block content
 * @return string Returns the block content
 */

return function($attributes) {
    $wrapper_attributes = get_block_wrapper_attributes();
    $media_title = $attributes['mediaTitle'] ?? '';
    $media_type = $attributes['mediaType'] ?? 'movie';
    $poster_url = $attributes['posterUrl'] ?? '';
    $release_year = $attributes['releaseYear'] ?? '';
    $block_id = 'media-card-' . wp_unique_id();

    if (empty($media_title)) {
        return '';
    }

    $type_label = $media_type === 'movie' ? __( 'Film', 'child' ) : __( 'Serie', 'child' );

    ob_start(); ?>
    <div <?php echo $wrapper_attributes; ?>>
        <div class="child-media-card" aria-label="<?php echo esc_attr( $type_label ); ?>">
            <div class="child-media-card__media" id="<?php echo esc_attr( $block_id ); ?>">
                <?php if ( ! empty( $poster_url ) ) : ?>
                    <img 
                        src="<?php echo esc_url( $poster_url ); ?>" 
                        alt="<?php echo esc_attr( $media_title ); ?>" 
                        class="child-media-card__poster"
                        loading="lazy"
                        crossorigin="anonymous"
                        onload="childMediaAmbilightInit('<?php echo esc_js( $block_id ); ?>', this)"
                    />
                <?php else : ?>
                    <div class="child-media-card__placeholder" aria-hidden="true"></div>
                <?php endif; ?>
            </div>
            
            <div class="child-media-card__meta">
                <h3 class="child-media-card__title"><?php echo esc_html( $media_title ); ?></h3>
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
