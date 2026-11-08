<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('personal_id')->constrained('personal')->cascadeOnDelete();
            $table->string('tipo', 100);
            $table->string('nombre_personalizado')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('ruta_privada')->unique();
            $table->string('nombre_original');
            $table->string('mime', 100);
            $table->unsignedBigInteger('tamano');
            $table->foreignId('cargado_por')->constrained('users')->restrictOnDelete();
            $table->foreignId('eliminado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['empresa_id', 'personal_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_documentos');
    }
};
