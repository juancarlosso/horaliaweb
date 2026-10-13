@extends('layouts.dashboard', ['pageTitle' => 'Empresas', 'activeSection' => 'empresas'])

@section('content')
<style>
    .page-inner { max-width:none; }
    .companies-page { width:100%; }
    .companies-heading { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1.25rem; }
    .companies-heading h1 { margin:0 0 .35rem; color:var(--body-text); font-size:1.5rem; font-weight:800; letter-spacing:-.02em; }
    .companies-heading p { margin:0; color:var(--body-text-muted); font-size:.875rem; }
    .companies-heading .btn-hr svg { width:16px; height:16px; stroke:currentColor; stroke-width:1.8; }
    .companies-status { margin-bottom:1rem; padding:.75rem 1rem; border:1px solid rgba(16,185,129,.22); border-radius:.625rem; color:#087f5b; background:rgba(16,185,129,.08); font-size:.845rem; }
    .company-logo { position:relative; display:grid; place-items:center; width:42px; height:42px; overflow:hidden; border:1px solid var(--body-border); border-radius:.7rem; color:var(--color-primary); background:var(--color-primary-light); }
    .company-logo img { position:absolute; inset:0; width:100%; height:100%; object-fit:contain; background:var(--card-bg); }
    .company-logo svg { width:20px; height:20px; stroke:currentColor; stroke-width:1.8; }
    .company-rfc { color:var(--body-text-muted); font-size:.78rem; white-space:nowrap; }
    .company-name { font-weight:700; }
    .company-actions { display:flex; justify-content:flex-end; gap:.4rem; white-space:nowrap; }
    .company-actions i { display:inline-block; width:15px; min-width:15px; font-size:15px; line-height:1; text-align:center; }
    .company-actions form { margin:0; }
    .companies-page a.btn-hr,.companies-page a.btn-hr:hover,.companies-page a.btn-hr:focus-visible { text-decoration:none; }
    .company-empty { padding:2.5rem 1rem; color:var(--body-text-muted); text-align:center; }
    .company-pagination { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:1rem 1.25rem; border-top:1px solid var(--body-border); color:var(--body-text-muted); font-size:.8rem; }
    .company-pagination-actions { display:flex; align-items:center; gap:.5rem; }
    .company-pagination [aria-disabled="true"] { cursor:default; opacity:.55; pointer-events:none; }
    .company-empty-icon { display:grid; place-items:center; width:54px; height:54px; margin:0 auto .9rem; border-radius:1rem; color:var(--color-primary); background:var(--color-primary-light); }
    .company-empty-icon svg { width:25px; height:25px; stroke:currentColor; stroke-width:1.8; }
    .company-empty h2 { margin:0 0 .4rem; color:var(--body-text); font-size:1rem; font-weight:750; }
    .company-empty p { margin:0 0 1rem; font-size:.845rem; }
    .company-history-modal .modal-dialog { width:min(32rem,calc(100% - 2rem)); }
    .company-history-modal .modal-content { border:1px solid var(--body-border); border-radius:.9rem; color:var(--body-text); background:var(--card-bg); }
    .company-history-modal .modal-header,.company-history-modal .modal-footer { border-color:var(--body-border); }
    .company-history-modal .modal-title { font-size:1.15rem; font-weight:800; }
    .company-history-modal .modal-body p { margin:0 0 .8rem; color:var(--body-text-muted); font-size:.9rem; line-height:1.6; }
    .company-history-modal .modal-body strong { color:var(--body-text); }
    .company-history-modal .modal-body a { color:var(--color-primary); font-weight:700; }
    @media(max-width:700px) { .companies-heading { align-items:stretch; flex-direction:column; } .companies-heading .btn-hr { align-self:flex-start; } .company-pagination { align-items:flex-start; flex-direction:column; } }
</style>

<section class="companies-page">
    <header class="companies-heading">
        <div>
            <h1>Empresas</h1>
            <p>Administra los datos y las tolerancias de tus empresas.</p>
        </div>
        @if($canCreate)
            <a class="btn-hr btn-primary-hr" href="{{ route('empresas.create') }}">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                Agregar empresa
            </a>
        @endif
    </header>


    <div class="card-hr">
        <div class="card-hd">
            <div>
                <h2 class="card-title-hr">Listado de empresas</h2>
                <p class="card-subtitle-hr">{{ $empresas->total() }} {{ $empresas->total() === 1 ? 'empresa registrada' : 'empresas registradas' }}</p>
            </div>
        </div>
        @if($empresas->count())
            <div class="table-wrap">
                <table class="bs-table hover">
                    <thead>
                        <tr>
                            <th scope="col">Logotipo</th>
                            <th scope="col">RFC</th>
                            <th scope="col">Razón social</th>
                            <th scope="col">Teléfono</th>
                            <th scope="col">Min.Tolerancia</th>
                            <th scope="col">Mts.Tolerancia</th>
                            <th scope="col" class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($empresas as $empresa)
                            <tr>
                                <td>
                                    @php($logoUrl = $empresa->logoUrl())
                                    <div class="company-logo">
                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 21h18M5 21V7l8-4v18m0-12h6v12M8 9v.01M8 12v.01M8 15v.01M8 18v.01M16 13v.01M16 16v.01M16 19v.01"/></svg>
                                        @if($logoUrl)
                                            <img src="{{ $logoUrl }}" alt="Logotipo de {{ $empresa->razon_social }}" width="42" height="42" loading="lazy" onerror="this.remove()">
                                        @endif
                                    </div>
                                </td>
                                <td><span class="company-rfc">{{ $empresa->rfc }}</span></td>
                                <td><span class="company-name">{{ $empresa->razon_social }}</span></td>
                                <td>{{ $empresa->telefono ?: '—' }}</td>
                                <td>{{ $empresa->minutos_tolerancia_entrada }}</td>
                                <td>{{ $empresa->metros_distancia_entrada }}</td>
                                <td>
                                    <div class="company-actions">
                                        @if($isAdmin || in_array($empresa->id, $manageableIds, true))
                                            <a class="btn-hr btn-outline-hr btn-sm-hr" href="{{ route('empresas.edit', $empresa) }}" aria-label="Editar {{ $empresa->razon_social }}" title="Editar">
                                                <i class="fa-light fa-pen-to-square" aria-hidden="true"></i>
                                                Editar
                                            </a>
                                            @if($empresa->personal_count === 0)
                                                <form method="POST" action="{{ route('empresas.destroy', $empresa) }}" data-company-delete data-company-name="{{ $empresa->razon_social }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn-hr btn-danger-hr btn-sm-hr" type="submit" aria-label="Eliminar {{ $empresa->razon_social }}" title="Eliminar">
                                                        <i class="fa-light fa-trash-can" aria-hidden="true"></i>
                                                        Eliminar
                                                    </button>
                                                </form>
                                            @else
                                                <button class="btn-hr btn-danger-hr btn-sm-hr" type="button" data-company-history-modal aria-haspopup="dialog" aria-controls="company-history-modal" aria-label="Eliminar {{ $empresa->razon_social }}" title="Eliminar">
                                                    <i class="fa-light fa-trash-can" aria-hidden="true"></i>
                                                    Eliminar
                                                </button>
                                            @endif
                                        @else
                                            <span class="company-rfc">Solo lectura</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($empresas->hasPages())
                <nav class="company-pagination" aria-label="Paginación de empresas">
                    <span>Mostrando {{ $empresas->firstItem() }}–{{ $empresas->lastItem() }} de {{ $empresas->total() }}</span>
                    <div class="company-pagination-actions">
                        @if($empresas->onFirstPage())
                            <span class="btn-hr btn-outline-hr btn-sm-hr" aria-disabled="true">Anterior</span>
                        @else
                            <a class="btn-hr btn-outline-hr btn-sm-hr" href="{{ $empresas->previousPageUrl() }}">Anterior</a>
                        @endif
                        <span>Página {{ $empresas->currentPage() }} de {{ $empresas->lastPage() }}</span>
                        @if($empresas->hasMorePages())
                            <a class="btn-hr btn-outline-hr btn-sm-hr" href="{{ $empresas->nextPageUrl() }}">Siguiente</a>
                        @else
                            <span class="btn-hr btn-outline-hr btn-sm-hr" aria-disabled="true">Siguiente</span>
                        @endif
                    </div>
                </nav>
            @endif
        @else
            <div class="company-empty">
                <div class="company-empty-icon"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 21h18M5 21V7l8-4v18m0-12h6v12M8 9v.01M8 12v.01M8 15v.01M16 13v.01M16 16v.01"/></svg></div>
                <h2>Aún no hay empresas</h2>
                <p>Cuando registres una empresa, aparecerá en esta lista.</p>
                @if($canCreate)
                    <a class="btn-hr btn-primary-hr" href="{{ route('empresas.create') }}">Agregar empresa</a>
                @endif
            </div>
        @endif
    </div>
</section>
<div class="modal fade company-history-modal" id="company-history-modal" tabindex="-1" aria-labelledby="company-history-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="company-history-modal-title">Solicitud de eliminación definitiva</h2>
                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p><strong>Esta empresa cuenta con registros de asistencia y no puede eliminarse directamente.</strong></p>
                <p>Para solicitar su eliminación definitiva, incluyendo toda la información y los registros asociados, es necesario contactar a <strong>Solventia Software</strong>.</p>
                <p>Envía tu solicitud a <a href="mailto:contacto@solventia.com.mx">contacto@solventia.com.mx</a> y nuestro equipo te ayudará con el proceso.</p>
            </div>
            <div class="modal-footer">
                <button class="btn-hr btn-outline-hr" type="button" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
<script>
    var companyHistoryModalElement = document.getElementById('company-history-modal');
    document.querySelectorAll('[data-company-history-modal]').forEach(function (button) {
        button.addEventListener('click', function () {
            window.bootstrap.Modal.getOrCreateInstance(companyHistoryModalElement).show();
        });
    });

    function submitCompanyDeletion(form) {
        var button = form.querySelector('button[type="submit"]');
        if (button) {
            button.disabled = true;
            button.innerHTML = '<i class="fa-light fa-spinner fa-spin" aria-hidden="true"></i> Eliminando…';
        }

        form.dataset.confirmed = 'true';
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                form.requestSubmit();
            });
        });
    }

    document.querySelectorAll('[data-company-delete]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (form.dataset.confirmed === 'true') {
                delete form.dataset.confirmed;
                return;
            }

            event.preventDefault();
            if (!window.Swal) {
                if (window.confirm('¿Eliminar ' + form.dataset.companyName + ' y sus datos relacionados de Horalia? Los CFDI timbrados y movimientos que Stripe o el SAT deban conservar pueden permanecer en sus plataformas externas. Esta acción es permanente y no se puede deshacer.')) {
                    submitCompanyDeletion(form);
                }
                return;
            }

            Swal.fire({
                icon: 'warning',
                title: '¿Eliminar empresa?',
                text: 'Se eliminarán de Horalia los datos relacionados de la empresa, horarios, centros, departamentos, puestos, tarjetas guardadas, historial de pagos, facturas y archivos. Los CFDI timbrados y movimientos que Stripe o el SAT deban conservar pueden permanecer en sus plataformas externas. Esta acción es permanente y no se puede deshacer.',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn-hr btn-danger-hr',
                    cancelButton: 'btn-hr btn-outline-hr'
                },
                reverseButtons: true
            }).then(function (result) {
                if (result.isConfirmed) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Eliminando empresa',
                        text: 'Estamos borrando sus datos relacionados y archivos. Espera a que termine el proceso.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: function () {
                            Swal.showLoading();
                            submitCompanyDeletion(form);
                        }
                    });
                }
            });
        });
    });
</script>
@endsection
