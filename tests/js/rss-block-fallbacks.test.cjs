const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');

const root = path.resolve(__dirname, '..', '..');
const blockNames = [
  'book-rating',
  'magic-cards',
  'media-cover-grid',
  'media-recommendation',
  'music-recommendation',
  'pixelfed-feed',
  'popular-posts',
  'post-likes',
  'videogame-recommendation',
  'visual-link-preview',
];

test('every dynamic block has an explicit feed fallback', () => {
  for (const blockName of blockNames) {
    const renderPath = path.join(root, 'blocks', blockName, 'render.php');
    const source = fs.readFileSync(renderPath, 'utf8');
    assert.match(source, /child_is_feed_render\(\)/, `${blockName} must branch for feeds`);
  }
});

test('collection feed fallbacks keep their compact item caps', () => {
  const grid = fs.readFileSync(path.join(root, 'blocks/media-cover-grid/render.php'), 'utf8');
  const pixelfed = fs.readFileSync(path.join(root, 'blocks/pixelfed-feed/render.php'), 'utf8');

  assert.match(grid, /array_slice\( \$items, 0, 4 \)/);
  assert.match(grid, /'title'\s*=> \$show_title \? \$title : ''/);
  assert.match(grid, /'image_alt'\s*=> \$title/);
  assert.match(pixelfed, /return child_render_feed_card/);
});
