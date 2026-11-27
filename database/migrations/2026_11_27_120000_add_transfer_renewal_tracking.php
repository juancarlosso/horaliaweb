<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->date('periodo_suspendido_por_transferencia')->nullable();
        });

        Schema::create('transferencia_avisos_renovacion', function (Blueprint $table) {
            $table->foreignId('transferencia_pago_id')
                ->constrained('transferencias_pago', indexName: 'transfer_avisos_pago_fk')->cascadeOnDelete();
            $table->date('fecha');
            $table->string('destinatario');
            $table->primary(['transferencia_pago_id', 'fecha', 'destinatario'], 'transfer_avisos_renovacion_pk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transferencia_avisos_renovacion');
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn('periodo_suspendido_por_transferencia');
        });
    }
};
