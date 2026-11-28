<?php

namespace App\Jobs;

use App\Models\ReporteHorasTrabajadas;
use App\Services\ReporteHorasTrabajadasService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\LazyCollection;

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

    public function handle(ReporteHorasTrabajadasService $service): void
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

        $reporte->update(['estado' => ReporteHorasTrabajadas::LISTO]);
    }

    public function failed(): void
    {
        $reporte = ReporteHorasTrabajadas::query()->where('tipo', ReporteHorasTrabajadas::HORAS)->find($this->reporteId);
        if (! $reporte || $reporte->estado === ReporteHorasTrabajadas::LISTO) {
            return;
        }

        $reporte->update(['estado' => ReporteHorasTrabajadas::FALLIDO]);
        $reporte->filas()->delete();
    }
}
