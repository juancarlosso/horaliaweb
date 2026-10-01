/**
 * bs-config.js — BrowserSync configuration for HRNexus
 *
 * Usage: npm run dev  (starts watch:scss + watch:js + this)
 *
 * Serves from project root so /dist/, /public/, /src/ all resolve correctly
 * from pages at any directory depth.
 */
'use strict';

module.exports = {
  // Serve from project root — resolves both /public/ and /dist/ correctly
  server: {
    baseDir: './',
    index:   'public/index.html',
    routes: {
      '/': './public/index.html',
    },
  },

  port:      3000,
  open:      'local',               // auto-open browser
  startPath: '/public/index.html',
  notify:    false,                 // no BrowserSync overlay banner
  ghostMode: false,                 // disable click/scroll mirroring

  // Watch these files for live reload / CSS injection
  files: [
    'dist/css/hrnexus-core.css',    // built from src/scss/core.scss — CSS hot-inject (no page reload)
    'dist/css/hrnexus-pages.css',   // hand-maintained (not SCSS-built) — still hot-inject on save
    'dist/css/hrnexus-utils.css',   // hand-maintained (not SCSS-built) — still hot-inject on save
    'dist/js/hrnexus.min.js',       // JS triggers page reload
    'public/**/*.html',             // HTML triggers page reload
  ],

  reloadDelay:   100,   // ms after file change before reload
  injectChanges: true,  // inject CSS without full reload
  logLevel:      'info',
  logPrefix:     'HRNexus',
};
