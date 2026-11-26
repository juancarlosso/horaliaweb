<?php

namespace App\Services;

use App\Models\Asistencia;
use App\Models\ReporteHorasTrabajadas;
use Carbon\CarbonInterface;
use Generator;

class ReporteHorasTrabajadasService
{
    public function filas(ReporteHorasTrabajadas $reporte): Generator
    {
        $query = Asistencia::query()
            ->with(['personal.empresa', 'pausas'])
            ->where('empresa_id', $reporte->empresa_id)
            ->where('fecha', '>=', $reporte->desde->toDateString())
            ->where('fecha', '<', $reporte->hasta->addDay()->toDateString())
            ->orderBy('personal_id')
            ->orderBy('fecha');

        $last = null;
        $weeks = [];
        do {
            $batch = clone $query;
            if ($last !== null) {
                $batch->where(fn ($cursor) => $cursor
                    ->where('personal_id', '>', $last->personal_id)
                    ->orWhere(fn ($samePerson) => $samePerson
                        ->where('personal_id', $last->personal_id)
                        ->where('fecha', '>', $last->getRawOriginal('fecha'))));
            }

            $records = $batch->limit(500)->get();
            foreach ($records as $record) {
                if ($last !== null && $last->personal_id !== $record->personal_id) {
                    yield from $this->summarizeWeeks($last->personal?->nombre ?? '—', $weeks);
                    $weeks = [];
                }

                $day = $record->fecha->toDateString();
                $monday = $record->fecha->copy()->startOfWeek(CarbonInterface::MONDAY)->toDateString();
                $week = $weeks[$monday] ?? [
                    'days' => [],
                    'indeterminate' => [],
                    'worked' => 0,
                    'pauses' => ['sanitario' => 0, 'comida' => 0, 'descanso' => 0, 'otras' => 0],
                ];

                $week['days'][$day] = true;
                $minutes = $record->tiempoProductivoMinutos();
                if ($minutes === null) {
                    $week['indeterminate'][$day] = true;
                }
                $week['worked'] += $minutes ?? 0;

                foreach ($record->pausas as $pause) {
                    if ($pause->fin && $pause->inicio_utc && $pause->fin_utc) {
                        $type = array_key_exists($pause->tipo, $week['pauses']) ? $pause->tipo : 'otras';
                        $week['pauses'][$type] += (int) $pause->inicio_utc->diffInSeconds($pause->fin_utc);
                    }
                }

                $weeks[$monday] = $week;
                $last = $record;
            }
        } while ($records->count() === 500);

        if ($last !== null) {
            yield from $this->summarizeWeeks($last->personal?->nombre ?? '—', $weeks);
        }
    }

    private function summarizeWeeks(string $name, array $weeks): Generator
    {
        ksort($weeks);
        foreach ($weeks as $monday => $week) {
            yield [
                'personal' => $name,
                'semana' => $monday,
                'dias_registrados' => count($week['days']),
                'dias_indeterminados' => count($week['indeterminate']),
                'minutos_trabajados' => $week['worked'],
                'minutos_sanitario' => intdiv($week['pauses']['sanitario'], 60),
                'minutos_comida' => intdiv($week['pauses']['comida'], 60),
                'minutos_descanso' => intdiv($week['pauses']['descanso'], 60),
                'minutos_otras_pausas' => intdiv($week['pauses']['otras'], 60),
                'minutos_pausas' => intdiv(array_sum($week['pauses']), 60),
            ];
        }
    }
}
