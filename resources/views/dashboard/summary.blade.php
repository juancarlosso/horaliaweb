@extends('layouts.dashboard', ['pageTitle' => 'Resumen', 'activeSection' => 'summary'])

@section('content')
<style>
    .admin-summary-page { width:100%; max-width:none; }
    .admin-summary-heading { margin-bottom:1.25rem; }
    .admin-summary-heading h1 { margin:0 0 .35rem; color:var(--body-text); font-size:1.5rem; font-weight:800; }
    .admin-summary-heading p { margin:0; color:var(--body-text-muted); font-size:.875rem; }
    .admin-summary-count { color:var(--body-text-muted); font-size:.8rem; }
    .admin-summary-table { min-width:700px; }
    .admin-summary-table th:not(:first-child),.admin-summary-table td:not(:first-child) { text-align:center; }
    .admin-sort-button { display:inline-flex; align-items:center; justify-content:center; gap:.35rem; padding:.2rem .1rem; border:0; color:inherit; background:transparent; font:inherit; font-weight:750; cursor:pointer; }
    .admin-sort-button:hover,.admin-sort-button:focus-visible { color:var(--color-primary); }
    .admin-sort-indicator { width:.8rem; color:var(--body-text-muted); font-size:.72rem; }
    .admin-sort-button[aria-pressed="true"] .admin-sort-indicator { color:var(--color-primary); }
    .admin-summary-table th:first-child .admin-sort-button { justify-content:flex-start; }
    .admin-summary-company { color:var(--body-text); font-weight:750; }
    .admin-summary-number { font-variant-numeric:tabular-nums; font-weight:700; }
    .admin-summary-empty { padding:2.5rem 1rem; color:var(--body-text-muted); text-align:center; }
    @media(max-width:700px) { .admin-summary-table { min-width:640px; } }
</style>

<section class="admin-summary-page">
    <header class="admin-summary-heading">
        <div><h1>Resumen</h1><p>Totales por empresa de centros de trabajo, horarios, personal, asistencias y métodos de pago.</p></div>
    </header>

    <div class="card-hr">
        <div class="card-hd">
            <div><h2 class="card-title-hr">Empresas</h2><p class="card-subtitle-hr">Desglose de los registros asociados a cada empresa.</p></div>
            <span class="admin-summary-count">{{ number_format($companies->count()) }} {{ $companies->count() === 1 ? 'empresa' : 'empresas' }}</span>
        </div>
        @if($companies->isEmpty())
            <div class="admin-summary-empty">Todavía no hay empresas registradas.</div>
        @else
            <div class="table-wrap">
                <table class="bs-table hover admin-summary-table">
                    <thead>
                        <tr>
                            <th scope="col" aria-sort="ascending"><button class="admin-sort-button" type="button" data-sort-key="empresa" data-sort-type="text" aria-pressed="true">Empresa<span class="admin-sort-indicator" aria-hidden="true">↑</span></button></th>
                            <th scope="col"><button class="admin-sort-button" type="button" data-sort-key="centros" data-sort-type="number" aria-pressed="false"><abbr title="Centros de trabajo">CT</abbr><span class="admin-sort-indicator" aria-hidden="true">↕</span></button></th>
                            <th scope="col"><button class="admin-sort-button" type="button" data-sort-key="horarios" data-sort-type="number" aria-pressed="false"><abbr title="Horarios">HRS</abbr><span class="admin-sort-indicator" aria-hidden="true">↕</span></button></th>
                            <th scope="col"><button class="admin-sort-button" type="button" data-sort-key="personal" data-sort-type="number" aria-pressed="false"><abbr title="Personal">PRSN</abbr><span class="admin-sort-indicator" aria-hidden="true">↕</span></button></th>
                            <th scope="col"><button class="admin-sort-button" type="button" data-sort-key="asistencias" data-sort-type="number" aria-pressed="false"><abbr title="Registros de asistencia">RAST</abbr><span class="admin-sort-indicator" aria-hidden="true">↕</span></button></th>
                            <th scope="col"><button class="admin-sort-button" type="button" data-sort-key="metodos-pago" data-sort-type="number" aria-pressed="false"><abbr title="Métodos de pago">MP</abbr><span class="admin-sort-indicator" aria-hidden="true">↕</span></button></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($companies as $company)
                            <tr>
                                <td data-sort-value="{{ $company->nombre ?: 'Empresa #' . $company->id }}"><span class="admin-summary-company">{{ $company->nombre ?: 'Empresa #' . $company->id }}</span></td>
                                <td class="admin-summary-number" data-sort-value="{{ $company->centros_count }}">{{ number_format($company->centros_count) }}</td>
                                <td class="admin-summary-number" data-sort-value="{{ $company->horarios_count }}">{{ number_format($company->horarios_count) }}</td>
                                <td class="admin-summary-number" data-sort-value="{{ $company->personal_count }}">{{ number_format($company->personal_count) }}</td>
                                <td class="admin-summary-number" data-sort-value="{{ $company->asistencias_count }}">{{ number_format($company->asistencias_count) }}</td>
                                <td class="admin-summary-number" data-sort-value="{{ $company->metodos_pago_count }}">{{ number_format($company->metodos_pago_count) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>
<script>
    (function () {
        const table = document.querySelector('.admin-summary-table');
        if (!table) return;

        const body = table.tBodies[0];
        const collator = new Intl.Collator('es', { sensitivity: 'base', numeric: true });
        let currentKey = 'empresa';
        let currentDirection = 1;

        table.querySelectorAll('[data-sort-key]').forEach(function (button) {
            button.addEventListener('click', function () {
                const key = button.dataset.sortKey;
                const direction = key === currentKey ? currentDirection * -1 : 1;
                const columnIndex = button.closest('th').cellIndex;
                const type = button.dataset.sortType;
                const rows = Array.from(body.rows);

                rows.sort(function (rowA, rowB) {
                    const cellA = rowA.cells[columnIndex];
                    const cellB = rowB.cells[columnIndex];
                    const valueA = cellA.dataset.sortValue ?? cellA.textContent.trim();
                    const valueB = cellB.dataset.sortValue ?? cellB.textContent.trim();
                    const comparison = type === 'number'
                        ? Number(valueA) - Number(valueB)
                        : collator.compare(valueA, valueB);

                    return comparison * direction;
                });

                rows.forEach(function (row) { body.appendChild(row); });
                table.querySelectorAll('thead th').forEach(function (heading) {
                    heading.removeAttribute('aria-sort');
                    const sortButton = heading.querySelector('[data-sort-key]');
                    if (!sortButton) return;

                    const active = sortButton === button;
                    sortButton.setAttribute('aria-pressed', active ? 'true' : 'false');
                    sortButton.querySelector('.admin-sort-indicator').textContent = active
                        ? (direction === 1 ? '↑' : '↓')
                        : '↕';
                    if (active) heading.setAttribute('aria-sort', direction === 1 ? 'ascending' : 'descending');
                });

                currentKey = key;
                currentDirection = direction;
            });
        });
    })();
</script>
@endsection
