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
        <div class="book-display" aria-label="<?php echo esc_attr__( 'Buchbewertung', 'child' ); ?>">
            <div class="book-display__card">
                <?php if (!empty($cover_url)) : ?>
                    <div class="book-cover-frame">
                        <img 
                            src="<?php echo esc_url($cover_url); ?>" 
                            alt="<?php echo esc_attr($book_title); ?>" 
                            class="book-cover"
                            loading="lazy"
                        />
                    </div>
                <?php else : ?>
                    <div class="book-cover book-cover--placeholder" aria-hidden="true"></div>
                <?php endif; ?>
                
                <div class="book-rating" aria-label="<?php echo esc_attr__( 'Bewertung', 'child' ); ?>">
                    <?php for ($i = 1; $i <= 5; $i++) : ?>
                        <span class="star<?php echo $i <= $normalized_rating ? ' active' : ''; ?>">★</span>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="book-info">
                <h3 class="book-title"><?php echo esc_html($book_title); ?></h3>
                <?php if (!empty($author)) : ?>
                    <p class="book-author">
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
