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
