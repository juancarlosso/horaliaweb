/**
 * main.js — Single init entry point
 */
function initBootstrapComponents() {
  if (typeof window.bootstrap === 'undefined') return;
  document.querySelectorAll('[data-bs-toggle="tooltip"]')
    .forEach(el => { try { new window.bootstrap.Tooltip(el); } catch {} });
  document.querySelectorAll('[data-bs-toggle="popover"]')
    .forEach(el => { try { new window.bootstrap.Popover(el); } catch {} });
}

function initScrollTop() {
  const btn = document.getElementById('scrollTopBtn');
  const pc  = document.querySelector('.page-content');
  if (!btn || !pc) return;
  pc.addEventListener('scroll', () => {
    btn.style.display = pc.scrollTop > 300 ? 'flex' : 'none';
  });
  btn.addEventListener('click', () => pc.scrollTo({ top: 0, behavior: 'smooth' }));
}

function initFormLabels() {
  let generatedId = 0;
  const controls = document.querySelectorAll('input, select, textarea');

  controls.forEach(control => {
    if (control.type === 'hidden' || control.hasAttribute('aria-label') || control.hasAttribute('aria-labelledby')) return;
    if (control.labels && control.labels.length) return;

    const parentLabel = control.closest('label');
    const nearbyLabels = control.parentElement ? control.parentElement.querySelectorAll('label') : [];
    const label = parentLabel || (nearbyLabels.length === 1 ? nearbyLabels[0] : null);

    if (label) {
      if (!control.id) {
        do {
          generatedId += 1;
          control.id = `hrnexus-field-${generatedId}`;
        } while (document.querySelectorAll(`#${CSS.escape(control.id)}`).length > 1);
      }
      label.htmlFor = control.id;
      return;
    }

    const fallback = control.getAttribute('placeholder') || control.getAttribute('name') || control.getAttribute('title');
    if (fallback) control.setAttribute('aria-label', fallback);
  });
}

function init() {
  ThemeManager.init();
  SidebarManager.init();
  TopbarManager.init();
  TooltipManager.init();
  initFormLabels();
  initSearch();
  initTables();
  initKanban();
  initCharts();
  initBootstrapComponents();
  initScrollTop();

  window.HRNexus = { ThemeManager, SidebarManager, TopbarManager };
  console.log('[HRNexus] v4.0.0 ✓');
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}
