<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asistencias', function (Blueprint $table) {
            $table->foreignId('centro_trabajo_entrada_id')->nullable()->constrained('centros')->restrictOnDelete();
            $table->foreignId('centro_trabajo_salida_id')->nullable()->constrained('centros')->restrictOnDelete();
            $table->string('timezone_entrada', 64)->nullable();
            $table->string('timezone_salida', 64)->nullable();
            $table->dateTime('llegada_utc')->nullable();
            $table->dateTime('salida_utc')->nullable();
        });

        $this->backfillCurrentCenters();
    }

    public function down(): void
    {
        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropConstrainedForeignId('centro_trabajo_entrada_id');
            $table->dropConstrainedForeignId('centro_trabajo_salida_id');
            $table->dropColumn(['timezone_entrada', 'timezone_salida', 'llegada_utc', 'salida_utc']);
        });
    }

    private function backfillCurrentCenters(): void
    {
        $linkedEntries = 0;
        $linkedExits = 0;
        $unresolved = 0;

        DB::table('asistencias as a')
            ->leftJoin('personal as p', 'p.id', '=', 'a.personal_id')
            ->leftJoin('centros as c', function ($join) {
                $join->on('c.id', '=', 'p.centro_id')
                    ->on('c.empresa_id', '=', 'p.empresa_id');
            })
            ->where(function ($query) {
                $query->whereNotNull('a.llegada')->orWhereNotNull('a.salida');
            })
            ->select([
                'a.id as asistencia_id',
                'a.llegada',
                'a.salida',
                'a.centro_trabajo_entrada_id',
                'a.centro_trabajo_salida_id',
                'c.id as centro_actual_id',
            ])
            ->orderBy('a.id')
            ->chunkById(500, function ($rows) use (&$linkedEntries, &$linkedExits, &$unresolved): void {
                foreach ($rows as $row) {
                    $updates = [];

                    if ($row->llegada !== null && $row->centro_trabajo_entrada_id === null) {
                        if ($row->centro_actual_id !== null) {
                            $updates['centro_trabajo_entrada_id'] = $row->centro_actual_id;
                            $linkedEntries++;
                        } else {
                            $unresolved++;
                        }
                    }

                    if ($row->salida !== null && $row->centro_trabajo_salida_id === null) {
                        if ($row->centro_actual_id !== null) {
                            $updates['centro_trabajo_salida_id'] = $row->centro_actual_id;
                            $linkedExits++;
                        } else {
                            $unresolved++;
                        }
                    }

                    if ($updates !== []) {
                        DB::table('asistencias')
                            ->where('id', $row->asistencia_id)
                            ->update($updates);
                    }
                }
            }, 'a.id', 'asistencia_id');

        Log::info('Vinculación histórica de centros completada durante la migración.', [
            'entradas_vinculadas' => $linkedEntries,
            'salidas_vinculadas' => $linkedExits,
            'movimientos_sin_centro_valido' => $unresolved,
        ]);

        if ($unresolved > 0) {
            Log::warning('Algunas asistencias históricas no tienen empleado o centro actual válido; sus referencias quedaron nulas.', [
                'movimientos_sin_centro_valido' => $unresolved,
            ]);
        }
    }
};
