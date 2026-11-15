<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('incapacidades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('personal_id')->constrained('personal')->cascadeOnDelete();
            $table->foreignId('registrado_por_user_id')->constrained('users')->restrictOnDelete();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('motivo', 40);
            $table->text('observaciones')->nullable();
            $table->unsignedSmallInteger('jornadas_afectadas');
            $table->json('snapshot');
            $table->timestamp('snapshot_generado_en');
            $table->string('estado', 20)->default('vigente');
            $table->foreignId('cancelado_por_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelado_en')->nullable();
            $table->timestamps();
            $table->index(['personal_id', 'estado', 'fecha_inicio', 'fecha_fin'], 'incapacidades_personal_estado_periodo_idx');
        });
        Schema::create('incapacidad_comprobantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incapacidad_id')->constrained('incapacidades')->cascadeOnDelete();
            $table->string('ruta_privada');
            $table->string('nombre_original');
            $table->string('mime', 100);
            $table->unsignedBigInteger('tamano');
            $table->foreignId('cargado_por_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incapacidad_comprobantes');
        Schema::dropIfExists('incapacidades');
    }
};
