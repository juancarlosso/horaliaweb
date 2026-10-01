# How to Open HRNexus Dashboard

For setup, customization, and the full page reference, see **[documentation/index.html](./documentation/index.html)**. This file covers only how to preview the pages locally.

## Option 1 — Double-click (simplest)
Just double-click `public/index.html` in your file explorer.
All local links use relative paths. The bundled Bootstrap CSS/JS works locally; Google Fonts and Bootstrap Icons are loaded from external CDNs and require an internet connection.

## Option 2 — VS Code Live Server (recommended for full features)
1. Install the "Live Server" extension in VS Code
2. Right-click `public/index.html` → **Open with Live Server**
3. Dashboard opens at `http://127.0.0.1:5500/public/index.html`

## Option 3 — Python local server
```bash
cd hrnexus-bs
python -m http.server 3000
# Then open: http://localhost:3000/public/index.html
```

## Option 4 — Node.js
```bash
cd hrnexus-bs
npx serve .
# Then open the URL shown in terminal
```

## About the browser console messages
These are **not errors from this dashboard**:

| Message | Cause | Safe to ignore? |
|---|---|---|
| `chrome-extension://...` denied | A Chrome extension (screen recorder) trying to inject CSS | ✅ Yes |
| `[HRNexus] v4.4.0 ✓` | Dashboard loaded successfully | ✅ Expected |
| `localStorage` warnings | Chrome security on file:// (theme/sidebar state won't persist across pages) | ✅ Yes — use Live Server to fix |

