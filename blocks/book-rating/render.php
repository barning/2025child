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
    $genre = $attributes['genre'] ?? '';
    $cover_url = $attributes['coverUrl'] ?? '';
    $rating = $attributes['rating'] ?? 0;

    if (empty($book_title)) {
        return '';
    }

    ob_start(); ?>
    <div <?php echo $wrapper_attributes; ?>>
        <div class="book-display">
            <?php if (!empty($cover_url)) : ?>
                <img src="<?php echo esc_url($cover_url); ?>" 
                     alt="<?php echo esc_attr($book_title); ?>" 
                     class="book-cover" />
            <?php endif; ?>
            
            <div class="book-info">
                <h3 class="book-title"><?php echo esc_html($book_title); ?></h3>
                <p class="book-author"><?php echo esc_html($author); ?></p>
                <?php if (!empty($genre)) : ?>
                    <p class="book-genre"><?php echo esc_html($genre); ?></p>
                <?php endif; ?>
                
                <div class="book-rating">
                    <?php for ($i = 1; $i <= 5; $i++) : ?>
                        <span class="star<?php echo $i <= $rating ? ' active' : ''; ?>">★</span>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
};