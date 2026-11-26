<div class="modal fade report-photo-modal" id="attendance-report-photo-modal" tabindex="-1" aria-labelledby="attendance-report-photo-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h2 class="modal-title fs-5" id="attendance-report-photo-title">Foto de asistencia</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div><div class="modal-body"><img id="attendance-report-photo" src="" alt="Foto de asistencia"></div><div class="modal-footer"><button type="button" class="btn-hr btn-outline-hr" data-bs-dismiss="modal">Cerrar</button></div></div></div>
</div>

<div class="modal fade report-photo-modal" id="attendance-report-pauses-modal" tabindex="-1" aria-labelledby="attendance-report-pauses-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h2 class="modal-title fs-5" id="attendance-report-pauses-title">Pausas de la jornada</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div><div class="modal-body"><div class="table-wrap"><table class="bs-table"><thead><tr><th>Tipo</th><th>Inicio</th><th>Fin</th><th>Duración</th><th>Estado</th></tr></thead><tbody id="attendance-report-pauses-body"></tbody></table></div><p id="attendance-report-pauses-empty" hidden>No hay pausas registradas en esta asistencia.</p></div><div class="modal-footer"><button type="button" class="btn-hr btn-outline-hr" data-bs-dismiss="modal">Cerrar</button></div></div></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalElement = document.getElementById('attendance-report-photo-modal');
    const photoModal = window.bootstrap.Modal.getOrCreateInstance(modalElement);
    const photo = document.getElementById('attendance-report-photo');
    const title = document.getElementById('attendance-report-photo-title');

    document.addEventListener('click', function (event) {
        const button = event.target.closest('[data-photo-url]');
        if (!button) return;
        photo.src = button.dataset.photoUrl;
        photo.alt = button.dataset.photoTitle || 'Foto de asistencia';
        title.textContent = button.dataset.photoTitle || 'Foto de asistencia';
        photoModal.show();
    });

    modalElement.addEventListener('hidden.bs.modal', function () { photo.src = ''; });

    const pausesElement = document.getElementById('attendance-report-pauses-modal');
    const pausesModal = window.bootstrap.Modal.getOrCreateInstance(pausesElement);
    const pausesBody = document.getElementById('attendance-report-pauses-body');
    const pausesEmpty = document.getElementById('attendance-report-pauses-empty');
    document.addEventListener('click', async function (event) {
        const button = event.target.closest('[data-pause-detail-url]');
        if (!button) return;
        pausesBody.replaceChildren();
        pausesEmpty.hidden = true;
        pausesModal.show();
        try {
            const response = await fetch(button.dataset.pauseDetailUrl, {headers:{'Accept':'application/json'}});
            if (!response.ok) throw new Error('No se pudo cargar el detalle.');
            const data = await response.json();
            for (const pause of data.pausas) {
                const row = document.createElement('tr');
                for (const value of [pause.tipo, pause.inicio, pause.fin, pause.duracion, pause.estado]) {
                    const cell = document.createElement('td'); cell.textContent = value; row.appendChild(cell);
                }
                pausesBody.appendChild(row);
            }
            pausesEmpty.hidden = data.pausas.length > 0;
        } catch (error) {
            const row = document.createElement('tr'); const cell = document.createElement('td');
            cell.colSpan = 5; cell.textContent = error.message; row.appendChild(cell); pausesBody.appendChild(row);
        }
    });
});
</script>
