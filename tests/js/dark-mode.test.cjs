const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');

const root = path.resolve(__dirname, '../..');

function read(relativePath) {
  return fs.readFileSync(path.join(root, relativePath), 'utf8');
}

function relativeLuminance(hex) {
  const channels = hex
    .match(/[a-f\d]{2}/gi)
    .map((channel) => Number.parseInt(channel, 16) / 255)
    .map((channel) =>
      channel <= 0.04045 ? channel / 12.92 : ((channel + 0.055) / 1.055) ** 2.4
    );

  return 0.2126 * channels[0] + 0.7152 * channels[1] + 0.0722 * channels[2];
}

function contrastRatio(foreground, background) {
  const foregroundLuminance = relativeLuminance(foreground);
  const backgroundLuminance = relativeLuminance(background);
  const lighter = Math.max(foregroundLuminance, backgroundLuminance);
  const darker = Math.min(foregroundLuminance, backgroundLuminance);

  return (lighter + 0.05) / (darker + 0.05);
}

test('theme.json preserves parent palette slugs with paired light and dark colors', () => {
  const theme = JSON.parse(read('theme.json'));
  const palette = Object.fromEntries(
    theme.settings.color.palette.map(({ slug, color }) => [slug, color])
  );

  assert.equal(theme.$schema, 'https://schemas.wp.org/wp/6.7/theme.json');
  assert.equal(theme.version, 3);
  assert.deepEqual(palette, {
    base: 'light-dark(#FFFFFF, #161513)',
    contrast: 'light-dark(#111111, #F5F2E8)',
    'accent-1': 'light-dark(#FFEE58, #433B13)',
    'accent-2': 'light-dark(#F6CFF4, #3B293A)',
    'accent-3': 'light-dark(#503AA8, #2E2852)',
    'accent-4': 'light-dark(#686868, #A9A59E)',
    'accent-5': 'light-dark(#FBFAF3, #24231F)',
    'accent-6': 'color-mix(in srgb, currentColor 20%, transparent)',
  });
});

test('global styles provide semantic tokens, legacy fallback, and section 4 correction', () => {
  const styles = read('style.css');

  assert.match(styles, /:root\s*{[\s\S]*?color-scheme:\s*light;/);
  assert.match(
    styles,
    /@supports\s*\(color:\s*light-dark\(#fff, #000\)\)[\s\S]*?color-scheme:\s*light dark;/
  );
  assert.match(styles, /@supports not\s*\(color:\s*light-dark\(#fff, #000\)\)/);

  for (const token of [
    '--child-color-card-surface',
    '--child-color-elevated-surface',
    '--child-color-control-surface',
    '--child-color-text-muted',
    '--child-color-text-subtle',
    '--child-color-link',
    '--child-color-link-hover',
    '--child-color-border-subtle',
    '--child-color-image-outline',
    '--child-color-section-4-text',
  ]) {
    assert.match(styles, new RegExp(token));
  }

  assert.match(styles, /\.is-style-section-4[\s\S]*?--child-color-section-4-text/);
  assert.match(styles, /\.is-style-section-4 \.wp-element-button/);
  assert.match(
    styles,
    /@media\s*\(prefers-color-scheme:\s*dark\)[\s\S]*?body\s*{[\s\S]*?--wp--preset--color--contrast/
  );
  assert.match(
    styles,
    /a:where\(:not\(\.wp-element-button\)\)[\s\S]*?--child-color-link/
  );
  assert.match(styles, /\.is-style-section-5 \.wp-element-button/);
});

test('custom block surfaces use the shared adaptive tokens', () => {
  const surfaceFiles = [
    'blocks/book-rating/style.css',
    'blocks/magic-cards/style.css',
    'blocks/media-cover-grid/style.css',
    'blocks/media-recommendation/style.css',
    'blocks/music-recommendation/style.css',
    'blocks/pixelfed-feed/style.css',
    'blocks/videogame-recommendation/style.css',
    'blocks/visual-link-preview/style.css',
  ];

  for (const relativePath of surfaceFiles) {
    const styles = read(relativePath);
    assert.match(styles, /--child-color-(?:card|control|elevated)-surface/, relativePath);
    assert.doesNotMatch(styles, /background(?:-color)?:\s*#(?:fff|ffffff|f5f5f5|dfe2e5)\b/i, relativePath);
  }

  const previewStyles = read('blocks/visual-link-preview/style.css');
  assert.match(previewStyles, /--child-color-text-muted/);
  assert.match(previewStyles, /--child-color-text-subtle/);
});

test('editor and browser metadata follow the active color scheme', () => {
  const editorStyles = read('assets/editor.css');
  const registration = read('inc/blocks.php');
  const metadata = read('inc/head-footer-injections.php');

  assert.match(editorStyles, /color-scheme:\s*light dark/);
  assert.match(registration, /child_enqueue_color_scheme_editor_styles/);
  assert.match(registration, /assets\/editor\.css/);
  assert.match(
    registration,
    /add_action\(\s*'enqueue_block_editor_assets',\s*'child_enqueue_color_scheme_editor_styles'/
  );
  assert.match(
    metadata,
    /<meta name="theme-color" content="#FFFFFF" media="\(prefers-color-scheme: light\)" \/>/
  );
  assert.match(
    metadata,
    /<meta name="theme-color" content="#161513" media="\(prefers-color-scheme: dark\)" \/>/
  );
  assert.match(
    metadata,
    /add_action\(\s*'wp_head',\s*'child_output_color_scheme_meta_tags',\s*1\s*\)/
  );
});

test('release packaging includes the color scheme definition and editor asset', () => {
  const packager = read('scripts/package.js');

  assert.match(packager, /path\.join\( root, 'theme\.json' \)/);
  assert.match(packager, /path\.join\( root, 'assets' \)/);
  assert.match(packager, /'editor color-scheme stylesheet'/);
});

test('dark appearance foreground and background pairs exceed WCAG AA', () => {
  const pairs = {
    root: ['#F5F2E8', '#161513'],
    'section-1': ['#F5F2E8', '#24231F'],
    'section-2': ['#F5F2E8', '#3B293A'],
    'section-3': ['#F5F2E8', '#433B13'],
    'section-4': ['#F1C8EB', '#2E2852'],
    'section-5': ['#161513', '#F5F2E8'],
    'focus-indicator': ['#A9A59E', '#161513'],
    'global-link': ['#8CBDF4', '#161513'],
    'global-link-hover': ['#C8DDF5', '#161513'],
    'global-button': ['#161513', '#8CBDF4'],
  };

  for (const [name, [foreground, background]] of Object.entries(pairs)) {
    assert.ok(
      contrastRatio(foreground, background) >= 4.5,
      `${name} must meet WCAG AA contrast`
    );
  }
});
