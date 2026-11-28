<?php

namespace App\Jobs;

use App\Models\ReporteGenerado;
use App\Services\ReporteAsistenciasSnapshot;
use App\Services\ReporteIncidenciasService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

class GenerarReporte implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 600;

    public int $backoff = 15;

    public function __construct(public string $reporteId)
    {
        $this->onConnection('reportes')->onQueue('reportes')->beforeCommit();
    }

    public function handle(ReporteAsistenciasSnapshot $asistencias, ReporteIncidenciasService $incidencias): void
    {
        $reporte = ReporteGenerado::query()->find($this->reporteId);
        if (! $reporte || $reporte->estado === ReporteGenerado::LISTO) {
            return;
        }

        $reporte->update(['estado' => ReporteGenerado::PROCESANDO]);
        $reporte->resultado()->delete();

        $resumen = $reporte->tipo === ReporteGenerado::INCIDENCIAS
            ? $this->generarIncidencias($reporte, $incidencias)
            : $asistencias->generar($reporte);
        $reporte->update(['resumen' => $resumen, 'estado' => ReporteGenerado::LISTO]);
    }

    public function failed(): void
    {
        $reporte = ReporteGenerado::query()->find($this->reporteId);
        if (! $reporte || $reporte->estado === ReporteGenerado::LISTO) {
            return;
        }

        $reporte->update(['estado' => ReporteGenerado::FALLIDO, 'resumen' => null]);
        $reporte->resultado()->delete();
    }

    private function generarIncidencias(ReporteGenerado $reporte, ReporteIncidenciasService $service): array
    {
        $rows = LazyCollection::make(fn () => $service->porBloques(
            $reporte->empresa_id, $reporte->desde->toDateString(), $reporte->hasta->toDateString(), $reporte->filtros['tipos'],
        ))->values();
        $total = 0;
        foreach ($rows->chunk(250) as $batch) {
            DB::table('reportes_generados_filas')->insert($batch->map(fn ($row) => [
                'reporte_id' => $reporte->id,
                'origen_id' => $row['employee_id'],
                'fecha' => $row['fecha'],
                'nombre' => $row['empleado'],
                'datos' => json_encode($row + ['etiqueta' => ReporteIncidenciasService::LABELS[$row['tipo']]], JSON_THROW_ON_ERROR),
                'busqueda' => mb_strtolower(implode(' ', [
                    $row['fecha'], substr($row['fecha'], 8, 2).'/'.substr($row['fecha'], 5, 2).'/'.substr($row['fecha'], 0, 4),
                    $row['empleado'], $row['codigo'], ReporteIncidenciasService::LABELS[$row['tipo']], $row['detalle'],
                ])),
            ])->all());
            $total += $batch->count();
        }

        return ['total' => $total];
    }
}
