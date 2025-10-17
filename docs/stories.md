# Stories Feature Documentation

The Stories feature adds Instagram-style stories to your WordPress theme. It supports both images and videos in a 9:16 format, with features like content warnings, expiration dates, and touch/keyboard navigation.

## Features

- Support for images and videos in 9:16 format
- Story duration and expiration settings
- Content warnings
- Touch and keyboard navigation
- Progress indicators
- OpenStories-compatible REST API endpoint
- Responsive design
- Gutenberg block for easy integration

## Post Type: Story

The feature adds a new custom post type 'story' with the following meta fields:

- `_story_expiry_date`: DateTime when the story should expire
- `_story_duration`: Display duration in seconds
- `_story_content_warning`: Optional warning message

## REST API Endpoint

The Stories feature provides a REST API endpoint that follows the [OpenStories format](https://openstories.fyi/):

```
GET /wp-json/twentytwentyfivechild/v1/stories
```

Example response:
```json
{
  "version": "https://jsonfeed.org/version/1.1",
  "title": "Site Name Stories",
  "_open_stories": {
    "version": "0.0.9"
  },
  "feed_url": "https://example.com/wp-json/twentytwentyfivechild/v1/stories",
  "items": [
    {
      "id": "123",
      "content_text": "Story description",
      "authors": [{
        "name": "Author Name",
        "url": "https://example.com/author"
      }],
      "_open_stories": {
        "mime_type": "image/jpeg",
        "url": "https://example.com/story.jpg",
        "alt": "Image description",
        "date_expired": "2025-10-24T00:00:00Z",
        "duration_in_seconds": 5
      }
    }
  ]
}
```

## Block Usage

1. Add the Stories block to any page or post
2. Create stories through the Stories post type in the admin area
3. Upload media (images/videos) in 9:16 format
4. Set expiration date and duration as needed

## Navigation

- Swipe left/right on touch devices
- Use arrow keys (←/→) on desktop
- ESC key to close
- Click navigation buttons or close button

## Development

### File Structure

```
blocks/
  stories/
    block.json       # Block configuration
    index.js         # Block editor component
    render.php       # Frontend rendering
    style.css        # Frontend styles
    editor.css       # Editor styles
    viewer.js        # Story viewer logic
inc/
  stories.php        # Post type and REST API implementation
```

### Build Process

1. Install dependencies:
```bash
npm install
```

2. Build the block:
```bash
npm run build
```

### Adding Features

To extend the Stories feature:

1. Add new meta fields in `inc/stories.php`
2. Update the REST API response in `twentytwentyfivechild_get_stories_feed()`
3. Modify the viewer UI in `blocks/stories/render.php`
4. Add corresponding styles in `style.css`
5. Update viewer logic in `viewer.js`

## Credits

This implementation is inspired by the [OpenStories specification](https://github.com/dddddddddzzzz/OpenStories)