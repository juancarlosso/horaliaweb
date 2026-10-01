<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal', function (Blueprint $table) {
            $table->foreignId('centro_id')->nullable()->after('empresa_id')->constrained('centros')->nullOnDelete();
            $table->string('sexo', 20)->nullable()->after('nombre');
            $table->string('laborados')->nullable();
        });

        Schema::create('horarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personal_id')->constrained('personal')->cascadeOnDelete();
            $table->unsignedTinyInteger('dia');
            $table->time('entrada')->nullable();
            $table->time('salida')->nullable();
            $table->foreignId('horario_id')->nullable()->constrained('horarios_empresas')->nullOnDelete();
            $table->timestamps();
            $table->unique(['personal_id', 'dia']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios');

        Schema::table('personal', function (Blueprint $table) {
            $table->dropConstrainedForeignId('centro_id');
            $table->dropColumn(['sexo', 'laborados']);
        });
    }
};
