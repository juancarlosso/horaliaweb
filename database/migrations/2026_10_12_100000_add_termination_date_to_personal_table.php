<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal', function (Blueprint $table) {
            $table->timestamp('fecha_baja')->nullable()->after('activo');
        });
    }

    public function down(): void
    {
        Schema::table('personal', function (Blueprint $table) {
            $table->dropColumn('fecha_baja');
        });
    }
};
