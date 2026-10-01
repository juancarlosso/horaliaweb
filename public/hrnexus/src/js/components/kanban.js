/**
 * kanban.js — HTML5 drag-and-drop kanban board
 */
function initKanban() {
  const board = document.querySelector('.kanban-board');
  if (!board) return;

  let dragSrc = null;

  // Make cards draggable
  board.querySelectorAll('.kcard').forEach(card => {
    card.setAttribute('draggable', 'true');

    card.addEventListener('dragstart', e => {
      dragSrc = card;
      e.dataTransfer.effectAllowed = 'move';
      e.dataTransfer.setData('text/plain', '');
      setTimeout(() => { card.style.opacity = '0.4'; }, 0);
    });

    card.addEventListener('dragend', () => {
      card.style.opacity = '';
      dragSrc = null;
    });
  });

  // Column drop targets
  board.querySelectorAll('.kanban-col').forEach(col => {
    col.addEventListener('dragover', e => {
      e.preventDefault();
      e.dataTransfer.dropEffect = 'move';
      col.style.outline    = '2px dashed var(--color-primary)';
      col.style.background = 'rgba(79,110,247,0.04)';
    });

    col.addEventListener('dragleave', () => {
      col.style.outline    = '';
      col.style.background = '';
    });

    col.addEventListener('drop', e => {
      e.preventDefault();
      col.style.outline    = '';
      col.style.background = '';

      if (dragSrc && !col.contains(dragSrc)) {
        col.appendChild(dragSrc);
        // Update column card counts
        board.querySelectorAll('.kanban-col').forEach(c => {
          const badge = c.querySelector('.kanban-count');
          if (badge) badge.textContent = c.querySelectorAll('.kcard').length;
        });
      }
    });
  });
}
