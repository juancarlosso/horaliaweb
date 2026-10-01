#!/usr/bin/env node
/**
 * scripts/build-themes.js — Compile per-theme SCSS overrides
 *
 * Usage: node scripts/build-themes.js
 *        npm run build:themes
 *
 * Input:  src/scss/themes/*.scss   (one file per theme, not prefixed with _)
 * Output: dist/css/themes/<name>.min.css
 */
'use strict';

const { execSync } = require('child_process');
const fs   = require('fs');
const path = require('path');

const ROOT      = path.resolve(__dirname, '..');
const THEMES    = path.join(ROOT, 'src', 'scss', 'themes');
const DIST      = path.join(ROOT, 'dist', 'css', 'themes');
const NODE_MODS = path.join(ROOT, 'node_modules');
const SCSS_SRC  = path.join(ROOT, 'src', 'scss');

// Create output dir
fs.mkdirSync(DIST, { recursive: true });

// Find all top-level SCSS theme files (skip partials starting with _)
if (!fs.existsSync(THEMES)) {
  console.log('  [themes] src/scss/themes/ not found — nothing to compile.');
  process.exit(0);
}

const files = fs.readdirSync(THEMES)
  .filter(f => f.endsWith('.scss') && !f.startsWith('_'));

if (files.length === 0) {
  console.log('  [themes] No theme files found in src/scss/themes/.');
  process.exit(0);
}

let ok = 0, fail = 0;

files.forEach(file => {
  const name    = path.basename(file, '.scss');
  const srcFile = path.join(THEMES, file);
  const outFile = path.join(DIST, `${name}.min.css`);

  const cmd = [
    'npx sass',
    `"${srcFile}" "${outFile}"`,
    '--style=compressed',
    '--no-source-map',
    `--load-path="${NODE_MODS}"`,
    `--load-path="${SCSS_SRC}"`,
    '--quiet-deps',
    '--silence-deprecation=import',
  ].join(' ');

  try {
    execSync(cmd, { stdio: 'inherit' });
    console.log(`  [themes] ✓  ${name}.min.css`);
    ok++;
  } catch (e) {
    console.error(`  [themes] ✗  ${name} — ${e.message.split('\n')[0]}`);
    fail++;
  }
});

console.log(`\n  [themes] Done — ${ok} compiled, ${fail} failed`);
if (fail > 0) process.exit(1);
