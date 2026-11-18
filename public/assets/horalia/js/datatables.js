document.addEventListener('DOMContentLoaded', () => {
    if (!window.DataTable) return;

    for (const table of document.querySelectorAll('[data-datatable]')) {
        const defaultLength = Number(table.dataset.datatable);
        const pageLength = Number(table.dataset.perPage) || defaultLength;
        const lengths = [...new Set([10, 25, defaultLength, 100])].sort((a, b) => a - b);
        const source = table.closest('[data-datatable-source]');
        const headers = Array.from(table.tHead.rows[0].cells);
        const columns = headers.map((cell, index) => ({
            index, name: cell.textContent.trim(), sort: cell.dataset.sortKey,
            action: cell.classList.contains('no-search'),
        }));
        const exportColumns = columns.filter((column) => !column.action);
        const initialUrl = new URL(window.location.href);
        const initialSearch = initialUrl.searchParams.get('q')?.trim() ?? '';
        const page = Math.max(1, Number(table.dataset.page) || 1);
        const sortColumn = columns.find((column) => column.sort && column.sort === initialUrl.searchParams.get('sort'));

        const exportButton = table.hasAttribute('data-datatable-no-csv') ? null : document.createElement('button');
        if (exportButton) {
            exportButton.type = 'button';
            exportButton.className = 'btn-hr btn-outline-hr btn-sm-hr datatable-export';
            exportButton.innerHTML = '<i class="fa-solid fa-download" aria-hidden="true"></i> Exportar CSV';
        }
        let loading = false;
        let exporting = false;
        const updateExportButton = () => {
            if (exportButton) exportButton.disabled = loading || exporting;
        };

        const options = {
            pageLength,
            lengthMenu: lengths,
            order: sortColumn ? [[sortColumn.index, initialUrl.searchParams.get('direction') ?? 'asc']] : [],
            orderMulti: false,
            autoWidth: false,
            layout: {
                topStart: { search: { placeholder: table.dataset.searchPlaceholder || 'Buscar...' } },
                topEnd: exportButton ? ['pageLength', exportButton] : 'pageLength',
                bottomStart: 'info',
                bottomEnd: { paging: { firstLast: false, buttons: 5 } },
            },
            columnDefs: [
                { targets: columns.filter((column) => column.action || (source && !column.sort)).map((column) => column.index), orderable: false },
                { targets: 'no-search', searchable: false },
                { targets: 'text-center', className: 'text-center' },
                { targets: 'text-end', className: 'text-end' },
            ],
            language: {
                search: '<span class="visually-hidden">Buscar</span>',
                lengthMenu: 'Mostrar _MENU_ registros',
                emptyTable: table.dataset.empty || 'No hay registros disponibles.',
                zeroRecords: 'No se encontraron coincidencias.',
                info: 'Mostrando _START_–_END_ de _TOTAL_',
                infoEmpty: 'Mostrando 0 registros',
                infoFiltered: '(de _MAX_ en total)',
                processing: 'Cargando…',
                paginate: { previous: '‹', next: '›' },
                aria: {
                    orderable: 'Ordenar por esta columna',
                    orderableReverse: 'Invertir el orden',
                    paginate: { previous: 'Página anterior', next: 'Página siguiente' },
                },
            },
        };

        if (source) {
            options.serverSide = true;
            options.processing = true;
            options.searchDelay = Number(table.dataset.searchDelay) || 400;
            options.search = { search: initialSearch };
            options.displayStart = (page - 1) * pageLength;
            const initialResults = tableResponse(source);
            let request;

            options.ajax = async ({ draw, start, length, search, order }, callback) => {
                if (draw === 1) {
                    callback({ draw, ...initialResults });
                    return;
                }

                const currentPage = Math.floor(start / length) + 1;
                const term = search.value.trim();
                const url = new URL(window.location.href);
                setTableParam(url, 'q', term);
                setTableParam(url, 'page', currentPage > 1 ? currentPage : null);
                setTableParam(url, 'per_page', length !== defaultLength ? length : null);
                const sort = order[0];
                const sortKey = sort && columns[sort.column]?.sort;
                setTableParam(url, 'sort', sortKey);
                setTableParam(url, 'direction', sortKey && sort.dir === 'desc' ? 'desc' : null);

                request?.abort();
                const controller = new AbortController();
                request = controller;
                loading = true;
                updateExportButton();
                try {
                    const results = await fetchTable(url, controller.signal);
                    if (controller.signal.aborted) return;
                    callback({ draw, ...results });
                    history.replaceState(history.state, '', url);
                } catch {
                    if (controller.signal.aborted) return;
                    callback({ draw, recordsTotal: 0, recordsFiltered: 0, data: [] });
                    tableAlert('No se pudo cargar el listado', 'Intenta realizar la búsqueda de nuevo.');
                } finally {
                    if (!controller.signal.aborted) {
                        loading = false;
                        updateExportButton();
                    }
                }
            };
        }

        const dataTable = new DataTable(table, options);
        const container = dataTable.table().container();
        container.classList.add('horalia-datatable');
        container.querySelector('.dt-search input')?.setAttribute('maxlength', '150');
        for (const fallback of source?.querySelectorAll('[data-datatable-fallback]') ?? []) {
            fallback.hidden = true;
        }

        exportButton?.addEventListener('click', async () => {
            exporting = true;
            updateExportButton();
            try {
                const rows = [exportColumns.map((column) => csvField(column.name)).join(',')];
                const appendRows = (data) => {
                    for (const row of data) {
                        rows.push(exportColumns.map(({ index }) => csvField(cellText(row[index]))).join(','));
                    }
                };
                if (source) {
                    const url = new URL(window.location.href);
                    const exportLength = lengths.at(-1);
                    url.searchParams.set('per_page', exportLength);
                    url.searchParams.set('page', 1);
                    const first = await fetchTable(url);
                    appendRows(first.data);
                    for (let page = 2; page <= Math.ceil(first.recordsFiltered / exportLength); page++) {
                        url.searchParams.set('page', page);
                        appendRows((await fetchTable(url)).data);
                    }
                } else {
                    appendRows(dataTable.rows({ search: 'applied', order: 'applied' }).data().toArray());
                }
                const file = new Blob(['\uFEFF', rows.join('\r\n')], { type: 'text/csv;charset=utf-8' });
                const href = URL.createObjectURL(file);
                const link = document.createElement('a');
                link.href = href;
                link.download = `${initialUrl.pathname.split('/').filter(Boolean).pop() || 'listado'}.csv`;
                link.click();
                window.setTimeout(() => URL.revokeObjectURL(href), 1000);
            } catch {
                tableAlert('No se pudo exportar el listado', 'Intenta descargar el archivo CSV de nuevo.');
            } finally {
                exporting = false;
                updateExportButton();
            }
        });
    }
});

