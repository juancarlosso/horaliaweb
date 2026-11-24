<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horas_extras', function (Blueprint $table) {
            $table->id();
            // Las referencias de auditoría se conservan aunque se eliminen los registros de origen.
            $table->unsignedBigInteger('asistencia_id');
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('personal_id');
            $table->date('fecha');
            $table->unsignedInteger('detectados');
            $table->unsignedInteger('autorizados');
            $table->unsignedBigInteger('autorizador_id');
            $table->unsignedBigInteger('capturista_id');
            $table->text('motivo');
            $table->text('observaciones')->nullable();
            $table->json('jornada');
            $table->timestamp('created_at');
            $table->unique('asistencia_id');
            $table->unique(['personal_id', 'fecha']);
            $table->index(['empresa_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horas_extras');
    }
};
