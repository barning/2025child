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
    $image_alt   = $attributes['imageAlt'] ?? '';
    $accent      = $attributes['accentColor'] ?? 'rgba(0,0,0,0.35)';
    $layout      = $attributes['layout'] ?? 'text-left';

    $layout_class = sanitize_html_class($layout ?: 'text-left');
    $classes      = sprintf('wp-block-child-hero-teaser %s', $layout_class);

    $img_tag = '';
    if ($image_id > 0) {
        $src    = wp_get_attachment_image_url($image_id, 'large');
        $srcset = wp_get_attachment_image_srcset($image_id, 'large');
        $sizes  = wp_get_attachment_image_sizes($image_id, 'large');

        if ($src) {
            $img_tag = sprintf(
                '<img src="%s"%s%s alt="%s" loading="lazy" style="width:100%%;height:100%%;object-fit:cover;" />',
                esc_url($src),
                $srcset ? sprintf(' srcset="%s"', esc_attr($srcset)) : '',
                $sizes ? sprintf(' sizes="%s"', esc_attr($sizes)) : '',
                esc_attr($image_alt)
            );
        }
    } elseif (!empty($image_url)) {
        $img_tag = sprintf(
            '<img src="%s" alt="%s" loading="lazy" style="width:100%%;height:100%%;object-fit:cover;" />',
            esc_url($image_url),
            esc_attr($image_alt)
        );
    }

    $overlay_style = sprintf('background: linear-gradient(%s, rgba(0,0,0,0.0));', esc_attr($accent));

    $html  = '<div class="' . esc_attr($classes) . '">';
    $html .= '<div class="child-hero__media">' . $img_tag . '</div>';
    $html .= '<div class="child-hero__overlay" style="' . $overlay_style . '"></div>';
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
