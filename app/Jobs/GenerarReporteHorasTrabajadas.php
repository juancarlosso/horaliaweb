<?php

namespace App\Jobs;

use App\Exports\ReporteHorasTrabajadasExport;
use App\Models\ReporteHorasTrabajadas;
use App\Services\ReporteHorasTrabajadasService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\LazyCollection;
use RuntimeException;

class GenerarReporteHorasTrabajadas implements ShouldQueue
{
    use Queueable;

    public const CONNECTION = 'reportes';

    public const QUEUE = 'reportes';

    public int $tries = 2;

    public int $timeout = 600;

    public int $backoff = 15;

    public function __construct(public string $reporteId)
    {
        $this->onConnection(self::CONNECTION)->onQueue(self::QUEUE)->beforeCommit();
    }

    public function handle(ReporteHorasTrabajadasService $service, ReporteHorasTrabajadasExport $export): void
    {
        $reporte = ReporteHorasTrabajadas::query()->where('tipo', ReporteHorasTrabajadas::HORAS)->find($this->reporteId);
        if (! $reporte || $reporte->estado === ReporteHorasTrabajadas::LISTO) {
            return;
        }

        $reporte->update(['estado' => ReporteHorasTrabajadas::PROCESANDO]);
        $reporte->filas()->delete();

        $rows = LazyCollection::make(fn () => $service->filas($reporte))->values();
        foreach ($rows->chunk(250) as $batch) {
            $reporte->filas()->insert($batch
                ->map(fn (array $row) => $row + ['reporte_id' => $reporte->id])->all());
        }

        $tempPath = $export->generate($reporte);
        try {
            if (! Storage::disk('local')->putFileAs('reportes/horas-trabajadas', $tempPath, $reporte->id.'.xlsx', 'private')) {
                throw new RuntimeException('No se pudo guardar el resumen de horas trabajadas.');
            }

            $reporte->update(['estado' => ReporteHorasTrabajadas::LISTO]);
        } finally {
            @unlink($tempPath);
        }
    }

    public function failed(): void
    {
        $reporte = ReporteHorasTrabajadas::query()->where('tipo', ReporteHorasTrabajadas::HORAS)->find($this->reporteId);
        if (! $reporte || $reporte->estado === ReporteHorasTrabajadas::LISTO) {
            return;
        }

        $reporte->update(['estado' => ReporteHorasTrabajadas::FALLIDO]);
        $reporte->filas()->delete();
        Storage::disk('local')->delete($reporte->archivo());
    }
}
