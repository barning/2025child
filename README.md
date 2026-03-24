# TwentyTwentyFive Child Theme

A production-focused child theme for **Twenty Twenty-Five** that keeps customization logic modular, minimizes parent-theme duplication, and uses WordPress hooks/filters over template overrides.

## Refactored Structure

```text
.
├── assets/                      # Reserved for custom static assets.
├── blocks/                      # Block source (editor JS/CSS + render callbacks).
├── build/                       # Compiled block assets consumed by register_block_type().
├── inc/
│   ├── blocks.php               # Central dynamic block registration + shared style handling.
│   ├── bootstrap.php            # Child theme bootstrapping + module autoload.
│   ├── head-footer-injections.php
│   ├── media-recommendation.php
│   ├── notes.php
│   ├── videogame-recommendation.php
│   └── visual-link-preview-async.php
├── functions.php                # Thin entrypoint (version constant + bootstrap include).
└── style.css                    # Child metadata + minimal theme-level styles.
```

## Architectural Decisions

- **Single block registry**: all dynamic blocks are registered in one place (`inc/blocks.php`) to eliminate repeated boilerplate.
- **Hook-based behavior**: settings pages, AJAX endpoints, and front-end enhancements are implemented with core actions/filters.
- **Minimal `functions.php`**: startup logic is delegated to `inc/bootstrap.php` for maintainability.
- **No unnecessary template overrides**: the child theme favors extensibility via block render callbacks and hooks.

## Dynamic Blocks Registered

- `child/activity-embed`
- `child/book-rating`
- `child/magic-cards`
- `child/media-recommendation`
- `child/pixelfed-feed`
- `child/popular-posts`
- `child/videogame-recommendation`
- `child/visual-link-preview`

Each block:
- registers from `build/<slug>`
- uses server-side render callback from `blocks/<slug>/render.php`
- receives block style enqueueing plus a global style fallback for compatibility

## API-backed Features

### Media Recommendation (TMDB)
- Admin settings page under **Settings → Media Recommendation**.
- API key lookup order: option `child_tmdb_api_key`, then `TMDB_API_KEY` constant fallback.
- Editor AJAX endpoint: `wp_ajax_child_tmdb_search`.

### Videogame Recommendation (RAWG)
- Admin settings page under **Settings → Videogame Recommendation**.
- API key lookup order: option `child_rawg_api_key`, then `RAWG_API_KEY` constant fallback.
- Editor AJAX endpoint: `wp_ajax_child_rawg_search`.

## Notes Custom Post Type

`inc/notes.php` provides a `note` post type for short-form posts, with:
- generated internal titles for title-less notes,
- hidden `core/post-title` output on front end for notes,
- guarded rewrite flush logic for reliable `/notes` routing.

## Development

```bash
npm install
npm run build
```

To create a distribution package:

```bash
npm run dist
```

## Compatibility & Maintenance

- Keeps child-theme overrides intentionally minimal.
- Uses consistent prefixed function names (`child_*`) to avoid collisions.
- Consolidates duplicated registration/enqueue logic to simplify future parent-theme updates.


## Activity Embed Block

`Activity Embed` (`child/activity-embed`) accepts one URL attribute and supports:

- `https://connect.garmin.com/`
- `https://www.strava.com/`

It validates the URL server-side, detects the provider, and renders either oEmbed (for Strava when available) or a provider iframe fallback.

Example block markup in post content:

```html
<!-- wp:child/activity-embed {"url":"https://www.strava.com/activities/1234567890"} /-->
```
