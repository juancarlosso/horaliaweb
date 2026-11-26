function showReportDateWarning(input, message) {
    if (window.Swal.isVisible()) return;

    window.Swal.fire({
        icon: 'warning',
        title: 'Revisa el periodo',
        text: message,
        confirmButtonText: 'Entendido',
        buttonsStyling: false,
        customClass: { confirmButton: 'btn-hr btn-primary-hr' },
    }).then(() => input.focus());
}

document.addEventListener('invalid', (event) => {
    const input = event.target;
    if (!input.form?.matches('form[data-report-max-days]') || input.type !== 'date' || !input.validity.rangeOverflow || !window.Swal) return;

    event.preventDefault();
    showReportDateWarning(input, 'Puedes seleccionar fechas de ayer o anteriores.');
}, true);

document.addEventListener('submit', (event) => {
    const form = event.target.closest('form[data-report-max-days]');
    if (!form) return;

    const start = Date.parse(form.elements.desde.value + 'T00:00:00Z');
    const end = Date.parse(form.elements.hasta.value + 'T00:00:00Z');
    const days = Number(form.dataset.reportMaxDays);
    if (!(end < start || end - start >= days * 86400000)) return;

    event.preventDefault();
    event.stopImmediatePropagation();
    showReportDateWarning(form.elements.hasta, end < start
        ? 'La fecha final debe ser igual o posterior a la fecha inicial.'
        : `Selecciona un periodo de hasta ${days} días, incluyendo la fecha inicial y final.`);
}, true);
