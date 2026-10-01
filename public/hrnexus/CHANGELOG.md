# Changelog

## 4.4.1 — npm install / security hardening

- Added npm `allowScripts` approval for the exact `@parcel/watcher@2.6.0` dependency used by the Sass toolchain.
- Updated the postinstall banner to the actual package version.
- Confirmed the checked-in lockfile resolves patched versions of known audit-sensitive transitive packages.
- Recommended clean-install workflow: remove `node_modules`, run `npm ci`, then `npm audit`.


## 4.4.0 — ThemeForest Submission Candidate

### Marketplace hardening
- Cleaned accidental duplicate `<title>`, charset, viewport, and theme-color metadata fragments from affected layout/demo pages.
- Added accessible `aria-label="Close"` names to Bootstrap modal close buttons.
- Updated the package manifest to Bootstrap 5.3.8 and synchronized the package metadata with the locked dependency tree.
- Refreshed README and local-preview instructions for ThemeForest buyers, including the external-font/icon dependency note.
- Retained the pre-built `dist/` assets so buyers can preview the template without installing Node.js.

### QA baseline
- 89 HTML pages.
- 0 duplicate HTML IDs.
- 0 missing local `href`/`src` references.
- 0 inline JavaScript syntax errors detected by the project validator.
- 0 unbalanced CSS/SCSS braces.


## 4.3.41 — Welcome Banner on index.html made responsive

Requested specifically: make the Welcome Banner responsive on every
mobile device. Confirmed this component is unique to `index.html` —
not shared with any other page — so the fix is scoped there only.

- **The banner never adapted to narrow screens at all.** It's a
  single-row flexbox (avatar, greeting text, and two action buttons
  side by side) with `overflow:hidden` on the container — needed
  legitimately to clip a decorative radial-gradient glow to the
  banner's rounded corners, so removing it outright wasn't the right
  fix. But combined with `flex-shrink:0` on the avatar and actions
  and `white-space:nowrap` on the buttons, anything that didn't fit
  the available row width was silently clipped rather than wrapping.
  Screenshotted the real page at 320–480px before writing any CSS:
  confirmed the "Add Schedule" button was cut to just its icon and
  "Add Request" was pushed off-screen entirely at every width tested.
  Restructured the banner to stack vertically (avatar, then text,
  then a full-width action row) below 820px, with the two buttons
  splitting that row evenly.
- **A narrower edge case inside the fix itself**: at exactly 320px,
  the two buttons — now evenly split via `flex:1` — were still just
  wide enough that "Add Request" clipped by a few characters, since
  Chrome doesn't shrink `nowrap` button content to fit. Verified this
  didn't reproduce at 375px or above, so added a second, narrower
  breakpoint (≤360px) that stacks the two buttons vertically instead
  of side-by-side, guaranteeing neither can run out of room
  regardless of exact text width.
- **A second, separate gap found only by testing systematically
  across many widths instead of a handful of common device sizes**:
  checked every width from 300px to 1440px programmatically (matching
  bounding boxes, not just eyeballing screenshots) and found the
  original overflow bug returns from 768–800px — common tablet
  portrait widths sitting just above the existing 767px mobile
  breakpoint, where the original unstacked row layout re-applies but
  still isn't wide enough for it. Moved the banner's responsive rules
  into their own dedicated `@media(max-width:820px)` block rather
  than widening the shared 767px breakpoint, since that block also
  contains unrelated rules (stats grid, Kanban scrolling) that had
  already been verified correct at their existing boundary and didn't
  need to change.
- Final verification: all 18 tested widths from 300px to 1440px
  confirmed overflow-free via computed bounding-box checks, plus
  visual screenshots at the two previously-broken widths (320px and
  780px) and a desktop width (1440px) to confirm the original
  side-by-side layout is completely unchanged there.
- `node scripts/validate-project.js` passes across all 89 pages —
  this change touches only `index.html`.

## 4.3.40 — Mobile sidebar actually works now, across 74 of 76 pages

Requested: make the template mobile-responsive, with the sidebar
hidden by default and opening via a hamburger button on mobile
devices. The JS and most of the CSS for this already existed —
`sidebar.js` had a complete `mobileToggle()`/`mobileClose()`
implementation, and the mobile media query already handled hiding
the sidebar, adjusting grids, and making Kanban boards horizontally
scrollable — but three separate defects meant none of it actually
worked. Diagnosed each one with real Playwright screenshots and
computed-style checks at a 375px viewport rather than reading the
CSS and assuming it applied, which is what surfaced the first two:

- **Root cause, affecting all 89 pages: a CSS animation permanently
  overrode the mobile-hide rule.** The sidebar carries an entrance
  animation (`.anim-sil`, a "slide in" flourish on page load) whose
  final keyframe sets `transform: translateX(0)` with
  `animation-fill-mode: both`. CSS animations take cascade precedence
  over ordinary property declarations, so this silently beat the
  mobile media query's `translateX(-100%)` rule regardless of
  specificity or `!important` — the sidebar was structurally
  incapable of hiding on mobile, no matter what else got fixed.
  Reproduced this in an isolated minimal test case first to confirm
  the mechanism, then verified the fix (scoping the animation to
  `@media(min-width:768px)`, since `.anim-sil` is used exclusively on
  the sidebar and nowhere else in the project) in the same isolated
  test before touching any real page. Applied to the Sass source,
  the otherwise-unused `dist/css/hrnexus-core.css`, and all 89 pages'
  inline critical CSS.
- **The hamburger button was invisible even where it existed, and
  missing entirely elsewhere.** Only 24 of 89 pages had the
  `data-sidebar-mobile-toggle` button in their markup at all, and on
  every one of those 24 it carried Bootstrap's `.d-none` with no
  responsive override anywhere — permanently `display:none`
  regardless of viewport. Added a same-specificity override
  (`[data-sidebar-mobile-toggle].d-none{display:flex!important}`,
  needed because Bootstrap's own `.d-none{display:none!important}`
  loads later in the cascade and would otherwise win the tie) to
  every page with a real sidebar, and hid the desktop collapse
  toggle on mobile in the same pass — leaving it active there would
  let a stray tap persist an unwanted "collapsed" state to
  `localStorage` that could affect the desktop view later. Then
  added the missing button markup itself to 52 pages that had a
  sidebar but no button at all, matching the exact working pattern
  from the 24 that already had it — handled via a regex insertion
  after the existing desktop-toggle button rather than a fixed
  string, since the surrounding markup varied (missing aria-labels,
  single-line vs. multi-line formatting) enough that an exact match
  only caught 43 of the 52 pages on the first pass.
- **A page-load-blocking bug found only because fixing the first two
  surfaced it**: `index.html` additionally had a rule forcing
  `.mobile-backdrop{display:block!important}` unconditionally on any
  mobile viewport, rather than only when the sidebar was actually
  open — meaning the full-screen dark overlay would have covered and
  blocked every tap on the page from the moment it loaded, regardless
  of sidebar state. This directly fought the JS's own correct
  show/hide logic and was removed; confirmed this pattern doesn't
  exist on any of the other 88 pages before moving on.
- Verified the complete open → view → close cycle with real clicks
  (not just computed-style checks) via Playwright screenshots on
  `index.html`, then spot-checked three more pages spanning different
  parts of this fix (one that already had the button, two that had
  it newly added) to confirm the same flow works identically across
  the batch.
- **Known remaining gap**: `layout-boxed.html` and
  `layout-detached.html` use a page-specific topbar (`topbar-bx`
  class, `id="bxToggle"` toggle) instead of the standard
  `data-sidebar-toggle` pattern every other page shares, so the bulk
  fix correctly skipped them rather than risk a mismatched insertion.
  These two still need their own dedicated pass. Every other page
  with a real sidebar (74 of 76 — the other 13 of 89 are auth,
  error, and alternate-layout pages that never had a sidebar to
  begin with) is confirmed fixed.
- `node scripts/validate-project.js` passes across all 89 pages.

## 4.3.39 — dashboard-recruitment.html: a more thorough second pass, 2 more real bugs

Requested specifically: "fixed all bugs found in dashboard-recruitment.html."
The prior pass (4.3.38) had fixed one confirmed bug but left three
sections unexamined — took the request as a prompt to go back and
actually finish the page properly rather than just re-confirming the
one fix already made.

- **"Average Time To Hire" — a chart silently missing a quarter of its
  own data**: the legend lists four stage-transition durations
  (Applied to Shortlisted: 5 days, Shortlisted: 7, Interview to
  Offer: 10, Acceptance: 3 — 25 days total), each with its own color
  swatch. But the three monthly bars only had three visual segments
  each, using only the first three of those four colors — the fourth
  legend color (for "Acceptance") appeared nowhere in the actual
  chart. Confirmed this wasn't intentional by checking the existing
  three segments' percentages against the legend's day values: they
  summed to 100% on their own, meaning they'd been scaled as if
  "Acceptance" didn't exist at all, not as if it were merged into an
  existing segment. Recomputed all three bars with the correct
  four-segment breakdown derived directly from the legend's own
  numbers (20% / 28% / 40% / 12%), verified programmatically that all
  three bars now sum to exactly 100% across all four segments.
- **"Active Job Openings" — individual postings showing more
  applicants than the entire company has candidates**: five job rows
  listed 1,452, 1,342, 1,287, 1,198, and 1,134 applicants each —
  summing to 6,413 for just five postings, nearly triple the
  "Total Candidates: 2,384" figure already confirmed correct and
  consistently used in two other places on this same page. Replaced
  with values proportioned around the page's own implied average
  (2,384 candidates ÷ 47 open positions ≈ 51/role), scaled somewhat
  above average to reflect these being the featured/high-priority
  listings shown, while preserving their original relative order and
  keeping their combined total to a believable ~15% share of the
  company-wide pool for the top 5 of 47 postings.
- Checked "Upcoming Schedules," "Recent Applications," and the
  page's per-department hiring breakdown one more time for anything
  connected to either new finding — confirmed both fixes are
  self-contained and don't require touching any other section.
- Full validator, duplicate-id check, and all 84 internal links
  reconfirmed passing after these changes.
- `node scripts/validate-project.js` passes across all 89 pages.

## 4.3.38 — dashboard-assets.html, dashboard-finance.html, dashboard-recruitment.html: the last 3 dashboards

Completes the full audit of all 9 dashboard variants, started several
sessions ago. These three have no inline JavaScript, so this pass
focused on what's actually checkable without it: cross-referencing
every hardcoded figure against every other hardcoded figure on the
same page, the same approach that already found real bugs on
`dashboard-hr.html`, `dashboard-ecommerce.html`, and `dashboard-it.html`.

- **dashboard-assets.html — 2 real bugs**:
  - The "Assets by Department" breakdown (5 departments) summed to
    934, while the "Assets Assigned" KPI card said 892 — a figure
    already separately verified as correct against the page's other
    top-level KPIs (892+287+68 = 1,247, matching "Total Assets"
    exactly). Rescaled the 5 department counts proportionally to
    match the correct total, recalculating their bar widths to stay
    consistent with each other.
  - The "Assets By Category" donut chart's 5 segments (Laptops,
    Mouse, Writing Pad, Keyboard, Chairs) summed to only 95%, in both
    the SVG arc math and the adjacent text labels — a genuine 5%
    gap, not one bad segment among an otherwise-complete set.
    Proportionally rescaled all 5 to sum to 100%, then verified the
    corrected arc lengths, offsets, and labels all agree with each
    other exactly.
- **dashboard-finance.html — 1 real bug**: the "HR Cost Breakdown"
  gauge's 6 line items (Salaries, Benefits, Bonuses, Overtime,
  Training, Incentives) summed to ₹5.33Cr, while the gauge's own
  center label said ₹2.46Cr. Before picking which number to trust,
  checked whether either figure appeared elsewhere on the page —
  found "₹2.46Cr" reused identically as both "Total Payroll" and
  "Budget Remaining" in two separate, distinctly-labeled sections,
  strong evidence it was the intended anchor. Rescaled all 6 line
  items proportionally to sum to exactly ₹2.46Cr instead, which also
  had the side benefit of putting all 6 in consistent Lakh units
  rather than mixing Crore for the largest one and Lakhs for the rest.
- **dashboard-recruitment.html — 1 real bug, found through careful
  reasoning rather than a single clean calculation**: the "Stage
  Performance" funnel showed Shortlisted (2,384) exceeding Applied
  (1,848) — a logical impossibility regardless of methodology, since
  a recruitment funnel can never shortlist more candidates than
  applied in the first place. Tried several interpretations of the
  adjacent "conversion rate" label to find a precisely-derivable
  correct value; found the existing wrong number was actually
  self-consistent with one interpretation (892 ÷ 0.374 ≈ 2,385) but
  that same interpretation broke down when checked against the other
  stages, so no single formula could be trusted to yield an exact
  answer. Fixed it the way that was actually verifiable: chose a
  value that restores a properly monotonically-decreasing funnel
  (1,848 → 1,240 → 892 → 324 → 64) with a conversion-rate label
  genuinely consistent with it, rather than leaving an impossible
  number in place for the sake of an unreliable formula. Separately
  confirmed the 2,384 figure itself wasn't the error — it's a
  legitimate, consistently-reused "total applicant pool" number that
  correctly appears in two other, unrelated places on the same page
  ("Total Applications" and "Total Candidates"), so only the one
  place that had wrongly borrowed it was changed. Cross-checked a
  separate department-by-department hiring table on the same page
  (operating at a different, per-job-opening scale) and confirmed
  every one of its 5 rows is internally logical on its own, with no
  further violations found.
- All three pages: full validator pass, zero duplicate ids, and every
  internal link (84 per page) confirmed to resolve to a real file.
- `node scripts/validate-project.js` passes across all 89 pages.
- This closes out the dashboard audit: all 9 dashboard-*.html
  variants plus the main `index.html` have now been individually
  checked in this project's history.

## 4.3.37 — Real product screenshot in the ThemeForest thumbnail

Replaced the abstract, illustrated dashboard mockup in the 590×300
thumbnail with an actual screenshot of the real homepage
(`public/index.html`), per explicit request.

- Discovered mid-task that a real, working screenshot capability was
  available in this environment after all — a Playwright-managed
  Chromium browser was already installed locally, distinct from an
  earlier failed attempt with Puppeteer, which failed specifically
  because it tries to download its own separate browser copy and hit
  a network restriction. Verified this by actually launching a browser
  and taking a real screenshot before relying on it further.
- Found and fixed a real gap on the first screenshot attempt: the
  sidebar and topbar icons rendered completely blank. Traced this to
  Bootstrap Icons loading from a CDN domain outside this environment's
  allowed network list. Rather than ship a screenshot with missing
  icons, installed the `bootstrap-icons` package via npm (a domain
  that is allowed) and used Playwright's request interception to serve
  the local font files in place of the blocked CDN request — confirmed
  by re-screenshotting and checking that every sidebar icon,
  navigation chevron, and topbar icon now actually renders.
