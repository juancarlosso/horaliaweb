document.addEventListener('submit', (event) => {
    const form = event.target.closest('form[data-report-max-days]');
    if (!form) return;

    const start = Date.parse(form.elements.desde.value + 'T00:00:00Z');
    const end = Date.parse(form.elements.hasta.value + 'T00:00:00Z');
    const days = Number(form.dataset.reportMaxDays);
    if (!(end < start || end - start >= days * 86400000)) return;

    event.preventDefault();
    event.stopImmediatePropagation();
    window.Swal.fire({
        icon: 'warning',
        title: 'Revisa el periodo',
        text: end < start
            ? 'La fecha final debe ser igual o posterior a la fecha inicial.'
            : `Selecciona un periodo de hasta ${days} días, incluyendo la fecha inicial y final.`,
        confirmButtonText: 'Entendido',
        buttonsStyling: false,
        customClass: { confirmButton: 'btn-hr btn-primary-hr' },
    }).then(() => form.elements.hasta.focus());
}, true);
