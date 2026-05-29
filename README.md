# TwentyTwentyFive Child Theme

A production-focused child theme for **Twenty Twenty-Five** that keeps customization logic modular, minimizes parent-theme duplication, and prefers WordPress hooks/filters over template overrides.

## Project Structure

```text
.
├── assets/                      # Reserved for custom static assets.
├── blocks/                      # Block source files (editor JS/CSS + render callbacks).
├── build/                       # Compiled block assets used by register_block_type().
├── releases/                    # Versioned release notes.
├── inc/
│   ├── blocks.php               # Central dynamic block registration + shared styles.
│   ├── bootstrap.php            # Child theme bootstrapping + module autoload.
│   ├── book-rating.php
│   ├── head-footer-injections.php
│   ├── human-json.php
│   ├── media-recommendation.php
│   ├── notes.php
│   ├── post-likes.php
│   ├── rss-feed-footer.php
│   ├── videogame-recommendation.php
│   └── visual-link-preview-async.php
├── functions.php                # Thin entrypoint (version constant + bootstrap include).
├── package.json                 # Build, watch, and distribution scripts.
└── style.css                    # Child theme metadata + minimal global styles.
```

## Architectural Decisions

- **Single block registry:** Dynamic blocks are registered in one place (`inc/blocks.php`) to reduce repeated boilerplate.
- **Hook-based behavior:** Settings pages, AJAX endpoints, and front-end enhancements use core WordPress actions/filters.
- **Minimal `functions.php`:** Startup logic is delegated to `inc/bootstrap.php` for maintainability.
- **Minimal template overrides:** The child theme favors extensibility through block render callbacks and hooks.
- **README as project guide:** High-level project documentation now lives here, while release-specific details live in `releases/`.

## Dynamic Blocks Registered

- `child/book-rating`
- `child/magic-cards`
- `child/media-recommendation`
- `child/pixelfed-feed`
- `child/popular-posts`
- `child/post-likes`
- `child/videogame-recommendation`
- `child/visual-link-preview`

Each block:
- registers from `build/<slug>`
- uses a server-side render callback from `blocks/<slug>/render.php` (where applicable)
- receives block style enqueueing plus a global style fallback for compatibility

## API-Backed Features

### Media Recommendation (TMDB)

- Admin settings page under **Settings → Media Recommendation**.
- API key lookup order: option `child_tmdb_api_key`, then `TMDB_API_KEY` constant fallback.
- Editor AJAX endpoint: `wp_ajax_child_tmdb_search`.

### Videogame Recommendation (RAWG)

- Admin settings page under **Settings → Videogame Recommendation**.
- API key lookup order: option `child_rawg_api_key`, then `RAWG_API_KEY` constant fallback.
- Editor AJAX endpoint: `wp_ajax_child_rawg_search`.

## RSS Feed Footer

`inc/rss-feed-footer.php` appends lightweight links to feed-only post content so RSS readers include a route back to the original post and a mail reply option.
Posts can include a custom RSS footer message via a post editor metabox.

## Notes Custom Post Type

`inc/notes.php` provides a `note` post type for short-form posts, with:

- generated internal titles for title-less notes
- hidden `core/post-title` output on the front end for notes
- guarded rewrite flush logic for reliable `/notes` routing

## Development

Install dependencies and build compiled block assets:

```bash
npm install
npm run build
```

Watch block sources during development:

```bash
npm run start
```

Create a distribution package with a fresh build:

```bash
npm run dist
```

Release notes are tracked in `releases/` instead of a running devlog.

## Compatibility and Maintenance

- Keeps child-theme overrides intentionally minimal.
- Uses consistent prefixed function names (`child_*`) to avoid collisions.
- Consolidates duplicated registration/enqueue logic to simplify future parent-theme updates.
