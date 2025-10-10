<?php
declare(strict_types=1);

/**
 * Server-side render for Popular Posts block (styled card)
 * 
 * @package Child
 * @subpackage Blocks
 */

namespace Child\Blocks\PopularPosts;

const DEFAULT_TITLE = 'Some Favorites To Get You Started';
const DEFAULT_EMOJI = '✨';

/**
 * Renders the header section of the popular posts card
 *
 * @param string $emoji The emoji to display
 * @param string $title The title to display
 * @return string The rendered header HTML
 */
function render_header(string $emoji, string $title): string {
    return sprintf(
        '<div class="child-popular-card__header">
            <div class="child-popular-card__emoji" aria-hidden="true">%s</div>
            <h3 class="child-popular-card__title">%s</h3>
        </div>',
        esc_html($emoji),
        esc_html($title)
    );
}

/**
 * Renders the list of posts
 *
 * @param \WP_Query $query The query containing the posts
 * @return string The rendered posts list HTML
 */
function render_posts_list(\WP_Query $query): string {
    if (!$query->have_posts()) {
        return sprintf('<p>%s</p>', esc_html__('No popular posts yet.', 'child'));
    }

    $items = array_map(function($post) {
        return sprintf(
            '<li class="child-popular-card__item">
                <a class="child-popular-card__link" href="%s">%s</a>
            </li>',
            get_permalink($post),
            wp_strip_all_tags(get_the_title($post))
        );
    }, $query->posts);

    return sprintf('<ol class="child-popular-card__list">%s</ol>', implode('', $items));
}

/**
 * Main render function for the Popular Posts block
 *
 * @param array $attributes Block attributes from the editor
 * @return string The complete rendered block HTML
 */
return function(array $attributes): string {
    $selected_posts = $attributes['selectedPosts'] ?? [];
    $title = wp_strip_all_tags($attributes['title'] ?? __( DEFAULT_TITLE, 'child' ));
    $emoji = wp_strip_all_tags($attributes['emoji'] ?? DEFAULT_EMOJI);

    if (empty($selected_posts)) {
        return sprintf(
            '<div class="wp-block-child-popular-posts"><div class="child-popular-card"><p>%s</p></div></div>',
            esc_html__('Please select some posts.', 'child')
        );
    }

    $query = new \WP_Query([
        'post_type'      => 'post',
        'post__in'       => $selected_posts,
        'orderby'        => 'post__in',
        'no_found_rows'  => true,
        'post_status'    => 'publish',
    ]);

    $content = sprintf(
        '<div class="child-popular-card">%s%s</div>',
        render_header($emoji, $title),
        render_posts_list($query)
    );

    return sprintf('<div class="wp-block-child-popular-posts">%s</div>', $content);
};
