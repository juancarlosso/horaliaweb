/**
 * theme.js — Theme & dark-mode manager
 * NOTE: 'store' is injected by bundle.js before this file
 */
const ThemeManager = {
  init() {
    this.apply(store.get('hr-theme', 'light'));
    const preset = store.get('hr-color-theme', 'default');
    if (preset && preset !== 'default') this.applyPreset(preset);

    document.addEventListener('click', e => {
      const btn = e.target?.closest?.('[data-theme-toggle]');
      if (btn) {
        const cur = document.documentElement.getAttribute('data-bs-theme') || 'light';
        this.apply(cur === 'dark' ? 'light' : 'dark');
      }
      const presetEl = e.target?.closest?.('[data-theme-preset]');
      if (presetEl) this.applyPreset(presetEl.dataset.themePreset);
    });
  },

  apply(mode) {
    document.documentElement.setAttribute('data-bs-theme', mode);
    store.set('hr-theme', mode);
    document.querySelectorAll('.theme-icon-light').forEach(i => i.style.display = mode === 'dark'  ? 'none' : '');
    document.querySelectorAll('.theme-icon-dark').forEach(i  => i.style.display = mode === 'light' ? 'none' : '');
  },

  applyPreset(name) {
    document.documentElement.classList.remove('theme-midnight', 'theme-rose', 'theme-forest', 'theme-sunset');
    if (name && name !== 'default') document.documentElement.classList.add('theme-' + name);
    store.set('hr-color-theme', name || 'default');
  },
};
