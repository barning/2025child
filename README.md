# TwentyTwentyFive Child Theme — Plugin Replacements (Current status)

This child theme implements several features as native theme modules and custom Gutenberg blocks, replacing the need for a few third-party plugins. The repository contains both source code for the blocks and the compiled build outputs used by the theme.

# TwentyTwentyFive Child Theme — Overview

This child theme extends a block-based parent theme with a set of small, maintainable theme modules and custom Gutenberg blocks. It replaces certain plugin functionality with lightweight, theme-integrated code.

## Summary

- Child stylesheet is enqueued from `functions.php`.
- Modular structure: PHP modules live in `inc/` and are autoloaded.
- Block source is in `blocks/`; compiled assets live in `build/`.

## Included Blocks & Modules

- **Book Rating**
	- Location: `inc/book-rating.php`
	- Registration: `build/book-rating` (block assets) and server-side renderer at `blocks/book-rating/render.php`.
	- Purpose: Display a book rating card (cover, title, author, 0–5 stars). Rendering is done server-side in PHP.

- **Video Game**
	- Location: `inc/video-game.php`
	- Registration: `build/video-game` (block assets) and server-side renderer at `blocks/video-game/render.php`.
	- Purpose: Display a video game card (cover, title) with data from IGDB (Internet Game Database). Features a search interface in the editor and an ambilight-style drop shadow that adapts to the game cover colors. Uses mock data by default; configure IGDB API credentials via Customizer for production use.

- **Popular Posts**
	- Location: `inc/popular-posts.php`
	- Purpose: Provides a Gutenberg block to render a curated list of posts selected by editors.
- **Visual Link Preview**
	- Location: `inc/visual-link-preview.php` (block) and `inc/visual-link-preview-async.php` (background fetch)
	- Behavior: Fetches metadata (OG/Twitter) using `wp_safe_remote_get()`, parses HTML with `DOMDocument`/`DOMXPath`, normalizes image URLs, and caches results in a transient (`child_vlp_<md5(url)>`) for approximately 24 hours.

- **Head/Footer Injections (Fediverse)**
	- Location: `inc/head-footer-injections.php`
	- Purpose: Adds a Customizer setting `fediverse_creator_handle` and outputs the `<meta name="fediverse:creator">` tag in the page head when set.

## Files & Folders (quick)

- `blocks/` — block source (editor scripts, `block.json`, `render.php` where server-side rendering is required)
- `build/` — compiled, shipped block assets (JS/CSS)
- `inc/` — PHP modules registering blocks, render callbacks and helpers
- `functions.php` — bootstraps the theme (loads `inc/*.php`, enqueues child stylesheet)

## Development & Build

Prerequisites (recommended): Node.js, npm

From the theme root run:

```bash
npm install
npm run build
```

To create a distributable package:

```bash
npm run dist
```

Note: After changes to `blocks/` always run `npm run build` to update the `build/` assets — the theme registers assets from that folder.

## Developer Notes

- Styles: Each module registers `style-index.css` as a block style and also enqueues it globally as a frontend fallback when present.
- Popular Posts: The block renders a curated list chosen by editors.
- Visual Link Preview: Background fetch endpoint is `admin_post_child_vlp_fetch` (also available via `admin_post_nopriv_child_vlp_fetch`) and is responsible for fetch/parse/cache.

## Performance Optimizations

The theme includes several performance optimizations to minimize filesystem I/O operations:

- **Cached Module Loading**: `functions.php` caches the result of `glob()` when loading modules from `inc/` to avoid repeated filesystem scans on every page load.
- **Static CSS Caching**: All block modules (book-rating, popular-posts, video-game, visual-link-preview) use static variables to cache CSS file existence and modification time checks, reducing redundant `file_exists()` and `filemtime()` calls.
- **Clean Code**: Removed unreachable dead code from the Visual Link Preview render callback to improve code maintainability and execution efficiency.

These optimizations are transparent to users and require no configuration.

## Quick FAQ

- Where do I set the Fediverse meta tag? → Customizer → "Fediverse Author" (setting `fediverse_creator_handle`).
- Where are server-side renderers? → `blocks/<block>/render.php` and the corresponding `inc/*.php` registration files.
- How do I configure IGDB API? → Go to Appearance → Customize → "IGDB API Settings" to enter your Twitch/IGDB Client ID and Client Secret. By default, the video game block uses mock data for development.
