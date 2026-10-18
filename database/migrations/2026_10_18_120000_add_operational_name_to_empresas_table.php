<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('empresas', 'nombre')) {
            Schema::table('empresas', fn (Blueprint $table) => $table->string('nombre')->nullable()->after('razon_social'));
        }

        DB::table('empresas')->whereNull('nombre')->update(['nombre' => DB::raw('razon_social')]);
    }

    public function down(): void
    {
        // Keep the populated operational names if this migration is rolled back.
    }
};
