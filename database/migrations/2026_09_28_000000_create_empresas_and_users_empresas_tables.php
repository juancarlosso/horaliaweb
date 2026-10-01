<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->string('rfc', 13)->unique();
            $table->string('razon_social')->nullable();
            $table->longText('direccion')->nullable();
            $table->string('telefono')->nullable();
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });

        Schema::create('users_empresas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->boolean('control_total')->default(false);
            $table->timestamps();
            $table->unique(['user_id', 'empresa_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users_empresas');
        Schema::dropIfExists('empresas');
    }
};
