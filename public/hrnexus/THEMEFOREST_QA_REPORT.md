# HRNexus v4.4.0 — ThemeForest QA Report

## Scope

This pass covers the packaged 89-page HTML admin template, source structure, local asset references, metadata, responsive markup, production asset sizes, dependency references, documentation and ThemeForest listing preparation.

## Results

| Check | Result |
|---|---|
| HTML pages discovered | 89 |
| Viewport meta coverage | 89/89 |
| Local href/src reference check | 0 missing local targets |
| Duplicate HTML IDs | PASS |
| Inline JS syntax validation | PASS |
| CSS/SCSS brace validation | PASS |
| Favicon coverage | 89/89 |
| Image alt audit | No missing alt attributes found in supplied `<img>` elements |
| Bootstrap version | 5.3.8 |
| Bootstrap Icons | Updated to 1.13.1 |
| Production JS bundle | 13.8 KB |
| Production JS gzip estimate | 4.2 KB |
| Core + utility + page CSS | ~468 KB uncompressed |
| All primary CSS + JS | ~777 KB uncompressed / ~126 KB gzip estimate |

## Visual QA

A browser-based screenshot runner was attempted for desktop, tablet and mobile viewports. The execution environment blocks local browser navigation, so a truthful full-browser visual certification could not be completed here. A static HTML/CSS render was successfully generated for the main dashboard and reviewed as a layout sanity check.

**Required before marketplace submission:** deploy the preview to HTTPS and perform browser QA at:

- 1440 × 900 desktop
- 1024 × 900 tablet
- 390 × 844 mobile

At minimum, inspect the dashboard, employee directory, analytics, calendar, forms, tables, authentication, invoice and AI pages, then spot-check the remaining pages.

## Performance

The production CSS/JS payload is reasonable when served with Brotli/Gzip. The largest individual stylesheet is `hrnexus-utils.css` at ~327 KB uncompressed, but its estimated gzip size is ~48 KB. Production hosting should enable Brotli or Gzip, HTTP/2/HTTP/3 and long-lived caching for versioned/static assets.

## External Resources

The pages reference DM Sans from Google Fonts and Bootstrap Icons 1.13.1 from jsDelivr. These resources are documented in `documentation/ASSETS_AND_CREDITS.md`.

## ThemeForest Presentation

Envato recommends organizing editable files and documentation clearly, documenting third-party resources, and providing a functional high-quality live preview. Preview images should be captured from the actual hosted item. See the included marketplace files for the recommended title, description, tags and reviewer message.

## Submission Status

**Submission Candidate — pending hosted browser visual QA and final screenshot capture.**
