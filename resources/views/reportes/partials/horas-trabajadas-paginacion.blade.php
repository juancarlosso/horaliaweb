@if($paginator->hasPages())
    <nav class="card-bd d-flex flex-wrap gap-2 align-items-center justify-content-between" aria-label="Paginación del reporte">
        <span>Mostrando {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} de {{ $paginator->total() }}</span>
        <div class="d-flex gap-2">
            @if($paginator->previousPageUrl())
                <a class="btn-hr btn-outline-hr btn-sm-hr" href="{{ $paginator->previousPageUrl() }}">Anterior</a>
            @endif
            @if($paginator->nextPageUrl())
                <a class="btn-hr btn-outline-hr btn-sm-hr" href="{{ $paginator->nextPageUrl() }}">Siguiente</a>
            @endif
        </div>
    </nav>
@endif
