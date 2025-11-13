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
    $rating = isset( $attributes['rating'] ) ? (int) $attributes['rating'] : 0;

    if (empty($book_title)) {
        return '';
    }

    $normalized_rating = max(0, min(5, $rating));

    ob_start(); ?>
    <div <?php echo $wrapper_attributes; ?>>
        <div class="child-book-card" aria-label="<?php echo esc_attr__( 'Buchbewertung', 'child' ); ?>">
            <div class="child-book-card__media">
                <?php if ( ! empty( $cover_url ) ) : ?>
                    <img 
                        src="<?php echo esc_url( $cover_url ); ?>" 
                        alt="<?php echo esc_attr( $book_title ); ?>" 
                        class="child-book-card__cover"
                        loading="lazy"
                    />
                <?php else : ?>
                    <div class="child-book-card__placeholder" aria-hidden="true"></div>
                <?php endif; ?>
            </div>

            <div class="child-book-card__stars" aria-label="<?php echo esc_attr__( 'Bewertung', 'child' ); ?>">
                <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                    <span class="child-book-card__star<?php echo $i <= $normalized_rating ? ' is-active' : ''; ?>">★</span>
                <?php endfor; ?>
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
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
};
