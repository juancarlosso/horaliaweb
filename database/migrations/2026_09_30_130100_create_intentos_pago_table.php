<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intentos_pago', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->restrictOnDelete();
            $table->date('fecha_renovacion');
            $table->string('tarjeta_id')->nullable();
            $table->string('origen', 20);
            $table->string('concepto', 100);
            $table->decimal('cantidad', 10, 2);
            $table->string('moneda', 3)->default('mxn');
            $table->string('resultado', 20)->default('pendiente');
            $table->text('descripcion')->nullable();
            $table->string('codigo_respuesta', 100)->nullable();
            $table->string('transaccion_id')->nullable()->unique();
            $table->string('idempotency_key', 255)->unique();
            $table->unsignedTinyInteger('numero_ejecucion')->nullable();
            $table->timestamp('intentado_en');
            $table->timestamps();

            $table->index(['empresa_id', 'fecha_renovacion', 'resultado'], 'intentos_pago_renovacion_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intentos_pago');
    }
};
