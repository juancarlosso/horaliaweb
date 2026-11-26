<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reportes_horas_trabajadas', function (Blueprint $table) {
            $table->string('tipo', 25)->default('horas-trabajadas');
            $table->json('filtros')->nullable();
            $table->json('resumen')->nullable();
        });

        Schema::create('reportes_generados_filas', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('reporte_id')->constrained('reportes_horas_trabajadas', indexName: 'reportes_generados_filas_reporte_fk')->cascadeOnDelete();
            $table->date('fecha');
            $table->unsignedBigInteger('origen_id');
            $table->unsignedBigInteger('departamento_id')->nullable();
            $table->string('nombre')->nullable();
            $table->json('datos');
            $table->text('busqueda')->nullable();
            $table->index(['reporte_id', 'id']);
            $table->index(['reporte_id', 'origen_id']);
            $table->index(['reporte_id', 'fecha', 'origen_id'], 'reportes_generados_filas_fecha_idx');
            $table->index(['reporte_id', 'departamento_id', 'id'], 'reportes_generados_filas_departamento_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reportes_generados_filas');
        DB::table('reportes_horas_trabajadas')->where('tipo', '<>', 'horas-trabajadas')->delete();
        Schema::table('reportes_horas_trabajadas', function (Blueprint $table) {
            $table->dropColumn(['tipo', 'filtros', 'resumen']);
        });
    }
};
