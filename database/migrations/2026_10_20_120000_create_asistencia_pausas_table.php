<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asistencia_pausas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asistencia_id')->constrained('asistencias')->cascadeOnDelete();
            $table->string('tipo', 24);
            $table->dateTime('inicio');
            $table->dateTime('fin')->nullable();
            $table->dateTime('inicio_utc');
            $table->dateTime('fin_utc')->nullable();
            $table->timestamps();
            $table->index(['asistencia_id', 'inicio']);
            $table->index(['asistencia_id', 'fin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencia_pausas');
    }
};
