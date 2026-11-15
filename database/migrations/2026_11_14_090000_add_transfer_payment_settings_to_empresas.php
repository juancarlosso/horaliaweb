<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->string('metodo_pago', 20)->default('TARJETA')->after('stripe_default_payment_method_id');
            $table->string('referencia_transferencia', 32)->nullable()->unique()->after('metodo_pago');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropUnique(['referencia_transferencia']);
            $table->dropColumn(['metodo_pago', 'referencia_transferencia']);
        });
    }
};
