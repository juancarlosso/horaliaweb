<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal', function (Blueprint $table) {
            $table->foreignId('departamento_id')
                ->nullable()
                ->after('centro_id')
                ->constrained('departamentos')
                ->nullOnDelete();
            $table->foreignId('puesto_id')
                ->nullable()
                ->after('departamento_id')
                ->constrained('puestos')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('personal', function (Blueprint $table) {
            $table->dropConstrainedForeignId('puesto_id');
            $table->dropConstrainedForeignId('departamento_id');
        });
    }
};