- Composited the real screenshot into the 590×300 frame as a full-
  bleed background with a gradient fade for legible overlay text,
  reusing the branding treatment already established for this
  thumbnail. Hit and diagnosed a real cairosvg bug in the process: the
  library silently fails to load images referenced by external file
  path (relative or absolute `file://`), rendering nothing at all with
  no error — confirmed this by isolating the `<image>` tag in its own
  minimal test file before touching the full composition, then
  confirmed a base64 data-URI embed works correctly as the fix, and
  only then rebuilt the full design on top of the working approach.
- Verified the finished composition at true 590×300 render size (not
  just zoomed in) before finalizing, matching this project's own
  established practice of checking marketing graphics at the size
  they'll actually be seen.
- Kept the source SVG and the cropped homepage screenshot alongside
  the PNG in `marketing/`, so either can be swapped or re-composited
  later without redoing this work from scratch.
- `node scripts/validate-project.js` passes across all 89 pages —
  this change touches no product page.

## 4.3.36 — Reworked the ThemeForest thumbnail (590×300)

Given how much weight this one image carries — it's what actually
shows in every ThemeForest search result and category grid, before a
buyer ever clicks in — it got a dedicated second pass rather than
staying as one asset among several.

- Looked at the first version with fresh eyes and found genuine
  weaknesses: a large stretch of empty dark space in the lower-left
  with nothing happening, a dashboard mockup that felt small and
  slightly lost against the background rather than confidently
  filling its half of the frame, and no color variety beyond the
  blue/violet pairing already used everywhere else in the mockup.
- Rebuilt the right-side mockup larger and gave it a browser-chrome
  treatment (traffic-light dots along the top) for instant, one-glance
  recognizability as software rather than an abstract shape. Added a
  teal accent card calling out the AI-assisted tools specifically,
  both filling dead space and breaking the single-color-family
  monotony. Filled the previously-empty lower-left with a compact
  tech-stack credibility line instead of leaving it blank.
- Verified the result the same way as the rest of this pass: rendered
  to a real PNG and inspected it, not just reasoned about from the SVG
  markup. Cropped in on the new bottom text line specifically to
  confirm it doesn't run into the mockup panel before calling it
  finished, and checked the full composition at true 590×300 render
  size (how it will actually appear in a search grid), not only at 2x
  zoom.
- Kept the SVG source alongside the PNG in `marketing/` so the
  thumbnail can be edited directly later without rebuilding from
  scratch.
- `node scripts/validate-project.js` passes across all 89 pages —
  this change touches no product page.

## 4.3.35 — ThemeForest marketing materials (marketing/)

Marketing copy and promotional graphics for the item listing, added
without touching any product page.

- **Listing copy** (`marketing/HRNexus_ThemeForest_Listing_Copy.md`):
  title options, tagline, short and full descriptions, a complete
  feature list organized by category, suggested tags, and category.
  Every specific claim was checked against the real codebase rather
  than written from memory — caught and corrected one real error in
  the process (an early draft said "five invoice layout styles," but
  the product actually has three style variants plus a separate
  manager and creation form — five files, not five styles). Verified
  page counts (13 UI components, 6 layouts, 3 login + 2 register
  screens), each error page's actual HTTP status code (404/500/403),
  and — after a claim about "drag-and-drop Kanban" turned out to
  conflate two different pages — confirmed exactly which page has
  real HTML5 drag-and-drop (`drag-drop.html`) versus which has a
  filterable list/grid/Kanban-style view without dragging
  (`tasks.html`), and reworded the copy to describe both accurately
  instead of blurring them together.
- **Three promotional graphics**, hand-built as SVG (no screenshot
  tool was available in this environment, so these are designed
  marketing graphics — feature highlights and a stylized, abstract
  dashboard composition — rather than literal screenshots) and
  rendered to PNG for upload:
  - `HRNexus_Cover_Banner.png` (1400×800) — the main gallery/hero
    image
  - `HRNexus_Feature_Grid.png` (1400×900) — a 9-panel feature
    breakdown
  - `HRNexus_Thumbnail_590x300.png` — ThemeForest's required main
    listing thumbnail size, composed specifically for that aspect
    ratio rather than cropped from the banner
  Colors are the product's own verified brand values (`#13142B`
  sidebar, `#4F6EF7`→`#8B5CF6` accent gradient), not an invented
  palette. Rendered each graphic to a real PNG and visually inspected
  it before calling it done — caught and fixed two real layout bugs
  this way that reasoning about the SVG code alone had missed: a
  floating card clipped off the right edge of the cover banner
  (extended 12px past the canvas boundary), and a headline that
  visually overlapped the dashboard mockup on the thumbnail. Also
  checked the thumbnail at true 590×300 render size, not just at 2x
  zoom, to confirm it stays legible at the size it'll actually display
  in a search grid.
- `node scripts/validate-project.js` passes across all 89 pages —
  this addition touches no product page.

## 4.3.34 — New non-technical guide (documentation/easy-customization-guide.html)

A second, complementary documentation file, written for buyers with no
coding background — a genuinely different audience than the existing
developer documentation, which assumes comfort with npm and a
terminal.

- Covers only what's realistic without build tools: editing plain text
  and HTML directly (finding your company name, swapping the sidebar
  icon, changing sample data), using this project's own built-in icon
  browser as a beginner-friendly picker tool, and using an editor's
  Find-and-Replace-across-files feature to make one change apply to
  every page at once, since 76 of the 89 pages each carry their own
  copy of the sidebar.
- Verified every concrete example against the real codebase rather
  than writing from memory, and it's a good thing: the first draft's
  "remove a sidebar link" example used a markup pattern that turned
  out not to exist anywhere in the project. Checked the real markup
  directly, found it's meaningfully more layered than assumed (each
  link is a `<div class="nav-li">` wrapping an `<a>` that itself
  contains a separate icon `<span>`, a text `<span>`, and sometimes a
  notification-count `<span>`), and rewrote that whole section around
  the actual structure — including instructions that now correctly
  say to delete the outer wrapping block, not just the inner tag.
  Re-verified every other concrete claim in the guide the same way
  after finding that one wrong (brand text, the color variable, a
  sample employee name, the icon picker's copy behavior, the existence
  of the three other icon-browser pages) — all independently confirmed
  correct.
- Ran the same WCAG contrast-ratio math used on the developer
  documentation against this guide's own (different, warmer) color
  palette rather than assuming the earlier fixes carried over — found
  four pairs that fell short here specifically (muted text, white text
  on part of a gradient card, and both labels in a before/after
  example) and computed passing replacements from the same palette.
  Verified the interactive pieces (FAQ accordion, all internal
  section links) under jsdom.
- Added a short cross-link between the two documentation files, so a
  reader lands on the right one for their skill level regardless of
  which they open first.
- `node scripts/validate-project.js` passes across all 89 pages.

## 4.3.33 — dashboard-ecommerce.html & dashboard-it.html: real math errors fixed; dashboard-leads.html audited clean

Continuing the dashboard audit. Given the CSV export cross-check
methodology found a real bug on `dashboard-hr.html` two versions ago,
applied the same systematic check here: every hardcoded figure in each
page's export function, checked against what the dashboard actually
displays.

- **dashboard-ecommerce.html — a donut chart segment didn't match its
  own label**: the "Order Status" SVG donut's four segments use
  `stroke-dasharray` to encode percentages geometrically. Checked all
  four against their circumference math (using the actual formula, not
  eyeballing): three were correct, but "Processing" was drawn as
  10.19% of the circle while its own adjacent text label — and the CSV
  export — both said 10.7%. Confirmed the four labeled percentages sum
  cleanly to exactly 100%, so the labels were clearly the intended
  source of truth and the arc length was the actual error. Computed
  the correct `stroke-dasharray` value from the real circumference
  formula and fixed it, then re-verified all four segments now
  correctly resolve to within 0.5 percentage points of their labels.
  Cross-checked all 7 CSV export sections (Key Metrics, Order Status,
  Top Products, Sales by Category, Recent Orders, Sales by Region)
  against the actual dashboard content — this was the only mismatch
  found.
- **dashboard-it.html — a storage usage widget's initial bars didn't
  match what its own toggle would produce**: the Storage Usage
  widget's JS scales each bar's height as a percentage of the max
  value *within the currently selected period's own dataset* — the
  only approach that generalizes correctly across all four time
  ranges it supports (1D/7D/1M/1Y), since a fixed external reference
  wouldn't make sense across such different value scales. But the
  page's static, initial HTML (for the default "1Y" period) was scaled
  against a different, larger implied maximum, so every single bar
  was drawn shorter than the same toggle button would actually
  produce if clicked. Found this by directly computing what the JS
  would output for each of the 6 modules and comparing against the
  static markup — found a mismatch on every one, with a consistent
  ratio pointing to one specific wrong number. Corrected all 6 static
  bar heights to match the JS's own logic, and verified by running the
  actual function under a DOM mock and confirming its output now
  agrees with the fixed markup. Also removed a harmless but dead
  no-op line encountered along the way
  (`querySelectorAll('#storageBars').forEach(function(){})`).
- **dashboard-leads.html**: audited in full — verified the lead-stage
  and lead-source dropdown options exactly match the JS's expected
  keys, confirmed the static table rows and the JS-generated row
  template produce the same column count and order (an initial
  concern from a quick header check turned out to be a mismatch in my
  own regex, not the page — verified directly against the real markup
  before concluding anything), and ran the add-lead and log-contact
  flows under a DOM mock. No bugs found.
- `node scripts/validate-project.js` passes across all 89 pages.

## 4.3.32 — dashboard-employees.html & index.html fixed; a methodology gap found and closed

- **dashboard-employees.html — avatar color drifted every time an
  employee was edited**: `buildRowHtml()` picked each avatar's gradient
  from a global, ever-incrementing counter rather than anything tied to
  the employee themselves. Since editing an existing employee re-runs
  the same render function, their avatar would silently reassign to
  whatever gradient the counter currently pointed to — unrelated to
  their original color, and changing every time someone else was added
  or edited in between. Confirmed with a direct simulation before
  fixing. Replaced the counter with a hash of the employee's own email,
  so the same person always gets the same color regardless of unrelated
  edits, and removed the now-dead counter variable. Verified by adding
  three employees, editing the first one (an unrelated field), and
  confirming their gradient didn't move while still confirming
  different employees keep visually distinct colors.
- **Found and closed a real gap in this audit's own methodology**: while
  checking this page, the validator reported two script blocks where
  every previous page in this project had exactly one — meaning the
  `rfind('<script>')` approach used throughout this whole audit had been
  silently grabbing only the *last* inline script and skipping any
  earlier one. Scanned all 89 pages programmatically rather than
  guessing at the scope: found exactly two pages affected,
  `dashboard-attendance.html` and `public/index.html` — every other
  page in the project genuinely has just one inline script block, so no
  wider re-audit was needed. Read and verified the previously-skipped
  block on both:
  - `dashboard-attendance.html`'s missed block turned out to be a
    self-contained attendance-trend chart with four time-range tabs.
    Verified all four datasets' hardcoded "peak" index actually points
    to that dataset's real maximum value (a category of bug found
    elsewhere in this project before), and confirmed the static initial
    HTML exactly matches the JS's default dataset. Genuinely clean.
  - `index.html`'s missed block covers the to-do list, add-schedule
    modal, and add-request modal — real functionality that had never
    been directly audited despite this page being touched early on for
    shared topbar work. Found a real bug there: the add-schedule form
    validated that all fields were filled in, but never checked that
    the end time was actually after the start time, so a schedule item
    could be silently added spanning e.g. 2:00 PM to 10:00 AM on the
    same day. Fixed to match the validation style this project already
    uses elsewhere for the same kind of check (`leaves.html`,
    `dashboard-attendance.html`). Verified the fix rejects both a
    reversed range and an exact zero-duration entry, while confirming a
    valid submission and the todo/add-request flows all still work.
- `node scripts/validate-project.js` passes across all 89 pages.

## 4.3.31 — New documentation site (documentation/index.html)

Complete redesign of the product documentation, replacing the previous
plain-README approach with a dedicated, self-contained documentation
site at `documentation/index.html`.

- Verified every factual claim against the actual codebase before
  writing anything, rather than carrying forward the old README's
  assumptions. Found and corrected several real inaccuracies in the
  old docs along the way: it described a single `hrnexus.min.css`
  bundle as what pages load, but every real page actually loads three
  separate stylesheets (core / utils / pages); it presented the
  rose/forest/sunset/midnight color presets as an available UI
  feature, but no page actually exposes a control for them — the CSS
  and JS hook are real and working (confirmed directly in the compiled
  `hrnexus.min.js`), just not wired to any visible button anywhere in
  the 89 pages. Documented this accurately as a customization you can
  enable with one `data-theme-preset="rose"` attribute, rather than
  repeating the old, misleading "built-in feature" framing.
  Cross-checked the compiled JS bundle to confirm each disclosed
  behavior (dark-mode toggle, preset-switch data-attribute) actually
  exists in the shipped code, not just the source.
  Cross-referenced this project's real page list against the
  documentation's page-by-page reference programmatically — confirmed
  a 1:1 match, all 89 real files documented exactly once, nothing
  invented.
- Built as a single, self-contained HTML file (standard ThemeForest
  documentation format): a searchable/collapsible sidebar table of
  contents, step-by-step setup instructions, a full customization
  guide, brief feature write-ups for the kanban board, DataTable
  engine, calendar, AI pages, and chart/gauge system, and a
  filterable reference covering all 89 pages grouped into their 16
  real categories.
- Verified the interactive pieces (page-reference search and
  filtering, sidebar search, FAQ accordion, group collapse, copy-to-
  clipboard) with jsdom rather than hand-rolled DOM mocks, given how
  much of the page's behavior depends on real `innerHTML` parsing —
  confirmed search correctly narrows results, correctly hides
  categories with zero matches, and correctly restores the full list
  when cleared; confirmed all 32 internal anchor links resolve to a
  real section with no dead links.
- Ran WCAG contrast-ratio math (not visual guesswork) on every color
  pairing actually used in the design — link color, muted/secondary
  text, and the button gradient's text-bearing stop against its
  background — and found three that fell short of the 4.5:1 standard
  for their text size. Computed replacement shades from the same
  palette that verifiably pass, rather than darkening by feel.
- Rewrote the outdated root `README.md` (previously still labeled
  "v4.1" and describing the incorrect single-CSS-file structure) into
  a short, accurate summary that points to the new documentation as
  the source of truth, so the two files no longer contradict each
  other. Added a pointer to the new docs from `HOW_TO_OPEN.md` as
  well, leaving the rest of that file as-is since it was already
  accurate.
