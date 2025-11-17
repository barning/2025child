<?php
declare(strict_types=1);

/**
 * Server-side render callback for the Hero Teaser block.
 *
 * @return callable(array<string,mixed>):string
 */
return static function(array $attributes = []): string {
    $title       = $attributes['title'] ?? '';
    $subtitle    = $attributes['subtitle'] ?? '';
    $description = $attributes['description'] ?? '';
    $image_id    = isset($attributes['imageId']) ? (int) $attributes['imageId'] : 0;
    $image_url   = $attributes['imageUrl'] ?? '';
    $image_alt   = isset($attributes['imageAlt']) ? sanitize_text_field($attributes['imageAlt']) : '';
    $accent      = $attributes['accentColor'] ?? 'rgba(0,0,0,0.35)';
    $layout      = $attributes['layout'] ?? 'text-left';

    $layout_class = sanitize_html_class($layout ?: 'text-left');
    $classes      = sprintf('wp-block-child-hero-teaser %s', $layout_class);
    $style_attr   = $accent ? sprintf(' style="--child-hero-accent:%s;"', esc_attr($accent)) : '';

    $img_tag = '';
    if ($image_id > 0) {
        $img_tag = wp_get_attachment_image(
            $image_id,
            '2048x2048',
            false,
            [
                'class'   => 'child-hero__img',
                'loading' => 'lazy',
                'alt'     => $image_alt,
            ]
        );
    } elseif (!empty($image_url)) {
        $img_tag = sprintf(
            '<img class="child-hero__img" src="%s" alt="%s" loading="lazy" />',
            esc_url($image_url),
            esc_attr($image_alt)
        );
    }

    $html  = '<div class="' . esc_attr($classes) . '"' . $style_attr . '>';
    $html .= '<div class="child-hero__media">';
    if ($img_tag) {
        $html .= $img_tag;
    } else {
        $html .= '<div class="child-hero__media-placeholder" aria-hidden="true"></div>';
    }
    $html .= '<div class="child-hero__overlay" aria-hidden="true"></div>';
    $html .= '</div>';
    $html .= '<div class="child-hero__content">';

    if ($subtitle) {
        $html .= '<div class="child-hero__subtitle">' . wp_kses_post($subtitle) . '</div>';
    }

    if ($title) {
        $html .= '<h2 class="child-hero__title">' . wp_kses_post($title) . '</h2>';
    }

    if ($description) {
        $html .= '<div class="child-hero__description">' . wp_kses_post($description) . '</div>';
    }

    $html .= '</div></div>';

    return $html;
};
