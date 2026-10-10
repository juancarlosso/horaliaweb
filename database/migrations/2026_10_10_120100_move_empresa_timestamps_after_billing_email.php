<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('empresas', function (Blueprint $table) {
            $table->timestamp('created_at')->nullable()->change()->after('correo_facturacion');
            $table->timestamp('updated_at')->nullable()->change()->after('created_at');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('empresas', function (Blueprint $table) {
            $table->timestamp('created_at')->nullable()->change()->after('fecha_renovacion');
            $table->timestamp('updated_at')->nullable()->change()->after('created_at');
        });
    }
};
