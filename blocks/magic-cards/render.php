<?php
/**
 * Render Magic Cards Block
 *
 * @param array  $attributes The block attributes
 * @param string $content    The block content
 * @return string Returns the block content
 */

return function($attributes) {
    $wrapper_attributes = get_block_wrapper_attributes();
    $display_type = $attributes['displayType'] ?? 'single';

    if ($display_type === 'moxfield') {
        return child_render_moxfield_embed($attributes, $wrapper_attributes);
    } else {
        return child_render_magic_card($attributes, $wrapper_attributes);
    }
};

/**
 * Render a Moxfield deck embed
 */
function child_render_moxfield_embed($attributes, $wrapper_attributes) {
    $moxfield_url = $attributes['moxfieldUrl'] ?? '';
    
    if (empty($moxfield_url)) {
        return '';
    }

    // Extract deck ID from URL
    if (!preg_match('/moxfield\.com\/decks\/([a-zA-Z0-9_-]+)/', $moxfield_url, $matches)) {
        return '';
    }

    $deck_id = $matches[1];
    $embed_url = 'https://www.moxfield.com/embed/' . esc_attr($deck_id);

    ob_start(); ?>
    <div <?php echo $wrapper_attributes; ?>>
        <div class="child-magic-moxfield">
            <iframe 
                src="<?php echo esc_url($embed_url); ?>" 
                class="child-magic-moxfield__iframe"
                loading="lazy"
                frameborder="0"
                allowfullscreen
                title="<?php echo esc_attr__('Moxfield Deck Embed', 'child'); ?>"
            ></iframe>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render a single Magic card
 */
function child_render_magic_card($attributes, $wrapper_attributes) {
    $card_name = $attributes['cardName'] ?? '';
    $card_image_url = $attributes['cardImageUrl'] ?? '';
    
    if (empty($card_name)) {
        return '';
    }

    // Fallback: if no image URL, try to fetch from Scryfall cache
    if (empty($card_image_url) && !empty($attributes['scryfallId'])) {
        $cache_key = 'child_mtg_' . md5($attributes['scryfallId']);
        $cached_image = get_transient($cache_key);
        if ($cached_image) {
            $card_image_url = $cached_image;
        }
    }

    ob_start(); ?>
    <div <?php echo $wrapper_attributes; ?>>
        <div class="child-magic-card" aria-label="<?php echo esc_attr__('Magic: The Gathering Card', 'child'); ?>">
            <div class="child-magic-card__media">
                <?php if (!empty($card_image_url)) : ?>
                    <img 
                        src="<?php echo esc_url($card_image_url); ?>" 
                        alt="<?php echo esc_attr($card_name); ?>" 
                        class="child-magic-card__image"
                        loading="lazy"
                    />
                <?php else : ?>
                    <div class="child-magic-card__placeholder" aria-hidden="true">
                        <span>🃏</span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="child-magic-card__meta">
                <h3 class="child-magic-card__name"><?php echo esc_html($card_name); ?></h3>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
