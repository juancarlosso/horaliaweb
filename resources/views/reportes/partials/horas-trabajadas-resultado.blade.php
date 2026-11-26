@include('reportes.partials.resultado-con-tabla', [
    'backUrl' => route('reportes.horas-trabajadas.index'),
    'downloadUrl' => route('reportes.horas-trabajadas.download', $reporte),
    'storeUrl' => route('reportes.horas-trabajadas.store'),
    'table' => 'reportes.partials.horas-trabajadas-tabla',
])
