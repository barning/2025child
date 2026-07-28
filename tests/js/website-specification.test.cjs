const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');

const root = path.resolve(__dirname, '../..');
const blocksRoot = path.join(root, 'blocks');

function walk(directory, extension) {
  return fs.readdirSync(directory, { withFileTypes: true }).flatMap((entry) => {
    const entryPath = path.join(directory, entry.name);
    if (entry.isDirectory()) {
      return walk(entryPath, extension);
    }
    return entryPath.endsWith(extension) ? [entryPath] : [];
  });
}

test('recommendation cards do not introduce headings into the page outline', () => {
  for (const file of [...walk(blocksRoot, '.php'), ...walk(blocksRoot, '.js')]) {
    const source = fs.readFileSync(file, 'utf8');
    assert.doesNotMatch(source, /<h[1-6]\b/i, path.relative(root, file));
  }
});

test('animated styles include a reduced-motion override', () => {
  const cssFiles = [path.join(root, 'style.css'), ...walk(blocksRoot, '.css')];
  const animatedFiles = cssFiles.filter((file) =>
    /\b(?:animation|transition)(?:-[a-z-]+)?:/.test(fs.readFileSync(file, 'utf8'))
  );

  assert.ok(animatedFiles.length > 0);
  for (const file of animatedFiles) {
    const css = fs.readFileSync(file, 'utf8');
    assert.match(css, /@media\s*\(\s*prefers-reduced-motion:\s*reduce\s*\)/, path.relative(root, file));
    assert.match(css, /transition:\s*none/, path.relative(root, file));
  }
});

test('frontend block styles rely on metadata instead of a global fallback', () => {
  const registration = fs.readFileSync(path.join(root, 'inc', 'blocks.php'), 'utf8');
  assert.doesNotMatch(registration, /child_enqueue_dynamic_block_styles_globally/);
  assert.doesNotMatch(registration, /-style-global/);
  assert.doesNotMatch(registration, /wp_enqueue_block_style/);
});

test('rendered content images reserve intrinsic space', () => {
  for (const file of walk(blocksRoot, '.php')) {
    const source = fs.readFileSync(file, 'utf8');
    const normalizedSource = source.replace(/<\?php[\s\S]*?\?>/g, 'PHP_VALUE');
    for (const image of normalizedSource.matchAll(/<img\b[^>]*>/gis)) {
      assert.match(image[0], /\bwidth\s*=/i, path.relative(root, file));
      assert.match(image[0], /\bheight\s*=/i, path.relative(root, file));
    }
  }
});

test('the visual fixture uses German and leaves the page title as the only h1', () => {
  const fixture = fs.readFileSync(
    path.join(root, 'tests', 'visual', 'mu-plugins', 'child-layout-fixture.php'),
    'utf8'
  );
  assert.match(fixture, /update_option\(\s*'WPLANG',\s*'de_DE'\s*\)/);
  assert.doesNotMatch(fixture, /wp:heading/);
});
