<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checador_sesiones', function (Blueprint $table) {
            $table->foreignId('centro_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('checador_sesiones')->whereNull('centro_id')->delete();

        Schema::table('checador_sesiones', function (Blueprint $table) {
            $table->foreignId('centro_id')->nullable(false)->change();
        });
    }
};
