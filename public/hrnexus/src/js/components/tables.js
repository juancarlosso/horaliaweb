/**
 * tables.js — Click-to-sort on table[data-sortable] th[data-sort]
 */
function initTables() {
  document.querySelectorAll('table[data-sortable]').forEach(table => {
    table.querySelectorAll('th[data-sort]').forEach((th, colIdx) => {
      th.style.cursor = 'pointer';
      th.title = 'Click to sort';
      let asc = true;

      th.addEventListener('click', () => {
        const tbody = table.querySelector('tbody');
        if (!tbody) return;

        const rows = [...tbody.querySelectorAll('tr')];
        const num  = s => parseFloat(s.replace(/[^0-9.-]/g, ''));

        rows.sort((a, b) => {
          const av = (a.cells[colIdx]?.textContent || '').trim();
          const bv = (b.cells[colIdx]?.textContent || '').trim();
          const an = num(av), bn = num(bv);
          if (!isNaN(an) && !isNaN(bn)) return asc ? an - bn : bn - an;
          return asc ? av.localeCompare(bv) : bv.localeCompare(av);
        });

        rows.forEach(r => tbody.appendChild(r));
        table.querySelectorAll('th[data-sort]').forEach(h => h.removeAttribute('data-sort-dir'));
        th.setAttribute('data-sort-dir', asc ? 'asc' : 'desc');
        asc = !asc;
      });
    });
  });
}
