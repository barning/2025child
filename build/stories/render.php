<?php
declare(strict_types=1);

namespace Child\Blocks\Stories;

use WP_Block;
use WP_Post;

/**
 * Determine whether a story has expired.
 */
function story_is_expired(int $story_id): bool {
    $expiry = get_post_meta($story_id, '_story_expiry_date', true);
    if (empty($expiry)) {
        return false;
    }

    $timestamp = strtotime($expiry);

    return $timestamp ? $timestamp <= time() : false;
}

/**
 * Retrieve media data for a story preview.
 */
function get_story_preview_media(WP_Post $story): ?array {
    $thumbnail_id = get_post_thumbnail_id($story);
    if ($thumbnail_id) {
        $url = get_the_post_thumbnail_url($story, 'thumbnail');
        if ($url) {
            $alt = get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true);
            return [
                'url' => $url,
                'alt' => $alt ?: $story->post_title,
            ];
        }
    }

    $media_items = get_attached_media('', $story->ID);
    if (!empty($media_items)) {
        $media_item = reset($media_items);
        $url = wp_get_attachment_image_url($media_item->ID, 'thumbnail') ?: wp_get_attachment_url($media_item->ID);
        if ($url) {
            $alt = get_post_meta($media_item->ID, '_wp_attachment_image_alt', true);
            return [
                'url' => $url,
                'alt' => $alt ?: $story->post_title,
            ];
        }
    }

    $avatar_url = get_avatar_url($story->post_author, ['size' => 96]);
    if ($avatar_url) {
        return [
            'url' => $avatar_url,
            'alt' => $story->post_title,
        ];
    }

    return null;
}

/**
 * Render a single story preview button.
 */
function render_story_preview(WP_Post $story): ?string {
    if (story_is_expired($story->ID)) {
        return null;
    }

    $media = get_story_preview_media($story);
    if (!$media) {
        return null;
    }

    $title = wp_strip_all_tags(get_the_title($story));
    $preview_title = $title ?: __('Story', 'twentytwentyfivechild');

    $label = sprintf(
        /* translators: %s: story title. */
        __('Open story: %s', 'twentytwentyfivechild'),
        $preview_title
    );

    $image = sprintf(
        '<img src="%s" alt="%s" loading="lazy" decoding="async">',
        esc_url($media['url']),
        esc_attr($media['alt'])
    );

    return sprintf(
        '<button type="button" class="story-preview" data-story-id="%1$s" aria-label="%2$s" role="listitem">' .
            '<span class="story-avatar">%3$s</span>' .
            '<span class="story-title">%4$s</span>' .
        '</button>',
        esc_attr((string) $story->ID),
        esc_attr($label),
        $image,
        esc_html(wp_trim_words($preview_title, 4, '…'))
    );
}

/**
 * Server-side render callback for the Stories block.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Saved block content.
 * @param WP_Block $block      Block instance.
 */
return function (array $attributes = [], string $content = '', ?WP_Block $block = null): string {
    $query_args = [
        'post_type'      => 'story',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'no_found_rows'  => true,
        'fields'         => 'all',
    ];

    /** @var WP_Post[] $stories */
    $stories = get_posts($query_args);

    $previews = array_filter(array_map(__NAMESPACE__ . '\\render_story_preview', $stories));

    $wrapper_attributes = get_block_wrapper_attributes([
        'data-feed'            => esc_url(rest_url('twentytwentyfivechild/v1/stories')),
        'data-continue-label'  => esc_attr__('Continue', 'twentytwentyfivechild'),
    ]);

    if (empty($previews)) {
        return sprintf(
            '<div %1$s><p>%2$s</p></div>',
            $wrapper_attributes,
            esc_html__('No stories available yet.', 'twentytwentyfivechild')
        );
    }

    ob_start();
    ?>
    <div <?php echo $wrapper_attributes; ?>>
        <div class="stories-container" role="list">
            <?php echo implode('', $previews); ?>
        </div>
        <div class="story-viewer" hidden aria-hidden="true">
            <div class="story-viewer-content" role="dialog" aria-modal="true" aria-live="polite" aria-hidden="true" tabindex="-1">
                <button type="button" class="story-close" aria-label="<?php echo esc_attr__('Close stories', 'twentytwentyfivechild'); ?>">&times;</button>
                <div class="story-media" role="presentation"></div>
                <div class="story-progress" aria-hidden="true"></div>
                <div class="story-navigation" aria-label="<?php echo esc_attr__('Story navigation', 'twentytwentyfivechild'); ?>">
                    <button type="button" class="story-prev" aria-label="<?php echo esc_attr__('Previous story', 'twentytwentyfivechild'); ?>">&lt;</button>
                    <button type="button" class="story-next" aria-label="<?php echo esc_attr__('Next story', 'twentytwentyfivechild'); ?>">&gt;</button>
                </div>
            </div>
        </div>
    </div>
    <?php

    return trim((string) ob_get_clean());
};
