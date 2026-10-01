/**
 * topbar.js — Notification & profile panels, search
 */
const TopbarManager = {
  init() {
    // Panel toggles (bell, profile avatar)
    document.addEventListener('click', e => {
      const trigger = e.target?.closest?.('[data-panel-toggle]');

      if (trigger) {
        e.stopPropagation();
        const panelId = trigger.dataset.panelToggle;
        const panel   = document.getElementById(panelId);
        if (!panel) return;

        // Close every other panel
        document.querySelectorAll('.hr-panel').forEach(p => {
          if (p.id !== panelId) p.style.display = 'none';
        });

        panel.style.display = panel.style.display === 'block' ? 'none' : 'block';
        return;
      }

      // Click anywhere outside → close all panels
      if (!e.target?.closest?.('.hr-panel') && !e.target?.closest?.('[data-panel-toggle]')) {
        document.querySelectorAll('.hr-panel').forEach(p => p.style.display = 'none');
      }
    });

    // ESC closes panels
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') {
        document.querySelectorAll('.hr-panel').forEach(p => p.style.display = 'none');
      }
    });
  },
};