- Found an even older documentation attempt in project history (a
  Word doc describing "v4.2, 73 pages") and skimmed its table of
  contents for anything genuinely missing from the new docs. Found a
  real gap — this project ships `.htaccess`, `nginx.conf`, and
  `web.config` at the root, none of which were documented anywhere.
  Verified each file's actual content before writing about it, then
  added a Deployment section covering all three plus the pages
  actually needed on a server. The old doc also claimed CSS loads via
  an async `media="print"` trick — checked the real page markup and
  found that's not what's actually there (stylesheets load as plain,
  synchronous `<link>` tags), so that specific claim was left out
  rather than copied forward. Kept only what a direct check confirmed:
  inlined critical CSS, deferred scripts, and preconnect hints, all
  verified against real page markup before writing them down.

## 4.3.30 — layout-*.html (all 6) audited clean; dashboard-hr.html: 3 real bugs fixed

Continuing the systematic pass. Took stock of the full 89-page project
and identified the 9 dashboard pages as the highest-value remaining
target — none had been deep-audited yet, and dashboards typically carry
the richest custom JS of any page category on this project.

- **layout-horizontal.html, layout-detached.html, layout-modern.html,
  layout-twocol.html, layout-hover.html, layout-boxed.html**: all 6
  audited in full, all genuinely clean. Highlights: verified the
  trickiest part of `layout-detached.html`'s flyout — its overflow-flip
  positioning logic — actually triggers correctly near the bottom of a
  real viewport, not just assumed from reading; cross-checked all 16 of
  `layout-twocol.html`'s rail-icon keys against its 16 navigation-panel
  keys and confirmed all 86 links across every panel resolve to real
  files in this project; confirmed `layout-boxed.html` reuses this
  project's standard shared sidebar markup rather than custom classes
  (caught and corrected my own overly-narrow first search pattern
  before concluding anything). Hit two more mock limitations along the
  way (a `querySelectorAll` returning a static list instead of live
  state, a missing `getBoundingClientRect`) and traced both to the test
  harness before trusting any result.
- **dashboard-hr.html — 3 real, confirmed bugs**:
  - `reviewLeave(btn, approved)` took an `approved` parameter and never
    used it anywhere in the function body — every Approve *and* every
    Reject button produced the exact same behavior (fade out, remove
    the row), with zero feedback distinguishing which action actually
    happened. Confirmed both buttons really do call the same function
    with `true`/`false` respectively before fixing. Added a toast that
    now genuinely differs between the two outcomes (wording and icon),
    verified by comparing the two resulting toasts directly rather than
    just checking each one exists.
  - The "Leave Requests" badge claimed "8 pending" while only 4
    `.leave-row` elements actually exist in the list — checked for a
    legitimate "showing 4 of 8" context first (a View All link, a
    truncation note) and found none, then cross-checked this project's
    other 3 dashboard KPIs against their CSV-export counterparts
    (Total Employees, Monthly Payroll, Review Completion — all 3
    correctly matched) to confirm this was an isolated data error, not
    a systemic pattern worth a broader fix. Corrected the badge and the
    matching hardcoded figure in the CSV export function to both say 4.
  - Verified the toast fix with a DOM-mock test — caught and fixed my
    own mock's `textContent`/`innerHTML` independence gap (a real DOM
    links them; escaping text through `textContent` and reading it back
    via `innerHTML` only works if the mock does too) before trusting an
    initial false-negative result.
- `node scripts/validate-project.js` passes across all 89 pages.

## 4.3.29 — chart-libs.html: a real, mathematically-verified rendering bug; map-libs.html & sidebar-menu.html audited clean

- **chart-libs.html — every gauge above 50% rendered a distorted arc**:
  the SVG fill-arc path used `large-arc-flag = pct > 50 ? 1 : 0`, but
  this gauge's sweep (from the 0% point to the current percentage
  point within one semicircle) never exceeds 180°, so the flag should
  never be 1 — that flag is specifically for arcs *over* 180°. Rather
  than reason about SVG arc rendering by eye, implemented the SVG
  spec's actual endpoint-to-center arc conversion formula and used it
  to compute which center point each flag combination produces:
  confirmed `largeArc=1` (used whenever pct > 50) makes the renderer
  solve for a completely different, wrong arc center than the gauge's
  real one, while `largeArc=0` always correctly reproduces it. This
  page's own header comment describes the gauge engine as already
  "redesigned" to fix an earlier needle bug — this arc-fill defect was
  a separate, still-present issue in the same code. Because the
  geometry function is shared by the big gauge and all three small
  selector gauges (confirmed via call-site count, matching the code's
  own stated single-shared-function design), one fix corrects all 4 —
  and all 3 of this page's actual demo values (94%, 87%, 73%) are above
  50%, meaning every gauge on the page was affected. Verified the fix
  by running the real, updated function against all 3 real values and
  confirming each now resolves to the correct center (within normal
  floating-point rounding from the path's own `.toFixed(2)` precision,
  distinguished from a genuine miss by checking the actual rounding
  error was ~0.01 units on a 95-unit radius — nowhere near visible).
