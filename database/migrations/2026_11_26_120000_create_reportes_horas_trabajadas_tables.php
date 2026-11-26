<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asistencias', function (Blueprint $table) {
            $table->index(['empresa_id', 'personal_id', 'fecha']);
        });

        Schema::create('reportes_horas_trabajadas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->date('desde');
            $table->date('hasta');
            $table->string('estado', 20);
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('reportes_horas_trabajadas_filas', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('reporte_id')->constrained('reportes_horas_trabajadas', indexName: 'horas_trabajadas_filas_reporte_fk')->cascadeOnDelete();
            $table->string('personal');
            $table->date('semana');
            $table->unsignedTinyInteger('dias_registrados');
            $table->unsignedTinyInteger('dias_indeterminados');
            $table->unsignedInteger('minutos_trabajados');
            $table->unsignedInteger('minutos_sanitario');
            $table->unsignedInteger('minutos_comida');
            $table->unsignedInteger('minutos_descanso');
            $table->unsignedInteger('minutos_otras_pausas');
            $table->unsignedInteger('minutos_pausas');
            $table->index(['reporte_id', 'id'], 'horas_trabajadas_filas_reporte_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reportes_horas_trabajadas_filas');
        Schema::dropIfExists('reportes_horas_trabajadas');
        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropIndex(['empresa_id', 'personal_id', 'fecha']);
        });
    }
};
