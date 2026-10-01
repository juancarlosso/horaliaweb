/**
 * tooltip.js — Sidebar tooltips when collapsed
 */
const TooltipManager = {
  tip: null,

  init() {
    this.tip = Object.assign(document.createElement('div'), {
      className: 'hr-sidebar-tooltip',
    });
    document.body.appendChild(this.tip);

    document.addEventListener('mouseenter', e => {
      const el = e.target?.closest?.('[data-sidebar-tooltip]');
      if (!el || !document.querySelector('.sidebar.collapsed')) return;
      this.show(el, el.dataset.sidebarTooltip);
    }, true);

    document.addEventListener('mouseleave', e => {
      if (e.target?.closest?.('[data-sidebar-tooltip]')) this.hide();
    }, true);
  },

  show(el, label) {
    if (!label || !this.tip) return;
    const r = el.getBoundingClientRect();
    this.tip.textContent = label;
    Object.assign(this.tip.style, {
      left:      `${r.right + 10}px`,
      top:       `${r.top + r.height / 2}px`,
      transform: 'translateY(-50%)',
      opacity:   '1',
    });
  },

  hide() { if (this.tip) this.tip.style.opacity = '0'; },
};
