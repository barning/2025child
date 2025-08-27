# TwentyTwentyFive Child Theme — Plugin Replacements

This child theme replaces the following WordPress plugins with native theme modules or blocks:

## Replaced Plugins

1. **Head, Footer and Post Injections**
   - Replaced for the Mastodon journalism feature: Customizer field for `fediverse:creator` meta tag output in `<head>`.

3. **WordPress Popular Posts**
   - Replaced with a custom Gutenberg block (`Popular Posts`) that displays the most viewed posts, tracked via post meta.

4. **Visual Link Preview**
   - Replaced with a custom Gutenberg block (`Visual Link Preview`) that fetches OG/Twitter metadata and renders a rich preview card.

---

## Block Build Instructions

To use the custom Gutenberg blocks, you must build the block assets:

1. Install dependencies (run in your theme directory):
   ```
   npm install
   ```
2. Build the blocks and package:
   ```
   npm run dist
   ```

The distributable theme will be generated in `dist/twentytwentyfive-child/` and as a zip.

## GitHub / Development

- Ensure Node 18+ is installed.
- Build: `npm run build`
- Package: `npm run package` (runs build automatically)
- Combined: `npm run dist`

A `.gitignore` is included to exclude `node_modules/`, `dist/`, archives, and editor junk files.
