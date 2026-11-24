@extends('layouts.dashboard', ['pageTitle' => 'Detalle de horas extras'])

@section('content')
@include('components.administrative-catalog-styles')
<style>
    .detalle-texto { grid-column: 1 / -1; }
</style>
<div class="card-hr">
    <div class="card-hd d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h1 class="card-title-hr">Autorización de horas extras {{ $registro->folio }}</h1>
        <div class="d-flex gap-2 flex-wrap">
            <a class="btn-hr btn-outline-hr" href="{{ route('horas-extras.index', ['empresa_id' => $registro->empresa_id, 'mes' => $registro->fecha->month, 'anio' => $registro->fecha->year]) }}"><i class="{{ config('constantes.tipo_iconos') }} fa-arrow-left" aria-hidden="true"></i> Regresar</a>
            <a class="btn-hr btn-primary-hr" href="{{ route('horas-extras.pdf', $registro) }}"><i class="{{ config('constantes.tipo_iconos') }} fa-download" aria-hidden="true"></i> Descargar PDF</a>
        </div>
    </div>
    <div class="card-bd">
        @include('horas-extras.datos')
        @if($expediente || $constancia)
            <section class="border-top pt-3 mt-3" aria-labelledby="titulo-constancia-firmada">
                <h2 class="card-title-hr mb-2" id="titulo-constancia-firmada">Constancia firmada</h2>
                @if($constancia)
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <span class="text-break">{{ $constancia->nombre_original }}</span>
                        <a class="btn-hr btn-outline-hr btn-sm-hr" href="{{ route('horas-extras.constancia.download', $registro) }}"><i class="{{ config('constantes.tipo_iconos') }} fa-download" aria-hidden="true"></i> Descargar constancia firmada</a>
                    </div>
                @elseif($puedeCargarConstancia)
                    <form method="POST" action="{{ route('horas-extras.constancia.store', $registro) }}" enctype="multipart/form-data" class="row g-2 align-items-end mb-3">
                        @csrf
                        <div class="col-md-6">
                            <label class="form-label-hr" for="constancia_archivo">Archivo firmado <span class="text-danger" aria-hidden="true">*</span></label>
                            <input class="hr-input" type="file" name="archivo" id="constancia_archivo" accept=".pdf,.jpg,.jpeg,.png,.webp" aria-describedby="constancia_formatos" required>
                        </div>
                        <div class="col-md-auto">
                            <button class="btn-hr btn-primary-hr" type="submit">Subir constancia firmada</button>
                        </div>
                        <small class="col-12 text-muted" id="constancia_formatos">PDF, JPG, PNG o WebP. Máximo {{ number_format(config('filesystems.personal_document_max_kb', 10240) / 1024, 0) }} MB.</small>
                    </form>
                @else
                    <p class="text-muted">Aún no hay una constancia firmada asociada.</p>
                @endif
                @if($expediente)
                    <a href="{{ route('personal.edit', ['personal' => $registro->personal_id, 'tab' => 'documentos']) }}">Ver documentos del personal</a>
                @endif
            </section>
        @endif
    </div>
</div>
@endsection

@if(session('constancia_cargada'))
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                Swal.fire({
                    icon: 'success', text: @json(session('constancia_cargada')), confirmButtonText: 'Entendido',
                    buttonsStyling: false, customClass: { confirmButton: 'btn-hr btn-primary-hr' },
                });
            });
        </script>
    @endpush
@endif
