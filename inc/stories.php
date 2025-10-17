<?php
/**
 * Stories Feature Implementation
 *
 * Implements Instagram-style stories with support for images and videos.
 * Follows the OpenStories specification: https://github.com/dddddddddzzzz/OpenStories
 *
 * @package twentytwentyfivechild
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Stories Custom Post Type
 */
function twentytwentyfivechild_register_story_post_type() {
    $labels = array(
        'name'                  => _x('Stories', 'Post type general name', 'twentytwentyfivechild'),
        'singular_name'         => _x('Story', 'Post type singular name', 'twentytwentyfivechild'),
        'menu_name'            => _x('Stories', 'Admin Menu text', 'twentytwentyfivechild'),
        'add_new'              => _x('Add New', 'story', 'twentytwentyfivechild'),
        'add_new_item'         => __('Add New Story', 'twentytwentyfivechild'),
        'edit_item'            => __('Edit Story', 'twentytwentyfivechild'),
        'new_item'             => __('New Story', 'twentytwentyfivechild'),
        'view_item'            => __('View Story', 'twentytwentyfivechild'),
        'search_items'         => __('Search Stories', 'twentytwentyfivechild'),
        'not_found'            => __('No stories found', 'twentytwentyfivechild'),
        'not_found_in_trash'   => __('No stories found in trash', 'twentytwentyfivechild'),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug' => 'story'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 5,
        'supports'           => array('title', 'editor', 'thumbnail'),
        'show_in_rest'       => true,
        'menu_icon'          => 'dashicons-format-gallery'
    );

    register_post_type('story', $args);
}
add_action('init', 'twentytwentyfivechild_register_story_post_type');

/**
 * Meta Box für Story-Einstellungen hinzufügen
 */
function twentytwentyfivechild_add_story_meta_boxes() {
    add_meta_box(
        'story_settings',
        __('Story Settings', 'twentytwentyfivechild'),
        'twentytwentyfivechild_story_settings_callback',
        'story',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'twentytwentyfivechild_add_story_meta_boxes');

/**
 * Meta Box Callback
 */
function twentytwentyfivechild_story_settings_callback($post) {
    wp_nonce_field('story_settings_nonce', 'story_settings_nonce');
    
    $expiry_date = get_post_meta($post->ID, '_story_expiry_date', true);
    $duration = get_post_meta($post->ID, '_story_duration', true);
    $content_warning = get_post_meta($post->ID, '_story_content_warning', true);
    
    ?>
    <p>
        <label for="story_expiry_date"><?php _e('Expiry Date:', 'twentytwentyfivechild'); ?></label>
        <input type="datetime-local" id="story_expiry_date" name="story_expiry_date" value="<?php echo esc_attr($expiry_date); ?>">
    </p>
    <p>
        <label for="story_duration"><?php _e('Display Duration (in seconds):', 'twentytwentyfivechild'); ?></label>
        <input type="number" id="story_duration" name="story_duration" value="<?php echo esc_attr($duration); ?>" min="1" max="60">
    </p>
    <p>
        <label for="story_content_warning"><?php _e('Content Warning:', 'twentytwentyfivechild'); ?></label>
        <input type="text" id="story_content_warning" name="story_content_warning" value="<?php echo esc_attr($content_warning); ?>" placeholder="Optional warning message before displaying content">
    </p>
    <?php
}

/**
 * Meta Box Daten speichern
 */
function twentytwentyfivechild_save_story_meta($post_id) {
    if (!isset($_POST['story_settings_nonce'])) {
        return;
    }

    if (!wp_verify_nonce($_POST['story_settings_nonce'], 'story_settings_nonce')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (isset($_POST['story_expiry_date'])) {
        update_post_meta($post_id, '_story_expiry_date', sanitize_text_field($_POST['story_expiry_date']));
    }

    if (isset($_POST['story_duration'])) {
        update_post_meta($post_id, '_story_duration', absint($_POST['story_duration']));
    }

    if (isset($_POST['story_content_warning'])) {
        update_post_meta($post_id, '_story_content_warning', sanitize_text_field($_POST['story_content_warning']));
    }
}
add_action('save_post_story', 'twentytwentyfivechild_save_story_meta');

/**
 * REST API Endpoint für Stories
 */
function twentytwentyfivechild_register_story_rest_route() {
    register_rest_route('twentytwentyfivechild/v1', '/stories', array(
        'methods' => 'GET',
        'callback' => 'twentytwentyfivechild_get_stories_feed',
        'permission_callback' => '__return_true'
    ));
}
add_action('rest_api_init', 'twentytwentyfivechild_register_story_rest_route');

/**
 * Stories Feed im OpenStories Format zurückgeben
 */
function twentytwentyfivechild_get_stories_feed() {
    $args = array(
        'post_type' => 'story',
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
    );

    $stories = get_posts($args);
    $feed_items = array();

    foreach ($stories as $story) {
        $media = get_attached_media('', $story->ID);
        if (empty($media)) {
            continue;
        }

        $media_item = reset($media);
        $mime_type = $media_item->post_mime_type;
        $media_url = wp_get_attachment_url($media_item->ID);
        
        $expiry_date = get_post_meta($story->ID, '_story_expiry_date', true);
        if ($expiry_date && strtotime($expiry_date) < time()) {
            continue;
        }

        $item = array(
            'id' => (string)$story->ID,
            'content_text' => wp_strip_all_tags($story->post_content),
            'authors' => array(
                array(
                    'name' => get_the_author_meta('display_name', $story->post_author),
                    'url' => get_author_posts_url($story->post_author)
                )
            ),
            '_open_stories' => array(
                'mime_type' => $mime_type,
                'url' => $media_url,
                'alt' => get_post_meta($media_item->ID, '_wp_attachment_image_alt', true),
                'date_expired' => $expiry_date,
                'duration_in_seconds' => (int)get_post_meta($story->ID, '_story_duration', true),
                'content_warning' => get_post_meta($story->ID, '_story_content_warning', true)
            )
        );

        if (strpos($mime_type, 'video/') === 0) {
            $item['_open_stories']['title'] = $story->post_title;
        }

        $feed_items[] = $item;
    }

    $feed = array(
        'version' => 'https://jsonfeed.org/version/1.1',
        'title' => get_bloginfo('name') . ' Stories',
        '_open_stories' => array(
            'version' => '0.0.9'
        ),
        'feed_url' => get_rest_url(null, 'twentytwentyfivechild/v1/stories'),
        'items' => $feed_items
    );

    return $feed;
}