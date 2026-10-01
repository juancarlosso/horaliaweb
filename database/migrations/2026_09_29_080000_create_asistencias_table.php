<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personal_id')->constrained('personal')->cascadeOnDelete();
            $table->date('fecha');
            $table->dateTime('llegada')->nullable();
            $table->dateTime('salida')->nullable();
            $table->unsignedInteger('minutos_tarde')->default(0);
            $table->unsignedInteger('minutos_salida_temprano')->default(0);
            $table->decimal('latitud', 10, 7)->nullable();
            $table->decimal('longitud', 10, 7)->nullable();
            $table->string('ip', 45)->nullable();
            $table->decimal('latitud_salida', 10, 7)->nullable();
            $table->decimal('longitud_salida', 10, 7)->nullable();
            $table->string('ip_salida', 45)->nullable();
            $table->string('rango_entrada')->nullable();
            $table->string('rango_salida')->nullable();
            $table->string('foto_entrada')->nullable();
            $table->string('foto_salida')->nullable();
            $table->timestamps();

            $table->unique(['personal_id', 'fecha']);
            $table->index(['fecha', 'personal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencias');
    }
};
