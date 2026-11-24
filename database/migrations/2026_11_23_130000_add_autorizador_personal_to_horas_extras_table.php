<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('horas_extras', function (Blueprint $table) {
            $table->unsignedBigInteger('autorizador_id')->nullable()->change();
            $table->unsignedBigInteger('autorizador_personal_id')->nullable();
        });
    }

    public function down(): void
    {
        if (DB::table('horas_extras')->whereNotNull('autorizador_personal_id')->exists()) {
            throw new RuntimeException('Revertir esta migración eliminaría la referencia a empleados que ya autorizaron horas extras.');
        }

        Schema::table('horas_extras', function (Blueprint $table) {
            $table->dropColumn('autorizador_personal_id');
            $table->unsignedBigInteger('autorizador_id')->nullable(false)->change();
        });
    }
};
