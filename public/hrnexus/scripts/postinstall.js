#!/usr/bin/env node
/**
 * scripts/postinstall.js
 * Runs automatically after `npm install`.
 * Creates required output directories and prints quick-start instructions.
 */
'use strict';

const fs   = require('fs');
const path = require('path');

const ROOT = path.resolve(__dirname, '..');

const DIRS = [
  'dist/css',
  'dist/css/themes',
  'dist/js',
  'dist/assets',
];

DIRS.forEach(d => {
  fs.mkdirSync(path.join(ROOT, d), { recursive: true });
});

console.log(`
╔══════════════════════════════════════════════════════════════════════╗
║         HRNexus Admin Dashboard v4.4.1 — Dependencies installed!      ║
╠══════════════════════════════════════════════════════════════════════╣
║                                                                      ║
║  BUILD (compile SCSS + bundle JS):                                   ║
║    npm run build                                                     ║
║                                                                      ║
║  DEV MODE (watch + live reload at localhost:3000):                   ║
║    npm run dev                                                       ║
║    → open http://localhost:3000/public/index.html                   ║
║                                                                      ║
║  PRODUCTION BUILD (minified, no source maps):                        ║
║    npm run build:prod                                                ║
║                                                                      ║
║  COMPILE THEME VARIANTS:                                             ║
║    npm run build:themes                                              ║
║                                                                      ║
║  OPEN WITHOUT A SERVER:                                              ║
║    Double-click public/index.html in your file explorer             ║
║    (all paths are relative — no server required)                     ║
║                                                                      ║
║  CUSTOMISE BRAND COLOUR:                                             ║
║    Edit src/scss/abstracts/_variables.scss                           ║
║    Change  \$primary: #4F6EF7;  then run  npm run build             ║
║                                                                      ║
╚══════════════════════════════════════════════════════════════════════╝
`);
