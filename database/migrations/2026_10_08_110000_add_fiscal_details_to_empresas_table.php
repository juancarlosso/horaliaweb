<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $columns = [
        'domicilio_calle',
        'domicilio_numero_exterior',
        'domicilio_numero_interior',
        'domicilio_colonia',
        'domicilio_codigo_postal',
        'domicilio_municipio',
        'domicilio_ciudad',
        'domicilio_estado',
        'regimen_fiscal',
        'correo_facturacion',
    ];

    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            if (!Schema::hasColumn('empresas', 'domicilio_calle')) $table->string('domicilio_calle')->nullable();
            if (!Schema::hasColumn('empresas', 'domicilio_numero_exterior')) $table->string('domicilio_numero_exterior', 30)->nullable();
            if (!Schema::hasColumn('empresas', 'domicilio_numero_interior')) $table->string('domicilio_numero_interior', 30)->nullable();
            if (!Schema::hasColumn('empresas', 'domicilio_colonia')) $table->string('domicilio_colonia', 150)->nullable();
            if (!Schema::hasColumn('empresas', 'domicilio_codigo_postal')) $table->string('domicilio_codigo_postal', 5)->nullable();
            if (!Schema::hasColumn('empresas', 'domicilio_municipio')) $table->string('domicilio_municipio', 150)->nullable();
            if (!Schema::hasColumn('empresas', 'domicilio_ciudad')) $table->string('domicilio_ciudad', 150)->nullable();
            if (!Schema::hasColumn('empresas', 'domicilio_estado')) $table->string('domicilio_estado', 100)->nullable();
            if (!Schema::hasColumn('empresas', 'regimen_fiscal')) $table->string('regimen_fiscal', 3)->nullable();
            if (!Schema::hasColumn('empresas', 'correo_facturacion')) $table->string('correo_facturacion')->nullable();
        });
    }

    public function down(): void
    {
        $existingColumns = array_values(array_filter(
            $this->columns,
            fn (string $column) => Schema::hasColumn('empresas', $column)
        ));

        if ($existingColumns) {
            Schema::table('empresas', fn (Blueprint $table) => $table->dropColumn($existingColumns));
        }
    }
};
