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
 * Register the Stories block assets.
 */
function twentytwentyfivechild_register_stories_block() {
    register_block_type(
        get_stylesheet_directory() . '/build/stories'
    );
}
add_action('init', 'twentytwentyfivechild_register_stories_block');

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
        <input type="number" id="story_duration" name="story_duration" value="<?php echo esc_attr($duration); ?>" min="1" max="60" step="1">
    </p>
    <p>
        <label for="story_content_warning"><?php _e('Content Warning:', 'twentytwentyfivechild'); ?></label>
        <input type="text" id="story_content_warning" name="story_content_warning" value="<?php echo esc_attr($content_warning); ?>" placeholder="<?php echo esc_attr__('Optional warning message before displaying content', 'twentytwentyfivechild'); ?>">
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
        $duration = min(60, max(1, absint($_POST['story_duration'])));
        update_post_meta($post_id, '_story_duration', $duration);
    }

    if (isset($_POST['story_content_warning'])) {
        update_post_meta($post_id, '_story_content_warning', sanitize_text_field($_POST['story_content_warning']));
    }
}
add_action('save_post_story', 'twentytwentyfivechild_save_story_meta');

/**
 * Determine whether the provided story has expired.
 */
function twentytwentyfivechild_story_is_expired(int $story_id): bool {
    $expiry = get_post_meta($story_id, '_story_expiry_date', true);
    if (empty($expiry)) {
        return false;
    }

    $timestamp = strtotime($expiry);

    return $timestamp ? $timestamp <= time() : false;
}

/**
 * Return the primary media attachment for a story.
 * Prefers attached media but falls back to the featured image.
 */
function twentytwentyfivechild_get_story_media_post(\WP_Post $story): ?\WP_Post {
    $media_items = get_attached_media('', $story->ID);
    if (!empty($media_items)) {
        $media_items = wp_list_sort($media_items, 'menu_order');
        foreach ($media_items as $media_item) {
            $mime_type = get_post_mime_type($media_item) ?: $media_item->post_mime_type;
            if (is_string($mime_type) && (strpos($mime_type, 'image/') === 0 || strpos($mime_type, 'video/') === 0)) {
                return $media_item;
            }
        }
    }

    $thumbnail_id = get_post_thumbnail_id($story);
    if ($thumbnail_id) {
        $thumbnail = get_post($thumbnail_id);
        if ($thumbnail instanceof \WP_Post) {
            return $thumbnail;
        }
    }

    return null;
}

/**
 * Convert stored expiry to ISO8601 string.
 */
function twentytwentyfivechild_normalize_expiry(?string $expiry): ?string {
    if (empty($expiry)) {
        return null;
    }

    $timestamp = strtotime($expiry);

    return $timestamp ? gmdate('c', $timestamp) : null;
}

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
    $stories = get_posts(
        [
            'post_type'      => 'story',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'no_found_rows'  => true,
        ]
    );

    $items = [];

    foreach ($stories as $story) {
        if (!($story instanceof \WP_Post)) {
            continue;
        }

        if (twentytwentyfivechild_story_is_expired($story->ID)) {
            continue;
        }

        $media_post = twentytwentyfivechild_get_story_media_post($story);
        if (!($media_post instanceof \WP_Post)) {
            continue;
        }

        $mime_type = get_post_mime_type($media_post) ?: $media_post->post_mime_type;
        if (!is_string($mime_type) || (strpos($mime_type, 'image/') !== 0 && strpos($mime_type, 'video/') !== 0)) {
            continue;
        }

        $media_url = wp_get_attachment_url($media_post->ID);
        if (!$media_url) {
            continue;
        }

        $duration = (int) get_post_meta($story->ID, '_story_duration', true);
        if ($duration <= 0) {
            $duration = 5;
        }

        $content_warning = trim((string) get_post_meta($story->ID, '_story_content_warning', true));
        $expiry_iso = twentytwentyfivechild_normalize_expiry(get_post_meta($story->ID, '_story_expiry_date', true));

        $alt_text = get_post_meta($media_post->ID, '_wp_attachment_image_alt', true);
        if (!$alt_text) {
            $alt_text = get_the_title($story);
        }

        $poster = '';
        if (strpos($mime_type, 'video/') === 0) {
            $poster_id = get_post_thumbnail_id($story);
            if ($poster_id) {
                $poster = wp_get_attachment_image_url($poster_id, 'large') ?: '';
            }
        }

        $author_id = (int) $story->post_author;
        $author = [
            'name' => get_the_author_meta('display_name', $author_id) ?: get_bloginfo('name'),
        ];

        $author_url = get_author_posts_url($author_id);
        if ($author_url) {
            $author['url'] = $author_url;
        }

        $author_avatar = get_avatar_url($author_id, ['size' => 96]);
        if ($author_avatar) {
            $author['avatar'] = $author_avatar;
        }

        $item = [
            'id'             => (string) $story->ID,
            'title'          => get_the_title($story),
            'url'            => get_permalink($story),
            'content_text'   => wp_strip_all_tags($story->post_content),
            'date_published' => get_post_time('c', true, $story),
            'authors'        => [$author],
            '_open_stories'  => [
                'mime_type'           => $mime_type,
                'url'                 => $media_url,
                'alt'                 => $alt_text,
                'duration_in_seconds' => $duration,
            ],
        ];

        if (!empty($poster)) {
            $item['_open_stories']['poster'] = $poster;
        }

        if ($expiry_iso) {
            $item['_open_stories']['date_expired'] = $expiry_iso;
        }

        if ($content_warning !== '') {
            $item['_open_stories']['content_warning'] = $content_warning;
        }

        if (strpos($mime_type, 'video/') === 0) {
            $item['_open_stories']['title'] = get_the_title($story);
        }

        $items[] = $item;
    }

    $feed = [
        'version'        => 'https://jsonfeed.org/version/1.1',
        'title'          => get_bloginfo('name') . ' Stories',
        'home_page_url'  => home_url('/'),
        'feed_url'       => rest_url('twentytwentyfivechild/v1/stories'),
        'description'    => get_bloginfo('description'),
        'language'       => get_bloginfo('language'),
        'icon'           => get_site_icon_url(192) ?: null,
        'ttl'            => 300,
        '_open_stories'  => [
            'version' => '0.0.9',
        ],
        'items'          => array_values($items),
    ];

    if (empty($feed['description'])) {
        unset($feed['description']);
    }

    if (empty($feed['language'])) {
        unset($feed['language']);
    }

    if (empty($feed['icon'])) {
        unset($feed['icon']);
    }

    return rest_ensure_response($feed);
}
