<?php

namespace App\Services;

use App\Models\Asistencia;
use App\Models\AsistenciaPausa;
use App\Models\Departamento;
use App\Models\Empresa;
use App\Models\Horario;
use App\Models\Personal;
use App\Models\ReporteGenerado;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

class ReporteAsistenciasSnapshot
{
    public const INDICADORES = [
        'total' => 'Registros',
        'late' => 'Llegadas tarde',
        'early_departure' => 'Salidas anticipadas',
        'out_of_range' => 'Fuera de rango',
        'without_exit' => 'Sin salida',
    ];

    public function generar(ReporteGenerado $reporte): array
    {
        $porDepartamento = $reporte->tipo === ReporteGenerado::DEPARTAMENTO;
        $departamentos = [];
        if ($porDepartamento) {
            foreach ($reporte->empresa->departamentos()->orderBy('nombre')->get(['id', 'nombre']) as $departamento) {
                $departamentos[$departamento->id] = $this->resumenDepartamento($departamento->id, $departamento->nombre);
            }
        }

        $totales = array_fill_keys(array_keys(self::INDICADORES), 0);
        $relaciones = $porDepartamento ? ['personal.departamento' => fn ($query) => $query->where('empresa_id', $reporte->empresa_id)]
            : ['personal.empresa', 'personal.horarios', 'pausas'];

        $records = Asistencia::query()->with($relaciones)
            ->where('empresa_id', $reporte->empresa_id)
            ->where('fecha', '>=', $reporte->desde->toDateString())
            ->where('fecha', '<', $reporte->hasta->addDay()->toDateString())
            ->lazyByIdDesc(300);

        foreach ($records->chunk(250) as $batch) {
            $rows = [];
            foreach ($batch as $record) {
                $personal = $record->personal;
                $departamentoId = $porDepartamento ? $personal?->departamento?->id : null;
                if ($porDepartamento && ! isset($departamentos[$departamentoId ?? 0])) {
                    $departamentos[$departamentoId ?? 0] = $this->resumenDepartamento($departamentoId, $personal?->departamento?->nombre ?? 'Sin departamento');
                }

                $indicadores = $this->indicadores($record);
                foreach ($indicadores as $key => $value) {
                    $totales[$key] += $value;
                    if ($porDepartamento) {
                        $departamentos[$departamentoId ?? 0][$key] += $value;
                    }
                }

                $rows[] = [
                    'reporte_id' => $reporte->id,
                    'origen_id' => $record->id,
                    'fecha' => $record->fecha->toDateString(),
                    'departamento_id' => $departamentoId,
                    'nombre' => $personal?->nombre,
                    'busqueda' => $porDepartamento ? null : $this->textoBusqueda($record),
                    'datos' => json_encode($this->datos($record, $porDepartamento) + ['indicadores' => $indicadores], JSON_THROW_ON_ERROR),
                ];
            }
            DB::table('reportes_generados_filas')->insert($rows);
        }

        return ['totales' => $totales, 'departamentos' => array_values($departamentos)];
    }

    public function asistencias(ReporteGenerado $reporte, bool $porFecha = true): LazyCollection
    {
        $query = $reporte->resultado();
        if (! $porFecha) {
            return $query->lazyById(300, 'origen_id')->map(fn ($fila) => $this->restaurar($fila->datos));
        }

        return $query->orderByDesc('fecha')->orderByDesc('origen_id')->lazy(300)
            ->map(fn ($fila) => $this->restaurar($fila->datos));
    }

    private function resumenDepartamento(?int $id, ?string $nombre): array
    {
        return ['id' => $id, 'nombre' => $nombre] + array_fill_keys(array_keys(self::INDICADORES), 0);
    }

    private function indicadores(Asistencia $record): array
    {
        return [
            'total' => 1,
            'late' => (int) ($record->minutos_tarde > 0),
            'early_departure' => (int) ($record->minutos_salida_temprano > 0),
            'out_of_range' => (int) ($record->rango_entrada === 'Fuera de rango' || $record->rango_salida === 'Fuera de rango'),
            'without_exit' => (int) ($record->llegada !== null && $record->salida === null),
        ];
    }

    private function datos(Asistencia $record, bool $porDepartamento): array
    {
        $personal = $record->personal;
        $asistencia = Arr::only($record->getAttributes(), [
            'id', 'fecha', 'llegada', 'salida', 'minutos_tarde', 'minutos_salida_temprano', 'rango_entrada', 'rango_salida',
        ]);
        if (! $porDepartamento) {
            $asistencia += Arr::only($record->getAttributes(), [
                'modalidad', 'distancia_entrada_metros', 'distancia_salida_metros', 'llegada_utc', 'salida_utc',
                'foto_entrada', 'foto_salida', 'latitud', 'longitud', 'latitud_salida', 'longitud_salida',
            ]);
        }
        $datos = [
            'asistencia' => $asistencia,
            'personal' => $personal ? ['nombre' => $personal->nombre, 'codigo' => $personal->codigo] : null,
        ];

        if ($porDepartamento) {
            return $datos + ['departamento' => $personal?->departamento?->nombre];
        }

        return $datos + [
            'empresa' => $personal?->empresa?->only(['nombre', 'incluir_pausas_tiempo_trabajado']),
            'horarios' => $personal?->horarios->map(fn ($horario) => Arr::only($horario->getAttributes(), ['dia', 'entrada', 'salida']))->all() ?? [],
            'pausas' => $record->pausas->map(fn ($pausa) => Arr::only($pausa->getAttributes(), ['id', 'tipo', 'inicio', 'fin', 'inicio_utc', 'fin_utc']))->all(),
        ];
    }

    private function textoBusqueda(Asistencia $record): string
    {
        return mb_strtolower(implode(' ', [
            $record->personal?->nombre, $record->personal?->codigo, $record->personal?->empresa?->nombre,
            $record->fecha->format('d/m/Y'), $record->modalidad === 'HOME_OFFICE' ? 'Home Office' : 'Presencial',
            ...array_values($record->only(['fecha', 'llegada', 'salida', 'rango_entrada', 'rango_salida', 'distancia_entrada_metros', 'distancia_salida_metros', 'minutos_tarde', 'minutos_salida_temprano'])),
        ]));
    }

    public function restaurar(array $datos): Asistencia
    {
        $record = (new Asistencia)->newFromBuilder($datos['asistencia']);
        $personal = $datos['personal'] === null ? null : (new Personal)->newFromBuilder($datos['personal']);
        $record->setRelation('personal', $personal);
        if (array_key_exists('departamento', $datos)) {
            $personal?->setRelation('departamento', $datos['departamento'] === null ? null : new Departamento(['nombre' => $datos['departamento']]));

            return $record;
        }

        $personal?->setRelation('empresa', $datos['empresa'] === null ? null : new Empresa($datos['empresa']));
        $personal?->setRelation('horarios', $this->restaurarRelacion(new Horario, $datos['horarios']));

        return $record->setRelation('pausas', $this->restaurarRelacion(new AsistenciaPausa, $datos['pausas']));
    }

    private function restaurarRelacion(Model $model, array $rows)
    {
        return $model->newCollection(array_map(fn ($row) => $model->newFromBuilder($row), $rows));
    }
}
