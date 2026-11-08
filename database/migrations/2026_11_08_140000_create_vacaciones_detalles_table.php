<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vacaciones_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vacacion_id')->constrained('vacaciones')->cascadeOnDelete();
            $table->date('fecha');
            $table->boolean('cuenta');
            $table->string('motivo', 150);
            $table->json('festivos')->nullable();
            $table->timestamps();
            $table->unique(['vacacion_id', 'fecha']);
            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacaciones_detalles');
    }
};
