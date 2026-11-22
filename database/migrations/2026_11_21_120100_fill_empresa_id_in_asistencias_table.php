<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $company = DB::table('personal')
            ->select('empresa_id')
            ->whereColumn('personal.id', 'asistencias.personal_id');

        DB::table('asistencias')
            ->whereNull('empresa_id')
            ->update(['empresa_id' => $company]);
    }

    public function down(): void
    {
        // La migración de esquema elimina la columna al revertirse.
    }
};
