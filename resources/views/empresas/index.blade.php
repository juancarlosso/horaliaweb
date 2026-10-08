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
                                            <form method="POST" action="{{ route('empresas.destroy', $empresa) }}" data-company-delete data-company-name="{{ $empresa->razon_social }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn-hr btn-danger-hr btn-sm-hr" type="submit" aria-label="Eliminar {{ $empresa->razon_social }}" title="Eliminar">
                                                    <i class="fa-light fa-trash-can" aria-hidden="true"></i>
                                                    Eliminar
                                                </button>
                                            </form>
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
<script>
    document.querySelectorAll('[data-company-delete]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (form.dataset.confirmed === 'true') {
                delete form.dataset.confirmed;
                return;
            }

            event.preventDefault();
            if (!window.Swal) {
                if (window.confirm('¿Eliminar ' + form.dataset.companyName + '? Esta acción no se puede deshacer.')) {
                    form.dataset.confirmed = 'true';
                    form.requestSubmit();
                }
                return;
            }

            Swal.fire({
                icon: 'warning',
                title: '¿Eliminar empresa?',
                text: 'Se eliminará ' + form.dataset.companyName + '. Esta acción no se puede deshacer.',
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
                    form.dataset.confirmed = 'true';
                    form.requestSubmit();
                }
            });
        });
    });
</script>
@endsection
