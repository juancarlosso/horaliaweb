/**
 * search.js — Live table search via [data-table-search]
 */
function initSearch() {
  document.querySelectorAll('[data-table-search]').forEach(input => {
    const tableId = input.dataset.tableSearch;
    const table   = tableId
      ? document.getElementById(tableId)
      : (input.closest('.card-hr') || input.closest('.card') || document.body).querySelector('table');
    if (!table) return;

    input.addEventListener('input', () => {
      const q = input.value.toLowerCase().trim();
      table.querySelectorAll('tbody tr').forEach(row => {
        row.style.display = !q || row.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    });
  });
}
