<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('folios_factura', function (Blueprint $table) {
            $table->id();
            $table->string('serie', 10)->unique();
            $table->unsignedBigInteger('ultimo_folio');
            $table->timestamps();
        });

        DB::table('folios_factura')->insert([
            'serie' => config('constantes.facturacion.serie', 'H'),
            'ultimo_folio' => (int) config('constantes.facturacion.ultimo_folio_inicial', 1000),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::create('facturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intento_pago_id')->unique()->constrained('intentos_pago')->restrictOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('serie', 10);
            $table->unsignedBigInteger('folio');
            $table->string('uuid', 36)->nullable()->unique();
            $table->string('estado', 32)->default('procesando');
            $table->string('modo_sifei', 20);
            $table->string('uso_cfdi', 4);
            $table->string('forma_pago_sat', 2);
            $table->string('correo_facturacion');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('iva', 12, 2);
            $table->decimal('total', 12, 2);
            $table->string('xml_path')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('xml_staging_path')->nullable();
            $table->string('pdf_staging_path')->nullable();
            $table->timestamp('timbrado_en')->nullable();
            $table->timestamp('archivos_subidos_en')->nullable();
            $table->timestamp('correo_encolado_en')->nullable();
            $table->string('codigo_error', 100)->nullable();
            $table->text('mensaje_error')->nullable();
            $table->longText('xml_pendiente')->nullable();
            $table->timestamps();

            $table->unique(['serie', 'folio']);
            $table->index(['estado', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facturas');
        Schema::dropIfExists('folios_factura');
    }
};