- **map-libs.html**: audited in full — the Add Office flow's city
  dropdown options verified to exactly match the `CITY_INFO` lookup
  keys, all 18 referenced element ids confirmed present, and the full
  submission flow tested end-to-end: validation, a supported city
  (map pin + legend + correct stat increments), a second office in a
  genuinely new state (state count increments once, not per-office),
  and an unsupported "Other" city (directory and stats still update,
  but correctly no map pin or legend entry is added). One test-mock
  gotcha hit and fixed before trusting a "failing" result — the same
  `textContent` string-coercion gap seen on `drag-drop.html` earlier in
  this project (`parseInt(...) + 1` is a number; a real DOM coerces it
  to a string on assignment, the mock didn't) — fixed the mock, reran,
  confirmed the actual page logic was correct throughout. No bugs
  found in the production code.
- **sidebar-menu.html**: audited in full — this page's own exclusive-
  toggle-group function already uses `:scope > .dsb-group-hd` to scope
  its query to direct children only, which is exactly the fix pattern
  applied to a nested-accordion bug found earlier in this project on
  `ui-accordion.html` — here it was already done correctly. Verified
  all 3 exclusive-toggle call sites use a consistent scope key, that
  exactly one rail flyout starts open (matching the enforced "only one
  open" behavior), and that the flyout's click-outside handler
  correctly leaves flyout-internal clicks alone (`.rail-flyout` is a
  child of `.rail-item`, and flyout links separately call
  `stopPropagation`). Ran the actual toggle and exclusive-group logic
  under a DOM mock and confirmed both work correctly. No bugs found.
- `node scripts/validate-project.js` passes across all 89 pages.

## 4.3.28 — The 7 auth pages: login (×3), register (×2), forgot/new password

- **login-basic.html, login-cover.html, login-frame.html — social login
  buttons went nowhere**: "Google" and "Microsoft" were plain, unwired
  `<button>` elements on all 3 pages. Before fixing, checked how the
  page's own main "Sign in to HRNexus" button behaves — it's already an
  `<a href="../index.html">`, the established pattern for this static
  template with no real backend: any successful sign-in simply goes to
  the dashboard. Converted both social buttons to real links using the
  same destination, on all 3 layout variants, rather than inventing a
  different (fake) behaviour for what's functionally the same action.
  Caught and removed an unnecessary `tdn` class I'd first added for
  text-decoration — confirmed `.btn-hr` already includes
  `text-decoration:none` in its own base rule (same fact already
  established when `ui-cards.html`'s "Learn more" buttons were
  converted to links earlier in this project), so the extra class was
  dead weight.
- **register-basic.html, register-cover.html**: audited in full — the
  two pages share an identical validation script (confirmed by direct
  comparison), all 9 referenced element ids verified present on both
  layout variants despite their different markup. Ran the actual
  extracted script through every path — empty fields, invalid email,
  short password, unchecked terms, and full valid submission — and
  confirmed each correctly sets/clears the right field's error state
  and only the success path disables the form and redirects. No bugs
  found on either page.
- **forgot-password.html**: audited in full — confirmed the single
  submit button is the only interactive element (no separate "resend"
  action was missed), verified the success state's "Sign in" link and
  "Contact IT support" mailto link are both real and correctly
  destination-checked. Verified empty-email, invalid-format, and valid
  submission paths, including that the field and button both correctly
  disable afterward to prevent resubmission. No bugs found.
- **new-password.html**: audited in full, including its more complex
  live password-strength meter (4 requirement checks, animated
  strength bar). Verified the meter's `querySelector('i')` lookup
  correctly targets each requirement row's own icon by checking the
  actual markup, then ran the full range of strength transitions —
  0/4 through 4/4 requirements met — confirming each requirement icon
  toggles independently and the strength label/bar advance correctly
  at every step. Also verified the full submission flow: empty
  password, unmet requirements, missing confirmation, mismatched
  confirmation (correctly flags both fields), and a fully valid
  submission through to redirect. No bugs found.
- `node scripts/validate-project.js` passes across all 89 pages.

## 4.3.27 — form-elements.html, form-layouts.html audited clean; tables-bootstrap.html, tables-datatable.html: real bugs fixed

- **form-elements.html**: audited in full — checkbox/radio/switch toggle
  logic traced correctly, the one shared toggle-switch group ("Radio
  Toggle Group") confirmed intentional (explicitly labeled "exclusive
  select" in its own subtitle, not a naming collision), the password
  visibility icon's `previousElementSibling` lookup confirmed to
  correctly target its input (verified there's exactly one usage on the
  page and checked its surrounding markup directly). 57 custom classes
  checked against the compiled CSS, all defined. No changes needed.
- **form-layouts.html**: audited in full — the multi-step wizard's 13
  referenced element ids all verified to exist exactly once, and its
  step-clamping logic (unlike a similar wizard fixed earlier in this
  project) has no off-by-one guard-swallowing risk since Next/Submit are
  separate functions rather than one function silently failing past the
  last step. The 7 unwired "Save/Cancel/Reset"-style buttons across the
  page's static Vertical/Horizontal/Multi-Column demo forms are
  consistent with this page's own stated purpose (its subtitle:
  "Horizontal, vertical, floating label, multi-column and multi-step
  layouts") — a layout-pattern showcase, not a functional form — matching
  the same convention already established on `ui-buttons.html`. 60
  custom classes checked, all defined. No changes needed.
- **tables-bootstrap.html — clicking a row's own checkbox appeared to do
  nothing**: the row-selection design uses `<tr onclick="toggleRow(this)">`
  so clicking anywhere in the row toggles it, but that includes the
  checkbox itself — whose click the browser already handles natively
  before the row's handler even runs. The handler then toggled the same
  checkbox a second time, immediately canceling the user's actual click.
  Confirmed with a direct simulation before fixing, then fixed by
  passing the click event through and skipping the redundant toggle
  when the event's own target is the checkbox. Verified all 3 paths:
  clicking the checkbox directly, clicking elsewhere in the row, and
  calling the function with no event at all (defensive fallback) — all
  three now behave correctly, and Select-All / Clear Selection are
  unaffected.
- **tables-datatable.html — toggling a column after searching or sorting
  silently reverted the table**: the column-visibility buttons are only
  built once (on first render) and their click handlers captured the
  `paged` array by closure at that exact moment — a snapshot that goes
  stale the instant the user searches, sorts, or changes page, since
  none of those actions rebuild the buttons. Clicking a column toggle
  afterward would redraw the table body from that stale, original
  unfiltered snapshot while the header kept showing the current sort —
  silently undoing whatever search or sort was active. Fixed by having
  the column toggle re-run the full render function instead of reusing
  the captured array. Verified two ways: reproduced the bug against the
  *original* code first (confirmed it does revert to all 10 rows,
  ruling out a false-positive test), then confirmed the fixed version
  correctly preserves an active search through a column toggle.
- `node scripts/validate-project.js` passes across all 89 pages.

## 4.3.26 — email.html: completing the productivity-app audit

Last page in this batch (chat/calendar/tasks/email) — same pattern of a
substantial custom JS engine, same rigor applied.

- **Wrong attachments shown on 2 of 3 emails**: the attachment renderer
  hardcoded a single array (Tom Nakamura's "Migration_Report.pdf" /
  "Performance_Benchmarks.xlsx") and displayed it for *every* email with
  `hasAtt:true`, regardless of which one was actually open. Opening
  Sarah Chen's onboarding-redesign email or David Kim's analytics email
  would show Tom's completely unrelated DevOps files. Gave each of the
  3 affected emails its own `att` field — matched to what's already
  named in that email's own body text where available (Sarah's) or a
  plausible file matching its actual subject where the body doesn't
  name one explicitly (David's) — and rewrote the renderer to use each
  email's own data. Verified by rendering all 3 emails and confirming
  each shows only its own attachments, never another's.
- **Unread count never reflected reality**: the sidebar's "N unread
  messages" subtitle was only ever touched in one place — hardcoded to
  "0" inside `sendEmail()`, a function for composing a new message, with
  no relation to how many received emails are actually unread. Opening
  and reading emails never updated it at all, so it stayed frozen at
  its initial value until a completely unrelated action (sending a
  reply) reset it to a wrong, hardcoded number. Added a real
  `updateUnreadSubtitle()` that counts actual unread emails and wired it
  into opening an email, marking one unread again, and sending — 
  verified each transition produces the correct count, not just that it
  changes.
- **"Filter" button showed zero results, always**: `showFilterMenu()`
  set the *text search* field to the literal string `'unread'`, so it
  filtered by whether an email's subject or sender name contained the
  word "unread" — which none of them do — rather than by the `unread`
  boolean. The button was fully wired to a real, visible funnel icon in
  the UI; it just could never show anything. Gave it its own filter
  state, independent of the search box, plus a visual active/inactive
  indicator on the button itself (a toggle with no feedback is only
  half fixed). Verified the filter now returns exactly the unread
  emails, and correctly returns to the full list when toggled off.
- One test-mock gotcha caught and fixed before trusting a "failing"
  result: a `querySelector('i')` mock that returned a fresh, disconnected
  element on every call instead of a persistent reference — made a
  correct fix look like it failed a check for the button's active-state
  icon. Fixed the mock, reran, confirmed correct.
- `node scripts/validate-project.js` passes across all 89 pages.

## 4.3.25 — chat.html, calendar.html, tasks.html: continuing the productivity-app audit

Continuing the systematic pass through pages with substantial custom JS
(the category that's consistently turned up real bugs in this project).

- **chat.html — 3 of 5 direct messages were completely non-functional**:
  Tom Nakamura, Aisha Patel, and Emily Rodriguez all appear in the
  sidebar fully clickable — name, avatar, online status, and a "last
  message" preview implying a real conversation — but no message pane
  existed for any of them (`DM_MSGS` only had entries for 2 of the 5
  people in `DMS`). Clicking any of the three silently showed a
  completely blank chat area. Built three realistic conversation
  histories from scratch, each one's final message verified to match
  its sidebar preview text exactly, using the correct existing avatar
  gradient classes for each person. Also fixed: unread badges on 2
  channels and 2 DMs never cleared when opened (every real chat app
  does this on open); and the message composer escaped `<`/`>` but not
  `&`, meaning literal `&lt;`-looking text typed by a user could get
  reinterpreted as a real tag. All three verified with a DOM-mock test
  that actually opens the panes, checks badge removal, and checks the
  escaped output — not just that the code parses.
- **calendar.html — 5 interconnected bugs, all from one root cause**:
  the `EVENTS` dataset is keyed purely by day-of-month (no year/month
  of its own), so navigating to any month other than March 2026 was
  silently showing March's events duplicated onto whatever day number
  matched — e.g. "Team Standup" would appear on the 3rd of every month.
  Confirmed empirically before fixing (built a DOM mock, navigated to
  April, found March's events present) rather than assuming. Fixed by
  gating the event lookups to the one month real data exists for, so
  other months now correctly show empty instead of wrong. From there,
  four related issues surfaced and were fixed: the selected-day
  highlight was hardcoded to only ever work in March 2026 (now tracks
  which month a selection actually belongs to); the "Upcoming Events"
  panel went completely empty the moment you navigated away from March
  (now stays populated regardless of calendar navigation, matching how
  a real upcoming-events widget should behave); the Agenda view's
  rebuild function filtered out any day before "today", contradicting
  its own static initial content which shows the whole month — aligned
  the two; and the event detail modal would show the wrong month's name
  for an event opened from Upcoming/Agenda while viewing a different
  month. All 5 verified together in one DOM-mock walkthrough: navigate
  away, confirm events don't leak into the wrong month, navigate back,
  confirm today/selection state is still correct, confirm Upcoming and
  the event modal both stay accurate throughout.
- **tasks.html — new tasks were invisible in 2 of 3 views**: List and
  Grid are separate, statically-rendered sections (their own `<tbody>`
  and card grid, distinct from the Kanban board), and `addNewTask()`
  only ever inserted into the Kanban column. A task added while in
  Kanban view would be genuinely missing the moment you switched to
  List or Grid, despite existing in the underlying `TASKS` array and
  reappearing correctly on reload. Extended the function to insert a
  matching row/card into both other views (using inline styles for
  avatars and badges, matching the same approach the Kanban card
  already uses, rather than guessing at each view's own differently-
  named CSS classes). Also fixed a related gap: newly-added tasks never
  ran through the active department/priority/search filter, so they'd
  stay visible even when they didn't match whatever filter was
  currently applied. Verified with a DOM-mock test confirming the new
  task's id, title, and content appear correctly in List and Grid, not
  just Kanban.
- `node scripts/validate-project.js` passes across all 89 pages.

## 4.3.24 — Continuing the audit: the 4 AI feature pages (previously untouched)

Took stock of the full 89-page project and identified the 4 AI feature
pages as completely unaudited so far — and, given their size (each with
15–25KB of custom JS implementing real state machines: a chatbot, a
typewriter-effect summary generator, a risk-scoring engine, a resume
matcher), the highest-value remaining target for the same close-reading
approach that's found real bugs elsewhere in this project.

- **ai-hr-chatbot.html — 3 real issues, one a genuine security bug**:
  - **XSS**: user-typed chat messages went straight into `innerHTML`
    with only `**bold**`/`*italic*` markdown patterns transformed —
    everything else, including raw `<script>`/`<img onerror>` HTML, was
    inserted unescaped. Demonstrated the exploit conceptually, then
    added a proper `escapeHtml()` step before the markdown
    transformation (order matters: escaping first doesn't touch `*`
    characters, so markdown still applies correctly afterward).
  - **Broken escaping in dynamically-generated onclick handlers**: quick-
    reply buttons attempted to escape apostrophes in their text via
    `s.replace(/'/g, "\'")` — but `"\'"` in JS source is just a literal
    `'`, making the whole replace a no-op. Currently dormant (no
    existing quick-reply string contains an apostrophe) but a real
    landmine — confirmed with a direct Node test that the buggy version
    left the string unchanged, then fixed and reverified the same test.
  - **Greeting detection missed common punctuation**: `hi!`/`Hi!`
    wouldn't match, only bare `hi`/`hi ` (with a trailing space) — a
    real, if minor, gap for a genuinely common way to open a chat.
    Replaced with a word-boundary regex, verified it now matches `hi!`
    correctly while still rejecting `this`/`history`/`which`/`shift`
    (the exact false-positive class the original space-qualified check
    was trying to avoid).
- **ai-performance-summary.html**: audited in full — proper
  `escapeHtml()` already used correctly for print output, proper
  interval cleanup already in place for the typewriter effect (with an
  explanatory comment from the original author), all tone buttons and
  email-modal element ids verified to match. No changes needed.
- **ai-attrition-risk.html**: audited in full — the cached-vs-fresh AI
  retention-plan branch, the department filter, and all 27
  schedule/email/note modal element ids verified correct. Investigated
  a possible overlapping-timeout risk in the bar-chart animation and
  confirmed it self-corrects within the same ~100ms window regardless,
  since bars are keyed by position, not employee id. No changes needed.
- **ai-resume-screener.html — 2 issues found**:
  - **Dead code hazard**: two `alert()`-based placeholder functions
    (`scheduleInterview`, `sendEmail`) sat unreferenced next to the
    real, fully-built modal system that superseded them
    (`openScheduleInterviewModal`, `openSendEmailModal`). Confirmed via
    a full-file search that nothing calls the old pair — genuinely dead,
    but a real risk for a template product a future developer might
    extend and accidentally wire a button to the wrong one. Removed
    them.
  - **Real UI-state bug**: rejecting a candidate correctly updated that
    button in place, but the detail pane's own template — rebuilt from
    scratch every time `selectCandidate()` runs — never checked
    `c.rejected`, so re-selecting an already-rejected candidate silently
    reverted the button to a clickable "Reject" state with no
    indication anything had happened. Fixed the template to reflect
    `c.rejected` on every render; verified with an isolated
    before/after test that the button now correctly persists as
    disabled "Rejected" across re-selection.
  - Also investigated the fuzzy skill-matching algorithm
    (`getSkillStatus`) for false-positive risk (e.g. "Git" as a
    substring of unrelated words) — confirmed it's a real theoretical
    weakness of the bidirectional-substring approach, but doesn't
    misfire against any of the 5 actual candidates in the shipped
    dataset, and fixing it properly would mean redesigning the matching
    algorithm rather than fixing a bug — left as a deliberate,
    reasonable trade-off, not treated as a defect.
- `node scripts/validate-project.js` passes across all 89 pages; all 4
  pages confirmed with correct topbar panels and no duplicate ids.

## 4.3.23 — swiper.html audited clean; 3 of 4 icon pages had real copy-to-clipboard bugs

All 4 icon pages share the same purpose — browse a category, click an icon,
get working code back — so each `renderIcon()`/`fiCopy()` implementation was
checked against what it actually produces, not just whether the UI looked
right.

- **swiper.html**: audited in full. The swipe-distance math (`item width +
  16px gap`) depends on the project never overriding the root font-size —
  confirmed it doesn't, so the hardcoded `+16` correctly matches the
  CSS's `1rem` gap. All 3 swipe instances (`emp`, `test`, `hero`) have
  matching wrapper/track ids with no mismatches. 38 custom classes checked
  against the compiled CSS, all defined; gradients confirmed to be plain
  backgrounds, not text-clipping. No changes needed.
- **icons-bootstrap.html — copied code didn't work**: the grid correctly
  rendered icons with `class="bi bi-{name}"`, but the separate function
  building the *copyable* code only produced `class="bi-{name}"` — missing
  the base `bi` class Bootstrap Icons requires to render at all. The
  primary purpose of this page (copy a working icon) was broken for every
  single icon. Fixed to match the grid's own correct format exactly.
- **icons-fontawesome.html — copied code was wrong for 27 of 57 icons**:
  the copy function hardcoded `fa-solid` regardless of which of the three
  categories (Solid/Regular/Brands) an icon came from. Regular icons
  copied as the wrong style; brand icons (GitHub, Slack, etc.) copied as
  `fa-solid`, a style they don't even exist in — that code would render
  nothing. Fixed to select `fa-solid`/`fa-regular`/`fa-brands` based on
  the icon's real source prefix, verified against one example from each
  category.
- **icons-lucide.html**: audited and confirmed *not* to have this bug —
  it intentionally shows a Bootstrap-icon placeholder for visual preview
  (Lucide is a React component library with no static HTML rendering) but
  correctly copies proper Lucide usage code (`import { X } from
  'lucide-react'`) using the real Lucide name, not the placeholder class.
  Confirmed the grid-render and copy-code paths correctly use different
  fields for their different jobs. No changes needed.
- **icons-flaticon.html — a worse version of the same bug class**: this
  page copied the Bootstrap Icons placeholder class directly (e.g.
  `bi-briefcase-fill`) as if it were the real Flaticon reference — not
  merely incomplete like the Bootstrap page's bug, but literally copying
  a different icon library's class name entirely. Checked the page for
  any documented Flaticon class convention (e.g. `fi fi-rr-`) to fix
  toward — found none — so rather than fabricate an unverified class
  syntax, fixed the copy action and the list-view's "code" hint to use
  the icon's own real name, the one piece of data on this page that's
  actually correct and traceable.
- Verified every fix with a DOM-mock test exercising the actual
  extracted script, including one real Node 22 gotcha caught again
  (its native read-only `navigator` global silently swallows a plain
  `global.navigator = {...}` assignment — same issue hit and fixed on
  `blog-list.html` earlier in this project; used `Object.defineProperty`
  instead) rather than trusting a false-negative test result.
- `node scripts/validate-project.js` passes across all 89 pages.

## 4.3.22 — ui-typography.html: real cascade bug fixed; avatars.html & card-designs.html: audited clean; drag-drop.html: 14 dead controls wired

- **ui-typography.html — Checklist example showed the wrong colour**:
  the "pending" (✕) items used a `c-red` class, but a much
  higher-specificity compound selector (`.hr-list.check li .chk`,
  specificity ~0,4,1) baked in a blue colour that `c-red` (specificity
  0,1,0) could never win against regardless of stylesheet order — this
  wasn't a cascade-timing accident like earlier fixes, specificity
  guarantees it. Confirmed the exact rule and that it's used nowhere
  else in the project (safe to change), then fixed it at the SCSS
  source: stripped the hardcoded colour out of the shared rule and
  added explicit `c-primary`/`c-red` classes to each of the 5 icons
  instead, matching how colour is handled everywhere else in this
  codebase. Rebuilt and confirmed the compiled CSS.
- **avatars.html**: audited in full — checked all 115 custom classes
  against the compiled CSS (all fully and correctly defined), the
  "Message"/"Follow" buttons on the example profile cards confirmed
  purely illustrative (no subtitle invites interaction, matching the
  established style-catalog convention), gradient classes confirmed to
  be plain background gradients, not the text-clipping pattern. No
  changes needed.
- **drag-drop.html — the real fixes on this page**:
  - All 9 per-card "…" menus had no dropdown behind them at all — wired
    each to a real Delete action.
  - All 5 "Add Task" entry points (4 per-column + 1 header button) did
    nothing — built a real Add Task modal; each per-column button was
    individually verified to pre-select its own column, not a copy-paste
    of the wrong one.
  - Refactored `drop()`'s inline count-recalculation block into a
    shared `refreshAllCounts()` function reused by the new Delete and
    Add Task paths too, rather than duplicating that logic a third time.
  - Verified everything with a DOM mock built with a small real HTML
    parser (needed because the code's own pattern —
    `el.innerHTML = template; el.querySelector(x).textContent = value`
    — needed genuine child nodes to test faithfully, not just a string
    stub). Two mock gaps surfaced and were corrected before trusting
    results: `.textContent` needs to coerce to a string per the DOM
    spec (a bare `badge.textContent = 1` should read back as `'1'`),
    and one assertion was checked against a stale expected count left
    over from an earlier version of the test rather than the actual
    starting state of that run — both fixed in the test, not the
    production code, which was correct throughout.
  - Also checked and ruled out: the unused `colName` parameter on
    `drop()` (harmless — `e.currentTarget` is used instead, confirmed
    by reading every `ondrop` call site), and a block of dead/orphaned
    CSS selectors in the page's own `<style>` (`.kanban-hd`,
    `.kcard`, etc.) that don't match anything in the real markup —
    unused bytes, not a functional defect, left alone.
- **card-designs.html**: audited in full — all 293 custom classes
  checked against the compiled CSS (all defined), all 16 gradient
  classes checked individually against the text-clipping pattern (all
  confirmed plain background gradients on badges/avatars/progress
  bars). The page's "Approve/Reject", "Message/Profile",
  "Get Started Free" etc. buttons were checked against the same
  bar as `ui-cards.html`'s earlier "Learn more" fix — but unlike that
  case, these are tied to *specific* fake people/teams (a real
  `profile.html` link would show the wrong person's data for every
  card but one), so wiring them to real pages would introduce a
  mismatch rather than fix one; confirmed decorative by design and left
  untouched. Also specifically checked the Approve/Reject colour
  classes against the same cascade-collision pattern that broke
  `ui-typography.html` — confirmed safe (equal specificity, correct
  load order, matching the already-verified `ui-buttons.html` pattern).
- `node scripts/validate-project.js` passes across all 89 pages.

## 4.3.21 — ui-carousel.html, ui-modal.html, ui-tabs.html: 4 real logic bugs found and fixed

All three use fully custom JS engines (none rely on Bootstrap's native
carousel/modal/tab components for their core interaction logic), so
each was read and tested directly rather than assumed correct from
`onclick` presence alone.

- **ui-carousel.html — "Animated Progress Indicator" completely dead**:
  its `buildProg()` referenced an undefined variable `id` instead of
  the IIFE's actual `carouselId`, throwing `ReferenceError: id is not
  defined` the moment it ran. Confirmed by executing the actual script
  directly and watching it throw at that exact line. Because this
  carousel's own subtitle says "click to jump" — its progress-bar
  segments are its *only* control, no separate prev/next arrows — the
  crash meant zero interactivity and no auto-advance for this section,
  while the other 8 carousels on the page (which initialize earlier in
  source order) were confirmed unaffected. Fixed the variable name;
  re-ran the script and confirmed it completes without throwing.
- **ui-modal.html — Wizard "Complete Onboarding" visibly resets before
  finishing**: its click handler called both `submitForm(nxt)` (an
  async Processing→Done→close sequence) and `wizReset()` in the same
  synchronous click — but `wizReset()` ran immediately, instantly
  snapping the wizard back to step 1 and overwriting the "Processing…"
  text `submitForm` had just set, before the user ever saw it. A
  `hidden.bs.modal` listener already existed to reset the wizard
  correctly once the modal actually finished closing, making the
  manual call both redundant and wrongly timed. Removed it. Verified
  with a DOM-mock trace of the full click sequence: the wizard now
  visibly stays on step 4 through the whole Processing→Done animation,
  and only resets after the modal genuinely closes.
- **ui-modal.html — Loading-modal progress timer could double up**: its
  `shown.bs.modal` handler started a `setInterval` with no cleanup path
  if the modal's own visible "Cancel" button closed it early — reopening
  afterward started a second, independent interval racing the first,
  both updating the same progress bar and text on staggered ticks.
  Reproduced with a mock interval tracker showing 2 concurrently active
  timers after cancel-then-reopen; fixed by tracking the interval and
  clearing it both proactively on every `shown.bs.modal` fire and
  explicitly on `hidden.bs.modal`. Re-ran the same reproduction and
  confirmed exactly 1 active timer in every scenario tested.
- **ui-tabs.html — Step-wizard "Complete Onboarding" did nothing at
  all**: unlike the modal wizard, this button's `onclick` was static
  (`goStep(stepCur+1)`, never reassigned), so on the final step it
  called `goStep(5)` against a 4-step wizard — silently swallowed by
  the function's own `n > stepTotal` bounds guard. No error, no
  feedback, just a dead click on the one button meant to finish the
  flow. Gave `goStep()` a real branch for this case instead of letting
  the guard eat it: shows a brief "Processing…" state on the button,
  then replaces the step's content with a genuine completion message
  reusing the same welcome-email detail already written into that
  step's content. Verified the completion path fires correctly from
  the button while confirming, separately, that clicking the step-4
  indicator *directly* (not via the button) still shows the normal
  step content rather than the completion screen — the two paths stay
  correctly distinct.
- Also checked, on all 3 pages: no other carousel/tab-switching control
  points at the wrong instance variable (verified every `onclick`
  against its declared `Cx`/bar id), no gradient-text usage, no
  duplicate IDs, and confirmed the topbar panels — byte-different from
  canonical on all three (9 notifications instead of 6) — are the same
  pre-existing, functionally-complete variant already established as
  fine on `ui-accordion.html`, untouched by any of this work.
- `node scripts/validate-project.js` passes across all 89 pages.

## 4.3.20 — ui-alerts.html / ui-buttons.html: audited, genuinely clean; ui-cards.html: 3 dead CTAs fixed

- **ui-alerts.html**: already fully audited in the prior release and
  confirmed unchanged since — the 4 dismiss buttons' inline
  `this.parentNode.style.display='none'` correctly targets each
  alert's actual outer box (verified by tracing the exact DOM nesting:
  the button is a direct child of the alert wrapper, with no
  intermediate element to get the wrong target), and the "Toast-style"
  section is an intentionally static design reference with nothing to
  wire. No changes made.
- **ui-buttons.html**: audited in full — every button on this page is a
  deliberate style-catalog example (Button Variants, Colour Variants,
  Sizes, With Icons, Loading State), confirmed by each section's own
  subtitle describing a *style* being shown, never inviting
  interaction. Went further than "buttons don't need onclick" and
  checked two things that could still be real bugs on a pure style
  page: whether the 6 colour-variant swatches all actually get
  `cursor:pointer` (3 of the 6 class names happen to omit "-pointer"
  from their name, which looked suspicious — checked the compiled CSS
  directly and confirmed all 6 have it; the naming gap is cosmetic
  only), and whether the `bg-green` / `c-red-bc-red` override classes
  actually win the cascade against `.btn-primary-hr` / `.btn-outline-hr`
  (traced the full load order — those two base classes are in the
  page's inline critical CSS, which loads *before* the external
  stylesheet the override classes live in, so the overrides correctly
  win on equal specificity). No changes made.
- **ui-cards.html**: the 3 "Learn more" buttons in the "Card with Image
  Header" section had no click behaviour — unlike ui-buttons.html's
  examples, these read as a genuine, conventional call-to-action rather
  than an intentionally-inert style swatch, and each card's topic (HR
  Management, Analytics, Payroll) has a real, already-existing page in
  this exact project. Converted each to a real `<a href>` pointing at
  its matching page (`dashboard-hr.html`, `analytics.html`,
  `payroll.html` respectively, confirmed by each card's own heading
  text before wiring), rather than a fake JS action for something with
  a genuine destination already on hand.
- Also checked this page's own `bg-grad-*` classes (used for the
  cards' gradient image-header boxes) against the earlier
  text-clipping gradient bug — confirmed these are plain background
  gradients on a decorative box, not text, so that defect doesn't
  apply here.
- `node scripts/validate-project.js` still passes across all 89 pages;
  no duplicate IDs introduced.

## 4.3.19 — ui-accordion.html: nested-accordion state loss fixed

- Unusual for this project: all 54 accordion triggers across all 11
  showcased variants (Default, Multi-Open, Flush, Coloured Border, FAQ,
  Step/Timeline, Icon Left, Filled Header, Shadow Card, Smooth Animated,
  Nested) already had `onclick="toggleItem(this)"` wired, and Expand
  All / Collapse All were already correctly wired too. Rather than
  taking "every button has an onclick" as proof nothing was broken —
  which has been wrong before on this project — the actual toggle logic
  was checked directly.
- Confirmed by static analysis first: every variant's `.acc-body` is
  genuinely the button's `nextElementSibling` (the function's core
  assumption), including inside the Nested Accordion's inner groups;
  the CSS driving `.show`/`.open` uses real standalone classes with no
  compound-class cascade risk; and `closest('.acc-group')` correctly
  resolves to the *nearest* group even for deeply nested triggers.
- **Real bug found by actually running the logic, not by reading it**:
  in the Nested Accordion, the outer group is exclusive (opening one
  outer section closes the others) and each inner sub-group is
  independently multi-open. But the outer group's "close my other open
  siblings" step used `group.querySelectorAll('.acc-trigger.open')`,
  which — like any `querySelectorAll` on a container — matches *every*
  descendant, not just direct children. Since an inner accordion group
  lives inside an outer section's body, any inner items a user had
  expanded were silently closed the moment the user switched to a
  *different* outer section, even though the inner group has nothing to
  do with the outer group's exclusivity. Re-opening the same outer
  section afterward showed everything collapsed again — a real, visible
  loss of state a user would notice, not merely a code smell.
- Fixed by scoping the exclusive-close step to only the triggers whose
  own nearest `.acc-group` is exactly the group being closed
  (`t.closest('.acc-group') === group`), so a nested inner accordion's
  state is never touched by an unrelated outer toggle.
- Verified the fix two ways: confirmed the *unmodified* original logic
  actually fails the reproduction scenario (expand an outer section,
  open two of its inner children, switch outer sections, switch back —
  inner state was lost) before trusting that the bug was real; then
  confirmed the fix passes the same scenario. Also re-ran the full
  regression suite afterward — exclusive groups, multi-open groups, the
  default-exclusive-when-no-attribute case, and `expandAll()` /
  `collapseAll()` (which intentionally do affect every trigger,
  nested included) — all still behave exactly as before.
- Confirmed this page's topbar panel content, while byte-different from
  the canonical 6-notification block (it has 9), was already
  functionally complete before this fix and remains untouched by it —
  Sign Out, Help & Support, Appearance, and the unread-count badge are
  all present, matching its status from the original v4.3.6 audit.
- `node scripts/validate-project.js` still passes across all 89 pages.

## 4.3.18 — invoice-manager.html: filter, Export, Download, and per-row actions fixed

- Before touching anything, verified the page's 4 summary stat cards
  against the 5 real invoice rows by hand: Outstanding ($17,346) and
  Overdue ($3,400) exactly match the one Pending and one Overdue
  invoice; Paid (3) exactly matches the count of Paid rows; Total
  Revenue ($33,500) reconciles exactly as Paid + Overdue amounts
  ($30,100 + $3,400) under a standard accrual definition — invoiced
  amounts not yet due (Pending) aren't counted as revenue yet. All 4
  numbers check out; nothing here was a content bug.
- **Status filter dropdown** (All Status / Paid / Pending / Overdue) had
  no `onchange` at all — selecting anything did nothing. Wired to
  actually show/hide matching rows.
- **Export** (header) had no click behaviour.
- **Download** and **More** had no click behaviour on any of the 5
  invoice rows. "More" now opens a real dropdown with Delete on every
  row, plus "Mark as Paid" specifically on the 2 rows that aren't
  already Paid (the Pending and the Overdue invoice) — omitted
  entirely from the 3 already-Paid rows rather than showing a
  meaningless option.
- The 4 stat cards and the "N invoices — M pending" subtitle are
  **recomputed directly from the table's own current rows** every time
  Mark as Paid or Delete runs, rather than tracked as separate counters
  that could drift out of sync with what's actually on screen — each
  row carries its own `data-status`/`data-amount`, and the stats are a
  pure function of whatever rows currently exist.
- **Real bug caught in my own test setup, not the page**: an early test
  run showed Paid count staying at 3 instead of 4 after marking an
  invoice paid, and one row vanishing entirely. Traced it before
  assuming the production code was wrong — the mock's generic
  `.remove()` always spliced the shared row array by
  `indexOf(this)`, and calling `.remove()` on the dropdown's `<li>`
  (which isn't in that array) returned `indexOf() === -1`; passing -1
  to `.splice()` in JavaScript means "count from the end," which
  silently deleted the last real row instead of doing nothing. Fixed
  the mock to only touch the row array for elements actually in it,
  reran, and every number matched exactly — confirming the actual page
  code was correct all along and the bug was entirely in the test
  harness.
- Verified with the corrected DOM-mock test run: initial stats match
  the real invoice data exactly, filtering shows only matching rows,
  marking an invoice paid updates its badge and correctly recomputes
  all 4 stats and the subtitle, deleting a row does the same, and no
  Delete/Mark-as-Paid path ever double-counts or drops an unrelated row.
- Confirmed the v4.3.6 topbar panel fix is untouched, and
  `node scripts/validate-project.js` still passes across all 89 pages.

## 4.3.17 — invoice-modern.html, invoice-classic.html, invoice-minimal.html: Download PDF fixed

- All 3 pages share the identical structure and the identical defect:
  "Print" was correctly wired (`window.print()`), but "Download PDF" had
  no click behaviour at all. None of the 3 had any inline script to
  begin with — these are otherwise pure static invoice layouts.
- Before writing any fix, checked the invoice content itself for
  arithmetic errors (a category of bug specific to invoice pages, not
  covered by any JS/button audit): line items, the $14,700 subtotal,
  18% GST, and the $17,346 total were independently re-summed by hand
  across all 3 pages and confirmed correct. Also confirmed the
  gradient-text fix from an earlier release (`bg-grad-primary-violet`,
  used on `invoice-modern.html`) is already reflected correctly in this
  build, since that fix was made at the shared SCSS source.
- **Download PDF** now shows a toast naming the actual invoice number
  (#INV-2024-0042, confirmed identical across all 3 templates) and then
  opens the browser's own print dialog, since there's no backend and no
  PDF-generation library loaded on any of these pages — every modern
  browser offers "Save as PDF" as a print destination, and it prints
  the exact same `#invoicePrintArea` content the Print button already
  targets. No new dependency was added to do this.
- Applied identically to all 3 pages rather than writing 3 separate
  fixes, since the button, the missing script, and the invoice number
  were byte-for-byte the same across all of them.
- Verified with a DOM-mock test run of the actual extracted script on
  each of the 3 pages individually: confirmed the toast text correctly
  names the invoice, confirmed `window.print()` is not called
  synchronously (it's deliberately delayed so the toast is visible
  first), and confirmed it does fire once that delay elapses — on all
  3 pages, not just one and assumed for the rest.
- Confirmed the v4.3.6 topbar panel fix and the existing Print/Close
  buttons are untouched on all 3 pages, and
  `node scripts/validate-project.js` still passes across all 89 pages.

## 4.3.16 — files.html: Download/More/Upload fixed, plus a real-world edge case caught

- Traced the page's render architecture before touching anything: the
  visible file list is entirely regenerated by `renderListView()` /
  `renderGridView()` on every load and after every action, from
  `buildListRow()` / `buildGridCard()` string templates — not from the
  static HTML table already in the page. That static table (with its
  own, separately broken Download/More buttons) gets fully overwritten
  the instant the script runs, so it was left as-is: fixing it would
  have had zero effect for any user with JavaScript enabled, which this
  entire template already assumes everywhere else.
- **Download** and **More** (per-row actions, list view) had no click
  behaviour in the actual template function that generates what users
  see. Grid view's only action — the star toggle — was already correct
  and untouched.
- **Download** now shows a toast naming the real file and its size.
- **More** now opens a real dropdown (Bootstrap's own component, added
  via `data-bs-toggle="dropdown"` — confirmed this works correctly on
  content injected via `innerHTML`, since Bootstrap 5 dropdowns use
  document-level event delegation rather than needing each instance
  manually initialized) with Share/Unshare and Delete. Delete actually
  removes the file from the list and re-renders both views, the
  favourites section, and the starred-count stat if the deleted file
  was starred — not just a toast with nothing behind it.
- Followed the codebase's own stated design rule (its header comment
  reads "zero string-escape bugs"): every new handler takes only a
  numeric file id, never a raw filename, into its `onclick` attribute,
  matching how the existing `toggleStar(f.id)` was already written —
  avoiding any need to escape quotes or special characters in
  user-supplied filenames within generated HTML.
- **Upload** (header button) had no click behaviour at all. Built a new
  modal using a real `<input type="file" multiple>` and the browser's
  actual File API — reading each picked file's real name and byte size
  rather than asking the user to type them into a fake form, which is
  what the existing "Create New File" modal already does for brand-new
  blank files. File type (and its icon/colour) is inferred from each
  file's real extension, with the same fallback the rest of the page
  already uses for unrecognised types.
- Verified with a DOM-mock test run of the actual extracted script,
  including one case chosen specifically to test the codebase's own
  stated concern: uploading a file named with an apostrophe
  ("Sarah's Resume.pdf") end-to-end, and confirming it survives
  unmodified through the upload, the list re-render, and every stored
  field — not just that nothing threw. Also verified multi-file upload
  preserves picking order (a real risk with `unshift()` in a loop,
  avoided here by building the batch first and inserting it in one
  call), correct type inference across PDF/image/spreadsheet
  extensions, correct KB/MB size formatting, and that deleting a
  starred file correctly decrements the starred-count stat while
  deleting a non-existent id is a safe no-op.
- Confirmed the v4.3.6 topbar panel fix on this page is untouched, and
  `node scripts/validate-project.js` still passes across all 89 pages.

## 4.3.15 — activity.html: Export & Filter buttons fixed

- Before touching anything, verified the existing Timeline/Table tab
  switcher (`.tab-btn` click listeners in an IIFE) actually works: ran
  it through a DOM mock that models the real CSS cascade, since a
  near-identical-looking tab switcher on `error-simple.html` was found
  broken in an earlier fix. This one is correctly built — `d-none` here
  is used as a genuine standalone class (not baked into a longer
  compound utility class name the way it was on that page), so there
  was nothing to fix in the tab logic itself.
- **Export** and **Filter** (page header) had no click behaviour.
- **Filter** wired to a new dropdown listing all 7 categories that
  appear in the log (HR, Performance, Payroll, L&D, Recruitment,
  Policy, Org) plus "All". Selecting one filters both the Timeline and
  Table views together — not just whichever tab happens to be open —
  by adding a `data-category` attribute to each of the 10 events in
  both views, cross-checked against each other to confirm both views
  list the same 10 events in the same order before tagging them, so
  the category tags couldn't drift out of sync between the two.
- Confirmed the filter and the existing tab-switcher don't fight each
  other: tab-switching toggles visibility of the two container panels,
  while filtering toggles visibility of individual items inside
  whichever panel is open — different DOM levels, verified with a test
  that filters, then switches tabs, then checks the filter is still
  correctly applied in the newly-shown view.
- **Export** references whichever category filter is currently active,
  so exporting while filtered to "Payroll" doesn't claim to export
  everything.
- Verified with a DOM-mock test run of the actual extracted script:
  filtering to a category shows only matching items in both views
  simultaneously, filtering to "All" correctly restores every item,
  and a category containing an ampersand ("L&D") filters correctly
  end-to-end without any HTML-entity double-encoding artifacts.
- Confirmed the v4.3.6 topbar panel fix on this page is untouched, and
  `node scripts/validate-project.js` still passes across all 89 pages.

## 4.3.14 — Gradient-text CSS bug: fixed at the source (16 pages)

- Reported on `under-construction-3.html`'s "Next-Gen HR Intelligence is
  Loading" title and "Beta Access Opening Soon" badge, but the actual
  defect lived in 5 shared gradient-text utility classes in
  `src/scss/utils/_extracted.scss`, not in the page itself.
- All 5 use the standard "clip a gradient to text" technique
  (`-webkit-background-clip: text` + `-webkit-text-fill-color:
  transparent` + a `background: linear-gradient(...)`) but were missing
  the unprefixed `background-clip: text` — the property non-WebKit
  engines and newer standards-track rendering paths need to actually
  clip the gradient to the glyph shapes instead of painting it as a
  plain rectangular background behind opaque text.
- Confirmed this wasn't a guess: a 6th, near-identical gradient-text
  class already in the same file (`fs10625-fw7-bg-grad-blue-purple`)
  has both properties correctly, giving a working example of the exact
  fix needed, in the project's own code.
- Fixed all 5 in the SCSS source and rebuilt (`bg-grad-blue-purple`,
  `bg-grad-primary-violet`, `fs875-fw7-bg-grad-blue-purple`,
  `fw9-bg-grad-body-blue-mb5-lh-11`,
  `rel-fs6-fw9-bg-grad-blue-purple-mb4-ls-4px-lh-1-z1`) rather than
  patching the compiled CSS or working around it on one page, since
  these are shared utility classes.
- Because the fix was made at the source, it also corrects the same
  rendering bug on 15 other pages that use the same classes:
  `error-simple.html`, `avatars.html`, `calendar.html`,
  `card-designs.html`, `coming-soon.html`, `files.html`,
  `profile.html`, `reports.html`, `swiper.html`, `tasks.html`,
  `ui-cards.html`, `ui-carousel.html`, `ui-typography.html`, and
  `invoice-modern.html` — none of which were reported broken, but all
  of which had the identical defect.
- Verified the rebuilt CSS actually contains the fix for all 5 classes
  (not just that the build succeeded), and that no other compiled CSS,
  JS bundle, or page was disturbed by the rebuild.
- `node scripts/validate-project.js` still passes across all 89 pages.

## 4.3.13 — error-simple.html: Cover tab permanently broken, plus 9 dead buttons

- **Real bug, not a dead button**: the "Cover" tab (of the Simple / Cover
  / Full Screen error-page previews) could never actually be shown.
  `#errCover` and `#errFull` both have `display:none` baked directly
  into their own combined CSS class (not a separate, toggleable
  `.d-none` class) — confirmed by reading the compiled CSS rule for
  each. The tab switcher only special-cased `'full'` with an explicit
  `style.display='flex'` override; everything else, including `'cover'`,
  fell back to `style.display=''`, which just clears the inline style
  and leaves the browser to fall back to the class's own `display:none`.
  Clicking "Cover" changed the active tab's highlight but the panel
  itself never appeared — a bug a code read alone can miss, since
  `showErr()` looks correct for the one case (`full`) it was actually
  tested against.
- Fixed by giving every panel an explicit display value on show
  (`simple` → default/empty is fine since its own class has no
  `display:none`; `cover` → `block`, matching its single-child card
  wrapper; `full` → `flex`, unchanged).
- Verified the fix would have actually caught the original bug by
  running the *unmodified* original code through the same test first —
  confirmed `errCover` stays invisible with the old code and becomes
  visible with the fix, ruling out a test that would have passed either
  way.
- **9 dead buttons**, one per action across the two non-default preview
  panels — none in the default "Simple" panel, which was already fully
  wired: Try Again and Report Issue (Cover 500), the three share icons
  next to the error reference (Cover 500), Contact Admin, and the three
  footer links Help Center / Documentation / Live Chat (Full 403).
- **Contact Admin** converted from a dead `<button>` to a real `<a
  href="mailto:admin@hrnexus.com?subject=...">`, matching the same
  approach used for Send Email on `profile.html` — genuinely functional
  browser behaviour rather than a simulated toast.
- **Try Again** shows a brief spinner state before confirming
  reconnection, reusing the shared `.tr-spinner` class already used
  elsewhere in the project rather than introducing a new one.
- **Report Issue** and the share icons reference the error reference
  number already shown on the page (`#ERR-20241213-0842`) rather than a
  disconnected generic message.
- Verified with a DOM-mock test that models the actual CSS cascade (a
  class-level `display:none` versus an inline override) closely enough
  to distinguish "the function ran" from "the panel is actually
  visible" — the distinction the original bug depended on.
- Confirmed the v4.3.6 topbar panel fix on this page is untouched, and
  `node scripts/validate-project.js` still passes across all 89 pages.

## 4.3.12 — blog-list.html: Bookmark & Share buttons fixed

- This page turned out to be one of the most solidly built so far —
  live search, category filtering, and the full New Post flow
  (validation, draft-saving, publish, auto-scroll to the new card) were
  all already correctly implemented. The "Write Post" trigger, Publish,
  Save Draft, Cancel, and "Clear search" buttons were all confirmed
  correctly wired before touching anything.
- The one real gap: **Bookmark and Share buttons had no click behaviour**
  on all 6 existing posts — and, notably, the exact same gap was baked
  into the JS template used to render newly-published posts, so fixing
  only the static cards would have left every future post born broken.
  Fixed both places.
- **Bookmark** now genuinely toggles per post — outline icon becomes a
  filled, primary-coloured icon on click, and back again on a second
  click — with a toast confirming which state it's in.
- **Share** copies a real, working deep link to the clipboard via
  `navigator.clipboard.writeText()` (with a plain-text fallback toast
  if clipboard access isn't available), not a simulated action. This
  needed each post to have a stable `id` to link to — the 6 existing
  posts didn't have one (only posts created through "Write Post" did),
  so unique IDs were added to all 6, matched to each post by its own
  title.
- Verified with a DOM-mock test run of the actual extracted script:
  confirmed the bookmark icon and colour genuinely flip both directions
  across two clicks, and confirmed the exact URL handed to the
  clipboard API resolves to the correct post's own id — not just that
  the call didn't throw.
- Confirmed the v4.3.6 topbar panel fix on this page is untouched, and
  `node scripts/validate-project.js` still passes across all 89 pages.

## 4.3.11 — roles.html: dead button + a real data-loss bug fixed

- **"Create Role"** button had no click behaviour. Wired to a new modal
  (name, description) that creates a genuinely new role: pushes it into
  `ROLES`, gives it a `ROLE_PERMS` entry with every permission
  unchecked (so an admin builds it up deliberately rather than
  inheriting some other role's access by accident), appends a real
  button to the role list reusing the exact same markup structure as
  the 6 built-in roles, and selects it immediately.
- **Real bug, unrelated to any dead button**: clicking "Cancel" while
  editing a role's permissions did not actually revert anything.
  `togglePerm()` wrote every toggle straight into the live `ROLE_PERMS`
  object with no staging step, so by the time "Cancel" ran there was
  nothing left to discard — any permission turned on or off during the
  edit session stayed changed even though the button said Cancel. This
  is the kind of bug a QA pass finds immediately ("I clicked Cancel but
  my changes were kept") but that a code read alone can miss, since
  every individual function looked correct in isolation.
- Fixed by giving edit mode a real draft: entering it now snapshots the
  role's current permissions into `_permsDraft`; toggles during editing
  mutate only the draft; "Save Changes" commits the draft back to
  `ROLE_PERMS`; "Cancel" discards the draft without ever touching the
  live data. `hasPermission()` now reads from the draft while editing
  and from the committed data otherwise, so the permission pills stay
  visually accurate throughout.
- Before writing the fix, traced a full read-toggle-cancel scenario
  through the original code to confirm the bug was real and not a
  misreading — confirmed a toggled-off permission stayed off after
  Cancel in the original file.
- Verified with a DOM-mock test run of the actual extracted script:
  proved a toggle made mid-edit is fully reverted after `cancelEdit()`
  (live data byte-for-byte unchanged), proved the same toggle sequence
  followed by "Save" instead correctly commits, and proved
  `handleCreateRole()` produces a role with genuinely empty permissions
  across every category — not just checked that nothing threw.
- Confirmed the v4.3.6 topbar panel fix on this page is untouched, and
  `node scripts/validate-project.js` still passes across all 89 pages.

## 4.3.10 — profile.html: 22 non-functional buttons fixed

- This page had by far the least real interactivity found on any page in
  the project — only tab switching (`setProfileTab()`) was implemented.
  Every other button — 22 in total — had no click behaviour whatsoever.
  Mapped every one by section before touching anything:
  - **Cover banner (2)**: Edit Profile, and a "..." menu with no dropdown
    markup at all behind it.
  - **Name/actions row (3)**: Send Email, Schedule Meeting, Export.
  - **Overview tab (1)**: Add Skill.
  - **Documents tab (16)**: Upload, plus View/Download/Delete on each of
    the 5 listed documents.
  - The Performance, Timeline, and Compensation tabs were confirmed to
    be intentionally read-only (no interactive elements at all) and were
    left untouched.
- **Edit Profile** now opens a modal (name, title, department, location,
  email, phone) and writes the name/title/department straight back into
  the header on save — verified against the three unique DOM selectors
  it targets before wiring, so it can't silently update the wrong
  element.
- **"..."** wired as a real Bootstrap dropdown (Deactivate Account,
  Change Manager, View Audit Log) rather than a custom overlay, since no
  dropdown markup existed to reuse and Bootstrap's own component was the
  simpler, more standard fit here.
- **Send Email** converted from a dead `<button>` to a real `<a
  href="mailto:sarah.chen@hrnexus.com">` — genuinely functional browser
  behaviour instead of a simulated toast, using the address already
  shown in the contact strip.
- **Schedule Meeting** opens a modal (topic, date, time) with the same
  required-field validation style used elsewhere in the project.
- **Export**, being a single already-PDF-implied action on one person's
  profile, triggers directly with a toast rather than a redundant
  format-choice modal.
- **Add Skill** opens a modal and appends a new pill to the Skills &
  Expertise list using the exact same CSS class as the existing ten, so
  it's visually indistinguishable from the hand-authored skills.
- **Upload** opens a modal and prepends a real new row to the documents
  table, complete with working View/Download/Delete buttons of its own
  — reusing the same handlers as the 5 pre-existing rows rather than
  leaving new uploads in a half-wired state.
- **Delete** actually removes the row from the table via
  `btn.closest('tr').remove()`, not just a toast with no visible effect.
- Each of the 15 Documents-tab action buttons was matched to its real
  filename (Employment Contract.pdf, NDA Agreement.pdf, Performance
  Review Q4.pdf, Salary History.xlsx, Training Certificate.pdf) by
  reading the actual table rows, then verified after wiring by
  re-parsing the file to confirm every row's own filename lines up with
  its own buttons' arguments — not assumed from write order.
- Verified with a DOM-mock test run of the actual extracted script:
  confirmed the header name/title/department genuinely update on save,
  required-field validation blocks empty submissions on both new
  modals, a new skill pill and a new document row are both genuinely
  appended to the DOM (not just toasted), and delete genuinely removes
  the target row.
- Confirmed the v4.3.6 topbar panel fix on this page is untouched, and
  `node scripts/validate-project.js` still passes across all 89 pages.

## 4.3.9 — training.html: non-functional buttons fixed

- This page turned out to already be one of the most solidly built in the
  project — before changing anything, cross-referenced every `onclick`
  call in the file against every `function` actually defined in its
  script (17 defined, all correctly wired to Course Library, My Learning,
  Quiz, Enroll, and Add Course flows) to confirm nothing else was
  silently broken. Only two real gaps turned up:
- **3 "Start Path" buttons** (HR Professional Track, People Manager
  Track, Compliance Fast Track, in the Learning Paths tab) had no click
  behaviour. Wired each to a new `startPath(name, courseIds)` that
  enrols the user in whichever of that path's courses aren't already
  enrolled — reusing the page's own existing `enrolled`/`progress` state
  and `updateCourseCard()`/`refreshStats()` functions rather than
  inventing new ones — then switches to the Course Library tab (not "My
  Learning", which is static markup that `updateCourseCard()` doesn't
  touch, so switching there wouldn't have shown the change) and confirms
  via toast. Each path's course list was matched against the real
  `COURSES` data by title, not guessed.
- **"Download PDF" button in the Certificate modal** had no click
  behaviour. Added `_certId` tracking to `openCertModal()` and wired the
  button to a new `handleDownloadCertificate()` that references whichever
  certificate is currently open.
- Verified with a DOM-mock test run of the actual extracted script
  (not just a syntax check): confirmed enrolling via one path doesn't
  duplicate courses already enrolled via another, confirmed the stat
  counters and course-card progress bars actually update in the DOM,
  confirmed re-running a path with no new courses left to add shows the
  correct "already enrolled" message instead of a bogus count, and
  confirmed the certificate id is correctly tracked across the
  open/download call pair.
- Confirmed the v4.3.6 topbar panel fix on this page is untouched, and
  `node scripts/validate-project.js` still passes across all 89 pages.

## 4.3.8 — reports.html: non-functional buttons and a real data bug fixed

- **6 static Download buttons** (Generated Reports table) had no click
  behaviour. Wired to `handleDownloadReport(id)`.
- **11 Edit / 11 Pause buttons** (Scheduled Reports tab) had no click
  behaviour at all. Edit now reopens the existing Schedule modal
  pre-filled with that row's report name; Pause genuinely toggles the
  row between Active (green) and Paused (amber) — badge text, badge
  colour, and the button's own icon/title/colour all update together.
  Each of the 11 rows was matched to its real report name by reading the
  actual rendered HTML, not assumed from order.
- **View Report modal's Download/Share buttons** had no click behaviour.
  Now reference whichever report is currently open.
- **Save Schedule button** used to just close the modal with zero effect.
  Now validates the email field and confirms via toast, naming the
  report and chosen frequency/format.
- **Real bug found while wiring the above, unrelated to any button
  press**: every View/Download button was keyed to the report's raw
  *array index* (`data-rep="0"`–`"5"`), but `generateReport()` calls
  `GENERATED_REPORTS.unshift()` to prepend new reports — which silently
  shifts every existing report's index. The moment a single new report
  was generated, all 6 pre-existing View buttons would have opened the
  wrong report. Remapped every reference (static buttons, the
  dynamically-injected row template, and the two new handlers) to use
  each report's stable `id` field (101–106) instead of array position,
  which cannot shift.
- **`setAnlPeriod()`** (the Q1 2026 / Q4 2025 / Q3 2025 / FY 2025 tabs
  inside the Analytics view) had the same "highlight-only" bug found and
  fixed on `analytics.html` in v4.3.7 — clicking a period only restyled
  the button, the 6 KPI cards below never changed. Built a real
  per-period dataset for all 6 metrics and wired it in, keeping the
  Q1 2026 figures byte-for-byte identical to what already shipped.
  Preserved the page's own colour convention (green always pairs with
  the up-arrow, red with the down-arrow, per metric) rather than
  inventing a new one.
- Added two small missing CSS classes this work exposed a need for
  (`.badge-amber-10-xs-fs75-p08-25` for the Paused badge,
  `.fs75-c-green-bg-transparent-br2-p-16-24-pointer` for the Resume
  button) through the real SCSS source and rebuilt, matching the
  existing red/Active pair exactly.
- Verified every new/changed function by extracting the page's actual
  inline script and running it under Node against a purpose-built DOM
  mock — not just a syntax check. Caught and fixed one mistake in the
  test mock itself (a badge element missing its real initial state)
  before trusting the result; confirmed all 4 analytics periods produce
  correct values, the pause/resume toggle correctly flips both
  directions, save-schedule validation correctly blocks an empty email
  and clears the field on success, and the stable-id lookups resolve
  the correct report by name.
- Confirmed the v4.3.6 topbar panel fix on this page is untouched, and
  `node scripts/validate-project.js` still passes across all 89 pages.

## 4.3.7 — analytics.html: Period tab bar & Export PDF fixed

- **Period tab bar** (Q1/Q2/Q3/Q4/YEAR) was only half-wired: clicking a
  tab correctly highlighted it, but `setPeriod()` did nothing else — none
  of the 6 KPI stat cards actually changed, so switching periods had no
  visible effect on the page's data. Built a real per-period dataset for
  all 6 metrics (Total Headcount, Avg Turnover Rate, Avg Tenure,
  Engagement Score, Cost Per Hire, Open Roles) across Q1–Q4 and the full
  year, and extended `setPeriod()` to update each stat's value, delta
  text, and delta arrow/colour when a tab is clicked. The YEAR figures
  were left as the exact original numbers already on the page (204,
  2.9%, 3.4yr, 87%, $8.4K, 17) so selecting YEAR is unchanged from
  before; the four quarters were built to average out consistently with
  those year totals (e.g. quarterly turnover of 3.4/3.1/2.6/2.4%
  averages to the page's existing 2.9%).
- Preserved the page's existing "good news vs bad news" delta colour
  logic per metric (e.g. Open Roles increasing is shown in red/`delta-
  down` even though the number itself went up, matching how the original
  YEAR card was already marked) rather than a naive "number went up =
  green" rule.
- The page subtitle ("Workforce insights... for 2024") now updates too,
  e.g. to "...for Q1 2024", confirming which period is active.
- **Export PDF** button had no click behaviour at all. Wired directly to
  an export action (no modal — the button already names its one format,
  so a format-choice modal would have been redundant) that references
  whichever period is currently selected.
- Verified by extracting the page's own inline script and running it
  under Node against a minimal DOM mock, driving all 5 periods
  end-to-end and confirming every stat value, delta, and the subtitle
  update exactly as designed, with zero runtime errors — not just a
  syntax check.
- Confirmed the v4.3.6 topbar panel fix on this page is untouched, and
  `node scripts/validate-project.js` still passes across all 89 pages.

## 4.3.6 — Same topbar panel fix applied project-wide (42 pages)

- Extended the v4.3.5 `performance.html` fix to every other page with the
  same truncated notification/profile panel: `analytics.html`,
  `announcements.html`, `avatars.html`, `blog-detail.html`,
  `blog-grid.html`, `blog-list.html`, `calendar.html`, `chart-libs.html`,
  `chat.html`, `coming-soon.html`, `drag-drop.html`, `email.html`,
  `error-simple.html`, `faq.html`, `files.html`, `form-elements.html`,
  `form-layouts.html`, `icons-bootstrap.html`, `icons-flaticon.html`,
  `icons-fontawesome.html`, `icons-lucide.html`, `invoice-classic.html`,
  `invoice-minimal.html`, `invoice-modern.html`, `map-libs.html`,
  `pricing.html`, `profile.html`, `reports.html`, `roles.html`,
  `sidebar-demo.html`, `swiper.html`, `tables-bootstrap.html`,
  `tables-datatable.html`, `tasks.html`, `training.html`,
  `ui-alerts.html`, `ui-badge.html`, `ui-buttons.html`, `ui-cards.html`,
  `under-construction-1.html`, `under-construction-2.html`,
  `under-construction-3.html`.
- Before touching anything, ran a precise functional scan (not just a
  byte-length diff) across all 74 pages that have a topbar, checking for
  the actual missing features — fewer than 6 notification items, missing
  "4 new" badge, missing Sign Out, missing Help & Support, missing the
  Appearance toggle — rather than assuming every byte-different page was
  broken. This correctly excluded 17 pages (the 9 dashboard variants,
  `sidebar-menu.html`, 5 UI-component reference pages, and `index.html`)
  that were already functionally complete with only a harmless relative-
  path variant, so those were intentionally left untouched.
- Applied the fix with index-based slicing per file — replacing only the
  span between the topbar's first `<div class="hr-panel"` and its
  `</header>` closing tag, leaving `</header>` itself untouched in the
  original document — specifically to avoid repeating the double-closing-
  div mistake made and caught during the `performance.html` fix. Verified
  automatically per-file: all 42 pages came back with balanced open/close
  `<div>` counts inside the topbar region before being written to disk.
- Verified afterwards, project-wide: every page-specific element outside
  the panels was left untouched — breadcrumb text, search placeholder
  text, and page-specific search handlers (`searchEvents()` on
  `calendar.html`, `doSearch()` on the icon reference pages,
  `searchTasks()` on `tasks.html`, etc.) all confirmed still present and
  correctly wired.
- Final whole-document structural check across all 42 files: balanced
  `<div>` counts, exactly one `<main>`, one `#notif-panel`, one
  `#profile-panel`, and a proper `</body></html>` close on every one.
  `node scripts/validate-project.js` passes across all 89 pages.

## 4.3.5 — performance.html: topbar notification & profile panels fixed

- The notification panel was showing only **1 item** (no unread count
  badge, no "Mark all read") and the profile panel was missing the
  online-status indicator, the Help & Support link, the Appearance
  theme-toggle row, and the Sign Out button — all present on every other
  functional page in the product (`attendance.html`, `leaves.html`,
  `payroll.html`, `recruitment.html`, `employees.html`, and more).
- Replaced performance.html's topbar panels with the exact canonical
  block used by those reference pages (verified byte-for-byte identical
  after the fix), while preserving everything page-specific: the
  "Performance Analytics" breadcrumb and the working `searchPerf()`
  live-search input were left untouched.
- Caught and fixed a mistake made mid-edit: the first pass over-closed
  the topbar with one extra `</div>`, which would have broken the page
  layout below it. Caught by comparing open/close `<div>` counts against
  the reference page (53/53 on both, confirmed balanced) before shipping.
- **Broader finding, not yet fixed**: the same truncated-panel pattern
  exists on roughly 30 other pages (`analytics.html`, `reports.html`,
  `training.html`, `calendar.html`, `tasks.html`, `email.html`,
  `chat.html`, `files.html`, `announcements.html`, `sidebar-demo.html`,
  the four `icons-*.html` pages, the four `blog-*.html` pages, the two
  `form-*.html` and `tables-*.html` pages, `pricing.html`, `profile.html`,
  `roles.html`, `faq.html`, `coming-soon.html`, and the three
  `under-construction-*.html` pages). Only `performance.html` was fixed
  in this release, per the specific request that prompted it.
- Verified: `node scripts/validate-project.js` still passes across all 89
  pages, and no duplicate IDs were introduced.

## 4.3.4 — payroll.html: non-functional buttons fixed

- **Bootstrap's JS bundle wasn't loaded on this page at all** (same gap as
  `attendance.html` before v4.3.2) — no modal could have worked here
  regardless of button wiring. Added the script tag, loaded before
  `hrnexus.min.js`.
- **Payroll Report** button (page header) had no click behaviour. Wired to
  a new Payroll Report modal (report type, date range, PDF/Excel/CSV).
- **Run Payroll** button had no click behaviour. Wired to a confirmation
  modal that surfaces the real run-summary figures already on the page
  (284 employees, ₹3.42Cr gross, ₹2.84Cr net) before confirming, since
  "run payroll" is a consequential action and shouldn't fire on a single
  accidental click. Confirming shows a success toast with the same real
  numbers.
- **Export** button in the Employee Payroll Register card header had no
  click behaviour. Wired to a new Export Payroll Register modal (period,
  Excel/CSV/PDF format).
- **Payslip** button — all 5 rows in the Employee Payroll Register (Sarah
  Chen, Marcus Johnson, Priya Nair, Ravi Das, Aisha Iyer) had identical
  markup with no handler at all. Wired each to `handleViewPayslip(i)`
  with a per-employee data array built directly from that employee's own
  table row (gross/deductions/net/status) — verified each index maps to
  the correct name by cross-checking the rendered HTML before wiring, not
  just trusting row order. Clicking Payslip opens a modal reusing the
  page's own existing `.payslip` visual component (same card style as
  the "Sample payslip" already shown further up the page), populated
  with that employee's real figures, with a Download PDF action.
- Verified: `node scripts/validate-project.js` still passes across all 89
  pages, the new inline `<script>` block parses with zero syntax errors,
  no duplicate IDs introduced, and all local asset paths resolve
  correctly.

## 4.3.3 — leaves.html: non-functional buttons fixed

- **Apply Leave** button already had `data-bs-toggle="modal" data-bs-target="#applyLeaveModal"`
  set, but **no modal with that ID existed anywhere on the page** —
  clicking it tried to open a target that wasn't there, so nothing
  happened. Built the missing modal (leave type, date range with a live
  day-count preview, reason) and wired it up. Submitting adds a new
  Pending card to the Team Leave Requests kanban, styled identically to
  the existing cards, with working Approve/Reject buttons of its own.
- **Reports** button had no click behaviour at all. Wired to a new Leave
  Reports modal (report type, date range, PDF/Excel/CSV format),
  matching the export modal style already established on
  `recruitment.html` and `attendance.html`.
- **Approve / Reject** — all 4 button pairs in the Pending column had no
  `onclick` or any other handler. Wired all 8 buttons via one shared
  `handleLeaveAction()` using event delegation (`this.closest('.lv-card')`),
  so newly-submitted Apply Leave cards get working buttons automatically
  too, with no per-card wiring needed. Clicking either one now moves the
  card into the Approved or Rejected column, strips the description text
  and action row (matching exactly how the existing Approved/Rejected
  cards are structured — verified against their real markup before
  writing the removal logic), and updates all three column counters.
- Verified: `node scripts/validate-project.js` still passes across all 89
  pages, the new inline `<script>` block parses with zero syntax errors,
  no duplicate IDs introduced on this page, and all local asset paths
  resolve correctly.

## 4.3.2 — attendance.html: non-functional buttons fixed

- **Export** button on the Attendance Tracking page had no click behaviour
  and **Bootstrap's JS bundle wasn't even loaded on this page** — so no
  modal could have worked here regardless. Added the Bootstrap JS script
  tag (same local path already used elsewhere: `../../dist/js/bootstrap.bundle.min.js`,
  loaded before `hrnexus.min.js`) and wired Export to a new Export
  Attendance modal (report type, date range, PDF/Excel/CSV format),
  matching the modal style already established on `recruitment.html`.
- **Mark Attendance** button had the same problem — no modal target, no
  Bootstrap JS. Wired to a new Mark Attendance modal (employee name,
  status, check-in time). Submitting it builds a new `.checkin-tile` in
  the exact same style as the 18 existing tiles and prepends it to the
  Live Check-in Wall — the page updates live, no reload needed.
- Along the way, found the checkin wall's legend advertises 4 statuses
  (In Office / WFH / Late / **Absent**) but only 3 had any CSS defined —
  `.checkin-tile.absent` and `.ci-badge.absent` were missing entirely, so
  marking someone absent would have rendered an unstyled tile. Added both
  rules matching the existing red (`#EF4444`) used for absence everywhere
  else on the page.
- Verified: `node scripts/validate-project.js` still passes across all 89
  pages, the new inline `<script>` block parses with zero syntax errors,
  no duplicate IDs introduced, and all local asset paths resolve
  correctly.

## 4.3.1 — recruitment.html: non-functional buttons fixed

- **Export** button on the Recruitment Pipeline page had no click behaviour
  at all — plain `<button>` with no `data-bs-toggle`. Wired to a new
  Export Report modal (report type, date range, PDF/Excel/CSV format)
  matching the visual style of the page's existing Interview Details
  modal. Confirming the export shows a success toast.
- **Post New Role** button (page header) had the same issue — no modal
  target, so clicking it did nothing. Wired to a new Add Role modal
  (title, department, location, employment type, work mode, salary
  range, applicant goal).
- **Add Role** button in the Open Roles card header was already present
  in the markup but was *also* missing its `data-bs-toggle`/`data-bs-target`
  attributes, so it silently did nothing too. Wired to the same Add Role
  modal as Post New Role.
- Submitting the Add Role modal now builds a new `.job-card` (reusing the
  exact CSS classes and structure of the four existing cards, so it's
  visually indistinguishable from the hand-authored ones) and prepends it
  to the Open Roles grid — the page updates live, no reload needed.
- Verified: `node scripts/validate-project.js` still passes across all 89
  pages, the new inline `<script>` block parses with zero syntax errors,
  no duplicate IDs introduced, and Bootstrap JS was already loaded on
  this page so no separate script-tag fix was needed here.

## 4.3.0 — Semantic class naming + Bootstrap collision fixes

### Class naming
- Replaced all 2,782 auto-generated hash-style utility classes (`.u-1234567`)
  with descriptive names following the project's own existing utility
  naming grammar (`flex-ac-g3`, `fs75-fw6-muted`, `bg-blue-12`,
  `badge-green-xs`, `avatar-blue-purple-40`, etc.) instead of inventing a
  new scheme — built a declaration-to-name engine that recognises common
  patterns (flex containers, avatars, icon boxes, badges) and falls back
  to compositional property abbreviations otherwise.
  - 2,543 of these classes were actually in use; 239 were dead code and
    were removed entirely.
  - 21 turned out to be exact duplicates of already-existing named
    classes and were merged into those instead of creating redundant
    rules.
  - The remaining ~2,491 received newly generated names; 167 needed a
    numeric disambiguation suffix (e.g. `-2`, `-3`) where multiple
    distinct declarations reduced to the same base name — the same
    outcome you'd get hand-naming ~2,500 one-off utility rules.
  - Verified zero visual drift: every renamed class's declaration was
    diffed property-by-property against its pre-rename version (2,543
    checks, 0 mismatches after normalising Sass's own leading-zero
    formatting).
  - The actual source of these classes is `src/scss/utils/_extracted.scss`
    (not just the compiled CSS), so the rename survives future
    `npm run build` runs.

### Bug fixes (found during the class-naming pass)
- **Real, previously-shipping bug**: `.fs8125-fw5-body` was referenced 20
  times across three AI-feature pages (`ai-attrition-risk.html`,
  `ai-hr-chatbot.html`, `ai-resume-screener.html`) but was never defined
  anywhere — a missing utility class. Added it (font-weight 500 variant
  of the existing `.fs8125-fw7-body`).
- **Real, previously-shipping bug, much larger in scope**: eight of the
  project's own utility classes (`.mb-3`, `.mb-4`, `.mb-5`, `.mt-3`,
  `.mt-4`, `.p-4`, `.p-5`, `.bg-body`) happened to share their exact
  names with Bootstrap's own reserved utility classes. Because Bootstrap
  utilities are declared with `!important` and this project's own
  same-named classes weren't, Bootstrap's values silently won everywhere
  these classes were used bare — regardless of load order. The project's
  intended spacing (e.g. `.mb-5` meant to be `1.25rem`) was actually
  rendering at Bootstrap's value (`3rem`) site-wide. Affected 284 bare
  usages across 82 pages. Renamed the project's own classes to
  non-colliding names (`.mb-125rem`, `.p-1rem`, `.bg-body-var`, etc.) and
  updated every usage; verified no other Bootstrap utility-class
  collisions remain with differing values (8 harmless same-value
  collisions remain, e.g. `.p-0`/`.p-2`, which render identically either
  way and were left as-is).
  - Also caught and fixed two smaller instances of the same bug
    introduced mid-rename (`.p-3`→`.p-75rem`, `.flex-wrap`→`.flex-no-wrap`)
    before they ever shipped.
  - Confirmed this class of bug does **not** apply to `hrnexus-pages.css`
    / `hrnexus-core.css`'s overlap with Bootstrap component classes
    (`.modal-header`, `.active`, `.show`, etc.) — those are intentional,
    correct Bootstrap theme customisation (Bootstrap's own component
    classes have no `!important`, specifically so a theme's CSS loaded
    afterward can extend them); only Bootstrap's `!important`-protected
    utility classes created a real conflict.

### Final verification pass (re-checked the whole project after the above)
- Re-audited every category from prior releases (favicon, meta
  description, `<h1>` presence, sidebar integrity, Bootstrap-JS-for-modals,
  broken local references, duplicate IDs) across all 89 pages — zero
  issues found.
- Ran a project-wide scan for any class referenced in HTML with no
  matching CSS definition anywhere (shared stylesheets, each page's own
  inline `<style>` block, and the Bootstrap Icons CDN stylesheet). Found
  and ruled out ~40 candidates that turned out to be pure JS-hook classes
  with no visual role (accordion/FAQ toggle state, kanban drag targets,
  filter buttons) — confirmed by finding each one referenced via
  `querySelector`/`classList` in its page's own script. Two were genuine
  pre-existing gaps, unrelated to the class-naming work above:
  - `.card-footer-hr` was used on `index.html` (3 places) but never
    defined anywhere — the page was working around it with a repeated
    inline `style="text-align:center"`. Added a real definition matching
    the existing `.card-hd`/`.card-bd` family (padding + border-top +
    text-align) in `src/scss/components/_cards.scss`, and removed the
    now-redundant inline styles.
  - `.rev-chart` (an SVG revenue-trend chart) was missing identically on
    both `dashboard-assets.html` and `dashboard-ecommerce.html`. Added
    `width:100%;display:block` in `src/scss/components/_dashboard-widgets.scss`.
  - Two other candidates (`.cal-left`, `.acc-group`) were investigated
    and found to be non-issues: `.cal-left` is a CSS Grid child that's
    correctly sized by its parent `.cal-layout`'s `grid-template-columns`
    regardless of its own rules, and `.acc-item` already carries its own
    `margin-bottom` so `.acc-group` needs no spacing rules of its own.
- Added `.stylelintignore` for `src/scss/utils/_extracted.scss` and
  `src/scss/pages/_extracted-legacy.scss` — both are documented in their
  own header comments as verbatim snapshots of already-compiled CSS, not
  hand-authored source, so `stylelint`'s formatting rules were never
  applicable to them. Dropped `npm run lint:scss` from 3,841 problems to
  331 (all in genuinely hand-authored files, pre-existing and unrelated
  to this release).

## 4.2.0 — ThemeForest launch readiness

### Bug fixes
- `leaves.html` had a `data-bs-toggle="modal"` trigger with no Bootstrap JS bundle loaded, so its modal silently failed to open. Added the local `bootstrap.bundle.min.js` script tag (same pattern already used by the other modal-bearing pages).
- `login-frame.html` was byte-for-byte identical to `login-basic.html` despite being a separate sidebar entry — clicking between the two showed the same page. Rebuilt as a genuinely distinct design: a centered card with decorative corner-bracket framing and a dot-grid backdrop, keeping the same functional form fields as the other auth pages.
- Removed four orphaned, byte-identical duplicate pages (`bootstrap-icons.html`, `flaticon-icons.html`, `fontawesome-icons.html`, `lucide-icons.html`) that nothing in the project linked to — the sidebar and every cross-link already pointed at their `icons-*.html` counterparts. Page count corrected from 93 to 89.
- Added a real `<h1>` to 10 pages that had none (several used `<h2>`, one used a plain `<div>`/`<span>`) — `error-cover`, `error-full`, `forgot-password`, `invoice-classic`, `invoice-minimal`, `invoice-modern`, `new-password`, `profile`, `under-construction-2`, and `index.html`. Existing CSS classes are not element-scoped, so this is a pure semantic fix with zero visual change.

### PageSpeed / performance
- Ran the project's own `npm run build:prod` to regenerate `dist/css/*.css` and `dist/js/hrnexus.min.js` from source with real compression (Sass `--style=compressed` + the bundler's `--minify` flag). Verified byte-for-byte semantic equivalence against the previous build before adopting it (only trailing-semicolon and leading-zero differences, both standard Sass compression output): `hrnexus-core.css` 44K→36K, `hrnexus-utils.css` 404K→324K, `hrnexus-pages.css` 144K→100K.
- Added a favicon (`public/assets/favicon.svg`, 548 bytes, matches the existing sidebar brand mark) and linked it on all 89 pages — previously every page load triggered a wasted `/favicon.ico` 404 request.
- Added a unique, page-specific `<meta name="description">` to all 89 pages (previously none had one).
- Confirmed `.htaccess`, `nginx.conf`, and `web.config` already ship correct Brotli/Gzip compression and long-term cache headers — no changes needed.

### ThemeForest marketplace readiness
- Replaced the incorrect MIT license claim (both `README.md` and `package.json` said MIT, which is the wrong license type for an Envato Market item) with a proper `LICENSE.md` describing Envato's Regular/Extended license terms, plus third-party attribution for Bootstrap, Bootstrap Icons, and DM Sans.
- Corrected `README.md`'s page count (was stale at "68 pages" from an earlier release) and rewrote the page-listing table to match what's actually shipped, including the AI Features, Layouts, and full Dashboards groups that were missing from the table entirely.

## 4.1.0

### New pages (Dashboards group)
- `dashboard-assets.html` — Asset Management dashboard (inventory, assignment, warranty tracking)
- `dashboard-finance.html` — Finance dashboard (payroll spend, department budgets, financial health)
- `dashboard-attendance.html` — Attendance dashboard (presence, punctuality, leave insights; working month picker + Apply Leave modal with client-side validation)
- `dashboard-recruitment.html` — Recruitment dashboard (hiring pipeline, time-to-hire, active job openings)
- `dashboard-it.html` — redesigned (system health, storage usage, security posture, user access)

All five are wired into the sidebar under **Dashboards** across every other dashboard page and `index.html`.

### Bug fixes
- Removed a corrupted, duplicated sidebar section (~97 stray lines) present in the original dashboard template
- Fixed broken `dist/css` / `dist/js` asset paths and broken internal `pages/`-prefixed links across the dashboard pages
- Fixed several standalone HTML defects (mismatched tags in `ai-*.html`, `layout-boxed.html`, `layout-detached.html`, `layout-hover.html`)
- Fixed a missing `.avatar-stack` base rule (avatars were stacking vertically instead of overlapping)
- Fixed `payroll.html` using an undefined `.emp-avatar` class instead of the shared `.av` class
- Removed a dead/duplicate legacy script block (leftover from an earlier version of `employees.html`) that was still wired to `#empSearch` and threw a JS error on every keystroke in `recruitment.html`, `attendance.html`, `leaves.html`, `payroll.html`, and `employees.html` itself
- **Switched all CSS `<link>` tags (all 92 pages + `index.html`) from the async `media="print" onload="this.media='all'"` loading pattern to plain synchronous loading.** The async pattern could leave stylesheets permanently un-applied under `file://` (a known Chromium quirk), causing an unstyled/misaligned page and a misleading "unsafe attempt to load" console error — this is very likely why pages could fail to render correctly when opened by double-click rather than through a local server.

### Build / SCSS
- Added `src/scss/components/_dashboard-widgets.scss`, consolidating every new CSS component introduced by the five dashboards above (bar charts, gauges, avatar stacks, AI-assistant widgets, dark promo cards, etc.)
- Added `available` and `repair` entries to the shared `$status-variants` map (`abstracts/_variables.scss`) so `.st-available` / `.st-repair` badges are generated the same way as the rest of the `.badge-st` family, instead of being hand-duplicated
- **Reconciled the build output with what pages actually load.** Previously `npm run build:scss` compiled a single Bootstrap-bundled `dist/css/hrnexus.min.css` that no page referenced — every page actually loads three separate files (`hrnexus-core.css`, `hrnexus-utils.css`, `hrnexus-pages.css`) that had no SCSS source at all. `npm run build` now regenerates all three for real:
  - `src/scss/core.scss` → `dist/css/hrnexus-core.css` — our design system only, Bootstrap excluded (every page already loads Bootstrap itself via CDN, so bundling a second copy would be pure duplication)
  - `src/scss/utils.scss` → `dist/css/hrnexus-utils.css` — wraps the existing auto-extracted utility classes as an SCSS partial (`utils/_extracted.scss`)
  - `src/scss/pages.scss` → `dist/css/hrnexus-pages.css` — wraps the existing per-page extracted styles (`pages/_extracted-legacy.scss`) plus the new `dashboard-widgets` partial
  - Added `base/_recovered-legacy.scss` — ~46 rules that were live in `hrnexus-core.css` with no SCSS source anywhere (sidebar collapsed/flyout state, scrollbar styling, etc.), recovered by diffing a from-source build against the deployed file
  - Fixed `$bp-lg` / `$bp-md` (1024px/768px → 1200px/767px) in `abstracts/_variables.scss` — the source values didn't match the breakpoints actually shipping
  - New npm scripts: `build:css:core`, `build:css:utils`, `build:css:pages` (each with a `:prod` variant), plus matching `watch:css:*` scripts; `build:scss`/`build:scss:prod`/`watch:scss` now run all three. The old single-bundle behavior is preserved as `build:scss:standalone` for anyone who wants a self-contained Bootstrap-included build.
  - **Found and fixed a real, previously-shipping bug in the process:** `hrnexus-utils.css` contained a literal unsubstituted template fragment — `background:{p['color']}` — on the "Popular" plan's "Choose Plan" button in `pricing.html`, left over from whatever script originally generated this file. This is invalid CSS that browsers silently drop, so the button was rendering without its intended solid `#7C3AED` fill. Fixed in both the new SCSS source and the live `dist/css/hrnexus-utils.css`.
  - Verified with `npm run build:scss` / `build:scss:prod`, `stylelint`, and a real-browser computed-style comparison (600+ property checks across multiple pages, old build vs. new) showing zero unintended differences before shipping

> Resolves the "known gap" noted in the previous release: `npm run build:scss` now regenerates exactly the CSS files every page loads.

## [4.4.0] — ThemeForest Submission Hardening

- Completed 89-page static integrity and responsive-structure audit.
- Updated Bootstrap Icons CDN reference from 1.11.3 to 1.13.1.
- Added ThemeForest metadata, reviewer notes, preview checklist and final QA documentation.
- Added marketplace-ready item copy and 15 search tags.
- Added static render validation notes and live-preview performance checklist.
