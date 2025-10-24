<?php
/**
 * Server render for hero-teaser
 */

$attrs = $attributes;
$title = isset($attrs['title']) ? $attrs['title'] : '';
$subtitle = isset($attrs['subtitle']) ? $attrs['subtitle'] : '';
$description = isset($attrs['description']) ? $attrs['description'] : '';
$image_url = isset($attrs['imageUrl']) ? $attrs['imageUrl'] : '';
$image_alt = isset($attrs['imageAlt']) ? $attrs['imageAlt'] : '';
$accent = isset($attrs['accentColor']) ? $attrs['accentColor'] : 'rgba(0,0,0,0.35)';
$layout = isset($attrs['layout']) ? $attrs['layout'] : 'text-left';

$class = 'wp-block-child-hero-teaser ' . esc_attr($layout);

// Build image markup with responsive srcset if attachment ID is present
$img_tag = '';
if (!empty($attrs['imageId'])) {
  $id = intval($attrs['imageId']);
  $src = wp_get_attachment_image_url($id, 'large');
  $srcset = wp_get_attachment_image_srcset($id, 'large');
  $sizes = wp_get_attachment_image_sizes($id, 'large');
  if ($src) {
    $img_tag = sprintf('<img src="%s" srcset="%s" sizes="%s" alt="%s" loading="lazy" style="width:100%%;height:100%%;object-fit:cover;"/>', esc_url($src), esc_attr($srcset), esc_attr($sizes), esc_attr($image_alt));
  }
} elseif (!empty($image_url)) {
  $img_tag = sprintf('<img src="%s" alt="%s" loading="lazy" style="width:100%%;height:100%%;object-fit:cover;"/>', esc_url($image_url), esc_attr($image_alt));
}

$overlay_style = sprintf('background: linear-gradient(%s, rgba(0,0,0,0.0));', esc_attr($accent));

echo '<div class="' . $class . '">';
  echo '<div class="child-hero__media">';
    echo $img_tag;
  echo '</div>';
  echo '<div class="child-hero__overlay" style="' . $overlay_style . '"></div>';
  echo '<div class="child-hero__content">';
    if ($subtitle) echo '<div class="child-hero__subtitle">' . wp_kses_post($subtitle) . '</div>';
    if ($title) echo '<h2 class="child-hero__title">' . wp_kses_post($title) . '</h2>';
    if ($description) echo '<div class="child-hero__description">' . wp_kses_post($description) . '</div>';
  echo '</div>';
echo '</div>'; 

return;
?>
