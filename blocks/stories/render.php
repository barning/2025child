<?php
/**
 * Stories Block Render Template
 *
 * @package twentytwentyfivechild
 */

$stories = get_posts(array(
    'post_type' => 'story',
    'posts_per_page' => -1,
    'orderby' => 'date',
    'order' => 'DESC'
));

if (!empty($stories)) :
?>
<div class="wp-block-twentytwentyfivechild-stories">
    <div class="stories-container">
        <?php foreach ($stories as $story) :
            $media = get_attached_media('', $story->ID);
            if (empty($media)) continue;
            
            $media_item = reset($media);
            $thumbnail = get_the_post_thumbnail_url($story->ID, 'thumbnail');
            $expiry_date = get_post_meta($story->ID, '_story_expiry_date', true);
            
            if ($expiry_date && strtotime($expiry_date) < time()) continue;
        ?>
            <div class="story-preview" data-story-id="<?php echo esc_attr($story->ID); ?>">
                <div class="story-avatar">
                    <?php if ($thumbnail) : ?>
                        <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr($story->post_title); ?>">
                    <?php endif; ?>
                </div>
                <div class="story-title">
                    <?php echo esc_html($story->post_title); ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="story-viewer" style="display: none;">
        <div class="story-viewer-content">
            <div class="story-close">&times;</div>
            <div class="story-media"></div>
            <div class="story-progress"></div>
            <div class="story-navigation">
                <button class="story-prev">&lt;</button>
                <button class="story-next">&gt;</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>