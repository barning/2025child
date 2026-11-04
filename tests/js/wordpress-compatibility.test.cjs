const assert = require('node:assert/strict');
const test = require('node:test');
const packageJson = require('../../package.json');

test('WordPress runtime packages stay aligned with the supported 6.7 baseline', () => {
  const versions = {
    'api-fetch': '7.8.2',
    'block-editor': '14.3.16',
    blocks: '13.8.6',
    components: '28.8.12',
    'core-data': '7.8.16',
    data: '10.8.4',
    element: '6.8.1',
    'html-entities': '4.8.1',
    i18n: '5.8.2',
    'server-side-render': '5.8.12',
    url: '4.8.1',
  };

  for (const [name, version] of Object.entries(versions)) {
    const key = `@wordpress/${name}`;
    assert.equal(packageJson.devDependencies[key] ?? packageJson.dependencies[key], version, key);
  }
});
