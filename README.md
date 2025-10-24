# TwentyTwentyFive Child Theme — Plugin Replacements (Current status)

This child theme implements several features as native theme modules and custom Gutenberg blocks, replacing the need for a few third-party plugins. The repository contains both source code for the blocks and the compiled build outputs used by the theme.

## What this project replaces

- Head, Footer and Post Injections: partial replacement for the Mastodon journalism feature — a Customizer field is used to output the `fediverse:creator` meta tag in the site head.
- WordPress Popular Posts: replaced with a custom `Popular Posts` block. Note: this is a curated popular list — editors pick which posts to show using a post selector in the block inspector. The block is server-rendered (PHP) and outputs a styled card with a header (emoji + title) and a list of the selected posts in the chosen order. It currently does not automatically calculate popularity from view counts or post meta (that would be a straightforward enhancement).
- Visual Link Preview: replaced with a `Visual Link Preview` block that performs a server-side fetch of a URL, extracts Open Graph / Twitter meta tags (title, description, image) via DOM parsing, caches the result in a transient (12h), and renders a rich link card. If remote fetching fails, the block falls back to oEmbed or a simple link. The fetch uses `wp_safe_remote_get()` with sensible timeouts and a custom user-agent.

## Current status

- Source for custom blocks and editor assets lives under `blocks/` (for example `blocks/popular-posts/` and `blocks/visual-link-preview/`) and additional sources under `stories/src/`.
- Each block includes a `block.json`, editor script (`index.js`), editor styles and a server-side renderer (`render.php`). Both `Popular Posts` and `Visual Link Preview` are server-rendered blocks: the PHP `render.php` files return render callbacks used by WordPress to produce the front-end HTML.
- `Popular Posts` editor UX: the block uses Combobox selectors in the inspector to let editors pick posts. The block preview shows the card with selected post titles; the front-end output queries the selected posts via `WP_Query` and renders them in the order chosen.
- `Visual Link Preview` behavior: the block sends the URL to the server renderer which fetches the remote HTML, parses OG/Twitter tags using DOMDocument + DOMXPath, normalizes image URLs, stores a transient keyed by the URL (`child_vlp_{md5}`) for ~12 hours, and renders a link card. If fetching fails or the remote page does not return usable metadata, the block will attempt `wp_oembed_get()` or fallback to a plain anchor.
- Compiled block assets and shipping-ready files are written to the `build/` directory (one subfolder per block). The `dist/` folder and a zip archive are produced by the packaging scripts (`npm run dist`).
- PHP integration files and helpers are located in the project root and `inc/` (for example: `functions.php`, `inc/popular-posts.php`, `inc/visual-link-preview.php`, `inc/head-footer-injections.php`).
- Testing so far is manual/visual. The blocks are included and render correctly when built, but please run the build steps below after editing to regenerate `build/` assets before packaging.

## Quick build & package (recommended)

Prerequisites:

- Node.js 20+ (recommended)
- npm (bundled with Node.js) or an equivalent package manager

Typical workflow (run from the theme root `/Users/niklasbarning/Code/2025child`):

1. Install dependencies:

```bash
npm install
```

2. Build block assets for development:

```bash
npm run build
```

3. Create a distributable package (build + zip):

```bash
npm run package
# or to run the combined production pipeline:
npm run dist
```

Output:

- Built block assets and compiled JS/CSS are written to `build/` (one subfolder per block). These are the files the theme actually enqueues.
- `npm run dist` (project packaging) will create a `dist/twentytwentyfive-child/` folder and an archive suitable for distribution.

## Project layout (high level)

- `blocks/` — block source code and editor assets used during development.
- `visual-link-preview/`, `popular-posts/`, `stories/` — block-specific source and build folders.
- `build/` — compiled block assets (ready-to-ship).
- `dist/` — packaging output (created by `npm run dist`).
- `inc/` — PHP includes for server-side rendering and integration.
- `functions.php`, `render.php` files — theme bootstrap and per-block renderers where applicable.
- `assets/` — shared CSS/JS assets used by the theme and blocks.

## Notes and next steps

- If you modify block source code under `blocks/` or `stories/src/`, run `npm run build` to regenerate the compiled files in `build/`.
- Packaging uses the `build/` assets; make sure those are up to date before creating a distributable.
- Tests are currently manual/visual. Adding automated unit or integration tests for the build pipeline and PHP renderers would be a useful next improvement.

If you'd like, I can:

1. Commit this updated README for you.
2. Add a short CONTRIBUTING.md section or a small build-check script to verify artifacts before packaging.

---

Last updated: October 2025
