<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intentos_pago', function (Blueprint $table) {
            $table->string('forma_pago_sat', 2)->nullable()->after('tarjeta_ultimos4');
        });
    }

    public function down(): void
    {
        Schema::table('intentos_pago', function (Blueprint $table) {
            $table->dropColumn('forma_pago_sat');
        });
    }
};
