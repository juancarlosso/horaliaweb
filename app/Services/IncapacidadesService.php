<?php

namespace App\Services;

use App\Helper\Wasabi;
use App\Models\FestivoEmpresa;
use App\Models\FestivoOficial;
use App\Models\Incapacidad;
use App\Models\Personal;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class IncapacidadesService
{
    public function snapshot(Personal $personal, string $from, string $through): array
    {
        $days = array_filter(explode('@', (string) $personal->laborados));
        $schedules = $personal->horarios()->get()->keyBy('dia');
        $official = FestivoOficial::query()->whereBetween('fecha', [$from, $through])->get()->groupBy(fn ($row) => CarbonImmutable::parse($row->fecha)->toDateString());
        $company = FestivoEmpresa::query()->where('empresa_id', $personal->empresa_id)->whereDate('fecha', '>=', $from)->whereDate('fecha', '<=', $through)->get()->groupBy(fn ($row) => CarbonImmutable::parse($row->fecha)->toDateString());
        $dates = []; $count = 0;
        for ($date = CarbonImmutable::parse($from)->startOfDay(); $date->lte(CarbonImmutable::parse($through)->startOfDay()); $date = $date->addDay()) {
            $key = $date->toDateString(); $schedule = $schedules->get($date->dayOfWeekIso);
            $holidays = collect($official->get($key, []))->map(fn ($h) => ['tipo' => 'oficial', 'id' => $h->id, 'nombre' => $h->conmemoracion])->concat(collect($company->get($key, []))->map(fn ($h) => ['tipo' => 'empresa', 'id' => $h->id, 'nombre' => $h->conmemoracion]))->values()->all();
            $configured = (bool) ($schedule && in_array((string) $date->dayOfWeekIso, $days, true));
            $holidayWork = (bool) ($schedule?->trabaja_festivos);
            $affected = $configured && (!$holidays || $holidayWork);
            $reason = $affected ? 'Jornada programada' : ($holidays && !$holidayWork ? 'Festivo con descanso' : (!$configured ? 'Descanso o sin jornada asignada' : 'No laborable'));
            if ($affected) $count++;
            $dates[] = ['fecha' => $key, 'laborable' => $affected, 'motivo' => $reason,
                'dia_semana_iso' => $date->dayOfWeekIso, 'dias_laborados_configurados' => array_values($days),
                'horario' => $schedule ? ['id' => $schedule->id, 'horario_empresa_id' => $schedule->horario_id, 'modalidad' => $schedule->modalidad, 'entrada' => $schedule->entrada, 'salida' => $schedule->salida, 'trabaja_festivos' => $holidayWork] : null,
                'festivos' => $holidays];
        }
        return ['version' => 1, 'jornadas_afectadas' => $count, 'fechas' => $dates];
    }

    /** @param UploadedFile[] $files */
    public function register(array $data, int $userId, array $files = []): Incapacidad
    {
        $uploadedPaths = [];
        try {
        return DB::transaction(function () use ($data, $userId, $files, &$uploadedPaths) {
            $personal = Personal::query()->where('empresa_id', $data['empresa_id'])->whereKey($data['personal_id'])->lockForUpdate()->firstOrFail();
            $overlap = Incapacidad::query()->where('personal_id', $personal->id)->where('estado', 'vigente')->whereDate('fecha_inicio', '<=', $data['fecha_fin'])->whereDate('fecha_fin', '>=', $data['fecha_inicio'])->exists();
            if ($overlap) throw ValidationException::withMessages(['fecha_inicio' => 'El periodo se traslapa con una incapacidad vigente del colaborador.']);
            $snapshot = $this->snapshot($personal, $data['fecha_inicio'], $data['fecha_fin']);
            $incapacity = Incapacidad::create(['empresa_id' => $personal->empresa_id, 'personal_id' => $personal->id, 'registrado_por_user_id' => $userId,
                'fecha_inicio' => $data['fecha_inicio'], 'fecha_fin' => $data['fecha_fin'], 'motivo' => $data['motivo'], 'observaciones' => $data['observaciones'] ?? null,
                'jornadas_afectadas' => $snapshot['jornadas_afectadas'], 'snapshot' => $snapshot, 'snapshot_generado_en' => now(), 'estado' => 'vigente']);
            foreach ($files as $file) {
                $name = basename(str_replace('\\', '/', $file->getClientOriginalName()));
                $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name);
                $mime = (string) $file->getMimeType();
                if (!$name || !in_array($mime, ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'], true)) throw ValidationException::withMessages(['comprobantes' => 'Uno de los archivos no tiene un formato válido.']);
                $path = Wasabi::uploadWithOriginalName('personal/incapacidades/empresa-'.$personal->empresa_id.'/personal-'.$personal->id, $file, $name);
                if (!$path || !Storage::disk('wasabi')->exists($path)) throw new \RuntimeException('Wasabi no confirmó la carga del comprobante.');
                $uploadedPaths[] = $path;
                $incapacity->comprobantes()->create(['ruta_privada' => $path, 'nombre_original' => Str::limit($name, 255, ''), 'mime' => $mime, 'tamano' => $file->getSize(), 'cargado_por_user_id' => $userId]);
            }
            return $incapacity;
        });
        } catch (\Throwable $exception) {
            foreach ($uploadedPaths as $path) { try { Storage::disk('wasabi')->delete($path); } catch (\Throwable) {} }
            throw $exception;
        }
    }
}
