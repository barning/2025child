# WordPress Playground testing

This child theme can be tested with WordPress Playground, but use a packaged theme ZIP rather than loading the raw repository directly.

## Why a ZIP is required

The theme registers blocks from the generated `build/` directory, while `build/` is intentionally ignored in git. The browser version at <https://playground.wordpress.net> can install a theme from a ZIP, URL, WordPress.org slug, or Git directory, but it does **not** run this repository's `npm run build` step before activating the theme. A raw GitHub directory install will activate the PHP/theme files but will not provide the generated block assets.

## Browser smoke test with playground.wordpress.net

1. Build and package the theme locally:

   ```sh
   npm run dist
   ```

2. Open <https://playground.wordpress.net>.
3. In the Playground WordPress admin, go to **Appearance → Themes → Add New Theme → Upload Theme**.
4. Upload `dist/twentytwentyfive-child.zip`.
5. Activate **TwentyTwentyFive Child**.
6. Create or edit a post/page and add the custom blocks to smoke-test editor previews and frontend rendering.

## Shareable release blueprint

`playground/blueprint.json` is intended for release builds where the packaged ZIP is available at:

```text
https://github.com/barning/2025child/releases/latest/download/twentytwentyfive-child.zip
```

Once that asset exists on the latest GitHub release, open:

```text
https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/barning/2025child/main/playground/blueprint.json
```

The blueprint installs the Twenty Twenty-Five parent theme first, then installs and activates the packaged child theme ZIP.

## Local Playground development option

For local iteration, WordPress Playground's CLI can mount a theme directory from disk. Build assets first, then start the Playground server from this repository root:

```sh
npm run build
npx @wp-playground/cli server --auto-mount
```

This is useful for quick manual checks, but the browser-only `playground.wordpress.net` flow still needs a ZIP or hosted built artifact for full block testing.
