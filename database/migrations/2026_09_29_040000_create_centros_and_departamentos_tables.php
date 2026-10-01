<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('centros', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->nullable();
            $table->integer('activo')->default(0);
            $table->string('geolocalizacion')->nullable();
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->cascadeOnDelete();
            $table->timestamps();
            $table->index(['empresa_id', 'nombre']);
        });

        Schema::create('departamentos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->nullable();
            $table->string('politicas')->nullable();
            $table->integer('activo')->default(1);
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->cascadeOnDelete();
            $table->timestamps();
            $table->index(['empresa_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departamentos');
        Schema::dropIfExists('centros');
    }
};
