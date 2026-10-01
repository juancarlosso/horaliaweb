<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->string('logo')->nullable();
            $table->unsignedSmallInteger('minutos_tolerancia_entrada')->default(10);
            $table->unsignedInteger('metros_distancia_entrada')->default(20);
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn([
                'logo',
                'minutos_tolerancia_entrada',
                'metros_distancia_entrada',
            ]);
        });
    }
};
