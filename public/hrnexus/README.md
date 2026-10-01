# HRNexus Bootstrap Admin Dashboard v4.4.0

A complete HR admin dashboard built with native Bootstrap 5 + Sass.
**89 pages** with real content, dark mode, four color presets, drag-and-drop kanban,
sortable tables, live search, and a zero-dependency custom JS bundle — no jQuery required.

📖 **[Read the full documentation →](./documentation/index.html)**

The full guide covers setup, the build system, every customization option, and a
searchable reference for all 89 pages. This file is a quick summary only.

---

## Quick Start

```bash
# Preview instantly — no Node.js install needed; dist/ is pre-built
# Windows: double-click public/index.html
# macOS/Linux: open public/index.html

# Recommended: serve locally for full browser behavior
python -m http.server 3000
# → http://localhost:3000/public/index.html

# To customize: install dependencies, then start the dev server
npm install
npm run dev
```

## npm Scripts

| Command | What it does |
|---|---|
| `npm run dev` | Watch Sass + bundle JS + BrowserSync live reload |
| `npm run build` | Compile CSS + bundle JS (dev, with source maps) |
| `npm run build:prod` | Compile CSS + bundle JS (minified, no maps) |
| `npm run build:themes` | Compile each color preset to `dist/css/themes/` |
| `npm run validate:project` | Sanity-check the whole project for errors |

Full script reference: [documentation/index.html#build-system](./documentation/index.html#build-system)

## License

Distributed via Envato Market (ThemeForest) under Envato's standard licensing
terms — see [LICENSE.md](./LICENSE.md) for full details and third-party attributions.


## Third-party resources

The dashboard uses pinned Bootstrap Icons 1.11.3 and DM Sans via their official CDN/Google Fonts endpoints. The template remains fully editable and the documentation includes the required credits. An internet connection is required for those external font/icon requests; Bootstrap CSS and JavaScript are bundled locally.
