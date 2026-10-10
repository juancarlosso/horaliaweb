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
            $table->string('stripe_customer_id')->nullable()->change()->after('cancelar_al_renovar');
            $table->string('stripe_default_payment_method_id')->nullable()->change()->after('stripe_customer_id');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('empresas', function (Blueprint $table) {
            $table->string('stripe_customer_id')->nullable()->change()->after('rfc');
            $table->string('stripe_default_payment_method_id')->nullable()->change()->after('stripe_customer_id');
        });
    }
};
