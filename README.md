# TwentyTwentyFive Child Theme — Plugin Replacements

This child theme replaces the following WordPress plugins with native theme modules or blocks:

## Replaced Plugins

1. **WP Dark Mode**
   - Replaced with a native dark mode toggle (CSS/JS) in the theme footer. User preference is saved and applied automatically.

2. **Flexible Spacer Block**
   - Replaced with a custom Gutenberg block (`Flexible Spacer`) for adjustable vertical spacing in the editor and frontend.

3. **Head, Footer and Post Injections**
   - Replaced for the Mastodon journalism feature: Customizer field for `fediverse:creator` meta tag output in `<head>`.

4. **WordPress Popular Posts**
   - Replaced with a custom Gutenberg block (`Popular Posts`) that displays the most viewed posts, tracked via post meta.

5. **Image Placeholders**
   - Replaced with a filter that outputs a default placeholder image for posts without a featured image.

6. **Integrate Umami**
   - Replaced with a Customizer field for the Umami analytics script URL, output in the site footer.

7. **Speculative Loading**
   - Replaced with a Customizer textarea for resource hints (preload/prefetch), output as `<link>` tags in `<head>`.

8. **Visual Link Preview**
   - Replaced with a custom Gutenberg block (`Visual Link Preview`) that uses oEmbed to show rich previews for URLs.

---

Each replacement is implemented as a module in the `inc/` directory or as a custom block in the `blocks/` directory. See the source code for details and configuration options.

## Block Build Instructions

To use the custom Gutenberg blocks, you must build the block assets:

1. Install dependencies (run in your theme directory):
   ```
   npm install
   ```
2. Build the blocks:
   ```
   npm run build
   ```

This will generate the necessary JS/CSS for the blocks to appear in the editor.

## GitHub / Development

- Ensure Node 18+ is installed.
- Install deps: `npm install`
- Build blocks: `npm run build`
- Create distributable: `npm run package` or `npm run dist`
- The distributable theme lives in `dist/twentytwentyfive-child/` and `dist/twentytwentyfive-child.zip`.

A `.gitignore` is included to exclude `node_modules/`, `dist/`, archives, and editor junk files.
