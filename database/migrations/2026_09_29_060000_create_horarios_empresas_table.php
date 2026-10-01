<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horarios_empresas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_horario');
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->time('hora_entrada');
            $table->time('hora_salida');
            $table->timestamps();
            $table->index(['empresa_id', 'nombre_horario']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios_empresas');
    }
};
