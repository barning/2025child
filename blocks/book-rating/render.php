<?php
/**
 * Render Book Rating Block
 *
 * @param array  $attributes The block attributes
 * @param string $content    The block content
 * @return string Returns the block content
 */

return function($attributes) {
    $wrapper_attributes = get_block_wrapper_attributes();
    $book_title = $attributes['bookTitle'] ?? '';
    $author = $attributes['author'] ?? '';
    $cover_url = $attributes['coverUrl'] ?? '';
    $shop_url = $attributes['shopUrl'] ?? '';
    $block_id = 'book-card-' . wp_unique_id();

    if (empty($book_title)) {
        return '';
    }

    ob_start(); ?>
    <div <?php echo $wrapper_attributes; ?>>
        <div class="child-book-card" aria-label="<?php echo esc_attr__( 'Buch', 'child' ); ?>">
            <div class="child-book-card__media" id="<?php echo esc_attr( $block_id ); ?>">
                <?php if ( ! empty( $cover_url ) ) : ?>
                    <img 
                        src="<?php echo esc_url( $cover_url ); ?>" 
                        alt="<?php echo esc_attr( $book_title ); ?>" 
                        class="child-book-card__cover"
                        loading="lazy"
                        crossorigin="anonymous"
                        onload="childBookAmbilightInit('<?php echo esc_js( $block_id ); ?>', this)"
                    />
                <?php else : ?>
                    <div class="child-book-card__placeholder" aria-hidden="true"></div>
                <?php endif; ?>
            </div>
            
            <div class="child-book-card__meta">
                <h3 class="child-book-card__title"><?php echo esc_html( $book_title ); ?></h3>
                <?php if ( ! empty( $author ) ) : ?>
                    <p class="child-book-card__author">
                        <?php 
                            /* translators: %s: author name */
                            printf( esc_html__( 'Von %s', 'child' ), esc_html( $author ) ); 
                        ?>
                    </p>
                <?php endif; ?>
                <?php if ( ! empty( $shop_url ) ) : ?>
                    <p class="child-book-card__link-row">
                        <a class="child-book-card__link" href="<?php echo esc_url( $shop_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'Zum Shop', 'child' ); ?></a>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
};
