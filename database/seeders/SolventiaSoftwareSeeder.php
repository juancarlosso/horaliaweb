<?php

namespace Database\Seeders;

use App\Models\Centro;
use App\Models\Departamento;
use App\Models\Empresa;
use App\Models\Horario;
use App\Models\HorarioEmpresa;
use App\Models\Personal;
use App\Models\Puesto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SolventiaSoftwareSeeder extends Seeder
{
    private const COMPANY_NAME = 'SOLVENTIA SOFTWARE SAS';
    private const EMPLOYEE_COUNT = 50;

    public function run(): void
    {
        $faker = fake('es_MX');

        DB::transaction(function () use ($faker): void {
            $company = Empresa::query()->where('razon_social', self::COMPANY_NAME)->first();
            if (!$company) {
                $company = Empresa::query()->create([
                    'rfc' => $this->uniqueRfc(),
                    'razon_social' => self::COMPANY_NAME,
                    'direccion' => $faker->address(),
                    'telefono' => $faker->numerify('##########'),
                    'activa' => true,
                    'minutos_tolerancia_entrada' => random_int(0, 15),
                    'metros_distancia_entrada' => random_int(50, 500),
                ]);
            }

            $centros = collect(['Oficina central', 'Sede norte', 'Sede sur'])->map(
                fn (string $name) => Centro::query()->firstOrCreate(
                    ['empresa_id' => $company->id, 'nombre' => $name],
                    ['activo' => true, 'geolocalizacion' => $faker->latitude() . ',' . $faker->longitude()],
                ),
            );
            $departamentos = collect(['Desarrollo', 'Soporte', 'Administración', 'Ventas', 'Recursos Humanos'])->map(
                fn (string $name) => Departamento::query()->firstOrCreate(
                    ['empresa_id' => $company->id, 'nombre' => $name], ['activo' => true],
                ),
            );
            $puestos = collect(['Analista', 'Desarrollador', 'Coordinador', 'Especialista', 'Asistente', 'Gerente'])->map(
                fn (string $name) => Puesto::query()->firstOrCreate(
                    ['empresa_id' => $company->id, 'nombre' => $name], ['activo' => true],
                ),
            );
            $shifts = collect([
                ['nombre_horario' => 'Matutino', 'hora_entrada' => '08:00:00', 'hora_salida' => '17:00:00'],
                ['nombre_horario' => 'Flexible', 'hora_entrada' => '09:00:00', 'hora_salida' => '18:00:00'],
                ['nombre_horario' => 'Vespertino', 'hora_entrada' => '10:00:00', 'hora_salida' => '19:00:00'],
            ])->map(fn (array $data) => HorarioEmpresa::query()->firstOrCreate(
                ['empresa_id' => $company->id, 'nombre_horario' => $data['nombre_horario']], $data,
            ));

            for ($index = 1; $index <= self::EMPLOYEE_COUNT; $index++) {
                $email = sprintf('empleado%02d@solventia.example.test', $index);
                $personal = Personal::query()->firstOrNew(['email' => $email]);
                $days = collect(range(1, 7))->filter(fn () => random_int(0, 1) === 1)->take(5)->values();
                if ($days->isEmpty()) {
                    $days = collect([1, 2, 3, 4, 5]);
                }
                $shift = $shifts->random();

                $personal->fill([
                    'codigo' => sprintf('SOL-%04d', $index),
                    'pin' => $this->uniquePin($personal->exists ? $personal->id : null),
                    'nombre' => Str::upper($faker->name()),
                    'sexo' => $faker->randomElement(['Femenino', 'Masculino', 'Otro']),
                    'telefono' => $faker->numerify('##########'),
                    'foto' => 'https://i.pravatar.cc/300?u=' . urlencode($email),
                    'activo' => $faker->boolean(92),
                    'empresa_id' => $company->id,
                    'centro_id' => $centros->random()->id,
                    'departamento_id' => $departamentos->random()->id,
                    'puesto_id' => $puestos->random()->id,
                    'laborados' => $days->implode('@'),
                ])->save();

                foreach (range(1, 7) as $day) {
                    if ($days->contains($day)) {
                        Horario::query()->updateOrCreate(
                            ['personal_id' => $personal->id, 'dia' => $day],
                            ['entrada' => $shift->hora_entrada, 'salida' => $shift->hora_salida, 'horario_id' => $shift->id],
                        );
                    } else {
                        Horario::query()->where('personal_id', $personal->id)->where('dia', $day)->delete();
                    }
                }
            }
        });

        $this->command?->info('SOLVENTIA SOFTWARE SAS quedó preparada con 50 empleados de demostración.');
    }

    private function uniqueRfc(): string
    {
        do {
            $rfc = 'SSS' . now()->format('ymd') . strtoupper(Str::random(3));
        } while (Empresa::query()->where('rfc', $rfc)->exists());

        return $rfc;
    }

    private function uniquePin(?int $exceptId = null): string
    {
        do {
            $pin = str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);
        } while (Personal::query()->where('pin', $pin)->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))->exists());

        return $pin;
    }
}
