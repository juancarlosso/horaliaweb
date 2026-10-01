#!/usr/bin/env node
/**
 * scripts/bundle.js — Zero-dependency JS bundler for HRNexus
 *
 * Usage:
 *   node scripts/bundle.js            → development build (readable)
 *   node scripts/bundle.js --minify   → production build (compressed)
 *   node scripts/bundle.js --watch    → watch mode (requires chokidar)
 *
 * What it does:
 *   1. Reads each src/js file in dependency order
 *   2. Strips ES module import/export syntax
 *   3. Injects a safe localStorage wrapper (store) before all modules
 *   4. Wraps everything in a single IIFE
 *   5. Writes to dist/js/hrnexus.min.js
 */
'use strict';

const fs   = require('fs');
const path = require('path');

const args   = process.argv.slice(2);
const MINIFY = args.includes('--minify');
const WATCH  = args.includes('--watch');

const ROOT    = path.resolve(__dirname, '..');
const SRC_DIR = path.join(ROOT, 'src', 'js');
const OUT     = path.join(ROOT, 'dist', 'js', 'hrnexus.min.js');

// ── File order matters: dependencies before consumers ───────────────────────
const FILES = [
  'utils/theme.js',     // defines store + ThemeManager
  'utils/sidebar.js',   // SidebarManager  (uses store)
  'utils/topbar.js',    // TopbarManager
  'utils/tooltip.js',   // TooltipManager
  'utils/search.js',    // initSearch()
  'components/charts.js',  // initCharts()
  'components/tables.js',  // initTables()
  'components/kanban.js',  // initKanban()
  'main.js',            // init() + DOMContentLoaded
];

// ── Strip ES module syntax ───────────────────────────────────────────────────
function stripModules(src) {
  return src
    .replace(/^import\s+.*?from\s+['"][^'"]+['"];?\s*$/gm, '')  // import … from '…'
    .replace(/^import\s+['"][^'"]+['"];?\s*$/gm, '')             // import '…'
    .replace(/^export\s+const\s+/gm, 'const ')                  // export const → const
    .replace(/^export\s+function\s+/gm, 'function ')            // export function
    .replace(/^export\s+default\s+/gm, '')                      // export default
    .replace(/^export\s*\{[^}]*\};?\s*$/gm, '');                // export {}
}

// ── Very light minifier (removes comments + excess whitespace) ──────────────
function minify(src) {
  return src
    .replace(/\/\/[^\n]*/g, '')          // line comments
    .replace(/\/\*[\s\S]*?\*\//g, '')    // block comments
    .replace(/\n\s*\n+/g, '\n')          // blank lines
    .replace(/^\s+/gm, '')               // leading spaces per line
    .trim();
}

// ── Main build ───────────────────────────────────────────────────────────────
function build() {
  const date = new Date().toISOString().slice(0, 10);

  // safe localStorage wrapper — injected before all modules so 'store' is always defined
  const STORE_SNIPPET = `
// ── Safe localStorage wrapper (works under file:// and restricted origins) ──
var store = {
  get: function(k, def) {
    try { var v = localStorage.getItem(k); return v !== null ? v : (def !== undefined ? def : null); }
    catch(e) { return def !== undefined ? def : null; }
  },
  set: function(k, v) { try { localStorage.setItem(k, v); } catch(e) {} },
};
`;

  const parts = FILES.map(rel => {
    const file = path.join(SRC_DIR, rel);
    if (!fs.existsSync(file)) {
      console.warn(`  [bundle] SKIP — not found: ${rel}`);
      return '';
    }
    const raw = fs.readFileSync(file, 'utf8');
    const processed = stripModules(raw);
    return `\n// ── ${path.basename(rel)} ${'─'.repeat(Math.max(0, 60 - rel.length))}\n${processed}`;
  });

  const body = STORE_SNIPPET + parts.join('\n');
  const banner = `/**\n * HRNexus Bootstrap Admin Dashboard v4.0 — JS Bundle\n * Built: ${date} | ${MINIFY ? 'production (minified)' : 'development'}\n */`;
  const content = `${banner}\n(function(window, document) {\n'use strict';\n\n${MINIFY ? minify(body) : body}\n\n})(window, document);\n`;

  fs.mkdirSync(path.dirname(OUT), { recursive: true });
  fs.writeFileSync(OUT, content, 'utf8');

  const kb = (content.length / 1024).toFixed(1);
  console.log(`  [bundle] ✓  dist/js/hrnexus.min.js  —  ${kb} KB  ${MINIFY ? '(minified)' : '(dev)'}`);
}

// ── Run ──────────────────────────────────────────────────────────────────────
build();

if (WATCH) {
  console.log(`  [bundle] watching src/js/ …`);
  try {
    const chokidar = require('chokidar');
    chokidar.watch(SRC_DIR, { ignoreInitial: true }).on('all', (event, file) => {
      console.log(`  [bundle] changed: ${path.relative(SRC_DIR, file)}`);
      try { build(); } catch (e) { console.error(`  [bundle] ERROR: ${e.message}`); }
    });
  } catch {
    console.warn(`  [bundle] chokidar not installed. Run: npm install`);
  }
}
