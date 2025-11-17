Hero Teaser Block

Usage
- Insert the "Hero Teaser" block from the Widgets category in the Gutenberg editor.
- Choose a background image (Media Library) — the block stores the attachment ID and will output responsive srcset/sizes.
- Edit Subtitle, Title and Description using the inline RichText fields.
- Use the block settings to change layout (text-left, text-right, text-over) and set an overlay/accent color.

Attributes
- title (string, HTML) — main headline
- subtitle (string, HTML) — small intro or category label
- description (string, HTML) — short summary
- imageId (number) — attachment ID (preferred)
- imageUrl (string) — fallback image URL
- imageAlt (string) — alt text for accessibility
- accentColor (string) — CSS color or RGBA for overlay gradient
- layout (string) — 'text-left'|'text-right'|'text-over'

Notes & testing
- The block is server-rendered; make sure the theme has the `build/hero-teaser` files present (generated via `npm run dist`).
- Image `srcset` and `sizes` are generated via WordPress attachment helper functions when `imageId` is used.
- Lazy loading is enabled for improved performance.

Quick local checks
1. Run `npm run dist` from project root to generate `build/` and `dist/`.
2. Activate the theme in WP Admin (or upload the `dist/twentytwentyfive-child.zip`).
3. Open the Block Editor and add the block (Widgets → Hero Teaser). If you don't see it, clear browser cache and reload.

Accessibility
- Text remains selectable HTML; avoid putting essential text into the image itself.
- Use sufficient contrast for `accentColor`. Consider using contrast checkers for final color choices.

Options to extend
- Add fade-in animation (with `prefers-reduced-motion` support).
- Add a contrast validation inside the editor to warn if accentColor results in low contrast.
