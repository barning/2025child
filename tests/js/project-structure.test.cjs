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

test('Note thread picker searches beyond its initial recent Notes page', () => {
  const editor = fs.readFileSync(
    path.join(root, 'assets', 'note-thread-editor.js'),
    'utf8'
  );

  assert.match(editor, /onFilterValueChange:\s*setSearch/);
  assert.match(editor, /query\.search\s*=\s*search\.trim\(\)/);
  assert.match(editor, /\[\s*search\s*\]/);
  assert.match(editor, /isLoading:\s*! Array\.isArray\( notes \)/);
  assert.doesNotMatch(editor, /! Array\.isArray\( notes \)\s*\?\s*el\( Spinner \)/);
  assert.match(editor, /note\.content\?\.rendered/);
  assert.match(editor, /__experimentalRenderItem:\s*\( \{ item \} \)/);
  assert.match(editor, /item\.displayLabel \|\| item\.label/);
  assert.match(editor, /setSearch\( '' \)/);
});

test('published Note thread relationships render as read-only values', () => {
  const editor = fs.readFileSync(
    path.join(root, 'assets', 'note-thread-editor.js'),
    'utf8'
  );

  assert.match(editor, /locked\s*\?\s*el\( TextControl/);
  assert.match(editor, /readOnly:\s*true/);
  assert.doesNotMatch(editor, /disabled:\s*locked/);
});

test('Note permalinks use a dedicated minimal template', () => {
  const template = fs.readFileSync(
    path.join(root, 'templates', 'single-note.html'),
    'utf8'
  );
  const packager = fs.readFileSync(path.join(root, 'scripts', 'package.js'), 'utf8');

  assert.match(template, /<!-- wp:post-content/);
  assert.doesNotMatch(template, /hidden-written-by/);
  assert.doesNotMatch(template, /post-navigation/);
  assert.doesNotMatch(template, /more-posts/);
  assert.match(packager, /path\.join\( root, 'templates' \)/);
});

test('media grid music cards use an accessible portrait artwork stage', () => {
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
  assert.match(render, /class="child-media-cover-grid__artwork"/);
  assert.match(styles, /\.child-media-cover-grid__item--music \.child-media-cover-grid__cover--square\s*{[\s\S]+?aspect-ratio:\s*2\s*\/\s*3/);
  assert.match(styles, /\.child-media-cover-grid__item--music \.child-media-cover-grid__artwork\s*{[\s\S]+?aspect-ratio:\s*1\s*\/\s*1[\s\S]+?object-fit:\s*contain[\s\S]+?width:\s*82%/);
  assert.match(styles, /\.child-media-cover-grid__music-background\s*{[\s\S]+?filter:\s*blur\(/);
  assert.match(styles, /\.child-media-cover-grid__item--music \.child-media-cover-grid__cover--square::after\s*{[\s\S]+?inset:\s*0/);
  assert.doesNotMatch(styles, /\.child-media-cover-grid__item--music \.child-media-cover-grid__content\s*{/);
});
