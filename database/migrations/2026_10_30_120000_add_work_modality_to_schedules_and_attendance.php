<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('horarios', function (Blueprint $table) {
            $table->string('modalidad')->default('PRESENCIAL');
        });

        Schema::table('asistencias', function (Blueprint $table) {
            $table->string('modalidad')->default('PRESENCIAL');
            $table->decimal('distancia_entrada_metros', 12, 2)->nullable();
            $table->decimal('distancia_salida_metros', 12, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropColumn(['modalidad', 'distancia_entrada_metros', 'distancia_salida_metros']);
        });

        Schema::table('horarios', function (Blueprint $table) {
            $table->dropColumn('modalidad');
        });
    }
};
