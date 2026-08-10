const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');

const root = path.resolve(__dirname, '../..');

test('every source block has canonical metadata', () => {
  const blocksRoot = path.join(root, 'blocks');
  const directories = fs.readdirSync(blocksRoot, { withFileTypes: true });
  const blocks = directories.filter(
    (entry) => entry.isDirectory() && fs.existsSync(path.join(blocksRoot, entry.name, 'block.json'))
  );

  assert.equal(blocks.length, 10);

  for (const block of blocks) {
    const metadata = JSON.parse(
      fs.readFileSync(path.join(blocksRoot, block.name, 'block.json'), 'utf8')
    );
    assert.equal(metadata.name, `child/${block.name}`);
    assert.equal(metadata.textdomain, 'child');
    assert.ok(metadata.editorScript, `${block.name} must declare editorScript`);
    assert.equal(metadata.editorStyle, 'file:./index.css');
    assert.equal(metadata.style, 'file:./style-index.css');
  }
});

test('theme, package, lockfile, and release note versions match', () => {
  const style = fs.readFileSync(path.join(root, 'style.css'), 'utf8');
  const themeVersion = style.match(/^[ \t]*Version:\s*(\S+)/m)?.[1];
  const packageJson = JSON.parse(fs.readFileSync(path.join(root, 'package.json'), 'utf8'));
  const packageLock = JSON.parse(fs.readFileSync(path.join(root, 'package-lock.json'), 'utf8'));

  assert.equal(themeVersion, packageJson.version);
  assert.equal(packageLock.version, packageJson.version);
  assert.ok(fs.existsSync(path.join(root, 'releases', `v${themeVersion}.md`)));
});

test('block registration and the WordPress smoke test are idempotent', () => {
  const registration = fs.readFileSync(path.join(root, 'inc', 'blocks.php'), 'utf8');
  const workflow = fs.readFileSync(
    path.join(root, '.github', 'workflows', 'build-release.yml'),
    'utf8'
  );

  const guardPosition = registration.indexOf(
    "$registry->is_registered( $config['block_name'] )"
  );
  const requirePosition = registration.indexOf(
    "require $theme_dir . '/' . $config['render_file']"
  );

  assert.ok(guardPosition >= 0, 'registration must guard already-registered blocks');
  assert.ok(
    guardPosition < requirePosition,
    'the registration guard must run before loading render files'
  );
  assert.doesNotMatch(workflow, /wp eval 'do_action\\?\(["']init["']\)/);
});

test('media grid music cards reuse artwork in an accessible portrait treatment', () => {
  const render = fs.readFileSync(
    path.join(root, 'blocks', 'media-cover-grid', 'render.php'),
    'utf8'
  );
  const styles = fs.readFileSync(
    path.join(root, 'blocks', 'media-cover-grid', 'style.css'),
    'utf8'
  );

  assert.match(render, /class="child-media-cover-grid__music-background"/);
  assert.match(render, /class="child-media-cover-grid__music-background"[\s\S]+?alt=""[\s\S]+?aria-hidden="true"/);
  assert.match(render, /'music' !== \$type && \$show_meta && \$meta/);
  assert.match(styles, /\.child-media-cover-grid__item--music \.child-media-cover-grid__content\s*{[\s\S]+?aspect-ratio:\s*2\s*\/\s*1/);
  assert.match(styles, /\.child-media-cover-grid__item--music \.child-media-cover-grid__cover img\s*{[\s\S]+?object-fit:\s*contain/);
  assert.match(styles, /\.child-media-cover-grid__music-background\s*{[\s\S]+?filter:\s*blur\(/);
  assert.match(styles, /\.child-media-cover-grid__item--music \.child-media-cover-grid__content::after\s*{[\s\S]+?linear-gradient\(/);
});