function setTableParam(url, key, value) {
    if (value) url.searchParams.set(key, value);
    else url.searchParams.delete(key);
}

function tableAlert(title, text) {
    window.Swal.fire({ icon: 'error', title, text, confirmButtonText: 'Entendido' });
}

async function fetchTable(url, signal) {
    const response = await fetch(url, {
        headers: { 'X-Datatable-Fragment': '1' },
        cache: 'no-store',
        signal,
    });
    if (!response.ok || response.redirected) throw new Error('Respuesta del listado inválida');

    const fragment = document.createElement('template');
    fragment.innerHTML = await response.text();
    return tableResponse(fragment.content);
}

function tableResponse(fragment) {
    const summary = fragment.querySelector('[data-table-filtered]');
    const table = fragment.querySelector('[data-datatable]');
    if (!summary || !table) throw new Error('Respuesta del listado inválida');

    return {
        recordsTotal: Number(summary.dataset.tableTotal),
        recordsFiltered: Number(summary.dataset.tableFiltered),
        data: Array.from(table.tBodies[0].rows, (row) => Array.from(row.cells, (cell) => cell.innerHTML)),
    };
}

function cellText(value) {
    if (value && typeof value === 'object') value = value.display;
    const template = document.createElement('template');
    template.innerHTML = String(value ?? '');
    for (const node of template.content.querySelectorAll('.personal-avatar, .attendance-avatar, [aria-hidden="true"]')) node.remove();
    for (const node of template.content.querySelectorAll('div, br')) node.after(' ');
    return template.content.textContent.replace(/\s+/g, ' ').trim();
}

function csvField(text) {
    const escaped = /^[=+\-@＝＋－＠]/.test(text) ? "'" + text : text;
    return '"' + escaped.replaceAll('"', '""') + '"';
}
