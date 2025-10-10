#!/usr/bin/env node
const fs = require('fs');
const fsp = fs.promises;
const path = require('path');
const { execSync } = require('child_process');

const root = process.cwd();
const distRoot = path.join(root, 'dist');
const themeSlug = 'twentytwentyfive-child';
const outDir = path.join(distRoot, themeSlug);

async function rimraf(p){
  if (fs.existsSync(p)) {
    await fsp.rm(p, { recursive: true, force: true });
  }
}

async function ensureDir(p){
  await fsp.mkdir(p, { recursive: true });
}

async function safeCopy(src, dest){
  if (!fs.existsSync(src)) return;
  await ensureDir(path.dirname(dest));
  await fsp.cp(src, dest, { recursive: true });
}

async function copyIfExists(src, dest){
  if (!fs.existsSync(src)) return;
  await safeCopy(src, dest);
}

async function copyBlocksRenderPhp(){
  const blocksDir = path.join(root, 'blocks');
  if (!fs.existsSync(blocksDir)) return;
  const entries = await fsp.readdir(blocksDir, { withFileTypes: true });
  for (const ent of entries) {
    if (ent.isDirectory()) {
      const renderPhp = path.join(blocksDir, ent.name, 'render.php');
      if (fs.existsSync(renderPhp)) {
        const dest = path.join(outDir, 'blocks', ent.name, 'render.php');
        await safeCopy(renderPhp, dest);
      }
    }
  }
}

async function main(){
  await ensureDir(distRoot);
  await rimraf(outDir);
  await ensureDir(outDir);

  // Core theme files
  await safeCopy(path.join(root, 'style.css'), path.join(outDir, 'style.css'));
  await safeCopy(path.join(root, 'functions.php'), path.join(outDir, 'functions.php'));

  // Runtime assets
  await copyIfExists(path.join(root, 'screenshot.png'), path.join(outDir, 'screenshot.png'));

  // PHP modules and built blocks
  await copyIfExists(path.join(root, 'inc'), path.join(outDir, 'inc'));
  await copyIfExists(path.join(root, 'build'), path.join(outDir, 'build'));
  await copyBlocksRenderPhp();

  // Optional readme
  await copyIfExists(path.join(root, 'README.md'), path.join(outDir, 'README.md'));

  // Create zip
  const zipPath = path.join(distRoot, `${themeSlug}.zip`);
  try {
    execSync(`cd ${distRoot} && zip -rq ${themeSlug}.zip ${themeSlug}`);
    console.log(`Created ${zipPath}`);
  } catch (e) {
    console.warn('zip not available, creating tar.gz instead');
    execSync(`cd ${distRoot} && tar -czf ${themeSlug}.tar.gz ${themeSlug}`);
    console.log(`Created ${path.join(distRoot, themeSlug + '.tar.gz')}`);
  }
}

main().catch(err => { console.error(err); process.exit(1); });
