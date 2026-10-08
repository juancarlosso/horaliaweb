<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intentos_pago', function (Blueprint $table) {
            $table->string('folio', 10)->nullable()->unique('intentos_pago_folio_unique')->after('empresa_id');
            $table->string('tarjeta_marca', 30)->nullable()->after('tarjeta_id');
            $table->string('tarjeta_ultimos4', 4)->nullable()->after('tarjeta_marca');
        });
    }

    public function down(): void
    {
        Schema::table('intentos_pago', function (Blueprint $table) {
            $table->dropUnique('intentos_pago_folio_unique');
            $table->dropColumn('folio');
        });
    }
};
