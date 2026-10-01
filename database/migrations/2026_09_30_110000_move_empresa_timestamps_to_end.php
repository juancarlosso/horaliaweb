<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->rebuildSqliteTable(true);

            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('empresas', function (Blueprint $table) {
            $table->timestamp('created_at')->nullable()->change()->after('fecha_renovacion');
            $table->timestamp('updated_at')->nullable()->change()->after('created_at');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->rebuildSqliteTable(false);

            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('empresas', function (Blueprint $table) {
            $table->timestamp('created_at')->nullable()->change()->after('activa');
            $table->timestamp('updated_at')->nullable()->change()->after('created_at');
        });
    }

    private function rebuildSqliteTable(bool $timestampsAtEnd): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        try {
            Schema::create('empresas_reordered', function (Blueprint $table) use ($timestampsAtEnd) {
                $table->id();
                $table->string('rfc', 13)->unique();
                $table->string('razon_social')->nullable();
                $table->longText('direccion')->nullable();
                $table->string('telefono')->nullable();
                $table->boolean('activa')->default(true);
                if (!$timestampsAtEnd) {
                    $table->timestamps();
                }
                $table->string('logo')->nullable();
                $table->unsignedSmallInteger('minutos_tolerancia_entrada')->default(10);
                $table->unsignedInteger('metros_distancia_entrada')->default(20);
                $table->string('stripe_customer_id')->nullable();
                $table->decimal('precio', 10, 2)->nullable();
                $table->date('fecha_inicio')->nullable();
                $table->date('fecha_renovacion')->nullable();
                if ($timestampsAtEnd) {
                    $table->timestamps();
                }
            });

            DB::statement('INSERT INTO empresas_reordered (id, rfc, razon_social, direccion, telefono, activa, logo, minutos_tolerancia_entrada, metros_distancia_entrada, stripe_customer_id, precio, fecha_inicio, fecha_renovacion, created_at, updated_at) SELECT id, rfc, razon_social, direccion, telefono, activa, logo, minutos_tolerancia_entrada, metros_distancia_entrada, stripe_customer_id, precio, fecha_inicio, fecha_renovacion, created_at, updated_at FROM empresas');
            Schema::drop('empresas');
            Schema::rename('empresas_reordered', 'empresas');
        } finally {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }
};
