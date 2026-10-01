/**
 * sidebar.js — Sidebar navigation: toggle, groups, active state, mobile
 */
const SidebarManager = {
  sidebar:  null,
  main:     null,
  backdrop: null,
  popup:    null,
  popupOpenBtn: null,

  init() {
    this.sidebar  = document.querySelector('.sidebar');
    this.main     = document.querySelector('.main-content');
    this.backdrop = document.querySelector('.mobile-backdrop');
    this.popup    = document.getElementById('sb-main-popup');
    if (!this.sidebar) return;

    // Restore collapsed state
    if (store.get('hr-sidebar-collapsed', '0') === '1') this._setCollapsed(true, true);

    // Desktop hamburger toggle
    document.querySelector('[data-sidebar-toggle]')
      ?.addEventListener('click', () => this.toggle());

    // Mobile hamburger
    document.querySelectorAll('[data-sidebar-mobile-toggle]')
      .forEach(btn => btn.addEventListener('click', () => this.mobileToggle()));

    // Backdrop closes mobile drawer
    this.backdrop?.addEventListener('click', () => this.mobileClose());

    // Group expand / collapse — bind directly to each button
    this.sidebar.querySelectorAll('.nav-group-hd').forEach(btn => {
      btn.addEventListener('click', e => {
        e.preventDefault();
        e.stopPropagation();
        this._toggleGroup(btn);
      });
    });

    // ESC closes mobile + any open flyout
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') {
        this.mobileClose();
        this._closeFlyout();
      }
    });

    // Click elsewhere closes an open flyout
    document.addEventListener('click', e => {
      if (this.popupOpenBtn && this.popup &&
          !this.popup.contains(e.target) && !this.popupOpenBtn.contains(e.target)) {
        this._closeFlyout();
      }
    });
    window.addEventListener('resize', () => this._closeFlyout());

    this._markActive();
  },

  toggle() {
    const collapsed = this.sidebar.classList.toggle('collapsed');
    this.main?.classList.toggle('sidebar-collapsed', collapsed);
    store.set('hr-sidebar-collapsed', collapsed ? '1' : '0');
    this._closeFlyout();
  },

  _setCollapsed(yes, silent = false) {
    if (silent) this.sidebar.style.transition = 'none';
    this.sidebar.classList.toggle('collapsed', yes);
    this.main?.classList.toggle('sidebar-collapsed', yes);
    if (silent) setTimeout(() => { this.sidebar.style.transition = ''; }, 50);
  },

  mobileToggle() {
    const open = this.sidebar.classList.toggle('mobile-open');
    if (this.backdrop) this.backdrop.style.display = open ? 'block' : 'none';
    document.body.style.overflow = open ? 'hidden' : '';
  },

  mobileClose() {
    this.sidebar.classList.remove('mobile-open');
    if (this.backdrop) this.backdrop.style.display = 'none';
    document.body.style.overflow = '';
  },

  /* ── Group toggle ─────────────────────────────────────────────────────
   * HTML structure:
   *   <div class="nav-li">              ← btn.parentElement
   *     <button class="nav-group-hd">  ← btn
   *   </div>
   *   <div class="nav-group-body">     ← navLi.nextElementSibling
   *
   * Single-open accordion: opening one group now closes any other open
   * group first, instead of letting multiple stay open independently.
   *
   * Collapsed sidebar: clicking a group icon pops its submenu out to the
   * right as a flyout instead of expanding inline (there's no room for
   * labels when collapsed, and .sidebar's overflow:hidden would clip an
   * inline expansion anyway).
   * ─────────────────────────────────────────────────────────────────── */
  _toggleGroup(btn) {
    const navLi = btn.parentElement;           // the wrapping .nav-li
    const body  = navLi?.nextElementSibling;   // the .nav-group-body
    if (!body || !body.classList.contains('nav-group-body')) return;

    if (this.sidebar.classList.contains('collapsed')) {
      if (this.popupOpenBtn === btn) { this._closeFlyout(); return; }
      this._closeFlyout();
      this._openFlyout(btn, body);
      return;
    }

    const willOpen = !body.classList.contains('open');

    this.sidebar.querySelectorAll('.nav-group-body.open').forEach(b => {
      if (b !== body) b.classList.remove('open');
    });
    this.sidebar.querySelectorAll('.nav-group-hd.active').forEach(h => {
      if (h !== btn) {
        h.classList.remove('active');
        h.setAttribute('aria-expanded', 'false');
        h.querySelector('.nav-chevron')?.classList.remove('open');
      }
    });

    body.classList.toggle('open', willOpen);
    btn.querySelector('.nav-chevron')?.classList.toggle('open', willOpen);
    btn.setAttribute('aria-expanded', String(willOpen));
    btn.classList.toggle('active', willOpen);
  },

  /* ── Flyout popup for the collapsed sidebar ─────────────────────────
   * Requires a <div id="sb-main-popup"> somewhere on the page. If a page
   * doesn't have one yet, this safely does nothing rather than erroring —
   * clicking a group icon while collapsed just won't show a flyout there,
   * same as the previous behaviour, until the element is added. */
  _closeFlyout() {
    if (!this.popup) return;
    this.popup.classList.remove('visible');
    this.popup.setAttribute('aria-hidden', 'true');
    if (this.popupOpenBtn) {
      this.popupOpenBtn.classList.remove('popup-open');
      this.popupOpenBtn.setAttribute('aria-expanded', 'false');
    }
    this.popupOpenBtn = null;
  },

  _openFlyout(btn, body) {
    if (!this.popup) return;
    const title = btn.querySelector('.nav-text')?.textContent?.trim() || '';
    const links = body.querySelectorAll('.nav-lnk');

    let html = title ? `<div class="popup-title">${title}</div>` : '';
    links.forEach(link => {
      // Clone each real sidebar link so the flyout always matches the
      // actual nav-group-body content (labels, hrefs, badges) rather than
      // duplicating it by hand and risking the two falling out of sync.
      const clone = link.cloneNode(true);
      clone.classList.remove('nav-child');
      html += clone.outerHTML;
    });
    this.popup.innerHTML = html;

    // Make it paint (invisibly) before measuring — offsetHeight reads 0
    // while display:none, which would silently break the overflow checks
    // below for icons near the bottom of the sidebar.
    this.popup.style.visibility = 'hidden';
    this.popup.classList.add('visible');
    const popupHeight = this.popup.offsetHeight;

    const rect = btn.getBoundingClientRect();
    this.popup.style.left = `${Math.round(rect.right + 8)}px`;

    const spaceBelow = window.innerHeight - rect.top - 16;
    const spaceAbove = rect.bottom - 16;
    if (popupHeight > spaceBelow && spaceAbove >= popupHeight) {
      // Not enough room to grow downward from the icon — flip to anchor
      // from its bottom edge and grow upward instead, so the whole
      // submenu stays on screen.
      this.popup.style.top = 'auto';
      this.popup.style.bottom = `${Math.round(window.innerHeight - rect.bottom)}px`;
    } else {
      this.popup.style.bottom = 'auto';
      const top = Math.round(rect.top);
      const maxTop = window.innerHeight - popupHeight - 16;
      this.popup.style.top = `${Math.max(8, Math.min(top, maxTop))}px`;
    }

    this.popup.style.visibility = '';
    this.popup.setAttribute('aria-hidden', 'false');
    btn.classList.add('popup-open');
    btn.setAttribute('aria-expanded', 'true');
    this.popupOpenBtn = btn;
  },

  /* ── Mark the current page link as active ───────────────────────────── */
  _markActive() {
    // Works for both server (pathname) and file:// (filename from path)
    const page = window.location.pathname.split('/').pop() || 'index.html';

    // Clear any stray active/open state first. Several pages have leftover
    // 'active'/'open' classes baked into their static HTML from template
    // copy-paste (a leftover default group left open, sometimes alongside
    // the correct one) — clearing everything up front means whatever the
    // static markup says, the sidebar always ends up showing the single,
    // correct group for the current page.
    this.sidebar.querySelectorAll('.nav-group-hd.active').forEach(btn => {
      btn.classList.remove('active');
      btn.setAttribute('aria-expanded', 'false');
      btn.querySelector('.nav-chevron')?.classList.remove('open');
    });
    this.sidebar.querySelectorAll('.nav-group-body.open').forEach(body => {
      body.classList.remove('open');
    });

    this.sidebar.querySelectorAll('.nav-lnk[href]').forEach(link => {
      const href = link.getAttribute('href').split('/').pop().split('?')[0].split('#')[0];
      const active = href === page || (page === '' && href === 'index.html');
      link.classList.toggle('active', active);

      if (active) {
        const groupBody = link.closest('.nav-group-body');
        if (groupBody) {
          groupBody.classList.add('open');
          const groupBtn = groupBody.previousElementSibling?.querySelector('.nav-group-hd');
          if (groupBtn) {
            groupBtn.querySelector('.nav-chevron')?.classList.add('open');
            groupBtn.setAttribute('aria-expanded', 'true');
            groupBtn.classList.add('active');
          }
        }
      }
    });
  },
};
