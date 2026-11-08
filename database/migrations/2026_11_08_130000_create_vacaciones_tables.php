<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vacaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('personal_id')->constrained('personal')->cascadeOnDelete();
            $table->foreignId('autorizado_por_personal_id')->constrained('personal')->restrictOnDelete();
            $table->foreignId('registrado_por_user_id')->constrained('users')->restrictOnDelete();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->unsignedSmallInteger('total_dias');
            $table->string('estado', 20)->default('registrado');
            $table->foreignId('cancelado_por_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelado_en')->nullable();
            $table->timestamps();
            $table->index(['personal_id', 'estado', 'fecha_inicio', 'fecha_fin'], 'vacaciones_personal_estado_periodo_idx');
        });

        Schema::create('vacaciones_fechas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vacacion_id')->constrained('vacaciones')->cascadeOnDelete();
            $table->date('fecha');
            $table->timestamps();
            $table->unique(['vacacion_id', 'fecha']);
            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacaciones_fechas');
        Schema::dropIfExists('vacaciones');
    }
};
