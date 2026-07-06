# TwentyTwentyFive Child Theme

A production-focused child theme for **Twenty Twenty-Five** that keeps customization logic modular, minimizes parent-theme duplication, and prefers WordPress hooks/filters over template overrides.

## Project Structure

```text
.
├── assets/                      # Reserved for custom static assets.
├── blocks/                      # Block source files (editor JS/CSS + render callbacks).
│   └── shared/                  # Shared block-editor utilities used by multiple blocks.
├── build/                       # Compiled block assets used by register_block_type().
├── releases/                    # Versioned release notes.
├── inc/
│   ├── blocks.php               # Central dynamic block registration + shared styles.
│   ├── bootstrap.php            # Child theme bootstrapping + module autoload.
│   ├── book-rating.php
│   ├── head-footer-injections.php
│   ├── human-json.php
│   ├── media-cover-grid.php
│   ├── media-cover-grid-dedupe.php
│   ├── media-cover-grid-normalizers.php
│   ├── media-recommendation.php
│   ├── music-recommendation.php
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
- `child/media-cover-grid`
- `child/media-recommendation`
- `child/music-recommendation`
- `child/pixelfed-feed`
- `child/popular-posts`
- `child/post-likes`
- `child/videogame-recommendation`
- `child/visual-link-preview`

Each block:
- registers from `build/<slug>`
- uses a server-side render callback from `blocks/<slug>/render.php` (where applicable)
- receives block style enqueueing plus a global style fallback for compatibility


### Dynamic Block Feature Structure

When adding or changing a dynamic block, mirror the existing examples in `blocks/post-likes`, `blocks/media-cover-grid`, and `blocks/media-recommendation`:

- Put block-owned assets under `blocks/{slug}/`. A feature should include `block.json` and `index.js`, then add `render.php` for dynamic output, `style.css` for front-end styles, `editor.css` for editor-only styles, and `view.js` only when the front end needs interactive behavior.
- Put shared editor-only helpers under `blocks/shared/` when multiple block editors need the same state or UI primitive. Current shared media helpers cover search state, search feedback, and reusable search-result row rendering.
- Put server-side helper modules in `inc/{slug}.php`. Keep `blocks/{slug}/render.php` focused on rendering sanitized markup and delegate reusable queries, REST/AJAX handlers, settings, cache helpers, and data normalization to the matching `inc/` file.
- Expose render-time behavior through stable, public `child_*` functions instead of anonymous helper logic that other blocks cannot reuse. For example, `blocks/media-cover-grid/render.php` calls the public helpers from `inc/media-cover-grid.php`, while `blocks/post-likes/render.php` reads counts through the post-likes helper API.
- Localize editor data from `inc/blocks.php` with `child_localize_block_editor_script( $block_name, $object_name, $data )` on `enqueue_block_editor_assets`, as `inc/media-recommendation.php` does for the Media Recommendation editor AJAX URL and nonce.
- Name cache keys with a `child_{slug}_{purpose}_v{n}` convention, define them as constants in the owning `inc/{slug}.php`, and bump the version when the cached shape changes. Invalidate through explicit `child_flush_{slug}_cache()` helpers on all relevant write hooks, following `inc/media-cover-grid.php`; modules with write paths such as `inc/post-likes.php` should clear any future cached counts or aggregates immediately after inserts/deletes so REST responses and render callbacks stay consistent.

## API-Backed Features

### Media Recommendation Blocks

The recommendation blocks keep their saved attribute schemas independent, but share editor primitives where possible:

- `blocks/shared/media/useSearchState.js` centralizes search term, loading, result, selection, and error state.
- `blocks/shared/media/SearchFeedback.js` centralizes loading/error output.
- `blocks/shared/media/SearchResultsList.js` centralizes result row button rendering while each block keeps its own result markup and CSS classes.
- Provider-specific search and mapping stays in each feature block so Books, TMDB, Apple/iTunes, and RAWG can evolve independently.

### Media Cover Grid

`child/media-cover-grid` auto-builds a cover grid from published recommendation blocks. It uses:

- `inc/media-cover-grid.php` for post scanning, caching, and shared grid helpers.
- `inc/media-cover-grid-normalizers.php` to normalize book, movie/series, videogame, and music block attributes into a shared media item contract.
- `inc/media-cover-grid-dedupe.php` to merge duplicate recommendations while preserving source-post metadata.
- `blocks/media-cover-grid/view.js` for front-end filtering. The default filter state is **Alle**, individual media type filters hide empty year sections automatically, and year separators are rendered from each item’s source post year.

The grid intentionally keeps the recommendation block schemas unchanged and treats the grid item shape as a derived view model.

### Book Recommendation (Google Books)

- Admin settings page under **Settings → Book Rating**.
- API key lookup order: option `child_google_books_api_key`, then `CHILD_GOOGLE_BOOKS_API_KEY` constant fallback.
- Editor REST endpoint: `/child/v1/books`.

### Media Recommendation (TMDB)

- Admin settings page under **Settings → Media Recommendation**.
- API key lookup order: option `child_tmdb_api_key`, then `TMDB_API_KEY` constant fallback.
- Editor AJAX endpoint: `wp_ajax_child_tmdb_search`.

### Music Recommendation (Apple/iTunes)

- Editor REST endpoint: `/child/v1/music`.
- Supports songs and albums while only loading audio previews on explicit front-end interaction.
- Keeps legal/privacy messaging in the block editor.

### Videogame Recommendation (RAWG)

- Admin settings page under **Settings → Videogame Recommendation**.
- API key lookup order: option `child_rawg_api_key`, then `RAWG_API_KEY` constant fallback.
- Editor AJAX endpoint: `wp_ajax_child_rawg_search`.
- Platform chip display helpers live in `blocks/videogame-recommendation/utils.php` for PHP rendering.

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
